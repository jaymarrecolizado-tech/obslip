<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $passSlip = $this->route('pass_slip');
        return $this->user()->can('certificates.submit') && $passSlip->creator_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:physical,digital,hybrid'],
            'office_name' => ['required', 'string', 'max:255'],
            'representative_name' => ['required', 'string', 'max:255'],
            'representative_position' => ['required', 'string', 'max:255'],
            'representative_contact' => ['nullable', 'string', 'max:100'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
            'signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'time_to.after' => 'End time must be after start time.',
            'signature_image.mimes' => 'Signature must be a PNG, JPG, or JPEG image.',
            'attachment.mimes' => 'Attachment must be a PDF or image file.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        if ($passSlip->status !== \App\Enums\PassSlipStatus::ARRIVED) {
            abort(400, 'Certificates can only be submitted for arrived pass slips.');
        }

        if ($passSlip->certificates()->where('status', '!=', \App\Enums\CertificateStatus::DRAFT)->exists()) {
            abort(400, 'A certificate has already been submitted for this pass slip.');
        }
    }
}