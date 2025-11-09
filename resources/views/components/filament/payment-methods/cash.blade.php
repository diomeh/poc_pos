@props([
    'total' => 0,
    'amountPaid' => 0,
    'change' => 0,
])

<div class="space-y-4">
    <!-- Amount Paid -->
    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
            Amount Paid
        </label>
        <x-filament::input.wrapper>
            <x-filament::input
                    type="number"
                    wire:model.live="amountPaid"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
            />
        </x-filament::input.wrapper>
    </div>

    <!-- Quick Amount Buttons -->
    @if($total > 0)
        <div class="grid grid-cols-3 gap-2">
            @foreach([5,10,20,50,100] as $amount)
                <button
                        type="button"
                        wire:click="$set('amountPaid', {{ $total + $amount }})"
                        class="px-3 py-2.5 text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg transition-all"
                >
                    +${{ $amount }}
                </button>
            @endforeach
            <button
                    type="button"
                    wire:click="$set('amountPaid', {{ $total }})"
                    class="px-3 py-2.5 text-sm font-medium bg-success-100 dark:bg-success-900/50 text-success-700 dark:text-success-300 hover:bg-success-200 dark:hover:bg-success-900/70 border border-success-300 dark:border-success-700 rounded-lg"
            >
                Exact
            </button>
        </div>
    @endif

    <!-- Change Display -->
    @if($amountPaid > 0)
        <div class="bg-success-50 dark:bg-success-900/20 border border-success-300 dark:border-success-800 rounded-lg p-4">
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-success-700 dark:text-success-400">Change Due:</span>
                <span class="text-2xl font-bold text-success-700 dark:text-success-400">
                    ${{ number_format(max(0, $change), 2) }}
                </span>
            </div>
        </div>
    @endif
</div>
