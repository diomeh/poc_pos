<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = $this->faker->randomFloat(2, 1, 500);
        do {
            $price = $this->faker->randomFloat(2, 1, 1000);
        } while ($price <= $cost);

        return [
            'sku'         => $this->faker->uuid(),
            'name'        => $this->state['name'] ?? 'pending',
            'description' => $this->state['description'] ?? null,
            'price'       => $price,
            'cost'        => $cost,
            'stock_qtty'  => $this->faker->randomNumber(2),
            'is_active'   => $this->faker->boolean(),
            'created_at'  => Carbon::now(),
            'updated_at'  => Carbon::now(),

            'category_id' => $this->state['category_id'] ?? Category::factory(),
        ];
    }
}
