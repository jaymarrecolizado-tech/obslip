<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Certificate;
use App\Models\PassSlip;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Chillerlan\QRCode\{QRCode, QROptions};
use Chillerlan\QRCode\Data\QRMatrix;
use Chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    private string $companyName;
    private string $companyAddress;
    private string $tagline;
    private string $primaryColor;
    private bool $showQr;

    public function __construct()
    {
        $this->companyName = Setting::getValue('company_name', 'DICT Region II');
        $this->companyAddress = Setting::getValue('company_address', 'Tuguegarao City, Cagayan');
        $this->tagline = Setting::getValue('pdf_company_tagline', 'Official Business Pass Slip');
        $this->primaryColor = Setting::getValue('pdf_primary_color', '#1e3a5f');
        $this->showQr = Setting::getValue('pdf_show_qr', true);
    }

    public function generatePassSlipPdf(PassSlip $passSlip, string $copyLabel = 'ORIGINAL'): string
    {
        $passSlip->load(['creator', 'supervisor', 'approver', 'employees', 'department', 'vehicle']);

        $qrCodeImage = null;
        if ($this->showQr && $passSlip->qr_code) {
            $qrCodeImage = $this->generateQrDataUri($passSlip->qr_verification_url);
        }

        $data = [
            'passSlip' => $passSlip,
            'companyName' => $this->companyName,
            'companyAddress' => $this->companyAddress,
            'tagline' => $this->tagline,
            'primaryColor' => $this->primaryColor,
            'showQr' => $this->showQr,
            'qrCodeImage' => $qrCodeImage,
            'copyLabel' => $copyLabel,
        ];

        $html = view('pdf.pass-slip', $data)->render();
        $pdf = Pdf::loadHtml($html)->setPaper('a4', 'portrait');

        $filename = "pass_slips/{$passSlip->slip_number}_{$copyLabel}.pdf";
        $path = "pdfs/{$filename}";

        Storage::disk('public')->makeDirectory('pdfs/pass_slips');
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function generateCertificatePdf(Certificate $certificate): string
    {
        $certificate->load(['passSlip.employees', 'passSlip.department', 'submittedBy', 'verifiedBy']);

        $data = [
            'certificate' => $certificate,
            'companyName' => $this->companyName,
            'companyAddress' => $this->companyAddress,
            'primaryColor' => $this->primaryColor,
        ];

        $html = view('pdf.certificate', $data)->render();
        $pdf = Pdf::loadHtml($html)->setPaper('a4', 'portrait');

        $filename = "certificates/certificate_{$certificate->id}.pdf";
        $path = "pdfs/{$filename}";

        Storage::disk('public')->makeDirectory('pdfs/certificates');
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function generateQrDataUri(string $data): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 5,
            'drawCircularModules' => false,
            'moduleValues' => [
                QRMatrix::M_FINDER_DARK => [30, 60, 100],
                QRMatrix::M_DATA_DARK => [50, 80, 120],
            ],
        ]);

        $qr = new QRCode($options);
        $imageData = $qr->render($data);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
