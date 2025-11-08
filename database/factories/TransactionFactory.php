<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->word(),
            'date'           => Carbon::now(),
            'total'          => $this->faker->randomFloat(10, 1, 10000),
            'status'         => collect(TransactionStatus::cases())->random()->value,
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now(),

            'cashier_id'  => User::factory(),
            'customer_id' => Customer::factory(),
        ];
    }
}
