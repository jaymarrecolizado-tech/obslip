<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PassSlipStatus;
use App\Models\PassSlip;
use Filament\Widgets\ChartWidget;

class PassSlipStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Pass Slips by Status';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $counts = PassSlip::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $data = [];

        foreach (PassSlipStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = $counts[$status->value] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pass Slips',
                    'data' => $data,
                    'backgroundColor' => [
                        '#9ca3af', '#3b82f6', '#f59e0b', '#22c55e',
                        '#6366f1', '#10b981', '#06b6d4', '#16a34a',
                        '#0ea5e9', '#ef4444',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
