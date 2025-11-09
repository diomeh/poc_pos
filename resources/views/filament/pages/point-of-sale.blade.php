@php use App\Enums\PaymentMethod; @endphp
<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        <!-- Left Side - Product Selection & Cart -->
        <div class="lg:col-span-2 space-y-4 lg:space-y-6">
            <!-- Search Bar -->
            <x-filament::section>
                <x-slot name="heading">
                    Search Products
                </x-slot>

                <div class="relative">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by product name or SKU..."
                        />
                    </x-filament::input.wrapper>

                    @if($search && count($this->searchProducts()) > 0)
                        <div class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-80 overflow-y-auto overflow-x-hidden transition-opacity duration-200">
                            @foreach($this->searchProducts() as $product)
                                <button
                                    type="button"
                                    wire:click="addToCart({{ $product['id'] }})"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-600 last:border-b-0 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate">
                                                {{ $product['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                                SKU: {{ $product['sku'] }} • {{ $product['category'] }} • Stock: {{ $product['stock'] }}
                                            </div>
                                        </div>
                                        <div class="text-base sm:text-lg font-semibold text-primary-600 dark:text-primary-400 whitespace-nowrap ml-2">
                                            ${{ number_format($product['price'], 2) }}
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <!-- Cart Items -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full gap-3">
                        <span>Cart ({{ count($cart) }} items)</span>
                        @if(count($cart) > 0)
                            <x-filament::button
                                color="danger"
                                size="sm"
                                wire:click="clearCart"
                                outlined
                            >
                                Clear Cart
                            </x-filament::button>
                        @endif
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @forelse($cart as $key => $item)
                        <div class="p-3 sm:p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                                <!-- Product Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white mb-1 break-words">
                                        {{ $item['name'] }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        SKU: {{ $item['sku'] }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1">
                                        ${{ number_format($item['unit_price'], 2) }} × {{ $item['qtty'] }}
                                    </div>

                                    <!-- Item Discount -->
                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            Item Discount:
                                        </label>
                                        <input
                                            type="number"
                                            wire:change="updateDiscount('{{ $key }}', $event.target.value)"
                                            value="{{ $item['discount'] }}"
                                            class="w-28 text-sm px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                            min="0"
                                            max="{{ $item['unit_price'] * $item['qtty'] }}"
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>

                                <!-- Quantity Controls & Actions -->
                                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                                    <!-- Quantity Controls -->
                                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                                        <button
                                            type="button"
                                            wire:click="updateQuantity('{{ $key }}', {{ max(1, $item['qtty'] - 1) }})"
                                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
                                            aria-label="Decrease quantity"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <input
                                            type="number"
                                            wire:change="updateQuantity('{{ $key }}', Math.max(1, parseInt($event.target.value) || 1))"
                                            value="{{ $item['qtty'] }}"
                                            class="w-20 text-center border-0 border-x border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 font-medium"
                                            min="1"
                                            aria-label="Quantity"
                                        />
                                        <button
                                            type="button"
                                            wire:click="updateQuantity('{{ $key }}', {{ $item['qtty'] + 1 }})"
                                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
                                            aria-label="Increase quantity"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white min-w-[80px] sm:min-w-[100px] text-right">
                                        ${{ number_format($item['subtotal'], 2) }}
                                    </div>

                                    <!-- Remove Button -->
                                    <button
                                        type="button"
                                        wire:click="removeFromCart('{{ $key }}')"
                                        class="text-danger-600 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300 transition-colors p-2 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-900/20 focus:outline-none focus:ring-2 focus:ring-danger-500"
                                        title="Remove from cart"
                                        aria-label="Remove {{ $item['name'] }} from cart"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 sm:py-16 text-center">
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">Your cart is empty</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Search and add products to begin a transaction</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <!-- Right Side - Order Summary & Payment -->
        <div class="space-y-4 lg:space-y-6">
            <!-- Order Summary -->
            <x-filament::section>
                <x-slot name="heading">
                    Order Summary
                </x-slot>

                <div class="space-y-3">
                    <!-- Subtotal -->
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Subtotal:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($subtotal, 2) }}
                        </span>
                    </div>

                    <!-- Tax -->
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Tax ({{ number_format($taxRate * 100, 1) }}%):
                        </span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($tax, 2) }}
                        </span>
                    </div>

                    <!-- Discount -->
                    <div class="flex justify-between items-center gap-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            Order Discount:
                        </span>
                        <div class="flex items-center gap-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">$</span>
                            <input
                                type="number"
                                wire:model.live="discount"
                                class="w-28 text-right text-sm font-semibold px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                min="0"
                                max="{{ $subtotal + $tax }}"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                    <!-- Total -->
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total:</span>
                        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                            ${{ number_format($total, 2) }}
                        </span>
                    </div>

                    <!-- Calculation Breakdown -->
                    @if(count($cart) > 0)
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1.5">
                                <div class="flex justify-between">
                                    <span>Items total:</span>
                                    <span class="font-medium">${{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>+ Tax:</span>
                                    <span class="font-medium">${{ number_format($tax, 2) }}</span>
                                </div>
                                @if($discount > 0)
                                    <div class="flex justify-between text-danger-600 dark:text-danger-400">
                                        <span>- Discount:</span>
                                        <span class="font-medium">-${{ number_format($discount, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <!-- Payment Section -->
            <x-filament::section>
                <x-slot name="heading">
                    Payment
                </x-slot>

                <div class="space-y-4">
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Payment Method
                        </label>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach($this->getPaymentMethods() as $value => $label)
                                <button
                                    type="button"
                                    wire:click="setPaymentMethod('{{ $value }}')"
                                    class="px-4 py-2.5 text-sm font-medium rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 {{ $paymentMethod === $value ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Render Selected Payment Component -->
                    @if($paymentMethod === PaymentMethod::Cash->value)
                        <x-filament.payment-methods.cash :total="$total" :amountPaid="$amountPaid" :change="$change"/>
                    @elseif($paymentMethod === PaymentMethod::CreditCard->value)
                        <x-filament.payment-methods.card :amountPaid="$amountPaid" :paymentReference="$paymentReference"/>
                    @elseif($paymentMethod === PaymentMethod::BankTransfer->value)
                        <x-filament.payment-methods.bank-transfer :amountPaid="$amountPaid" :paymentReference="$paymentReference"/>
                    @elseif($paymentMethod === PaymentMethod::PayPal->value)
                        <x-filament.payment-methods.paypal :amountPaid="$amountPaid" :paymentReference="$paymentReference"/>
                    @endif

                    <!-- Complete Sale Button -->
                    <x-filament::button
                        color="primary"
                        size="lg"
                        wire:click="completeSale"
                        :disabled="count($cart) === 0 || $amountPaid < $total"
                        class="w-full focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Complete Sale
                        </span>
                    </x-filament::button>

                    @if(count($cart) > 0 && $amountPaid < $total)
                        <p class="text-xs text-center text-gray-600 dark:text-gray-400">
                            {{ $amountPaid > 0 ? 'Insufficient payment amount' : 'Enter payment amount to complete sale' }}
                        </p>
                    @endif
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
