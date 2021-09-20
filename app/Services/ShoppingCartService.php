<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use App\Repositories\ProductRepository;
use App\Repositories\ShoppingCartRepository;
use App\Exceptions\ShoppingCartServiceException;
use App\Repositories\ShoppingCartProductsRepository;
use Illuminate\Support\Facades\DB;

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
     * @throws ShoppingCartServiceException
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
}
