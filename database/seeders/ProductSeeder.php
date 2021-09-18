<?php

namespace Database\Seeders;

use App\Helper\ProductHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (ProductHelper::PRODUCTS as $product) {
            DB::table('products')->insert($product);
        }
    }
}
