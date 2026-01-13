<?php

namespace App\Services;

use App\DataTransferObjects\CartItemData;
use App\DataTransferObjects\CartTotalsData;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service class handling all cart and checkout operations.
 * Designed for testability and single responsibility.
 */
class CartService
{
    private const float DEFAULT_TAX_RATE = 0.10;

    /**
     * @var array<int, CartItemData>
     */
    private array $items = [];

    private float        $taxRate;
    private float        $orderDiscount     = 0.0;
    private DiscountType $orderDiscountType = DiscountType::Fixed;

    public function __construct(?float $taxRate = null)
    {
        $this->taxRate = $taxRate ?? self::DEFAULT_TAX_RATE;
    }

    /*
    |--------------------------------------------------------------------------
    | Cart Item Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Add a product to the cart
     *
     * @throws Exception
     */
    public function addProduct(Product $product, int $quantity = 1): CartItemData
    {
        $this->validateProduct($product);
        $this->validateStock($product, $quantity);

        $productId = $product->id;

        if (isset($this->items[$productId])) {
            // Update existing item
            $existingItem = $this->items[$productId];
            $newQuantity  = $existingItem->quantity + $quantity;

            $this->validateStock($product, $newQuantity);

            $this->items[$productId] = $existingItem->withQuantity($newQuantity);
        } else {
            // Add new item
            $this->items[$productId] = CartItemData::fromProduct($product, $quantity);
        }

        return $this->items[$productId];
    }

    /**
     * Validate product for cart addition
     *
     * @throws Exception
     */
    private function validateProduct(Product $product): void
    {
        if (!$product->is_active) {
            throw new Exception("Product '{$product->name}' is inactive");
        }
    }

    /**
     * Validate stock availability
     *
     * @throws Exception
     */
    private function validateStock(Product $product, int $requestedQuantity): void
    {
        if ($product->stock_qtty < $requestedQuantity) {
            throw new Exception(
                "Insufficient stock for '{$product->name}'. Available: {$product->stock_qtty}"
            );
        }
    }

    /**
     * Increment item quantity
     */
    public function incrementQuantity(int $productId): ?CartItemData
    {
        if (!isset($this->items[$productId])) {
            return null;
        }

        $item = $this->items[$productId];

        if (!$item->canIncrement()) {
            throw new Exception("Maximum stock reached ({$item->maxStock} units)");
        }

        return $this->updateQuantity($productId, $item->quantity + 1);
    }

    /**
     * Update item quantity
     *
     * @throws Exception
     */
    public function updateQuantity(int $productId, int $quantity): ?CartItemData
    {
        if (!isset($this->items[$productId])) {
            return null;
        }

        if ($quantity <= 0) {
            $this->removeItem($productId);
            return null;
        }

        $item = $this->items[$productId];

        if ($quantity > $item->maxStock) {
            throw new Exception("Only {$item->maxStock} units available");
        }

        $this->items[$productId] = $item->withQuantity($quantity);

        return $this->items[$productId];
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $productId): bool
    {
        if (!isset($this->items[$productId])) {
            return false;
        }

        unset($this->items[$productId]);
        return true;
    }

