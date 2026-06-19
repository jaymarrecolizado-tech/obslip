<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CancelPassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $passSlip = $this->route('pass_slip');
        return $passSlip->canBeCancelledBy($this->user());
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'string', 'min:10', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        $allowedStatuses = [
            \App\Enums\PassSlipStatus::DRAFT,
            \App\Enums\PassSlipStatus::SUBMITTED,
            \App\Enums\PassSlipStatus::APPROVED,
        ];

        if (!in_array($passSlip->status, $allowedStatuses, true)) {
            abort(400, 'This pass slip cannot be cancelled in its current state.');
        }

        // Only admin/supervisor can cancel approved slips with reason
        if ($passSlip->status === \App\Enums\PassSlipStatus::APPROVED && !$this->reason) {
            abort(400, 'A reason is required when cancelling an approved pass slip.');
        }
    }
}