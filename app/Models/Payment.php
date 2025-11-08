<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'method',
        'amount',
        'reference',
        'status',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
    ];

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'payment_id');
    }
}
