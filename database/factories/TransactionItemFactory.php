<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        return [
            'qtty'       => $this->faker->randomNumber(4),
            'unit_price' => $this->faker->randomFloat(2, 1, 1000),
            'discount'   => $this->faker->randomFloat(2, 0, 100),
            'subtotal'   => $this->faker->randomFloat(2, 1, 10000),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'transaction_id' => Transaction::factory(),
            'product_id'     => Product::factory(),
        ];
    }
}
