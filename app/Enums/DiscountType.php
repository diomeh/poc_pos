<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountType: int implements HasLabel
{
    case None       = 0;
    case Fixed      = 1;
    case Percentage = 2;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed      => 'Fixed Amount',
            self::None       => 'No Discount',
        };
    }
}
