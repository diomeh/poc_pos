<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Database\Factories\Traits\CalculatesDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TransactionItemFactory extends Factory
{
    use CalculatesDiscount;

    protected $model = TransactionItem::class;

    public function definition(): array
    {
        $qtty      = $this->faker->randomNumber(2);
        $unitPrice = $this->state['unit_price'] ?? $this->faker->randomFloat(2, 1, 1000);
        [$discountAmount, $subtotal, $discountType] = $this->getDiscount($qtty * $unitPrice);

        return [
            'qtty'          => $qtty,
            'unit_price'    => $unitPrice,
            'discount'      => $discountAmount,
            'discount_type' => $discountType->value,
            'subtotal'      => $subtotal,
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),

            'transaction_id' => $this->state['transaction_id'] ?? Transaction::factory(),
            'product_id'     => $this->state['product_id'] ?? Product::factory(),
        ];
    }
}
