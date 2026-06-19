<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Foundation\Http\FormRequest;

class LogDepartureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('guard.log_departure');
    }

    public function rules(): array
    {
        return [
            'location' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $passSlip = $this->route('pass_slip');

        if ($passSlip->status !== PassSlipStatus::APPROVED) {
            abort(400, 'Only approved pass slips can be logged as departed.');
        }

        if ($passSlip->departure_time) {
            abort(400, 'This pass slip has already been logged as departed.');
        }
    }
}