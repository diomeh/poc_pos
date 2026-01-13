<?php

use App\Enums\DiscountType;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions');
            $table->foreignUuid('product_id')->constrained('products');
            $table->integer('qtty');
            $table->decimal('unit_price');
            $table->decimal('discount');
            $table->tinyInteger('discount_type')->default(DiscountType::Fixed->value);
            $table->decimal('subtotal');
            $table->decimal('total')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
