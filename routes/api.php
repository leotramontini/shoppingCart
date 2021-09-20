<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('shoppingCart')->group(function () {
    Route::post('/adding/product', 'App\Http\Controllers\ShoppingCartController@addingProduct');

    Route::delete('/{shoppingCartId}/product/{productId}', 'App\Http\Controllers\ShoppingCartController@removeProduct')
        ->where('shoppingCartId', '[0-9]+')
        ->where('productId', '[0-9]+');

    Route::put('/{shoppingCartId}/clear', 'App\Http\Controllers\ShoppingCartController@clearProduct')
        ->where('shoppingCartId', '[0-9]+');

    Route::get('/{shoppingCartId}', 'App\Http\Controllers\ShoppingCartController@getById')
        ->where('shoppingCartId', '[0-9]+');

    Route::patch('/{shoppingCartId}/product/{productId}/updateQuantity', 'App\Http\Controllers\ShoppingCartController@updateProductQuantity')
        ->where('shoppingCartId', '[0-9]+')
        ->where('productId', '[0-9]+');
});
