<?php

declare(strict_types=1);

namespace App\Filament\Resources\PassSlipResource\Pages;

use App\Enums\PassSlipStatus;
use App\Filament\Resources\PassSlipResource;
use App\Services\PdfService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewPassSlip extends ViewRecord
{
    protected static string $resource = PassSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn () => in_array($this->record->status, [
                    PassSlipStatus::Approved,
                    PassSlipStatus::Departed,
                    PassSlipStatus::Arrived,
                    PassSlipStatus::CertificateSubmitted,
                    PassSlipStatus::Verified,
                    PassSlipStatus::Completed,
                ]))
                ->action(function () {
                    $pdfService = app(PdfService::class);
                    $path = $pdfService->generatePassSlipPdf($this->record);

                    if (! $this->record->pdf_path) {
                        $this->record->update(['pdf_path' => $path]);
                    }

                    $contents = Storage::disk('public')->get($path);

                    return response($contents, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => "inline; filename=\"{$this->record->slip_number}.pdf\"",
                    ]);
                })
                ->openUrlInNewTab(),
        ];
    }
}
