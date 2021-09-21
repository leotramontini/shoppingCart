<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartProduct;
use Tests\TestCase;

class AddingProductShoppingCartTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->baseResource = '/api/shoppingCart';
    }

    public function testAddingProduct()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = true;
        $product->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'    => $product->id,
            'price'         => $product->price,
            'quantity'      => 1
        ]);

        $response->assertJson([
            'id'        => 1,
            'total'     => $product->price,
            'products'  => [
                [
                    'product_id'    => $product->id,
                    'name'          => $product->name,
                    'price'         => $product->price,
                    'quantity'      => 1
                ]
            ]
        ])->assertStatus(200);
    }

    public function testAddingProductWithQuantityBiggerThanStock()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = true;
        $product->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'    => $product->id,
            'price'         => $product->price,
            'quantity'      => 2
        ]);

        $response->assertJson([
            'message' => 'Product request quantity bigger than stock quantity'
        ])->assertStatus(404);
    }

    public function testAddingDisableProduct()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = false;
        $product->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'    => $product->id,
            'price'         => $product->price,
            'quantity'      => 1
        ]);

        $response->assertJson([
            'message' => 'Product not found'
        ])->assertStatus(404);
    }

    public function testAddingProductNotFound()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = true;
        $product->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'    => $product->id + $this->faker->randomDigitNotZero(),
            'price'         => $product->price,
            'quantity'      => 1
        ]);

        $response->assertJson([
            'message' => 'Product not found'
        ])->assertStatus(404);
    }

    public function testAddingProductInCreateShoppingCart()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = true;
        $product->save();

        $product2 = new Product();
        $product2->name     = $this->faker->name;
        $product2->quantity = 1;
        $product2->price    = 12;
        $product2->enable    = true;
        $product2->save();

        $shoppingCart = new ShoppingCart();
        $shoppingCart->total = 10;
        $shoppingCart->save();

        $shoppingCartProduct = new ShoppingCartProduct();
        $shoppingCartProduct->price             = $product->price;
        $shoppingCartProduct->shopping_cart_id  = $shoppingCart->id;
        $shoppingCartProduct->product_id        = $product->id;
        $shoppingCartProduct->quantity          = 1;
        $shoppingCartProduct->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'        => $product2->id,
            'price'             => $product2->price,
            'quantity'          => 1,
            'shopping_cart_id'  => $shoppingCart->id
        ]);

        $response->assertJson([
            'total'     => $product->price + $product2->price,
            'products'  => [
                [
                    'product_id'    => $product->id,
                    'name'          => $product->name,
                    'price'         => $product->price,
                    'quantity'      => 1
                ],
                [
                    'product_id'    => $product2->id,
                    'name'          => $product2->name,
                    'price'         => $product2->price,
                    'quantity'      => 1
                ],
            ]
        ])->assertStatus(200);
    }

    public function testAddingSomeProductInCreateShoppingCart()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = true;
        $product->save();

        $shoppingCart = new ShoppingCart();
        $shoppingCart->total = 10;
        $shoppingCart->save();

        $shoppingCartProduct = new ShoppingCartProduct();
        $shoppingCartProduct->price             = $product->price;
        $shoppingCartProduct->shopping_cart_id  = $shoppingCart->id;
        $shoppingCartProduct->product_id        = $product->id;
        $shoppingCartProduct->quantity          = 1;
        $shoppingCartProduct->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'        => $product->id,
            'price'             => $product->price,
            'quantity'          => 1,
            'shopping_cart_id'  => $shoppingCart->id
        ]);

        $response->assertJson([
            'message' => 'Product is already in shopping cart'
        ])->assertStatus(404);
    }

    public function testAddingDisableProductInCreateShoppingCart()
    {
        $product = new Product();
        $product->name      = $this->faker->name;
        $product->quantity  = 1;
        $product->price     = 10;
        $product->enable    = true;
        $product->save();

        $product2 = new Product();
        $product2->name     = $this->faker->name;
        $product2->quantity = 1;
        $product2->price    = 12;
        $product2->enable    = false;
        $product2->save();

        $shoppingCart = new ShoppingCart();
        $shoppingCart->total = 10;
        $shoppingCart->save();

        $shoppingCartProduct = new ShoppingCartProduct();
        $shoppingCartProduct->price             = $product->price;
        $shoppingCartProduct->shopping_cart_id  = $shoppingCart->id;
        $shoppingCartProduct->product_id        = $product->id;
        $shoppingCartProduct->quantity          = 1;
        $shoppingCartProduct->save();

        $response = $this->json('POST', $this->baseResource . '/adding/product', [
            'product_id'        => $product2->id,
            'price'             => $product2->price,
            'quantity'          => 1,
            'shopping_cart_id'  => $shoppingCart->id
        ]);

        $response->assertJson([
            'message' => 'Product not found'
        ])->assertStatus(404);
    }
}
