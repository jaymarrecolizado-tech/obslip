<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Pass Slip Management';

    protected static ?string $navigationLabel = 'Certificates';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Certificate Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('pass_slip_id')
                                    ->label('Pass Slip')
                                    ->relationship('passSlip', 'slip_number')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->label('Certificate Type')
                                    ->options(CertificateType::class)
                                    ->required(),
                                Forms\Components\TextInput::make('office_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('representative_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('representative_position')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('representative_contact')
                                    ->maxLength(100),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('time_from')
                                    ->required(),
                                Forms\Components\TimePicker::make('time_to')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(CertificateStatus::class)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(CertificateStatus::Draft),
                                Forms\Components\Select::make('submitted_by')
                                    ->label('Submitted By')
                                    ->relationship('submittedBy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('verified_by')
                                    ->label('Verified By')
                                    ->relationship('verifiedBy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('passSlip.slip_number')
                    ->label('Pass Slip #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('office_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('representative_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (CertificateStatus $state): string => match ($state) {
                        CertificateStatus::Draft => 'gray',
                        CertificateStatus::Submitted => 'info',
                        CertificateStatus::Verified => 'success',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CertificateStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->options(CertificateType::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }
}
