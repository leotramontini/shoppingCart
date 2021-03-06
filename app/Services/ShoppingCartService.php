<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use App\Models\ShoppingCart;
use Illuminate\Support\Facades\DB;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\ShoppingCartRepository;
use App\Exceptions\ShoppingCartServiceException;
use App\Repositories\ShoppingCartProductsRepository;

class ShoppingCartService
{
    /**
     * @var \App\Repositories\ShoppingCartRepository
     */
    protected $shoppingCartRepository;

    /**
     * @var \App\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Repositories\ShoppingCartProductsRepository
     */
    protected $shoppingCartProductsRepository;

    /**
     * @param \App\Repositories\ShoppingCartRepository $shoppingCartRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ShoppingCartRepository $shoppingCartRepository,
        ShoppingCartProductsRepository $shoppingCartProductsRepository
    ) {
        $this->productRepository                = $productRepository;
        $this->shoppingCartRepository           = $shoppingCartRepository;
        $this->shoppingCartProductsRepository   = $shoppingCartProductsRepository;
    }

    /**
     * @param array $inputs
     * @return array
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function addingProduct($inputs)
    {
        try {
            $product        = $this->productRepository->findWhere([
                'id'        => Arr::get($inputs, 'product_id'),
                'enable'    => true
            ])->first();

            if (!$product) {
                throw new ShoppingCartServiceException('Product not found', 404);
            }

            DB::beginTransaction();
            $inputs['price']    = $product->price;
            $shoppingCart       = $this->findOrCreateShoppingCart($inputs);

            if ($product->quantity < Arr::get($inputs, 'quantity')) {
                throw new ShoppingCartServiceException('Product request quantity bigger than stock quantity');
            }

            $products = $shoppingCart->shoppingCartProducts()->get();
            if (!$products->where('product_id', $product->id)->isEmpty()) {
                throw new ShoppingCartServiceException('Product is already in shopping cart');
            }

            $this->shoppingCartProductsRepository->create([
                'product_id'        => $product->id,
                'shopping_cart_id'  => $shoppingCart->id,
                'price'             => $product->price,
                'quantity'          => Arr::get($inputs, 'quantity')
            ]);
            DB::commit();
        } catch (Exception $error) {
            DB::rollBack();
            throw new ShoppingCartServiceException($error->getMessage());
        }
        $products = $this->getUpdatedProducts($shoppingCart->shoppingCartProducts()->get());
        $shoppingCart->refresh();

        return [
            'id'        => $shoppingCart->id,
            'total'     => $shoppingCart->total,
            'products'  => $products
        ];
    }

    /**
     * @param array $inputs
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws \App\Exceptions\ShoppingCartServiceException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function findOrCreateShoppingCart(array $inputs)
    {
        $productTotal = (Arr::get($inputs, 'price') * Arr::get($inputs, 'quantity'));
        if (!Arr::get($inputs, 'shopping_cart_id')) {
            return $this->createShoppingCart($productTotal);
        }

        $shoppingCart = $this->getShoppingCartById(Arr::get($inputs, 'shopping_cart_id'))->first();
        $total =  $productTotal + $shoppingCart->total;
        return $this->updateShoppingCartTotal($total, $shoppingCart->id);
    }

    /**
     * @param int $shoppingCartId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function getShoppingCartById(int $shoppingCartId)
    {
        try {
            return $this->shoppingCartRepository->find(['id' => $shoppingCartId]);
        } catch (Exception $error) {
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param float $total
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws \App\Exceptions\ShoppingCartServiceException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function createShoppingCart(float $total)
    {
        try {
            return $this->shoppingCartRepository->create(['total' => $total]);
        } catch (Exception $error) {
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param int $shoppingCartId
     * @param int $productId
     * @return array
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function removeProductFromShoppingCart($shoppingCartId, $productId)
    {
        try {
            $shoppingCart           = $this->shoppingCartRepository->find($shoppingCartId);
            $shoppingCartProducts   = $shoppingCart->shoppingCartProducts()->get();
            $shoppingCartProduct    = $shoppingCartProducts->where('product_id', $productId)->first();

            if (!$shoppingCartProduct) {
                throw new ShoppingCartServiceException('Product not found in shopping cart', 404);
            }

            DB::beginTransaction();
            $this->shoppingCartProductsRepository->delete($shoppingCartProduct->id);
            $total = $shoppingCart->total - ($shoppingCartProduct->price * $shoppingCartProduct->quantity);
            $this->updateShoppingCartTotal($total, $shoppingCartId);
            DB::commit();

            $products = $this->getUpdatedProducts($shoppingCart->shoppingCartProducts()->get());
            return [
                'id'        => $shoppingCart->id,
                'total'     => $total,
                'products'  => $products
            ];
        } catch (Exception $error) {
            DB::rollBack();
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param int $shoppingCartId
     * @return array
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function clearProduct(int $shoppingCartId)
    {
        try {
            $shoppingCart           = $this->shoppingCartRepository->find($shoppingCartId);
            $shoppingCartProducts   = $shoppingCart->shoppingCartProducts()->get();

            if ($shoppingCartProducts->isEmpty() && $shoppingCart->total == 0) {
                throw new ShoppingCartServiceException('Shopping cart is already empty');
            }

            $shoppingCartProductIds = $shoppingCartProducts->pluck('id')->all();
            DB::beginTransaction();
            $this->shoppingCartProductsRepository->deleteWhere([['id', 'IN', $shoppingCartProductIds]]);
            $this->updateShoppingCartTotal(0, $shoppingCartId);
            DB::commit();
            return [
                'id'        => $shoppingCart->id,
                'total'     => 0,
                'products'  => []
            ];
        } catch (Exception $error) {
            DB::rollBack();
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param int $shoppingCartId
     * @return array
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function getById(int $shoppingCartId)
    {
        try {
            $shoppingCart           = $this->shoppingCartRepository->find($shoppingCartId);
            $shoppingCartProducts   = $shoppingCart->shoppingCartProducts()->get();
            $products               = $this->getUpdatedProducts($shoppingCartProducts, $shoppingCart);
            $shoppingCart           = $this->updateShoppingCartTotalByProducts($products, $shoppingCart);

            return [
                'id'        => $shoppingCart->id,
                'total'     => $shoppingCart->total,
                'products'  => $products
            ];
        } catch (Exception $error) {
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param \Illuminate\Support\Collection $shoppingCartProducts
     * @return \Illuminate\Support\Collection
     */
    public function getUpdatedProducts(Collection $shoppingCartProducts)
    {
        $shoppingCartProductIds = $shoppingCartProducts->pluck('product_id')->all();
        $products               = $this->productRepository
                                    ->findWhereIn('id', $shoppingCartProductIds)
                                    ->where('enable', '=', true);
        $updatedProducts = [];
        foreach ($shoppingCartProducts as $shoppingCartProduct) {
            $product = $products->where('id', $shoppingCartProduct->product_id)->first();
            if ($product->quantity < $shoppingCartProduct->quantity) {
                continue;
            }
            $updatedProducts[] = [
                'id'            =>$shoppingCartProduct->id,
                'product_id'    => $product->id,
                'name'          => $product->name,
                'price'         => $product->price,
                'quantity'      => $shoppingCartProduct->quantity
            ];
        }

        return collect($updatedProducts);
    }

