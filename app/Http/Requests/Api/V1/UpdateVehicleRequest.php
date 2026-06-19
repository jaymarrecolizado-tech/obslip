<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vehicles.edit');
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')->id;

        return [
            'plate_number' => ['sometimes', 'string', 'max:20', "unique:vehicles,plate_number,{$vehicleId}"],
            'make' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'owner_id' => ['sometimes', 'nullable', 'uuid', 'exists:employees,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->owner_id) {
                $owner = \App\Models\Employee::find($this->owner_id);

                if (!$owner?->is_active) {
                    $validator->errors()->add('owner_id', 'Selected owner is inactive.');
                }

                $vehicleId = $this->route('vehicle')->id;
                $count = \App\Models\Vehicle::where('owner_id', $this->owner_id)
                    ->where('id', '!=', $vehicleId)
                    ->count();

                if ($count >= 2) {
                    $validator->errors()->add('owner_id', 'An employee can own at most 2 personal vehicles.');
                }
            }
        });
    }
}