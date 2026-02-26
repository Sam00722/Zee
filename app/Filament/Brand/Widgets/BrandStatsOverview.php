<?php

namespace App\Filament\Brand\Widgets;

use App\Models\Deposit;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BrandStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $brand  = auth()->user()->brands()->first();
        $brandId = $brand?->id;

        $pendingDeposits      = Deposit::where('brand_id', $brandId)->where('status', Deposit::STATUS_PENDING)->count();
        $paidDepositsAmount   = Deposit::where('brand_id', $brandId)->where('status', Deposit::STATUS_PAID)->sum('amount');
        $failedDeposits       = Deposit::where('brand_id', $brandId)->where('status', Deposit::STATUS_FAILED)->count();

        $pendingWithdrawals        = Withdrawal::where('brand_id', $brandId)->where('status', Withdrawal::STATUS_PENDING)->count();
        $completedWithdrawalsAmount = Withdrawal::where('brand_id', $brandId)->where('status', Withdrawal::STATUS_COMPLETED)->sum('amount');
        $totalWithdrawals          = Withdrawal::where('brand_id', $brandId)->count();

        // Last 7 days of deposit amounts for sparkline
        $depositChart = collect(range(6, 0))->map(
            fn ($d) => (float) Deposit::where('brand_id', $brandId)
                ->where('status', Deposit::STATUS_PAID)
                ->whereDate('paid_at', now()->subDays($d)->toDateString())
                ->sum('amount')
        )->values()->toArray();

        // Last 7 days of completed withdrawal amounts for sparkline
        $withdrawalChart = collect(range(6, 0))->map(
            fn ($d) => (float) Withdrawal::where('brand_id', $brandId)
                ->where('status', Withdrawal::STATUS_COMPLETED)
                ->whereDate('updated_at', now()->subDays($d)->toDateString())
                ->sum('amount')
        )->values()->toArray();

        return [
            Stat::make('Pending Deposits', $pendingDeposits)
                ->description($failedDeposits > 0 ? "{$failedDeposits} failed" : 'No failed deposits')
                ->descriptionIcon($failedDeposits > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock')
                ->color($failedDeposits > 0 ? 'danger' : 'warning'),

            Stat::make('Total Paid Deposits', '$' . number_format((float) $paidDepositsAmount, 2))
                ->description('Confirmed this account')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->chart($depositChart)
                ->color('success'),

            Stat::make('Pending Withdrawals', $pendingWithdrawals)
                ->description("Out of {$totalWithdrawals} total")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Completed Withdrawals', '$' . number_format((float) $completedWithdrawalsAmount, 2))
                ->description('Successfully processed')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->chart($withdrawalChart)
                ->color('info'),
        ];
    }
}
