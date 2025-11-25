<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        /** @var PaymentMethod $method */
        $method = collect(PaymentMethod::cases())->random();
        return [
            'method'     => $method->value,
            'amount'     => $this->state['amount'] ?? $this->faker->randomFloat(2, 1, 1000),
            'reference'  => $method !== PaymentMethod::Cash ? $this->faker->uuid() : null,
            'status'     => collect(PaymentStatus::cases())->random()->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
