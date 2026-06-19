<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ScanQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('guard.scan_qr');
    }

    public function rules(): array
    {
        return [
            'qr_code' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = \App\Models\PassSlip::where('qr_code', $this->qr_code)->first();

        if (!$passSlip) {
            abort(404, 'Pass slip not found or invalid QR code.');
        }

        if ($passSlip->status !== \App\Enums\PassSlipStatus::APPROVED
            && $passSlip->status !== \App\Enums\PassSlipStatus::DEPARTED) {
            abort(400, 'Pass slip is not in a valid state for scanning.');
        }
    }
}