<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'qtty',
        'unit_price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'transaction_id' => 'string',
        'product_id'     => 'integer',
        'qtty'           => 'integer',
        'unit_price'     => 'decimal:2',
        'discount'       => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
