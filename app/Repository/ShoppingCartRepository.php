<?php

namespace App\Repository;

use App\Models\ShoppingCart;
use Prettus\Repository\Eloquent\BaseRepository;

class ShoppingCartRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return ShoppingCart::class;
    }
}
