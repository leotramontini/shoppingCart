<?php

namespace App\Services;

use App\Models\ShoppingCart;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Repositories\ProductRepository;
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
            $shoppingCart   = $this->findOrCreateShoppingCart($inputs);

            if ($product->quantity < Arr::get($inputs, 'quantity')) {
                throw new ShoppingCartServiceException('Product request quantity bigger than stock quantity');
            }

            $products = $shoppingCart->shoppingCartProducts()->get();
            if (!$products->where('product_id', $product->id)->isEmpty()) {
                throw new ShoppingCartServiceException('Product already in shopping cart');
            }

            $this->shoppingCartProductsRepository->create([
                'product_id'        => $product->id,
                'shopping_cart_id'  => $shoppingCart->id,
                'price'             => Arr::get($inputs, 'price'),
                'quantity'          => Arr::get($inputs, 'quantity')
            ]);
            DB::commit();
        } catch (Exception $error) {
            DB::rollBack();
            throw new ShoppingCartServiceException($error->getMessage());
        }

        return [
            'id'        => $shoppingCart->id,
            'total'     => $shoppingCart->total,
            'products'  => $shoppingCart->shoppingCartProducts()->get(['product_id', 'quantity', 'price'])
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
        if (!Arr::get($inputs, 'shopping_cart_id')) {
            return $this->createShoppingCart(Arr::get($inputs, 'price'));
        }

        $shoppingCart = $this->getShoppingCartById(Arr::get($inputs, 'shopping_cart_id'))->first();
        $total = Arr::get($inputs, 'price') + $shoppingCart->total;
        $this->shoppingCartRepository->update(['total' => $total], $shoppingCart->id);
        return $shoppingCart->refresh();
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
            $total = $shoppingCart->total - $shoppingCartProduct->price;
            $this->shoppingCartRepository->update(['total' => $total], $shoppingCartId);
            DB::commit();
            return [
                'id'        => $shoppingCart->id,
                'total'     => $total,
                'products'  => $shoppingCart->shoppingCartProducts()->get(['product_id', 'quantity', 'price'])
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
            $this->shoppingCartRepository->update(['total' => 0], $shoppingCartId);
            DB::commit();
            return [
                'id'        => $shoppingCart->id,
                'total'     => 0,
                'products'  => $shoppingCart->shoppingCartProducts()->get(['product_id', 'quantity', 'price'])
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
            $shoppingCart = $this->updateShoppingCartTotalByProducts($products, $shoppingCart);
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
     * @return array
     */
    public function getUpdatedProducts($shoppingCartProducts)
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

        return $updatedProducts;
    }

    /**
     * @param array $products
     * @param \App\Models\ShoppingCart $shoppingCart
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws \App\Exceptions\ShoppingCartServiceException
     */
    public function updateShoppingCartTotalByProducts(array $products, ShoppingCart $shoppingCart)
    {
        try {
            $products   = collect($products);
            $prices     = $products->pluck('price')->all();
            return $this->updateShoppingCartTotal(array_sum($prices), $shoppingCart->id);
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
}
