<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pass_slips.edit_own_draft', $this->passSlip);
    }

    public function rules(): array
    {
        $passSlip = $this->route('pass_slip');

        return [
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'purpose' => ['sometimes', 'string', 'min:5', 'max:1000'],
            'transport_type' => ['sometimes', 'string', Rule::enum(\App\Enums\TransportType::class)],
            'employee_id' => ['sometimes', 'uuid', 'exists:employees,id'],
            'vehicle_id' => [
                'nullable',
                'uuid',
                'exists:vehicles,id',
                'required_if:transport_type,company_vehicle',
            ],
            'is_emergency' => ['sometimes', 'boolean'],
            'additional_employees' => ['sometimes', 'array'],
            'additional_employees.*' => ['uuid', 'exists:employees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'purpose.min' => 'The purpose must be at least 5 characters.',
            'vehicle_id.required_if' => 'A vehicle is required when using company vehicle.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        if ($passSlip && $passSlip->status !== \App\Enums\PassSlipStatus::DRAFT) {
            abort(403, 'Only draft pass slips can be edited.');
        }
    }
}