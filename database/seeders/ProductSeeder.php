<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'id' => 1,
                'name' => 'Double Cheeseburger Meal',
                'available_stock' => 50,
            ],
            [
                'id' => 2,
                'name' => 'Crispy Fried Chicken Bucket',
                'available_stock' => 10,
            ],
            [
                'id' => 3,
                'name' => 'Pepperoni Pizza Deluxe',
                'available_stock' => 30,
            ],
            [
                'id' => 4,
                'name' => 'Creamy Carbonara Pasta',
                'available_stock' => 25,
            ],
            [
                'id' => 5,
                'name' => 'Spicy Tonkotsu Ramen',
                'available_stock' => 40,
            ],
            [
                'id' => 6,
                'name' => 'Belgian Fries Large',
                'available_stock' => 60,
            ],
            [
                'id' => 7,
                'name' => 'Brown Sugar Milk Tea',
                'available_stock' => 100,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['id' => $product['id']],
                [
                    'name' => $product['name'],
                    'available_stock' => $product['available_stock'],
                ]
            );
        }
    }
}
