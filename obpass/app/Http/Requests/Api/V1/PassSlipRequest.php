<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\TransportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'transport_type' => ['required', Rule::enum(TransportType::class)],
            'is_emergency' => ['sometimes', 'boolean'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*' => ['exists:employees,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
