<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionStatus: int implements HasLabel, HasColor
{
    case PENDING   = 0;
    case COMPLETED = 1;
    case FAILED    = 2;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED    => 'Failed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING   => 'info',
            self::COMPLETED => 'success',
            self::FAILED    => 'danger',
        };
    }
}
