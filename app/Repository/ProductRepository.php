<?php

use Prettus\Repository\Eloquent\BaseRepository;
use App\Models\Product;

class ProductRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Product::class;
    }
}
