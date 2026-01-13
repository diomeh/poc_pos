<?php

namespace App\Filament\Pages;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\CartService;
use BackedEnum;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

/**
 * Point of Sale Page
 *
 * Touch-optimized, responsive POS interface.
 * Delegates business logic to CartService for testability.
 *
 * @property CartService|null $cartService
 */
class PointOfSale extends Page
{
    protected static string|null|BackedEnum $navigationIcon  = Heroicon::ShoppingCart;
    protected static ?string                $navigationLabel = 'Point of Sale';
    protected static ?string                $title           = 'Point of Sale';
    protected static ?int                   $navigationSort  = 1;
    #[Url(as: 'q')]
    public string                           $search          = '';

    /*
    |--------------------------------------------------------------------------
    | Public Properties (Livewire State)
    |--------------------------------------------------------------------------
    */

    // Search & Navigation
    #[Url(as: 'cat')]
    public ?int  $activeCategory = null;
    public array $cartItems      = [];

    // Cart State (serialized from CartService)
    public float $orderDiscount     = 0.0;
    public int   $orderDiscountType = 1;
    public float $amountPaid        = 0.0; // DiscountType::Fixed

    // Payment State
    public int    $paymentMethod    = 3;
    public string $paymentReference = ''; // PaymentMethod::Cash
    public ?int   $customerId       = null;
    public bool   $showProductGrid  = true;

