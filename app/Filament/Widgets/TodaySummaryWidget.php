<?php

namespace App\Filament\Widgets;

use App\Models\Deposit;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaySummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $depositsToday = Deposit::where('created_at', '>=', $today)->where('status', 'paid')->sum('amount');
        $withdrawalsToday = Withdrawal::where('created_at', '>=', $today)->where('status', 'completed')->sum('amount');
        $pendingDeposits = Deposit::where('status', 'pending')->count();
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

        return [
            Stat::make('Deposits today (paid)', '$' . number_format($depositsToday, 2)),
            Stat::make('Withdrawals today (completed)', '$' . number_format($withdrawalsToday, 2)),
            Stat::make('Pending deposits', (string) $pendingDeposits),
            Stat::make('Pending withdrawals', (string) $pendingWithdrawals),
        ];
    }
}
