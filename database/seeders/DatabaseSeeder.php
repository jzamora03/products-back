<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Beverages',  'slug' => 'beverages'],
            ['name' => 'Food',       'slug' => 'food'],
            ['name' => 'Electronics','slug' => 'electronics'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $beverages   = Category::where('slug', 'beverages')->first();
        $food        = Category::where('slug', 'food')->first();
        $electronics = Category::where('slug', 'electronics')->first();

      $products = [
    ['name' => 'Coca Cola 500ml',   'sku' => 'BEV-001', 'price' => 1.50,  'stock' => 200, 'category_id' => $beverages->id],
    ['name' => 'Orange Juice',      'sku' => 'BEV-002', 'price' => 2.00,  'stock' => 150, 'category_id' => $beverages->id],
    ['name' => 'Sparkling Water',   'sku' => 'BEV-003', 'price' => 1.20,  'stock' => 300, 'category_id' => $beverages->id],
    ['name' => 'Green Tea',         'sku' => 'BEV-004', 'price' => 1.80,  'stock' => 120, 'category_id' => $beverages->id],
    ['name' => 'Burger Classic',    'sku' => 'FOO-001', 'price' => 8.99,  'stock' => 50,  'category_id' => $food->id],
    ['name' => 'Veggie Burger',     'sku' => 'FOO-002', 'price' => 9.99,  'stock' => 30,  'category_id' => $food->id],
    ['name' => 'Chicken Sandwich',  'sku' => 'FOO-003', 'price' => 7.50,  'stock' => 45,  'category_id' => $food->id],
    ['name' => 'Caesar Salad',      'sku' => 'FOO-004', 'price' => 6.99,  'stock' => 0,   'category_id' => $food->id],
    ['name' => 'USB-C Cable',       'sku' => 'ELC-001', 'price' => 12.99, 'stock' => 80,  'category_id' => $electronics->id],
    ['name' => 'Wireless Mouse',    'sku' => 'ELC-002', 'price' => 29.99, 'stock' => 40,  'category_id' => $electronics->id],
    ['name' => 'Mechanical Keyboard','sku' => 'ELC-003', 'price' => 89.99, 'stock' => 15,  'category_id' => $electronics->id],
    ['name' => 'HDMI Cable 2m',     'sku' => 'ELC-004', 'price' => 8.99,  'stock' => 6,   'category_id' => $electronics->id],
];

        foreach ($products as $prod) {
            Product::firstOrCreate(['sku' => $prod['sku']], $prod);
        }
    }
}