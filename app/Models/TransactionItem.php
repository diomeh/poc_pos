<?php

namespace App\Models;

use Database\Factories\TransactionItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $transaction_id
 * @property int $product_id
 * @property int $qtty
 * @property numeric $unit_price
 * @property numeric $discount
 * @property numeric $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Transaction $transaction
 * @method static TransactionItemFactory factory($count = null, $state = [])
 * @method static Builder<static>|TransactionItem newModelQuery()
 * @method static Builder<static>|TransactionItem newQuery()
 * @method static Builder<static>|TransactionItem query()
 * @method static Builder<static>|TransactionItem whereCreatedAt($value)
 * @method static Builder<static>|TransactionItem whereDiscount($value)
 * @method static Builder<static>|TransactionItem whereId($value)
 * @method static Builder<static>|TransactionItem whereProductId($value)
 * @method static Builder<static>|TransactionItem whereQtty($value)
 * @method static Builder<static>|TransactionItem whereSubtotal($value)
 * @method static Builder<static>|TransactionItem whereTransactionId($value)
 * @method static Builder<static>|TransactionItem whereUnitPrice($value)
 * @method static Builder<static>|TransactionItem whereUpdatedAt($value)
 * @mixin Eloquent
 */
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
