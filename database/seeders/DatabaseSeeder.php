<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $customers = Customer::factory(20)->create();

        $categories = Category::factory(10)->create();
        $products   = Product::factory(50)->state(fn() => [
            'category_id' => $categories->random()->id,
        ])->create();

        $payments = Payment::factory(30)->create();

        $transactions = Transaction::factory(30)->state(fn() => [
            'cashier_id'  => $user->id,
            'customer_id' => $customers->random()->id,
            'payment_id'  => $payments->random()->id,
        ])->create();

        TransactionItem::factory(100)->state(fn() => [
            'transaction_id' => $transactions->random()->id,
            'product_id'     => $products->random()->id,
        ])->create();
    }
}
