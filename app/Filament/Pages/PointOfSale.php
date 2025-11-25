<?php

namespace App\Filament\Pages;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use BackedEnum;
use Exception;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Str;

class PointOfSale extends Page implements HasForms
{
    use InteractsWithForms;

    public const string DISCOUNT_TYPE_FIXED      = 'fixed';
    public const string DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    protected static string|null|BackedEnum $navigationIcon  = Heroicon::ShoppingCart;
    protected string                        $view            = 'filament.pages.point-of-sale';
    protected static ?string                $navigationLabel = 'Point of Sale';
    protected static ?string                $title           = 'Point of Sale';

    // Properties
    public string $search = '';
    public array  $cart   = [];

    // Math properties initialized to float to prevent type issues
    public float  $subtotal           = 0.00;
    public float  $tax                = 0.00;
    public float  $taxRate            = 0.10; // 10%
    public float  $discount           = 0.00;
    public string $discountType       = self::DISCOUNT_TYPE_FIXED;
    public float  $discountPercentage = 0.00;
    public float  $discountAmount     = 0.00;
    public float  $total              = 0.00;
    public float  $amountPaid         = 0.00;
    public float  $change             = 0.00;
    public int    $paymentMethod      = PaymentMethod::Cash->value;
    public ?int   $customerId         = null;
    public string $paymentReference   = '';

    // Listeners
    protected $listeners = ['productAdded' => 'addToCart'];

    public function mount(): void
    {
        $this->customerId = Customer::first()?->id;
        $this->calculateTotals();
    }

    /*
     * -----------------------------------------------------------------
     *  LIFECYCLE HOOKS
     * -----------------------------------------------------------------
     */

    public function updatedCart($value, $key): void
    {
        // Handle empty discount values for cart items
        if (str_ends_with($key, '.discount')) {
            $cartKey = (int)explode('.', $key)[1];
            if (isset($this->cart[$cartKey]) && ($value === '' || $value === null)) {
                $this->cart[$cartKey]['discount'] = 0.00;
            }
        }

        // When a cart item's discount_type changes, convert the discount value
        if (str_ends_with($key, '.discount_type')) {
            $cartKey = (int)explode('.', $key)[1];
            $this->handleItemDiscountTypeChange($cartKey);
        }
        $this->calculateTotals();
    }

    public function updatedDiscountType(): void
    {
        // When order discount type changes, convert the value
        $this->handleOrderDiscountTypeChange();
        $this->calculateTotals();
    }

    public function updatedDiscount(): void
    {
        // When discount value changes in fixed mode
        $this->calculateTotals();
    }

    public function updatedDiscountPercentage(): void
    {
        // When percentage changes
        $this->calculateTotals();
    }

    public function updatedAmountPaid(): void
    {
        $this->calculateChange();
    }

    /* ----------------------------------------------------------------- */

    /**
     * Handle item discount type change (fixed <-> percentage)
     */
    private function handleItemDiscountTypeChange(int $cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;

        $item            = $this->cart[$cartKey];
        $newType         = $item['discount_type'];
        $currentDiscount = (float)($item['discount'] ?? 0);

        if ($currentDiscount == 0) return;

        $lineTotal = (float)$item['unit_price'] * (int)$item['qtty'];

        if ($lineTotal == 0) return;

        // Switching TO percentage FROM fixed
        if ($newType === self::DISCOUNT_TYPE_PERCENTAGE) {
            // Convert dollar amount to percentage
            $percentage                       = ($currentDiscount / $lineTotal) * 100;
            $this->cart[$cartKey]['discount'] = min(100, round($percentage, 2));
        } // Switching TO fixed FROM percentage
        else {
            // Convert percentage to dollar amount
            $dollarAmount                     = ($currentDiscount / 100) * $lineTotal;
            $this->cart[$cartKey]['discount'] = min($lineTotal, round($dollarAmount, 2));
        }
    }

