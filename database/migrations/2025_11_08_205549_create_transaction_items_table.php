<?php

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Transaction::class)->constrained('transactions');
            $table->foreignIdFor(Product::class)->constrained('products');
            $table->integer('qtty');
            $table->decimal('unit_price');
            $table->decimal('discount');
            $table->decimal('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
