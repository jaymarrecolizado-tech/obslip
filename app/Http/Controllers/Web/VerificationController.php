<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PassSlipResource;
use App\Models\PassSlip;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    public function verify(string $qrCode): View|Response
    {
        $passSlip = PassSlip::where('qr_code', $qr_code)
            ->with(['employee.department'])
            ->firstOrFail();

        $passSlip->load(['employee.department', 'department.head']);

        // Verify status allows: approved, departed, arrived, completed
        $allowedStatuses = [
            \App\Enums\PassSlipStatus::APPROVED,
            \App\Enums\PassSlipStatus::DEPARTED,
            \App\Enums\PassSlipStatus::ARRIVED,
            \App\Enums\PassSlipStatus::COMPLETED,
        ];

        if (!in_array($passSlip->status, $allowedStatuses, true)) {
            abort(404, 'Invalid or expired pass slip.');
        }

        return view('verify.index', [
            'passSlip' => $passSlip,
            'is_emergency' => $passSlip->is_emergency,
        ]);
    }
}