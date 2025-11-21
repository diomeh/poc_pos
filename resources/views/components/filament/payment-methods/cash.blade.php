@props([
    'total' => 0,
    'amountPaid' => 0,
    'change' => 0,
])

@php
    // Calculate smart denomination suggestions based on the Total
    $suggestions = [];
    if($total > 0) {
        $suggestions[] = $total;                 // Exact amount
        $suggestions[] = ceil($total);           // Next dollar (e.g. 12.50 -> 13.00)
        $suggestions[] = ceil($total / 5) * 5;   // Next 5
        $suggestions[] = ceil($total / 10) * 10; // Next 10
        $suggestions[] = 20;
        $suggestions[] = 50;
        $suggestions[] = 100;

        // Filter: remove duplicates and amounts smaller than total (unless it's the exact amount)
        $suggestions = array_unique(array_filter($suggestions, fn($val) => $val >= $total));
        sort($suggestions);
        
        // Limit to top 6 most relevant buttons
        $suggestions = array_slice($suggestions, 0, 6);
    }
@endphp

<div class="space-y-4">
    <!-- Amount Input -->
    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
            Amount Tendered
        </label>
        <x-filament::input.wrapper prefix="$">
            <x-filament::input
                type="number"
                wire:model.blur="amountPaid"
                step="0.01"
                min="0"
                placeholder="0.00"
                inputmode="decimal"
                class="text-lg font-bold"
                autocomplete="off"
            />
        </x-filament::input.wrapper>
    </div>

    <!-- Smart Suggestions -->
    @if($total > 0 && !empty($suggestions))
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
            @foreach($suggestions as $amount)
                <button
                    type="button"
                    wire:click="$set('amountPaid', {{ $amount }})"
                    class="px-3 py-2.5 text-sm font-medium border rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 
                    {{ (float)$amountPaid === (float)$amount 
                        ? 'bg-primary-600 text-white border-primary-600 shadow-md' 
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600' 
                    }}"
                >
                    ${{ number_format($amount, 2) }}
                </button>
            @endforeach
        </div>
    @endif

    <!-- Change Display -->
    <div 
        class="rounded-lg p-4 border transition-colors duration-300
        {{ $change > 0 
            ? 'bg-success-50 dark:bg-success-900/20 border-success-300 dark:border-success-800' 
            : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' 
        }}"
    >
        <div class="flex justify-between items-center">
            <span class="text-sm font-semibold {{ $change > 0 ? 'text-success-700 dark:text-success-400' : 'text-gray-500' }}">
                Change Due:
            </span>
            <span class="text-2xl font-bold {{ $change > 0 ? 'text-success-700 dark:text-success-400' : 'text-gray-900 dark:text-white' }}">
                ${{ number_format(max(0, $change), 2) }}
            </span>
        </div>
    </div>
</div>
