<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $passSlip = $this->route('pass_slip');
        return $passSlip->creator_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        if ($passSlip->status !== PassSlipStatus::DRAFT) {
            abort(400, 'Only draft pass slips can be submitted.');
        }

        if (!$passSlip->employee) {
            abort(400, 'An employee must be assigned before submitting.');
        }

        if ($passSlip->transport_type->requiresVehicle() && !$passSlip->vehicle_id) {
            abort(400, 'A vehicle must be selected for this transport type.');
        }
    }
}