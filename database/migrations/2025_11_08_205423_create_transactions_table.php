<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->timestamp('date')->nullable();
            $table->decimal('total');
            $table->integer('status')->nullable();
            $table->foreignIdFor(User::class, 'cashier_id')->constrained('users');
            $table->foreignIdFor(Customer::class)->constrained('customers');
            $table->foreignIdFor(Payment::class)->nullable()->constrained('payments');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
