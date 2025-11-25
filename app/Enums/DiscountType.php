<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountType: int implements HasLabel
{
    case Percentage = 0;
    case Fixed      = 1;
    case None       = 2;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed      => 'Fixed Amount',
            self::None       => 'No Discount',
        };
    }
}
