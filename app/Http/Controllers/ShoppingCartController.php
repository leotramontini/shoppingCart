<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Services\ShoppingCartService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class ShoppingCartController extends Controller
{
    /**
     * @var \App\Services\ShoppingCartService
     */
    protected $shoppingCartService;

    /**
     * @param \App\Services\ShoppingCartService $shoppingCartService
     */
    public function __construct(ShoppingCartService $shoppingCartService)
    {
        $this->shoppingCartService = $shoppingCartService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addingProduct(Request $request)
    {
        $inputs = $request->all();

        $validator = Validator::make($inputs, [
            'product_id'        => 'required|int',
            'price'             => 'required|numeric',
            'quantity'          => 'required|int',
            'shopping_cart_id'  =>'int'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => Arr::first($validator->getMessageBag()->getMessages())[0]
            ], 422);
        }

        try {
            $shoppingCart = $this->shoppingCartService->addingProduct($inputs);
            return response()->json($shoppingCart);
        } catch (Exception $error) {
            return response()->json([
                'message' => $error->getMessage()
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param int $shoppingCartId
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeProduct(Request $request, int $shoppingCartId, int $productId)
    {
        try {
            $shoppingCart = $this->shoppingCartService->removeProductFromShoppingCart($shoppingCartId, $productId);
            return response()->json($shoppingCart);
        } catch (Exception $error) {
            return response()->json([
                'message' => $error->getMessage()
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param int $shoppingCartId
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearProduct(Request $request, int $shoppingCartId)
    {
        try {
            $shoppingCart = $this->shoppingCartService->clearProduct($shoppingCartId);
            return response()->json($shoppingCart);
        } catch (Exception $error) {
            return response()->json([
                'message' => $error->getMessage()
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param int $shoppingCartId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getById(Request $request, int $shoppingCartId)
    {
        try {
            $shoppingCart = $this->shoppingCartService->getById($shoppingCartId);
            return response()->json($shoppingCart);
        } catch (Exception $error) {
            return response()->json([
                'message' => $error->getMessage()
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param int $shoppingCartId
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProductQuantity(Request $request, int $shoppingCartId, int $productId)
    {
        try {
            $inputs = $request->all();

            $validator = Validator::make($inputs, [
                'quantity'  => 'required|int|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => Arr::first($validator->getMessageBag()->getMessages())[0]
                ], 422);
            }

            $shoppingCart = $this->shoppingCartService->updateProductQuantity($shoppingCartId, $productId, $inputs['quantity']);
            return response()->json($shoppingCart);
        } catch (Exception $error) {
            return response()->json([
                'message' => $error->getMessage()
            ], 404);
        }
    }
}
