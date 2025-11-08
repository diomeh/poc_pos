<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use DB;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransactionStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Transactions by Status';

    protected function getStats(): array
    {
        $statusCounts = Transaction::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $getCount = fn($collection, $key) => intval($collection[$key] ?? 0);

        return [
            Stat::make(TransactionStatus::COMPLETED->getLabel(), $getCount($statusCounts, TransactionStatus::COMPLETED->value))
                ->description('Successfully completed transactions')
                ->color(TransactionStatus::COMPLETED->getColor())
                ->icon(Heroicon::CheckCircle),

            Stat::make(TransactionStatus::PENDING->getLabel(), $getCount($statusCounts, TransactionStatus::PENDING->value))
                ->description('Awaiting payment or confirmation')
                ->color(TransactionStatus::PENDING->getColor())
                ->icon(Heroicon::Clock),

            Stat::make(TransactionStatus::FAILED->getLabel(), $getCount($statusCounts, TransactionStatus::FAILED->value))
                ->description('Failed or canceled transactions')
                ->color(TransactionStatus::FAILED->getColor())
                ->icon(Heroicon::XCircle),
        ];
    }
}
