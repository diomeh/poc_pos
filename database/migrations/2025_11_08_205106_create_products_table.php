<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('name');
            $table->longText('description')->nullable();
            $table->decimal('price')->nullable()->default(0.0);
            $table->decimal('cost')->nullable()->default(0.0);
            $table->integer('stock_qtty')->nullable()->default(1);
            $table->boolean('is_active')->nullable()->default(true);
            $table->foreignIdFor(Category::class)->constrained('categories');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
