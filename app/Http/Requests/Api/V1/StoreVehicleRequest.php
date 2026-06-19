<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vehicles.create');
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'owner_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.unique' => 'A vehicle with this plate number already exists.',
            'make.required' => 'Vehicle make is required.',
            'model.required' => 'Vehicle model is required.',
            'year.min' => 'Vehicle year must be 1990 or later.',
            'year.max' => 'Vehicle year cannot be in the future.',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateOwnerActive($validator);
            $this->validateOwnerVehicleLimit($validator);
        });
    }

    private function validateOwnerActive(Validator $validator): void
    {
        if (!$this->owner_id) {
            return;
        }

        $owner = \App\Models\Employee::find($this->owner_id);

        if (!$owner?->is_active) {
            $validator->errors()->add('owner_id', 'Selected owner is inactive.');
        }
    }

    private function validateOwnerVehicleLimit(Validator $validator): void
    {
        if (!$this->owner_id) {
            return;
        }

        $count = \App\Models\Vehicle::where('owner_id', $this->owner_id)->count();

        if ($count >= 2) {
            $validator->errors()->add('owner_id', 'An employee can own at most 2 personal vehicles.');
        }
    }
}