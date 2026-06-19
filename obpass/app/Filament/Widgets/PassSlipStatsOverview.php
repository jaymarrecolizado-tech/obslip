<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PassSlipStatus;
use App\Models\PassSlip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PassSlipStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pass Slips', PassSlip::count())
                ->description('All time')
                ->icon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Pending Approval', PassSlip::where('status', PassSlipStatus::Submitted->value)->count())
                ->description('Awaiting supervisor action')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Approved Today', PassSlip::where('status', PassSlipStatus::Approved->value)->whereDate('date', today())->count())
                ->description('Ready for departure')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Currently Out', PassSlip::where('status', PassSlipStatus::Departed->value)->count())
                ->description('Departed, not yet arrived')
                ->icon('heroicon-o-truck')
                ->color('info'),

            Stat::make('Completed', PassSlip::where('status', PassSlipStatus::Completed->value)->count())
                ->description('Fully processed')
                ->icon('heroicon-o-flag')
                ->color('success'),

            Stat::make('Cancelled', PassSlip::where('status', PassSlipStatus::Cancelled->value)->count())
                ->description('Voided slips')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
