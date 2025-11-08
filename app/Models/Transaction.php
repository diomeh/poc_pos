<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'date',
        'total',
        'status',
        'cashier_id',
        'customer_id',
        'payment_id',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'date'        => 'timestamp',
            'total'       => 'decimal:2',
            'status'      => TransactionStatus::class,
            'cashier_id'  => 'integer',
            'customer_id' => 'integer',
            'payment_id'  => 'integer',
        ];
    }
}
