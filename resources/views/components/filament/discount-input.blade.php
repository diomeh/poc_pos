@props([
    'wireModel',
    'wireModelType' => 'blur',
    'discountType' => 'fixed',
    'maxValue' => null,
    'size' => 'base', // 'sm' or 'base'
    'label' => null,
    'showCalculation' => false,
    'calculatedAmount' => 0,
])

@php
    $inputClasses = $size === 'sm'
        ? 'py-1.5 pr-2 pl-1.5 text-sm'
        : 'py-2 pr-3 pl-2 text-base sm:text-sm';

    $prefixClasses = $size === 'sm'
        ? 'text-sm'
        : 'text-base sm:text-sm';
@endphp

<div>
    @if($label)
        <label class="block {{ $size === 'sm' ? 'text-xs' : 'text-sm' }} font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center rounded-{{ $size === 'sm' ? 'md' : 'lg' }} border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-{{ $size === 'sm' ? '700' : '800' }} pl-{{ $size === 'sm' ? '2.5' : '3' }} focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
        <div class="shrink-0 {{ $prefixClasses }} text-gray-500 dark:text-gray-400 select-none font-semibold">
            {{ $discountType === 'percentage' ? '%' : '$' }}
        </div>

        @if($discountType === 'percentage')
            <input
                type="number"
                wire:model.{{ $wireModelType }}="{{ $wireModel }}"
                class="block min-w-0 grow bg-transparent {{ $inputClasses }} text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none"
                min="0"
                max="100"
                step="0.1"
                placeholder="0.0"
            />
        @else
            <input
                type="number"
                wire:model.{{ $wireModelType }}="{{ $wireModel }}"
                class="block min-w-0 grow bg-transparent {{ $inputClasses }} text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none"
                min="0"
                @if($maxValue) max="{{ $maxValue }}" @endif
                step="0.01"
                placeholder="0.00"
            />
        @endif

        <div class="grid shrink-0 grid-cols-1 focus-within:relative">
            <select
                wire:model.live="{{ str_replace('.discount', '.discount_type', $wireModel) }}"
                class="col-start-1 row-start-1 w-full appearance-none rounded-r-{{ $size === 'sm' ? 'md' : 'lg' }} bg-transparent {{ $inputClasses }} pr-{{ $size === 'sm' ? '6' : '7' }} text-xs text-gray-700 dark:text-gray-300 focus:outline-none"
            >
                <option value="fixed">Fixed</option>
                <option value="percentage">Percent</option>
            </select>
        </div>
    </div>

    @if($showCalculation && $discountType === 'percentage' && $calculatedAmount > 0)
        <p class="mt-1.5 text-xs text-success-600 dark:text-success-400 font-medium">
            = ${{ number_format($calculatedAmount, 2) }} discount
        </p>
    @endif
</div>
