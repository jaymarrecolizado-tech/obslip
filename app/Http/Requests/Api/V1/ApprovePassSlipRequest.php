<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Foundation\Http\FormRequest;

class ApprovePassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $passSlip = $this->route('pass_slip');
        return $passSlip->canBeApprovedBy($this->user());
    }

    public function rules(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        if ($passSlip->status !== PassSlipStatus::SUBMITTED) {
            abort(400, 'Only submitted pass slips can be approved.');
        }
    }
}