    /**
     * @param \Illuminate\Support\Collection $products
     * @param \App\Models\ShoppingCart $shoppingCart
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function updateShoppingCartTotalByProducts($products, ShoppingCart $shoppingCart)
    {
        try {
            $total = 0;
            foreach ($products as $product) {
                $total += $product['price'] * $product['quantity'];
            }
            return $this->updateShoppingCartTotal($total, $shoppingCart->id);
        } catch (Exception $error) {
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param float $total
     * @param int $shoppingCartId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function updateShoppingCartTotal(float $total, int $shoppingCartId)
    {
        try {
            return $this->shoppingCartRepository->update(['total' => $total], $shoppingCartId);
        } catch (Exception $error) {
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param int $shoppingCartId
     * @param int $productId
     * @param int $quantity
     * @return array
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function updateProductQuantity(int $shoppingCartId, int $productId, int $quantity)
    {
        try {
            $shoppingCart         = $this->shoppingCartRepository->find($shoppingCartId);
            $shoppingCartProducts = $shoppingCart->shoppingCartProducts()->get();
            $shoppingCartProduct  = $shoppingCartProducts->where('product_id', $productId)->first();

            if (!$shoppingCartProduct) {
                throw new ShoppingCartServiceException('Product not found in shopping cart', 404);
            }

            if ($shoppingCartProduct->quantity == $quantity) {
                throw new ShoppingCartServiceException('Quantity already update', 422);
            }

            $product = $this->productRepository->find($productId);
            if ($product->quantity < $quantity) {
                throw new ShoppingCartServiceException('Request quantity is bigger than stock quantity', 400);
            }

            $total = $shoppingCart->total;
            $total -= $shoppingCartProduct->quantity * $shoppingCartProduct->price;
            $total += $quantity * $shoppingCartProduct->price;

            DB::beginTransaction();
            $this->shoppingCartProductsRepository->update(['quantity' => $quantity], $shoppingCartProduct->id);
            $this->updateShoppingCartTotal($total, $shoppingCart->id);
            DB::commit();

            $products = $this->getUpdatedProducts($shoppingCart->shoppingCartProducts()->get());
            $shoppingCart->refresh();

            return [
                'id'        => $shoppingCart->id,
                'total'     => $shoppingCart->total,
                'products'  => $products
            ];
        } catch (Exception $error) {
            DB::rollBack();
            throw new ShoppingCartServiceException($error->getMessage(), $error->getCode());
        }
    }
}
