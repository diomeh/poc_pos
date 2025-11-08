<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'cost',
        'stock_qtty',
        'is_active',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function casts(): array
    {
        return [
            'price'       => 'decimal:2',
            'cost'        => 'decimal:2',
            'stock_qtty'  => 'integer',
            'is_active'   => 'boolean',
            'category_id' => 'integer',
        ];
    }
}
