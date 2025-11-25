@props([
    'amountPaid' => 0,
    'paymentReference' => '',
])

<div class="space-y-4">
    <!-- Reference -->
    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
            Transfer Reference #
        </label>
        <x-filament::input.wrapper>
            <x-slot name="prefix">
                <x-heroicon-o-building-library class="w-5 h-5 text-gray-400" />
            </x-slot>
            <x-filament::input
                type="text"
                wire:model.blur="paymentReference"
                placeholder="Bank transaction ID..."
                autocomplete="off"
            />
        </x-filament::input.wrapper>
    </div>

    <!-- Amount -->
    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
            Amount Transferred
        </label>
        <x-filament::input.wrapper prefix="$">
            <x-filament::input
                type="number"
                wire:model.blur="amountPaid"
                step="0.01"
                min="0"
                placeholder="0.00"
                inputmode="decimal"
            />
        </x-filament::input.wrapper>
    </div>
</div>
