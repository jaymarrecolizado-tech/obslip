<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Foundation\Http\FormRequest;

class ReturnPassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $passSlip = $this->route('pass_slip');
        return $passSlip->canBeReturnedBy($this->user());
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason must be provided when returning a pass slip.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'reason.max' => 'The reason must not exceed 500 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        if ($passSlip->status !== PassSlipStatus::SUBMITTED) {
            abort(400, 'Only submitted pass slips can be returned.');
        }
    }
}