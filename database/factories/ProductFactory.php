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
        return [
            'sku'         => $this->faker->uuid(),
            'name'        => $this->faker->word(),
            'description' => $this->faker->text(),
            'price'       => $this->faker->randomFloat(2, 1, 1000),
            'cost'        => $this->faker->randomFloat(2, 1, 500),
            'stock_qtty'  => $this->faker->randomNumber(4),
            'is_active'   => $this->faker->boolean(),
            'created_at'  => Carbon::now(),
            'updated_at'  => Carbon::now(),

            'category_id' => Category::factory(),
        ];
    }
}
