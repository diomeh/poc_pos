<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PaymentStats;
use App\Filament\Widgets\TransactionStats;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::Home;
    }

    public function getWidgets(): array
    {
        return [
            TransactionStats::class,
            PaymentStats::class,
        ];
    }
}
