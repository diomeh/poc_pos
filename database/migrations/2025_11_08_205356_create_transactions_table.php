<?php

use App\Enums\DiscountType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number');
            $table->timestamp('date')->nullable();
            $table->decimal('total');
            $table->decimal('subtotal');
            $table->decimal('tax')->default(0);
            $table->tinyInteger('discount_type')->default(DiscountType::Fixed->value);
            $table->decimal('discount')->default(0);
            $table->integer('status')->nullable();
            $table->foreignUuid('cashier_id')->constrained('users');
            $table->foreignUuid('customer_id')->constrained('customers');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
