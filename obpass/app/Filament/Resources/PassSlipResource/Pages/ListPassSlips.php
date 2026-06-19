<?php

namespace App\Filament\Resources\PassSlipResource\Pages;

use App\Filament\Resources\PassSlipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPassSlips extends ListRecords
{
    protected static string $resource = PassSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