    /**
     * Handle order discount type change (fixed <-> percentage)
     */
    private function handleOrderDiscountTypeChange(): void
    {
        // Calculate gross total first
        $grossTotal = $this->subtotal + $this->tax;

        if ($grossTotal == 0) return;

        // Switching TO percentage FROM fixed
        if ($this->discountType === self::DISCOUNT_TYPE_PERCENTAGE) {
            if ($this->discount > 0) {
                $percentage               = ($this->discount / $grossTotal) * 100;
                $this->discountPercentage = min(100, round($percentage, 2));
            } else {
                $this->discountPercentage = 0;
            }
        } // Switching TO fixed FROM percentage
        else {
            if ($this->discountPercentage > 0) {
                $dollarAmount   = ($this->discountPercentage / 100) * $grossTotal;
                $this->discount = min($grossTotal, round($dollarAmount, 2));
            } else {
                $this->discount = 0;
            }
        }
    }

    public function searchProducts(): array|Collection
    {
        if (empty($this->search) || strlen($this->search) < 2) {
            return [];
        }

        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->limit(20)
            ->get()
            ->map(fn($product) => [
                'id'       => $product->id,
                'name'     => $product->name,
                'sku'      => $product->sku,
                'price'    => (float)$product->price,
                'stock'    => $product->stock_qtty,
                'category' => $product->category->name ?? 'N/A',
            ]);
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

        if (!$this->validateProduct($product)) {
            return;
        }

        $cartKey = $product->id;

        if (isset($this->cart[$cartKey])) {
            $newQty = $this->cart[$cartKey]['qtty'] + 1;

            if ($newQty > $product->stock_qtty) {
                $this->notifyStockError($product->stock_qtty);
                return;
            }

            $this->cart[$cartKey]['qtty'] = $newQty;
        } else {
            $this->cart[$cartKey] = [
                'product_id'    => $product->id,
                'name'          => $product->name,
                'sku'           => $product->sku,
                'unit_price'    => (float)$product->price,
                'qtty'          => 1,
                'discount'      => 0.00,
                'discount_type' => self::DISCOUNT_TYPE_FIXED,
                'subtotal'      => (float)$product->price,
                'max_stock'     => $product->stock_qtty,
            ];
        }

        $this->search = '';
        $this->calculateTotals();

        Notification::make()->title('Added to cart')->success()->duration(1500)->send();
    }

    public function updateQuantity(int $cartKey, int $quantity): void
    {
        if (!isset($this->cart[$cartKey])) return;

        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }

        $maxStock = $this->cart[$cartKey]['max_stock'] ?? 0;

        if ($quantity > $maxStock) {
            $this->notifyStockError($maxStock);
            $this->cart[$cartKey]['qtty'] = $maxStock;
        } else {
            $this->cart[$cartKey]['qtty'] = $quantity;
        }

