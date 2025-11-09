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
            Stat::make(TransactionStatus::Completed->getLabel(), $getCount($statusCounts, TransactionStatus::Completed->value))
                ->description('Successfully completed transactions')
                ->color(TransactionStatus::Completed->getColor())
                ->icon(Heroicon::CheckCircle),

            Stat::make(TransactionStatus::Pending->getLabel(), $getCount($statusCounts, TransactionStatus::Pending->value))
                ->description('Awaiting payment or confirmation')
                ->color(TransactionStatus::Pending->getColor())
                ->icon(Heroicon::Clock),

            Stat::make(TransactionStatus::Failed->getLabel(), $getCount($statusCounts, TransactionStatus::Failed->value))
                ->description('Failed or canceled transactions')
                ->color(TransactionStatus::Failed->getColor())
                ->icon(Heroicon::XCircle),
        ];
    }
}
