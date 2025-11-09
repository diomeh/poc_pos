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

    public string $search           = '';
    public array  $cart             = [];
    public float  $subtotal         = 0;
    public float  $tax              = 0;
    public float  $taxRate          = 0.10; // 10% tax rate
    public float  $discount         = 0;
    public float  $total            = 0;
    public float  $amountPaid       = 0;
    public float  $change           = 0;
    public int    $paymentMethod    = PaymentMethod::Cash->value;
    public ?int   $customerId       = null;
    public string $paymentReference = '';

    protected $listeners = ['productAdded' => 'addToCart'];

    public function mount(): void
    {
        $this->customerId = Customer::find(1)->id;
        $this->calculateTotals();
    }

    public function searchProducts(): array|Collection
    {
        if (empty($this->search)) {
            return [];
        }

        return Product::query()
            ->where(function ($query) {
                $query->whereLike('name', "%{$this->search}%")
                    ->orWhereLike('sku', "%{$this->search}%");
            })
            ->where('is_active', true)
            ->limit(25)
            ->get()
            ->map(fn($product) => [
                'id'       => $product->id,
                'name'     => $product->name,
                'sku'      => $product->sku,
                'price'    => $product->price,
                'cost'     => $product->cost,
                'stock'    => $product->stock_qtty,
                'category' => $product->category->name ?? 'N/A',
            ]);
    }

    public function addToCart($productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            Notification::make()
                ->title('Product not found')
                ->danger()
                ->send();
            return;
        }

        if (!$product->is_active) {
            Notification::make()
                ->title('Product is inactive')
                ->danger()
                ->send();
            return;
        }

        if ($product->stock_qtty <= 0) {
            Notification::make()
                ->title('Product out of stock')
                ->danger()
                ->send();
            return;
        }

        $cartKey = $product->id;

        if (isset($this->cart[$cartKey])) {
            // Check if we have enough stock
            if ($this->cart[$cartKey]['qtty'] + 1 > $product->stock_qtty) {
                Notification::make()
                    ->title('Insufficient stock')
                    ->body('Only ' . $product->stock_qtty . ' units available')
                    ->warning()
                    ->send();
                return;
            }
            $this->cart[$cartKey]['qtty']++;
        } else {
            $this->cart[$cartKey] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'sku'        => $product->sku,
                'unit_price' => $product->price,
                'cost'       => $product->cost,
                'qtty'       => 1,
                'discount'   => 0,
                'subtotal'   => $product->price,
            ];
        }

        $this->updateCartItemSubtotal($cartKey);
        $this->calculateTotals();
        $this->search = '';

        Notification::make()
            ->title('Product added to cart')
            ->success()
            ->send();
    }

    public function updateQuantity($cartKey, $quantity): void
    {
        if ($quantity <= 0) {
            unset($this->cart[$cartKey]);
        } else {
            // Check stock availability
            $product = Product::find($this->cart[$cartKey]['product_id']);
            if ($product && $quantity > $product->stock_qtty) {
                Notification::make()
                    ->title('Insufficient stock')
                    ->body('Only ' . $product->stock_qtty . ' units available')
                    ->warning()
                    ->send();
                return;
            }

            $this->cart[$cartKey]['qtty'] = $quantity;
            $this->updateCartItemSubtotal($cartKey);
        }

        $this->calculateTotals();
    }

    public function updateDiscount($cartKey, $discount): void
    {
        $this->cart[$cartKey]['discount'] = max(0, $discount);
        $this->updateCartItemSubtotal($cartKey);
        $this->calculateTotals();
    }

    public function updateCartItemSubtotal($cartKey): void
    {
        $item                             = $this->cart[$cartKey];
        $this->cart[$cartKey]['subtotal'] = ($item['unit_price'] * $item['qtty']) - $item['discount'];
    }

    public function removeFromCart($cartKey): void
    {
        unset($this->cart[$cartKey]);
        $this->calculateTotals();

        Notification::make()
            ->title('Product removed from cart')
            ->success()
            ->send();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->calculateTotals();
        $this->amountPaid       = 0;
        $this->change           = 0;
        $this->discount         = 0;
        $this->paymentReference = '';

        Notification::make()
            ->title('Cart cleared')
            ->success()
            ->send();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = collect($this->cart)->sum('subtotal');
        $this->tax      = round($this->subtotal * $this->taxRate, 2);
        $this->total    = round($this->subtotal + $this->tax - $this->discount, 2);
        $this->total    = max(0, $this->total); // Ensure total is not negative

        // Recalculate change if amount paid exists
        if ($this->amountPaid > 0) {
            $this->calculateChange();
        }
    }

    public function calculateChange(): void
    {
        $this->change = max(0, round($this->amountPaid - $this->total, 2));
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function completeSale(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Cart is empty')
                ->danger()
                ->send();
            return;
        }

        if ($this->amountPaid < $this->total) {
            Notification::make()
                ->title('Insufficient payment')
                ->body('Amount paid must be at least $' . number_format($this->total, 2))
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();

        try {
            // Create payment record
            $payment = Payment::create([
                'method'    => PaymentMethod::from($this->paymentMethod),
                'amount'    => $this->amountPaid,
                'reference' => $this->paymentReference ?: null,
                'status'    => PaymentStatus::Completed,
            ]);

            // Generate invoice number
            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad(Transaction::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create transaction
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'date'           => now(),
                'total'          => $this->total,
                'status'         => TransactionStatus::Completed,
                'cashier_id'     => auth()->id(),
                'customer_id'    => $this->customerId,
                'payment_id'     => $payment->id,
            ]);

            // Create transaction items and update stock
            foreach ($this->cart as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $item['product_id'],
                    'qtty'           => $item['qtty'],
                    'unit_price'     => $item['unit_price'],
                    'discount'       => $item['discount'],
                    'subtotal'       => $item['subtotal'],
                ]);

                // Update product stock
                $product = Product::find($item['product_id']);
                $product?->decrement('stock_qtty', $item['qtty']);
            }

            DB::commit();

            Notification::make()
                ->title('Sale completed successfully')
                ->body('Invoice: ' . $invoiceNumber)
                ->success()
                ->duration(5000)
                ->send();

            // Reset everything
            $this->clearCart();

        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Sale failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function setPaymentMethod($method): void
    {
        $this->paymentMethod = $method;

        // Clear reference if switching to cash
        if ($method === PaymentMethod::Cash->value) {
            $this->paymentReference = '';
        }
    }

    public function getPaymentMethods(): array
    {
        return collect(PaymentMethod::cases())->reduce(function ($carry, $method) {
            $carry[$method->value] = $method->getLabel();
            return $carry;
        }, []);
    }
}
