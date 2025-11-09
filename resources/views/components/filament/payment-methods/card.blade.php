@props([
    'amountPaid' => 0,
    'paymentReference' => '',
])

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Reference Number</label>
        <x-filament::input.wrapper>
            <x-filament::input
                type="text"
                wire:model="paymentReference"
                placeholder="Card transaction ID..."
            />
        </x-filament::input.wrapper>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Amount Paid</label>
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
</div>
