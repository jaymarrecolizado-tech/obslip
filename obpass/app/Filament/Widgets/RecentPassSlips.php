<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PassSlipStatus;
use App\Filament\Resources\PassSlipResource;
use App\Models\PassSlip;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPassSlips extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Pass Slips';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => PassSlip::query()->with(['employees', 'department'])->latest('created_at'))
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->columns([
                Tables\Columns\TextColumn::make('slip_number')
                    ->label('Slip #')
                    ->searchable()
                    ->url(fn (PassSlip $record): string => PassSlipResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('employees.full_name')
                    ->label('Employee(s)')
                    ->limit(40),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PassSlipStatus $state): string => match ($state) {
                        PassSlipStatus::Draft => 'gray',
                        PassSlipStatus::Submitted => 'info',
                        PassSlipStatus::Returned => 'warning',
                        PassSlipStatus::Approved => 'success',
                        PassSlipStatus::Departed => 'info',
                        PassSlipStatus::Arrived => 'warning',
                        PassSlipStatus::CertificateSubmitted => 'info',
                        PassSlipStatus::Verified => 'success',
                        PassSlipStatus::Completed => 'success',
                        PassSlipStatus::Cancelled => 'danger',
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->date('M d, Y'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Created'),
            ]);
    }
}
