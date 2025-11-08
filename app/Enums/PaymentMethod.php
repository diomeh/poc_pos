<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PaymentMethod: int implements HasLabel
{
    case CreditCard   = 0;
    case PayPal       = 1;
    case BankTransfer = 2;
    case Cash         = 3;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CreditCard   => 'Credit Card',
            self::PayPal       => 'PayPal',
            self::BankTransfer => 'Bank Transfer',
            self::Cash         => 'Cash',
        };
    }
}
