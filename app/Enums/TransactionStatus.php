<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionStatus: int implements HasLabel, HasColor
{
    case Pending   = 0;
    case Completed = 1;
    case Failed    = 2;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Completed => 'Completed',
            self::Failed    => 'Failed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending   => 'info',
            self::Completed => 'success',
            self::Failed    => 'danger',
        };
    }
}
