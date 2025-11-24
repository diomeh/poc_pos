<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int|null $date
 * @property numeric $total
 * @property TransactionStatus|null $status
 * @property int $cashier_id
 * @property int $customer_id
 * @property int|null $payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $cashier
 * @property-read Customer $customer
 * @property-read Collection<int, TransactionItem> $items
 * @property-read int|null $items_count
 * @property-read Payment|null $payment
 * @method static TransactionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Transaction newModelQuery()
 * @method static Builder<static>|Transaction newQuery()
 * @method static Builder<static>|Transaction query()
 * @method static Builder<static>|Transaction whereCashierId($value)
 * @method static Builder<static>|Transaction whereCreatedAt($value)
 * @method static Builder<static>|Transaction whereCustomerId($value)
 * @method static Builder<static>|Transaction whereDate($value)
 * @method static Builder<static>|Transaction whereId($value)
 * @method static Builder<static>|Transaction whereInvoiceNumber($value)
 * @method static Builder<static>|Transaction wherePaymentId($value)
 * @method static Builder<static>|Transaction whereStatus($value)
 * @method static Builder<static>|Transaction whereTotal($value)
 * @method static Builder<static>|Transaction whereUpdatedAt($value)
 * @mixin Eloquent
 */
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

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
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