    // UI State
    public string    $viewMode = 'grid';
    protected string $view     = 'filament.pages.point-of-sale'; // 'grid' or 'list'

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->customerId    = Customer::first()?->id;
        $this->paymentMethod = PaymentMethod::Cash->value;
    }

    public function hydrate(): void
    {
        // Ensure cart service is synced on each request
        $this->syncCartService();
    }

    /*
    |--------------------------------------------------------------------------
    | Computed Properties (Cached per request)
    |--------------------------------------------------------------------------
    */

    private function syncCartService(): void
    {
        // Recreate service with current state - handled by computed property
        unset($this->cartService);
    }

    #[Computed]
    public function cartService(): CartService
    {
        $service = new CartService();
        $service->fromArray([
            'items'               => $this->cartItems,
            'order_discount'      => $this->orderDiscount,
            'order_discount_type' => $this->orderDiscountType,
        ]);
        return $service;
    }

    #[Computed]
    public function totals(): array
    {
        return $this->cartService->getTotals()->toArray();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->whereHas('products', fn($q) => $q->where('is_active', true)
                ->where('stock_qtty', '>', 0)
            )
            ->withCount(['products' => fn($q) => $q->where('is_active', true)
                ->where('stock_qtty', '>', 0)
            ])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products(): Collection
    {
        if (!empty($this->search)) {
            return $this->cartService->searchProducts($this->search);
        }

        return $this->cartService->getProductsByCategory($this->activeCategory);
    }

    #[Computed]
    public function change(): float
    {
        if ($this->paymentMethod !== PaymentMethod::Cash->value) {
            return 0.0;
        }
        return $this->cartService->calculateChange($this->amountPaid);
    }

    #[Computed]
    public function canCheckout(): bool
    {
        if (empty($this->cartItems)) {
            return false;
        }

        $total = $this->totals['total'] ?? 0;
        return round($this->amountPaid, 2) >= round($total, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Cart Actions
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function remainingAmount(): float
    {
        $total = $this->totals['total'] ?? 0;
        return max(0, round($total - $this->amountPaid, 2));
    }

    #[On('add-to-cart')]
    public function addToCart(int $productId): void
    {
        try {
            $product = Product::findOrFail($productId);
            $this->cartService->addProduct($product);
            $this->syncFromCartService();

            Notification::make()
                ->title('Added to cart')
                ->body($product->name)
                ->success()
                ->duration(1500)
                ->send();

            $this->search = '';

        } catch (Exception $e) {
            Notification::make()
                ->title('Cannot add to cart')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function syncFromCartService(): void
    {
        $state                   = $this->cartService->toArray();
        $this->cartItems         = $state['items'];
        $this->orderDiscount     = $state['order_discount'];
        $this->orderDiscountType = $state['order_discount_type'];
    }

    public function incrementItem(int $productId): void
    {
        try {
            $this->cartService->incrementQuantity($productId);
            $this->syncFromCartService();
        } catch (Exception $e) {
            Notification::make()
                ->title('Cannot increase quantity')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    public function decrementItem(int $productId): void
    {
        try {
            $this->cartService->decrementQuantity($productId);
            $this->syncFromCartService();
        } catch (Exception) {
            // Item removed or at minimum - no notification needed
        }
    }

    public function updateItemQuantity(int $productId, int $quantity): void
    {
        try {
            $this->cartService->updateQuantity($productId, $quantity);
            $this->syncFromCartService();
        } catch (Exception $e) {
            Notification::make()
                ->title('Cannot update quantity')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    public function removeItem(int $productId): void
    {
        $this->cartService->removeItem($productId);
        $this->syncFromCartService();

        Notification::make()
            ->title('Item removed')
            ->success()
            ->duration(1000)
            ->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Order Discount Actions
    |--------------------------------------------------------------------------
    */

    public function updateItemDiscount(int $productId, float $discount, int $type): void
    {
        $discountType = DiscountType::tryFrom($type) ?? DiscountType::Fixed;
        $this->cartService->updateItemDiscount($productId, $discount, $discountType);
        $this->syncFromCartService();
    }

    public function updatedOrderDiscount(): void
    {
        $this->syncCartService();
    }

    public function updatedOrderDiscountType(): void
    {
        $this->syncCartService();
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Actions
    |--------------------------------------------------------------------------
    */

    public function setOrderDiscount(float $discount, int $type): void
    {
        $this->orderDiscount     = $discount;
        $this->orderDiscountType = $type;
    }

    public function setPaymentMethod(int $method): void
    {
        $this->paymentMethod    = $method;
        $this->paymentReference = '';

        // Auto-fill exact amount for non-cash payments
        if ($method !== PaymentMethod::Cash->value) {
            $this->amountPaid = $this->totals['total'] ?? 0;
        }
    }

    public function setAmountPaid(float $amount): void
    {
        $this->amountPaid = $amount;
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation Actions
    |--------------------------------------------------------------------------
    */

    public function completeSale(): void
    {
        try {
            $transaction = $this->cartService->checkout(
                cashierId: auth()->id(),
                customerId: $this->customerId,
                amountPaid: $this->amountPaid,
                paymentMethod: PaymentMethod::from($this->paymentMethod),
                paymentReference: $this->paymentReference ?: null,
            );

            Notification::make()
                ->title('Sale completed!')
                ->body("Invoice: {$transaction->invoice_number}")
                ->success()
                ->duration(5000)
                ->send();

            $this->clearCart();

        } catch (Exception $e) {
            Notification::make()
                ->title('Sale failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearCart(): void
    {
        $this->cartItems         = [];
        $this->orderDiscount     = 0.0;
        $this->orderDiscountType = DiscountType::Fixed->value;
        $this->amountPaid        = 0.0;
        $this->paymentReference  = '';

        Notification::make()
            ->title('Cart cleared')
            ->success()
            ->duration(1500)
            ->send();
    }

    public function setCategory(?int $categoryId): void
    {
        $this->activeCategory = $categoryId;
        $this->search         = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    public function getPaymentMethods(): array
    {
        return collect(PaymentMethod::cases())
            ->mapWithKeys(fn($m) => [$m->value => $m->getLabel()])
            ->toArray();
    }

    public function getCashSuggestions(): array
    {
        $total = $this->totals['total'] ?? 0;

        if ($total <= 0) {
            return [];
        }

        $suggestions = array_unique([
            $total,                     // Exact
            ceil($total),               // Next dollar
            ceil($total / 5) * 5,       // Next 5
            ceil($total / 10) * 10,     // Next 10
            ceil($total / 20) * 20,     // Next 20
            50,
            100,
        ]);

        $suggestions = array_filter($suggestions, fn($v) => $v >= $total);
        sort($suggestions);

        return array_slice($suggestions, 0, 6);
    }
}
