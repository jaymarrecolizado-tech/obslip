<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PassSlip;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class PdfService
{
    public function generatePassSlipPdf(PassSlip $passSlip): void
    {
        $html = view('pdf.pass_slip', [
            'passSlip' => $passSlip->load([
                'employee.department',
                'department.head',
                'vehicle',
                'creator',
                'supervisor',
                'approver',
            ]),
        ])->render();

        $filename = "pass_slips/{$passSlip->slip_number}.pdf";
        $path = "pdfs/{$filename}";

        // Save original
        Storage::disk('public')->put($path, $html);
        $passSlip->pdf_path = $path;

        // Create duplicate if setting enabled
        if (\App\Models\Setting::get('pdf_generate_duplicate', true)) {
            $duplicateFilename = "pass_slips/{$passSlip->slip_number}_DUPLICATE.pdf";
            $duplicatePath = "pdfs/{$duplicateFilename}";
            Storage::disk('public')->put($duplicatePath, $html);
        }

        // Add QR code to bottom-right corner
        if (\App\Models\Setting::get('pdf_include_qr_code', true) && $passSlip->qr_code) {
            $qrPath = $this->generateQrCode($passSlip->qr_code);
            $html = $this->embedQrCode($html, $qrPath);
        }

        // Generate PDF using Browsershot
        $pdfPath = Storage::disk('public')->path($path);
        Browsershot::url('data:text/html;charset=utf-8,' . rawurlencode($html))
            ->setNodeBinary(storage_path('node_modules'))
            ->timeout(30)
            ->save($pdfPath);

        Log::info('PDF generated', [
            'pass_slip_id' => $passSlip->id,
            'path' => $pdfPath,
        ]);
    }

    private function generateQrCode(string $qrCode): string
    {
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::create(
            route('verify.qr', ['qr_code' => $qrCode], 'absolute'),
            \SimpleSoftwareIO\QrCode\Encoding::ISO_8859_1,
            \SimpleSoftwareIO\QrCode\ErrorCorrectionLevel::H,
            300
        );

        $filename = "qrcodes/{$qrCode}.png";
        $path = "public/{$filename}";

        Storage::disk('public')->put($path, $qrCode->writeString());

        return $filename;
    }

    private function embedQrCode(string $html, string $qrPath): string
    {
        $qrUrl = asset("storage/{$qrPath}");
        $qrSize = \App\Models\Setting::get('qr_code_size', 300);

        // Insert QR code image at bottom-right
        $style = <<<CSS
        .qr-code-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        .qr-code-image {
            width: {$qrSize}px;
            height: {$qrSize}px;
        }
        CSS;

        $qrHtml = <<<HTML
        <div class="qr-code-container">
            <style>{$style}</style>
            <img src="{$qrUrl}" class="qr-code-image" alt="QR Code" />
        </div>
        HTML;

        return str_replace('</body>', "{$qrHtml}</body>", $html);
    }

    public function generateCertificatePdf(Certificate $certificate): string
    {
        $html = view('pdf.certificate', [
            'certificate' => $certificate->load([
                'passSlip.employee',
                'passSlip.department',
                'submittedBy',
                'verifiedBy',
            ]),
        ])->render();

        $filename = "certificates/certificate_{$certificate->id}.pdf";
        $path = "pdfs/{$filename}";

        Storage::disk('public')->put($path, $html);

        $pdfPath = Storage::disk('public')->path($path);
        Browsershot::url('data:text/html;charset=utf-8,' . rawurlencode($html))
            ->setNodeBinary(storage_path('node_modules'))
            ->timeout(30)
            ->save($pdfPath);

        return Storage::url($path);
    }
}