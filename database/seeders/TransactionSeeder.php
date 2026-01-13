<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Factories\Traits\CalculatesDiscount;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    use CalculatesDiscount;

    /**
     * Seed the transactions table.
     *
     * Creates transactions with items and payments, simulating real POS activity.
     * Each transaction includes:
     * - Multiple transaction items (products purchased)
     * - Calculated subtotals, discounts, and taxes
     * - Associated payment records
     */
    public function run(): void
    {
        $users     = User::all();
        $customers = Customer::all();
        $products  = Product::all();

        // Check if required data exists
        if ($users->isEmpty() || $customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Cannot seed transactions: Users, Customers, or Products are missing.');
            $this->command->info('Please run UserSeeder, CustomerSeeder, and ProductSeeder first.');
            return;
        }

        // Create 50 transactions with varying complexity
        Transaction::factory(50)
            ->prependState(fn() => [
                'cashier_id'  => $users->random()->id,
                'customer_id' => $customers->random()->id,
            ])
            ->create()
            ->each(function (Transaction $transaction) use ($products) {
                // Add 1-10 random items to each transaction
                $itemCount = rand(1, 10);

                foreach (range(1, $itemCount) as $ignored) {
                    /** @var Product $product */
                    $product = $products->random();

                    // Create transaction item with product price
                    TransactionItem::factory()->create([
                        'transaction_id' => $transaction->id,
                        'product_id'     => $product->id,
                        'unit_price'     => $product->price,
                    ])->calculateTotal()->save();
                }

                // Calculate subtotal from all items
                $transaction->calculateSubtotal();

                // Apply a random discount
                [$discountAmount, $subtotal, $discountType] = $this->getDiscount($transaction->subtotal);
                $transaction->discount      = $discountAmount;
                $transaction->discount_type = $discountType;

                // Calculate 10% tax on subtotal after discount
                $transaction->tax = round(0.1 * $subtotal, 2);

                // Calculate final total and save
                $transaction->calculateTotal()->save();

                // Create payment record matching the transaction total
                Payment::factory()->create([
                    'transaction_id' => $transaction->id,
                    'amount'         => $transaction->total,
                    'status'         => PaymentStatus::from($transaction->status->value),
                ]);
            });
    }
}
