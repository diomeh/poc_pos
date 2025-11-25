<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int|null $date
 * @property numeric $total
 * @property TransactionStatus|null $status
 * @property int $cashier_id
 * @property int $customer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property numeric $subtotal
 * @property numeric $tax
 * @property numeric $discount
 * @property string $discount_amount
 * @property DiscountType $discount_type
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
 * @method static Builder<static>|Transaction whereDiscount($value)
 * @method static Builder<static>|Transaction whereDiscountAmount($value)
 * @method static Builder<static>|Transaction whereId($value)
 * @method static Builder<static>|Transaction whereInvoiceNumber($value)
 * @method static Builder<static>|Transaction whereStatus($value)
 * @method static Builder<static>|Transaction whereSubtotal($value)
 * @method static Builder<static>|Transaction whereTax($value)
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
        'subtotal',
        'tax',
        'discount',
        'discount_type',
        'status',
        'cashier_id',
        'customer_id',
    ];

    protected $casts = [
        'date'          => 'timestamp',
        'total'         => 'decimal:2',
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'discount'      => 'decimal:2',
        'discount_type' => DiscountType::class,
        'status'        => TransactionStatus::class,
        'cashier_id'    => 'integer',
        'customer_id'   => 'integer',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function calculateSubtotal(): self
    {
        $this->subtotal = $this->items
            ->each(fn(TransactionItem $transactionItem) => $transactionItem->calculateTotal())
            ->sum('total');

        return $this;
    }

    public function calculateTotal(): self
    {
        $this->calculateSubtotal();

        // 2. Apply transaction-level discount
        $discount = match ($this->discount_type) {
            DiscountType::Percentage => ($this->discount / 100) * $this->subtotal,
            DiscountType::Fixed      => $this->discount,
            default                  => 0,
        };

        // 3. Add tax (assuming tax is a fixed amount here)
        $this->total = $this->subtotal - $discount + $this->tax;

        return $this;
    }
}