        $this->calculateTotals();
    }

    public function removeFromCart(int $cartKey): void
    {
        unset($this->cart[$cartKey]);
        $this->calculateTotals();
    }

    public function clearCart(): void
    {
        $this->cart               = [];
        $this->amountPaid         = 0.00;
        $this->change             = 0.00;
        $this->discount           = 0.00;
        $this->discountPercentage = 0.00;
        $this->discountAmount     = 0.00;
        $this->discountType       = self::DISCOUNT_TYPE_FIXED;
        $this->paymentReference   = '';
        $this->calculateTotals();
    }

    /**
     * Master Calculation Logic
     * Properly separates item discounts from order discounts
     */
    public function calculateTotals(): void
    {
        $this->subtotal = 0.00;

        // Step 1: Calculate each item's subtotal after item-level discount
        foreach ($this->cart as $key => $item) {
            $lineTotal = (float)$item['unit_price'] * (int)$item['qtty'];

            $discountType  = $item['discount_type'] ?? self::DISCOUNT_TYPE_FIXED;
            $discountValue = (float)($item['discount'] ?? 0);

            // Calculate item discount
            if ($discountType === self::DISCOUNT_TYPE_PERCENTAGE) {
                $discountValue = min(100, max(0, $discountValue));
                $itemDiscount  = round($lineTotal * ($discountValue / 100), 2);
            } else {
                $itemDiscount = min($lineTotal, max(0, $discountValue));
            }

            $itemSubtotal                 = $lineTotal - $itemDiscount;
            $this->cart[$key]['subtotal'] = $itemSubtotal;

            $this->subtotal += $itemSubtotal;
        }

        // Step 2: Calculate Tax on subtotal (after item discounts)
        $this->tax = round($this->subtotal * $this->taxRate, 2);

        // Step 3: Calculate Gross Total (subtotal + tax)
        $grossTotal = $this->subtotal + $this->tax;

        // Step 4: Apply Order-Level Discount to Gross Total
        if ($this->discountType === self::DISCOUNT_TYPE_PERCENTAGE) {
            $percentage           = min(100, max(0, $this->discountPercentage));
            $this->discountAmount = round($grossTotal * ($percentage / 100), 2);
        } else {
            $this->discountAmount = min($grossTotal, max(0, $this->discount));
        }

        // Step 5: Calculate Final Total
        $this->total = max(0, round($grossTotal - $this->discountAmount, 2));

        // Recalculate Change
        $this->calculateChange();
    }

    public function calculateChange(): void
    {
        if ($this->paymentMethod === PaymentMethod::Cash->value) {
            $this->change = max(0, round($this->amountPaid - $this->total, 2));
        } else {
            $this->change = 0;
        }
    }

    public function setPaymentMethod(int $method): void
    {
        $this->paymentMethod = $method;

        if ($method === PaymentMethod::Cash->value) {
            $this->paymentReference = '';
            $this->calculateChange();
        } else {
            $this->change = 0;
        }
    }

    public function completeSale(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Cart is empty')->danger()->send();
            return;
        }

        if (round($this->amountPaid, 2) < round($this->total, 2)) {
            Notification::make()
                ->title('Insufficient payment')
                ->body('Amount paid must be at least $' . number_format($this->total, 2))
                ->danger()
                ->send();
            return;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        DB::beginTransaction();

        try {
            $invoiceNumber = Str::uuid()->toString();

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'date'           => now(),
                'status'         => TransactionStatus::Completed,
                'tax'            => $this->tax,
                'discount'       => $this->discountAmount,
                'discount_type'  => $this->discountType,
                'cashier_id'     => auth()->id(),
                'customer_id'    => $this->customerId,
            ]);

            foreach ($this->cart as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                if ($product && $product->stock_qtty >= $item['qtty']) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id'     => $item['product_id'],
                        'qtty'           => $item['qtty'],
                        'unit_price'     => $item['unit_price'],
                        'discount'       => $item['discount'] ?? 0,
                        'discount_type'  => DiscountType::from($item['discount_type']),
                    ]);

                    $product->decrement('stock_qtty', $item['qtty']);
                } else {
                    throw new Exception("Product '{$item['name']}' is out of stock.");
                }
            }

            $transaction->calculateTotal();

            Payment::create([
                'method'         => PaymentMethod::from($this->paymentMethod),
                'amount'         => $transaction->total,
                'reference'      => $this->paymentReference ?: null,
                'status'         => PaymentStatus::Completed,
                'transaction_id' => $transaction->id,
            ]);

            /** @noinspection PhpUnhandledExceptionInspection */
            DB::commit();

            Notification::make()
                ->title('Sale completed')
                ->body("Invoice: {$invoiceNumber}")
                ->success()
                ->send();

            $this->clearCart();

        } catch (Exception $e) {
            /** @noinspection PhpUnhandledExceptionInspection */
            DB::rollBack();
            Notification::make()->title('Sale failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function getPaymentMethods(): array
    {
        return collect(PaymentMethod::cases())
            ->mapWithKeys(fn($m) => [$m->value => $m->getLabel()])
            ->toArray();
    }

    // --- Helpers ---

    private function validateProduct(?Product $product): bool
    {
        if (!$product) {
            Notification::make()->title('Product not found')->danger()->send();
            return false;
        }
        if (!$product->is_active) {
            Notification::make()->title('Product is inactive')->danger()->send();
            return false;
        }
        if ($product->stock_qtty <= 0) {
            $this->notifyStockError(0);
            return false;
        }
        return true;
    }

    private function notifyStockError(int $available): void
    {
        Notification::make()
            ->title('Insufficient stock')
            ->body("Only {$available} units available")
            ->warning()
            ->send();
    }
}
