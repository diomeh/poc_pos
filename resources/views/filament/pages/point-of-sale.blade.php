@php
    use App\Enums\DiscountType;
    use App\Enums\PaymentMethod;
@endphp

<x-filament-panels::page>
    {{-- Main Container: Responsive 2-panel layout --}}
    <div
        x-data="{
            touchStart: null,
            handleTouchStart(e) { this.touchStart = e.touches[0].clientX },
            handleTouchEnd(e) {
                if (!this.touchStart) return;
                const diff = e.changedTouches[0].clientX - this.touchStart;
                if (Math.abs(diff) > 50) {
                    // Swipe detected - could toggle panels on mobile
                }
                this.touchStart = null;
            }
        }"
        @touchstart="handleTouchStart"
        @touchend="handleTouchEnd"
        class="flex flex-col lg:flex-row gap-4 lg:gap-6 h-[calc(100vh-12rem)] lg:h-[calc(100vh-10rem)]"
    >
        {{-- ============================================================== --}}
        {{-- LEFT PANEL: Products --}}
        {{-- ============================================================== --}}
        <div class="flex-1 flex flex-col min-h-0 lg:max-w-[60%] xl:max-w-[65%]">

            {{-- Search Bar --}}
            <div class="shrink-0 mb-4">
                <div
                    x-data="{ showResults: false }"
                    x-on:click.outside="showResults = false"
                    class="relative"
                >
                    <div class="relative">
                        <x-filament::input.wrapper>
                            <x-slot name="prefix">
                                <x-heroicon-m-magnifying-glass class="w-5 h-5 text-gray-400"/>
                            </x-slot>
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.300ms="search"
                                x-on:focus="showResults = true"
                                x-on:input="showResults = true"
                                placeholder="Search products by name or SKU..."
                                autocomplete="off"
                                class="text-base"
                            />
                            <x-slot name="suffix">
                                <div class="flex items-center gap-2">
                                    <div wire:loading wire:target="search">
                                        <x-filament::loading-indicator class="h-5 w-5"/>
                                    </div>
                                    @if($search)
                                        <button
                                            type="button"
                                            wire:click="clearSearch"
                                            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                                        >
                                            <x-heroicon-m-x-mark class="w-4 h-4 text-gray-400"/>
                                        </button>
                                    @endif
                                </div>
                            </x-slot>
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Search Results Dropdown --}}
                    @if($search && $this->products->isNotEmpty())
                        <div
                            x-show="showResults"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl max-h-80 overflow-y-auto"
                        >
                            @foreach($this->products as $product)
                                <button
                                    type="button"
                                    wire:click="addToCart('{{ $product->id }}')"
                                    x-on:click="showResults = false"
                                    class="w-full px-4 py-3 text-left hover:bg-primary-50 dark:hover:bg-primary-900/20 border-b border-gray-100 dark:border-gray-800 last:border-b-0 transition-colors active:bg-primary-100 dark:active:bg-primary-900/30"
                                >
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $product->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                                                <span>{{ $product->sku }}</span>
                                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                                <span class="{{ $product->stock_qtty <= 5 ? 'text-warning-600 dark:text-warning-400' : '' }}">
                                                    {{ $product->stock_qtty }} in stock
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                            ${{ number_format($product->price, 2) }}
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Category Tabs --}}
            <div class="shrink-0 mb-4 -mx-1">
                <div class="flex gap-2 overflow-x-auto pb-2 px-1 scrollbar-hide">
                    <button
                        type="button"
                        wire:click="setCategory(null)"
                        class="shrink-0 px-4 py-2.5 rounded-xl font-medium text-sm transition-all active:scale-95
                            {{ $activeCategory === null
                                ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                            }}"
                    >
                        All Products
                    </button>
                    @foreach($this->categories as $category)
                        <button
                            type="button"
                            wire:click="setCategory('{{ $category->id }}')"
                            class="shrink-0 px-4 py-2.5 rounded-xl font-medium text-sm transition-all active:scale-95
                                {{ $activeCategory === $category->id
                                    ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                }}"
                        >
                            {{ $category->name }}
                            <span class="ml-1.5 text-xs opacity-70">({{ $category->products_count }})</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="flex-1 overflow-y-auto min-h-0 -mx-1 px-1">
                @if(!$search && $this->products->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-center py-12">
                        <x-heroicon-o-cube class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4"/>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">No products available</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $activeCategory ? 'Try selecting a different category' : 'Add products to get started' }}
                        </p>
                    </div>
                @elseif(!$search)
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                        @foreach($this->products as $product)
                            <button
                                type="button"
                                wire:click="addToCart('{{ $product->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="addToCart('{{ $product->id }}')"
                                class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 text-left transition-all hover:shadow-lg hover:border-primary-300 dark:hover:border-primary-700 active:scale-[0.98] disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                {{-- Stock Badge --}}
                                @if($product->stock_qtty <= 5)
                                    <div class="absolute -top-2 -right-2 px-2 py-0.5 text-xs font-bold rounded-full
                                        {{ $product->stock_qtty <= 2 ? 'bg-danger-500 text-white' : 'bg-warning-500 text-white' }}">
                                        {{ $product->stock_qtty }} left
                                    </div>
                                @endif

                                {{-- Product Image Placeholder --}}
                                <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                                    <x-heroicon-o-cube class="w-8 h-8 text-gray-300 dark:text-gray-500 group-hover:scale-110 transition-transform"/>
                                </div>

                                {{-- Product Info --}}
                                <div class="space-y-1">
                                    <h3 class="font-semibold text-sm text-gray-900 dark:text-white line-clamp-2 leading-tight">
                                        {{ $product->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $product->sku }}
                                    </p>
                                    <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                        ${{ number_format($product->price, 2) }}
                                    </p>
                                </div>

                                {{-- Hover Add Icon --}}
                                <div class="absolute inset-0 bg-primary-600/10 dark:bg-primary-400/10 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <div class="bg-primary-600 text-white rounded-full p-3 shadow-lg transform scale-0 group-hover:scale-100 transition-transform">
                                        <x-heroicon-s-plus class="w-6 h-6"/>
                                    </div>
                                </div>

                                {{-- Loading Spinner --}}
                                <div wire:loading wire:target="addToCart('{{ $product->id }}')" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-xl flex items-center justify-center">
                                    <x-filament::loading-indicator class="w-8 h-8 text-primary-600"/>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- RIGHT PANEL: Cart & Payment --}}
        {{-- ============================================================== --}}
        <div class="w-full lg:w-[400px] xl:w-[450px] flex flex-col min-h-0 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">

            {{-- Cart Header --}}
            <div class="shrink-0 px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                        <x-heroicon-s-shopping-cart class="w-5 h-5 text-primary-600 dark:text-primary-400"/>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">Cart</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->totals['total_quantity'] ?? 0 }} items
                        </p>
                    </div>
                </div>
                @if(count($cartItems) > 0)
                    <x-filament::button
                        color="danger"
                        size="sm"
                        wire:click="clearCart"
                        wire:confirm="Clear all items from cart?"
                        outlined
                    >
                        Clear
                    </x-filament::button>
                @endif
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto min-h-0">
                @forelse($cartItems as $productId => $item)
                    <div
                        wire:key="cart-{{ $productId }}"
                        class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 last:border-b-0"
                    >
                        <div class="flex gap-3">
                            {{-- Item Info --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1">
                                    {{ $item['name'] }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    ${{ number_format($item['unit_price'], 2) }} each
                                </p>

                                {{-- Quantity Controls --}}
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <button
                                            type="button"
                                            wire:click="decrementItem('{{ $productId }}')"
                                            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-l-lg transition-colors active:bg-gray-300 dark:active:bg-gray-600 touch-manipulation"
                                            aria-label="Decrease quantity"
                                        >
                                            <x-heroicon-m-minus class="w-4 h-4 text-gray-600 dark:text-gray-300"/>
                                        </button>
                                        <span class="w-10 text-center font-semibold text-gray-900 dark:text-white text-sm tabular-nums">
                                            {{ $item['quantity'] }}
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="incrementItem('{{ $productId }}')"
                                            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-r-lg transition-colors active:bg-gray-300 dark:active:bg-gray-600 touch-manipulation"
                                            aria-label="Increase quantity"
                                        >
                                            <x-heroicon-m-plus class="w-4 h-4 text-gray-600 dark:text-gray-300"/>
                                        </button>
                                    </div>

                                    {{-- Item Discount Toggle --}}
                                    <button
                                        type="button"
                                        x-data="{ showDiscount: false }"
                                        x-on:click="showDiscount = !showDiscount"
                                        class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        title="Add discount"
                                    >
                                        <x-heroicon-m-tag class="w-4 h-4"/>
                                    </button>
                                </div>

                                {{-- Item Discount Input (if has discount) --}}
                                @if(($item['discount'] ?? 0) > 0)
                                    <div class="mt-2 text-xs text-success-600 dark:text-success-400 font-medium">
                                        -${{ number_format($item['discount_amount'] ?? 0, 2) }} discount
                                    </div>
                                @endif
                            </div>

                            {{-- Item Total & Remove --}}
                            <div class="flex flex-col items-end justify-between">
                                <button
                                    type="button"
                                    wire:click="removeItem('{{ $productId }}')"
                                    class="p-1.5 text-gray-400 hover:text-danger-600 dark:hover:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-900/20 rounded-lg transition-colors"
                                    aria-label="Remove item"
                                >
                                    <x-heroicon-m-x-mark class="w-4 h-4"/>
                                </button>
                                <span class="font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($item['subtotal'] ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full py-12 px-4 text-center">
                        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                            <x-heroicon-o-shopping-cart class="w-10 h-10 text-gray-400"/>
                        </div>
                        <p class="font-medium text-gray-900 dark:text-white">Cart is empty</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Tap products to add them
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Order Summary --}}
            @if(count($cartItems) > 0)
                <div class="shrink-0 border-t border-gray-200 dark:border-gray-800 px-4 py-3 space-y-2 bg-gray-50 dark:bg-gray-800/50">
                    {{-- Subtotal --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            ${{ number_format($this->totals['subtotal'] ?? 0, 2) }}
                        </span>
                    </div>

                    {{-- Tax --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            Tax ({{ number_format(($this->totals['tax_rate'] ?? 0.1) * 100) }}%)
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            ${{ number_format($this->totals['tax_amount'] ?? 0, 2) }}
                        </span>
                    </div>

                    {{-- Order Discount --}}
                    @if(($this->totals['order_discount_amount'] ?? 0) > 0)
                        <div class="flex justify-between text-sm text-success-600 dark:text-success-400">
                            <span>Discount</span>
                            <span class="font-medium">
                                -${{ number_format($this->totals['order_discount_amount'], 2) }}
                            </span>
                        </div>
                    @endif

                    {{-- Total --}}
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total</span>
                        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                            ${{ number_format($this->totals['total'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Payment Section --}}
            @if(count($cartItems) > 0)
                <div class="shrink-0 border-t border-gray-200 dark:border-gray-800 px-4 py-4 space-y-4">
                    {{-- Payment Method Pills --}}
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($this->getPaymentMethods() as $value => $label)
                            <button
                                type="button"
                                wire:click="setPaymentMethod({{ $value }})"
                                class="p-2.5 text-xs font-semibold rounded-xl transition-all active:scale-95
                                    {{ $paymentMethod === $value
                                        ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                    }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Cash Payment --}}
                    @if($paymentMethod === PaymentMethod::Cash->value)
                        <div class="space-y-3">
                            {{-- Amount Input --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Amount Tendered
                                </label>
                                <x-filament::input.wrapper prefix="$" class="text-lg">
                                    <x-filament::input
                                        type="number"
                                        wire:model.blur="amountPaid"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        class="text-lg font-bold"
                                    />
                                </x-filament::input.wrapper>
                            </div>

                            {{-- Quick Amount Buttons --}}
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($this->getCashSuggestions() as $amount)
                                    <button
                                        type="button"
                                        wire:click="setAmountPaid({{ $amount }})"
                                        class="py-2.5 text-sm font-semibold rounded-lg transition-all active:scale-95
                                            {{ abs($amountPaid - $amount) < 0.01
                                                ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                            }}"
                                    >
                                        ${{ number_format($amount, 2) }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Change Due --}}
                            <div class="p-3 rounded-xl {{ $this->change > 0 ? 'bg-success-100 dark:bg-success-900/30' : 'bg-gray-100 dark:bg-gray-800' }}">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium {{ $this->change > 0 ? 'text-success-700 dark:text-success-300' : 'text-gray-600 dark:text-gray-400' }}">
                                        Change Due
                                    </span>
                                    <span class="text-2xl font-bold {{ $this->change > 0 ? 'text-success-700 dark:text-success-300' : 'text-gray-900 dark:text-white' }}">
                                        ${{ number_format($this->change, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Non-Cash Payment --}}
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Reference Number
                                </label>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="text"
                                        wire:model.blur="paymentReference"
                                        placeholder="Transaction ID or reference..."
                                    />
                                </x-filament::input.wrapper>
                            </div>
                            <div class="p-3 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-primary-700 dark:text-primary-300">Amount to Charge</span>
                                    <span class="text-xl font-bold text-primary-700 dark:text-primary-300">
                                        ${{ number_format($this->totals['total'] ?? 0, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Complete Sale Button --}}
                    <x-filament::button
                        wire:click="completeSale"
                        wire:loading.attr="disabled"
                        :disabled="!$this->canCheckout"
                        color="primary"
                        size="xl"
                        class="w-full !py-4 text-lg font-bold"
                    >
                        <span wire:loading.remove wire:target="completeSale" class="flex items-center justify-center gap-2">
                            <x-heroicon-s-check-circle class="w-6 h-6"/>
                            Complete Sale
                        </span>
                        <span wire:loading wire:target="completeSale" class="flex items-center justify-center gap-2">
                            <x-filament::loading-indicator class="w-5 h-5"/>
                            Processing...
                        </span>
                    </x-filament::button>

                    {{-- Remaining Amount Warning --}}
                    @if(!$this->canCheckout && $this->remainingAmount > 0)
                        <p class="text-center text-sm font-medium text-danger-600 dark:text-danger-400">
                            Remaining: ${{ number_format($this->remainingAmount, 2) }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Custom Styles for touch optimization --}}
    <style>
        /* Hide scrollbar but keep functionality */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Better touch targets */
        .touch-manipulation {
            touch-action: manipulation;
        }

        /* Smooth scroll on iOS */
        .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
        }

        /* Prevent text selection on buttons for touch */
        button {
            -webkit-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* Number input - remove spinners */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</x-filament-panels::page>
