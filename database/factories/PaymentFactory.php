<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'method'     => collect(PaymentMethod::cases())->random()->value,
            'amount'     => $this->faker->randomFloat(2, 1, 1000),
            'reference'  => $this->faker->uuid(),
            'status'     => collect(PaymentStatus::cases())->random()->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