    /**
     * Decrement item quantity
     */
    public function decrementQuantity(int $productId): ?CartItemData
    {
        if (!isset($this->items[$productId])) {
            return null;
        }

        $item = $this->items[$productId];

        return $this->updateQuantity($productId, $item->quantity - 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Order Discount Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Update item discount
     */
    public function updateItemDiscount(
        int          $productId,
        float        $discount,
        DiscountType $type
    ): ?CartItemData
    {
        if (!isset($this->items[$productId])) {
            return null;
        }

        $this->items[$productId] = $this->items[$productId]->withDiscount($discount, $type);

        return $this->items[$productId];
    }

    /**
     * Get current order discount value
     */
    public function getOrderDiscount(): float
    {
        return $this->orderDiscount;
    }

    /**
     * Set order-level discount
     */
    public function setOrderDiscount(float $discount, DiscountType $type): void
    {
        $this->orderDiscount     = max(0, $discount);
        $this->orderDiscountType = $type;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * Get current order discount type
     */
    public function getOrderDiscountType(): DiscountType
    {
        return $this->orderDiscountType;
    }

    /**
     * Get all cart items
     *
     * @return array<int, CartItemData>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get item by product ID
     */
    public function getItem(int $productId): ?CartItemData
    {
        return $this->items[$productId] ?? null;
    }

    /**
     * Get item count
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }

    /**
     * Get tax rate
     */
    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    /**
     * Set tax rate
     */
    public function setTaxRate(float $rate): void
    {
        $this->taxRate = max(0, min(1, $rate));
    }

    /**
     * Export cart state to array
     */
    public function toArray(): array
    {
        $items = array_map(function ($item) { return $item->toArray(); }, $this->items);

        return [
            'items'               => $items,
            'tax_rate'            => $this->taxRate,
            'order_discount'      => $this->orderDiscount,
            'order_discount_type' => $this->orderDiscountType->value,
            'totals'              => $this->getTotals()->toArray(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | State Serialization (for Livewire)
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate and return all totals
     */
    public function getTotals(): CartTotalsData
    {
        return CartTotalsData::calculate(
            items: $this->items,
            taxRate: $this->taxRate,
            orderDiscount: $this->orderDiscount,
            orderDiscountType: $this->orderDiscountType,
        );
    }

    /**
     * Restore cart state from array
     */
    public function fromArray(array $data): void
    {
        $this->clear();

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $productId => $itemData) {
                $this->items[(int)$productId] = CartItemData::fromArray($itemData);
            }
        }

        $this->taxRate           = (float)($data['tax_rate'] ?? self::DEFAULT_TAX_RATE);
        $this->orderDiscount     = (float)($data['order_discount'] ?? 0);
        $this->orderDiscountType = isset($data['order_discount_type'])
            ? DiscountType::tryFrom((int)$data['order_discount_type']) ?? DiscountType::Fixed
            : DiscountType::Fixed;
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Clear all items from cart
     */
    public function clear(): void
    {
        $this->items             = [];
        $this->orderDiscount     = 0.0;
        $this->orderDiscountType = DiscountType::Fixed;
    }

    /**
     * Process checkout and create transaction
     *
     * @throws Exception
     */
    public function checkout(
        int           $cashierId,
        ?int          $customerId,
        float         $amountPaid,
        PaymentMethod $paymentMethod,
        ?string       $paymentReference = null,
    ): Transaction
    {
        // Validate cart
        if ($this->isEmpty()) {
            throw new Exception('Cart is empty');
        }

        $totals = $this->getTotals();

        // Validate payment amount
        if (round($amountPaid, 2) < round($totals->total, 2)) {
            throw new Exception(
                "Insufficient payment. Required: \${$totals->total}, Received: \${$amountPaid}"
            );
        }

        return DB::transaction(function () use (
            $cashierId,
            $customerId,
            $amountPaid,
            $paymentMethod,
            $paymentReference,
            $totals
        ) {
            // Create transaction
            $transaction = Transaction::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'date'           => now(),
                'status'         => TransactionStatus::Completed,
                'subtotal'       => $totals->subtotal,
                'tax'            => $totals->taxAmount,
                'discount'       => $totals->orderDiscount,
                'discount_type'  => $this->orderDiscountType,
                'total'          => $totals->total,
                'cashier_id'     => $cashierId,
                'customer_id'    => $customerId,
            ]);

            // Create transaction items and update stock
            foreach ($this->items as $item) {
                $product = Product::where('id', $item->productId)
                    ->lockForUpdate()
                    ->first();

                if (!$product || $product->stock_qtty < $item->quantity) {
                    throw new Exception(
                        "Product '{$item->name}' has insufficient stock"
                    );
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $item->productId,
                    'qtty'           => $item->quantity,
                    'unit_price'     => $item->unitPrice,
                    'discount'       => $item->discount,
                    'discount_type'  => $item->discountType,
                    'subtotal'       => $item->getLineTotal(),
                    'total'          => $item->getSubtotal(),
                ]);

                $product->decrement('stock_qtty', $item->quantity);
            }

            // Create payment record
            Payment::create([
                'transaction_id' => $transaction->id,
                'method'         => $paymentMethod,
                'amount'         => $totals->total,
                'reference'      => $paymentReference ?: null,
                'status'         => PaymentStatus::Completed,
            ]);

            return $transaction;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Product Search
    |--------------------------------------------------------------------------
    */

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate change for cash payment
     */
    public function calculateChange(float $amountPaid): float
    {
        $totals = $this->getTotals();
        return round(max(0, $amountPaid - $totals->total), 2);
    }

    /**
     * Search products by name or SKU
     */
    public function searchProducts(string $query, int $limit = 20): Collection
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('stock_qtty', '>', 0)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('sku', 'ilike', "%{$query}%");
            })
            ->with('category:id,name')
            ->select(['id', 'name', 'sku', 'price', 'stock_qtty', 'category_id'])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(?int $categoryId, int $limit = 50): Collection
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('stock_qtty', '>', 0)
            ->with('category:id,name')
            ->select(['id', 'name', 'sku', 'price', 'stock_qtty', 'category_id'])
            ->orderBy('name');

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->limit($limit)->get();
    }
}
