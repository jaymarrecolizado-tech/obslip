<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Foundation\Http\FormRequest;

class LogArrivalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('guard.log_arrival');
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

        if ($passSlip->status !== PassSlipStatus::DEPARTED) {
            abort(400, 'Only departed pass slips can be logged as arrived.');
        }

        if ($passSlip->arrival_time) {
            abort(400, 'This pass slip has already been logged as arrived.');
        }
    }
}