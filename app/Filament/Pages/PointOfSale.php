<?php

namespace App\Filament\Pages;

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

class PointOfSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::ShoppingCart;
    protected string $view = 'filament.pages.point-of-sale';
    protected static ?string $navigationLabel = 'Point of Sale';
    protected static ?string $title = 'Point of Sale';

    // Properties
    public string $search = '';
    public array $cart = [];
    
    // Math properties initialized to float to prevent type issues
    public float $subtotal = 0.00;
    public float $tax = 0.00;
    public float $taxRate = 0.10; // 10%
    public float $discount = 0.00;
    public float $total = 0.00;
    public float $amountPaid = 0.00;
    public float $change = 0.00;
    
    public string $paymentMethod = PaymentMethod::Cash->value;
    public ?int $customerId = null;
    public string $paymentReference = '';

    // Listeners
    protected $listeners = ['productAdded' => 'addToCart'];

    public function mount(): void
    {
        // Fallback if Customer ID 1 doesn't exist
        $this->customerId = Customer::first()?->id;
        $this->calculateTotals();
    }

    /* 
     * -----------------------------------------------------------------
     *  LIFECYCLE HOOKS (The Fix)
     *  These run automatically when wire:model updates these properties.
     * -----------------------------------------------------------------
     */

    public function updatedCart(): void
    {
        // Triggered when quantity or item discount changes via wire:model
        $this->calculateTotals();
    }

    public function updatedDiscount(): void
    {
        // Triggered when global discount changes
        $this->calculateTotals();
    }

    public function updatedAmountPaid(): void
    {
        // Triggered when payment amount changes
        $this->calculateChange();
    }

    /* ----------------------------------------------------------------- */

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
            ->limit(20) // Limit results for performance
            ->get()
            ->map(fn($product) => [
                'id'       => $product->id,
                'name'     => $product->name,
                'sku'      => $product->sku,
                'price'    => (float) $product->price,
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
            
            // Check cached stock first
            if ($newQty > $product->stock_qtty) {
                $this->notifyStockError($product->stock_qtty);
                return;
            }
            
            $this->cart[$cartKey]['qtty'] = $newQty;
        } else {
            $this->cart[$cartKey] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'sku'        => $product->sku,
                'unit_price' => (float) $product->price,
                'qtty'       => 1,
                'discount'   => 0.00,
                'subtotal'   => (float) $product->price,
                // Cache max stock here to avoid DB queries on quantity update
                'max_stock'  => $product->stock_qtty, 
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

        // Use cached max_stock to reduce DB calls
        $maxStock = $this->cart[$cartKey]['max_stock'] ?? 0;

        if ($quantity > $maxStock) {
            $this->notifyStockError($maxStock);
            $this->cart[$cartKey]['qtty'] = $maxStock; // Set to max available
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
        $this->cart = [];
        $this->amountPaid = 0.00;
        $this->change = 0.00;
        $this->discount = 0.00;
        $this->paymentReference = '';
        $this->calculateTotals();
    }

    /**
     * Master Calculation Logic
     * Handles Item Discounts, Subtotals, Tax, and Global Discounts
     */
    public function calculateTotals(): void
    {
        $this->subtotal = 0.00;

        foreach ($this->cart as $key => $item) {
            $lineTotal = (float) $item['unit_price'] * (int) $item['qtty'];
            $itemDiscount = (float) ($item['discount'] ?? 0);

            // Safety: Item discount cannot exceed the line total
            if ($itemDiscount > $lineTotal) {
                $itemDiscount = $lineTotal;
                $this->cart[$key]['discount'] = $itemDiscount;
            }

            $itemSubtotal = $lineTotal - $itemDiscount;
            $this->cart[$key]['subtotal'] = $itemSubtotal;
            
            $this->subtotal += $itemSubtotal;
        }

        // Calculate Tax
        $this->tax = round($this->subtotal * $this->taxRate, 2);

        // Calculate Gross
        $grossTotal = $this->subtotal + $this->tax;

        // Safety: Global discount cannot exceed Gross Total
        if ($this->discount > $grossTotal) {
            $this->discount = $grossTotal;
        }

        $this->total = round($grossTotal - $this->discount, 2);

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

    public function setPaymentMethod(string $method): void
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
        // 1. Validations
        if (empty($this->cart)) {
            Notification::make()->title('Cart is empty')->danger()->send();
            return;
        }

        // Floating point comparison fix
        if (round((float)$this->amountPaid, 2) < round($this->total, 2)) {
            Notification::make()
                ->title('Insufficient payment')
                ->body('Amount paid must be at least $' . number_format($this->total, 2))
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();

        try {
            // 2. Create Payment
            $payment = Payment::create([
                'method'    => PaymentMethod::from($this->paymentMethod),
                'amount'    => $this->total, // Usually we record the Total Due, not the Amount Tendered
                'reference' => $this->paymentReference ?: null,
                'status'    => PaymentStatus::Completed,
            ]);

            // 3. Generate Invoice Number
            $count = Transaction::whereDate('created_at', today())->count() + 1;
            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            // 4. Create Transaction
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'date'           => now(),
                'total'          => $this->total,
                'subtotal'       => $this->subtotal,
                'tax'            => $this->tax,
                'discount'       => $this->discount,
                'status'         => TransactionStatus::Completed,
                'cashier_id'     => auth()->id(),
                'customer_id'    => $this->customerId,
                'payment_id'     => $payment->id,
            ]);

            // 5. Create Items & Update Stock (With Locking)
            foreach ($this->cart as $item) {
                // Lock the product row to prevent race conditions
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                if ($product && $product->stock_qtty >= $item['qtty']) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id'     => $item['product_id'],
                        'qtty'           => $item['qtty'],
                        'unit_price'     => $item['unit_price'],
                        'discount'       => $item['discount'] ?? 0,
                        'subtotal'       => $item['subtotal'],
                    ]);

                    $product->decrement('stock_qtty', $item['qtty']);
                } else {
                    throw new Exception("Product '{$item['name']}' is out of stock.");
                }
            }

            DB::commit();

            Notification::make()
                ->title('Sale completed')
                ->body("Invoice: {$invoiceNumber}")
                ->success()
                ->send();

            $this->clearCart();

        } catch (Exception $e) {
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
