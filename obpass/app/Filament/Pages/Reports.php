<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\PassSlipStatus;
use App\Models\Department;
use App\Models\PassSlip;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $title = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.reports';

    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $department_id = null;
    public ?string $status = null;

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to = now()->toDateString();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    DatePicker::make('date_from')->label('Date From')->live(),
                    DatePicker::make('date_to')->label('Date To')->live(),
                    Select::make('department_id')
                        ->label('Department')
                        ->options(Department::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->live(),
                    Select::make('status')
                        ->label('Status')
                        ->options(collect(PassSlipStatus::cases())->mapWithKeys(fn (PassSlipStatus $s) => [$s->value => $s->label()]))
                        ->searchable()
                        ->live(),
                ]),
            ]);
    }

    /**
     * Filtered result set (cached per Livewire request).
     */
    public function getResultsProperty()
    {
        return PassSlip::query()
            ->with(['employees', 'department'])
            ->when($this->date_from, fn ($q) => $q->whereDate('date', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->whereDate('date', '<=', $this->date_to))
            ->when($this->department_id, fn ($q) => $q->where('department_id', $this->department_id))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('date')
            ->get();
    }

    public function exportCsv()
    {
        $rows = $this->results;

        return response()->streamDownload(function () use ($rows) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Slip #', 'Date', 'Employee(s)', 'Department', 'Status', 'Purpose', 'Duration (h)']);

            foreach ($rows as $slip) {
                fputcsv($fh, [
                    $slip->slip_number,
                    $slip->date?->toDateString(),
                    $slip->employees->pluck('full_name')->implode('; '),
                    $slip->department?->name,
                    $slip->status?->label(),
                    $slip->purpose,
                    $slip->duration_hours,
                ]);
            }

            fclose($fh);
        }, 'obpass-report.csv', ['Content-Type' => 'text/csv']);
    }
}
