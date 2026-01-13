<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the categories table.
     *
     * Creates product categories for the POS inventory.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Electronics',
                'description' => 'Devices and gadgets such as phones, laptops, cameras, and accessories.',
            ],
            [
                'name'        => 'Home Appliances',
                'description' => 'Household machines including refrigerators, washing machines, and vacuum cleaners.',
            ],
            [
                'name'        => 'Furniture',
                'description' => 'Home and office furniture such as desks, chairs, beds, and storage units.',
            ],
            [
                'name'        => 'Fashion',
                'description' => 'Clothing, footwear, and accessories for men, women, and children.',
            ],
            [
                'name'        => 'Beauty & Health',
                'description' => 'Personal care, cosmetics, skincare, and health products.',
            ],
            [
                'name'        => 'Sports & Outdoors',
                'description' => 'Equipment, apparel, and gear for sports, fitness, and outdoor activities.',
            ],
            [
                'name'        => 'Toys & Games',
                'description' => 'Board games, learning toys, action figures, and other children\'s entertainment products.',
            ],
            [
                'name'        => 'Automotive',
                'description' => 'Car accessories, tools, parts, and vehicle maintenance products.',
            ],
            [
                'name'        => 'Books & Media',
                'description' => 'Books, magazines, music, movies, and educational media.',
            ],
            [
                'name'        => 'Groceries',
                'description' => 'Food items, beverages, snacks, and other everyday grocery products.',
            ],
        ];

        foreach ($categories as $category) {
            Category::factory()->create($category);
        }
    }
}
