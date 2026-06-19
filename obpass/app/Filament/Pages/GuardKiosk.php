<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\PassSlipStatus;
use App\Models\PassSlip;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GuardKiosk extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $title = 'Guard Kiosk';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.guard-kiosk';

    public ?string $slip_number = null;

    public ?string $found_slip_id = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('slip_number')
                    ->label('Search by Slip Number')
                    ->placeholder('OB-2026-0001')
                    ->extraInputAttributes(['class' => 'text-lg']),
            ]);
    }

    public function searchSlip(): void
    {
        $data = $this->form->getState();
        $slip = PassSlip::where('slip_number', $data['slip_number'] ?? '')
            ->with(['employees', 'department', 'vehicle'])
            ->first();

        if (! $slip) {
            Notification::make()->title('Pass slip not found.')->danger()->send();
            $this->found_slip_id = null;

            return;
        }

        $this->found_slip_id = $slip->id;
        Notification::make()->title('Found ' . $slip->slip_number)->success()->send();
    }

    public function getFoundSlipProperty()
    {
        return $this->found_slip_id
            ? PassSlip::with(['employees', 'department', 'vehicle'])->find($this->found_slip_id)
            : null;
    }

    public function getTodaysActivityProperty()
    {
        return PassSlip::query()
            ->whereIn('status', [
                PassSlipStatus::Approved->value,
                PassSlipStatus::Departed->value,
                PassSlipStatus::Arrived->value,
            ])
            ->whereDate('date', today())
            ->with(['employees', 'department'])
            ->latest()
            ->get();
    }

    public function logDeparture(): void
    {
        $slip = $this->found_slip;
        if ($slip && $slip->depart()) {
            Notification::make()->title('Departure logged for ' . $slip->slip_number)->success()->send();
        } else {
            Notification::make()->title('Cannot log departure — slip must be Approved.')->danger()->send();
        }
    }

    public function logArrival(): void
    {
        $slip = $this->found_slip;
        if ($slip && $slip->arrive()) {
            Notification::make()->title('Arrival logged for ' . $slip->slip_number)->success()->send();
        } else {
            Notification::make()->title('Cannot log arrival — slip must be Departed.')->danger()->send();
        }
    }
}
