<?php

use App\Enums\DiscountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('subtotal')->after('total');
            $table->decimal('tax')->default(0)->after('subtotal');
            $table->tinyInteger('discount_type')->default(DiscountType::Fixed->value)->after('subtotal');
            $table->decimal('discount')->default(0)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax', 'discount', 'discount_type']);
        });
    }
};
