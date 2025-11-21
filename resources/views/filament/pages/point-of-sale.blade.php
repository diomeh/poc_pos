@php
    use App\Enums\PaymentMethod;
    // Optimize: Fetch search results once per render
    $searchResults = $search ? $this->searchProducts() : [];
@endphp

<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        
        <!-- Left Side - Product Selection & Cart -->
        <div class="lg:col-span-2 space-y-4 lg:space-y-6">
            
            <!-- Search Bar -->
            <x-filament::section>
                <x-slot name="heading">Search Products</x-slot>

                <div 
                    x-data="{ open: true }" 
                    x-on:click.outside="open = false" 
                    x-on:input.debounce="open = true"
                    class="relative"
                >
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            x-on:focus="open = true"
                            placeholder="Search by product name or SKU..."
                            autocomplete="off"
                        />
                        <!-- Loading Indicator for Search -->
                        <x-slot name="suffix">
                            <div wire:loading wire:target="search">
                                <x-filament::loading-indicator class="h-5 w-5 text-gray-400" />
                            </div>
                        </x-slot>
                    </x-filament::input.wrapper>

                    @if(!empty($searchResults))
                        <div 
                            x-show="open"
                            x-transition
                            class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-80 overflow-y-auto overflow-x-hidden"
                        >
                            @foreach($searchResults as $product)
                                <button
                                    type="button"
                                    wire:click="addToCart({{ $product['id'] }})"
                                    x-on:click="open = false"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-600 last:border-b-0 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate">
                                                {{ $product['name'] }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                                SKU: {{ $product['sku'] }} • Stock: {{ $product['stock'] }}
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
                                wire:confirm="Are you sure you want to clear the cart?"
                            >
                                Clear Cart
                            </x-filament::button>
                        @endif
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @forelse($cart as $key => $item)
                        <!-- CRITICAL: wire:key ensures Livewire tracks elements correctly during updates -->
                        <div 
                            wire:key="cart-item-{{ $key }}" 
                            class="p-3 sm:p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 {{ $errors->has('cart.'.$key.'.discount') ? 'border-danger-500 ring-1 ring-danger-500' : '' }}"
                        >
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
                                        <!-- 
                                            Using wire:model.blur ensures the calculation happens 
                                            only when the user leaves the field, preventing jumpy UI.
                                            Arguments: 
                                            1. We bind directly to the cart array. 
                                            2. Livewire component needs specific rules for this to work (see below).
                                        -->
                                        <input
                                            type="number"
                                            wire:model.blur="cart.{{ $key }}.discount"
                                            class="w-28 text-sm px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                            min="0"
                                            max="{{ $item['unit_price'] * $item['qtty'] }}"
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    @error('cart.'.$key.'.discount') 
                                        <span class="text-xs text-danger-600">{{ $message }}</span> 
                                    @enderror
                                </div>

                                <!-- Quantity Controls & Actions -->
                                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                                        <button
                                            type="button"
                                            wire:click="updateQuantity('{{ $key }}', {{ max(1, $item['qtty'] - 1) }})"
                                            wire:loading.attr="disabled"
                                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors"
                                        >
                                            -
                                        </button>
                                        <span class="w-12 text-center font-medium text-gray-900 dark:text-white">
                                            {{ $item['qtty'] }}
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="updateQuantity('{{ $key }}', {{ $item['qtty'] + 1 }})"
                                            wire:loading.attr="disabled"
                                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white min-w-[80px] sm:min-w-[100px] text-right">
                                        ${{ number_format($item['subtotal'], 2) }}
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeFromCart('{{ $key }}')"
                                        class="text-danger-600 hover:text-danger-700 p-2 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-900/20"
                                    >
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 sm:py-16 text-center">
                            <div class="flex justify-center mb-3">
                                <x-heroicon-o-shopping-cart class="w-12 h-12 text-gray-300" />
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">Cart is empty</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Search and add products to start.</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <!-- Right Side - Order Summary & Payment -->
        <div class="space-y-4 lg:space-y-6">
            
            <!-- Order Summary -->
            <x-filament::section>
                <x-slot name="heading">Order Summary</x-slot>
                
                <!-- Add a loading overlay for the summary when calculations are running -->
                <div class="space-y-3 relative">
                    <div wire:loading.flex wire:target="updateQuantity, removeFromCart, cart, discount" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 items-center justify-center">
                        <x-filament::loading-indicator class="h-6 w-6 text-primary-600" />
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Subtotal:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($subtotal, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Tax ({{ number_format($taxRate * 100, 1) }}%):
                        </span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($tax, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center gap-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            Order Discount:
                        </span>
                        <div class="flex items-center gap-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">$</span>
                            <!-- 
                                Use wire:model.blur or debounce.500ms. 
                                Live updates on every keystroke for currency calculation is poor UX/Performance 
                            -->
                            <input
                                type="number"
                                wire:model.blur="discount"
                                class="w-28 text-right text-sm font-semibold px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-primary-500"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                    </div>
                    @error('discount') <span class="text-xs text-danger-600 block text-right">{{ $message }}</span> @enderror

                    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total:</span>
                        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                            ${{ number_format($total, 2) }}
                        </span>
                    </div>
                </div>
            </x-filament::section>

            <!-- Payment Section -->
            <x-filament::section>
                <x-slot name="heading">Payment</x-slot>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Payment Method
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach($this->getPaymentMethods() as $value => $label)
                                <button
                                    type="button"
                                    wire:click="setPaymentMethod('{{ $value }}')"
                                    class="px-2 py-2.5 text-sm font-medium rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 
                                    {{ $paymentMethod === $value 
                                        ? 'bg-primary-600 text-white shadow-sm' 
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600' 
                                    }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dynamic Component Loading based on Enum -->
                    @if($paymentMethod === PaymentMethod::Cash->value)
                        <x-filament.payment-methods.cash :total="$total" :amountPaid="$amountPaid" :change="$change"/>
                    @elseif($paymentMethod === PaymentMethod::CreditCard->value)
                        <x-filament.payment-methods.card :amountPaid="$amountPaid" :paymentReference="$paymentReference"/>
                    @elseif($paymentMethod === PaymentMethod::BankTransfer->value)
                        <x-filament.payment-methods.bank-transfer :amountPaid="$amountPaid" :paymentReference="$paymentReference"/>
                    @elseif($paymentMethod === PaymentMethod::PayPal->value)
                        <x-filament.payment-methods.paypal :amountPaid="$amountPaid" :paymentReference="$paymentReference"/>
                    @endif

                    <x-filament::button
                        color="primary"
                        size="lg"
                        wire:click="completeSale"
                        wire:loading.attr="disabled"
                        :disabled="count($cart) === 0 || (float)$amountPaid < (float)$total"
                        class="w-full"
                    >
                        <span wire:loading.remove wire:target="completeSale" class="flex items-center justify-center gap-2">
                            <x-heroicon-o-check-circle class="w-5 h-5" />
                            Complete Sale
                        </span>
                        <span wire:loading wire:target="completeSale">
                            Processing...
                        </span>
                    </x-filament::button>
                    
                    @if(count($cart) > 0 && (float)$amountPaid < (float)$total)
                        <p class="text-xs text-center text-danger-600 dark:text-danger-400 font-medium">
                             Remaining: ${{ number_format($total - $amountPaid, 2) }}
                        </p>
                    @endif
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
