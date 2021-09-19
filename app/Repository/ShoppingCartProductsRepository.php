<?php

namespace App\Repository;

use App\Models\ShoppingCartProduct;
use Prettus\Repository\Eloquent\BaseRepository;

class ShoppingCartProductsRepository extends BaseRepository
{
    public function model()
    {
        return ShoppingCartProduct::class;
    }
}
