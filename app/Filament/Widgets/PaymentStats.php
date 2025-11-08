<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use DB;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Transactions by Payment Method';

    protected function getStats(): array
    {
        $paymentCounts = Payment::select('method', DB::raw('COUNT(*) as total'))
            ->groupBy('method')
            ->pluck('total', 'method');

        $getCount = fn($collection, $key) => $collection[$key] ?? 0;

        return [
            Stat::make(PaymentMethod::Cash->getLabel(), $getCount($paymentCounts, PaymentMethod::Cash->value))
                ->description('Transactions paid in cash')
                ->color('success')
                ->icon(Heroicon::Banknotes),

            Stat::make(PaymentMethod::CreditCard->getLabel(), $getCount($paymentCounts, PaymentMethod::CreditCard->value))
                ->description('Transactions paid by card')
                ->color('info')
                ->icon(Heroicon::CreditCard),

            Stat::make(PaymentMethod::PayPal->getLabel(), $getCount($paymentCounts, PaymentMethod::PayPal->value))
                ->description('PayPal transactions')
                ->color('primary')
                ->icon(Heroicon::GlobeAmericas),

            Stat::make(PaymentMethod::BankTransfer->getLabel(), $getCount($paymentCounts, PaymentMethod::BankTransfer->value))
                ->description('Bank transfer transactions')
                ->color('secondary')
                ->icon(Heroicon::BuildingLibrary),
        ];
    }
}
