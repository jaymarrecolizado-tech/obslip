<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use App\Filament\Resources\PassSlipResource\Pages;
use App\Models\PassSlip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PassSlipResource extends Resource
{
    protected static ?string $model = PassSlip::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Pass Slip Management';

    protected static ?string $navigationLabel = 'Pass Slips';

    protected static ?string $modelLabel = 'Pass Slip';

    protected static ?string $pluralModelLabel = 'Pass Slips';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pass Slip Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('slip_number')
                                    ->label('Slip Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn () => PassSlip::generateSlipNumber()),
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\Select::make('transport_type')
                                    ->label('Transport Type')
                                    ->options(TransportType::class)
                                    ->required()
                                    ->default(TransportType::CompanyVehicle),
                            ]),
                        Forms\Components\Textarea::make('purpose')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_emergency')
                            ->label('Emergency Pass Slip')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('Assignment')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('department_id')
                                    ->label('Department')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('employees')
                                    ->label('Employees')
                                    ->relationship('employees', 'full_name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->relationship('vehicle', 'plate_number')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('creator_id')
                                    ->label('Created By')
                                    ->relationship('creator', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn () => auth()->id()),
                                Forms\Components\Select::make('supervisor_id')
                                    ->label('Supervisor')
                                    ->relationship('supervisor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Forms\Components\Select::make('approver_id')
                                    ->label('Approver')
                                    ->relationship('approver', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->disabled(),
                            ]),
                    ]),

                Forms\Components\Section::make('Status & Timeline')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(PassSlipStatus::class)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(PassSlipStatus::Draft),
                                Forms\Components\TextInput::make('returned_reason')
                                    ->label('Return Reason')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('departure_time')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\DateTimePicker::make('arrival_time')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('duration_hours')
                                    ->label('Duration (hours)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Forms\Components\Section::make('QR & PDF')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('qr_code')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('pdf_path')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slip_number')
                    ->label('Slip #')
                    ->searchable()
                    ->sortable()
                    ->weight(1),
                Tables\Columns\TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transport_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),
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
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creator')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('Supervisor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approver')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employees.full_name')
                    ->label('Employees')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_hours')
                    ->label('Duration')
                    ->numeric(2)
                    ->suffix('h')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_emergency')
                    ->label('Emergency')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PassSlipStatus::class)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query) => $query->whereDate('date', today())),
                Tables\Filters\Filter::make('active')
                    ->label('Active Slips')
                    ->query(fn (Builder $query) => $query->whereNotIn('status', [
                        PassSlipStatus::Cancelled,
                        PassSlipStatus::Completed,
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Pass Slip Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('slip_number')
                            ->label('Slip Number')
                            ->weight(Infolists\Components\TextEntry\Weight::Bold),
                        Infolists\Components\TextEntry::make('date')
                            ->date('M d, Y'),
                        Infolists\Components\TextEntry::make('transport_type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),
                        Infolists\Components\TextEntry::make('status')
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
                        Infolists\Components\TextEntry::make('purpose')
                            ->columnSpanFull(),
                        Infolists\Components\IconEntry::make('is_emergency')
                            ->boolean(),
                    ]),
                Infolists\Components\Section::make('Assignment')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')->label('Created By'),
                        Infolists\Components\TextEntry::make('supervisor.name')->label('Supervisor'),
                        Infolists\Components\TextEntry::make('approver.name')->label('Approver'),
                        Infolists\Components\TextEntry::make('employees.full_name')
                            ->label('Employees'),
                        Infolists\Components\TextEntry::make('department.name')->label('Department'),
                        Infolists\Components\TextEntry::make('vehicle.plate_number')->label('Vehicle'),
                    ]),
                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('departure_time')->dateTime(),
                        Infolists\Components\TextEntry::make('arrival_time')->dateTime(),
                        Infolists\Components\TextEntry::make('duration_hours')->numeric(2)->suffix(' hours'),
                        Infolists\Components\TextEntry::make('returned_reason')->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPassSlips::route('/'),
            'create' => Pages\CreatePassSlip::route('/create'),
            'view' => Pages\ViewPassSlip::route('/{record}'),
            'edit' => Pages\EditPassSlip::route('/{record}/edit'),
        ];
    }
}
