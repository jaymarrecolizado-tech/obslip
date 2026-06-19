<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\CertificateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pass_slip_id' => ['required', 'exists:pass_slips,id'],
            'type' => ['required', Rule::enum(CertificateType::class)],
            'office_name' => ['required', 'string', 'max:255'],
            'representative_name' => ['required', 'string', 'max:255'],
            'representative_position' => ['required', 'string', 'max:255'],
            'representative_contact' => ['nullable', 'string', 'max:50'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after_or_equal:time_from'],
            'signature_path' => ['nullable', 'string', 'max:500'],
            'attachment_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
