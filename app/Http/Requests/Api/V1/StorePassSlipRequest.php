<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePassSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pass_slips.create');
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'transport_type' => ['required', 'string', Rule::enum(\App\Enums\TransportType::class)],
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
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
            'date.required' => 'The date field is required.',
            'date.after_or_equal' => 'The date cannot be in the past.',
            'purpose.required' => 'The purpose field is required.',
            'purpose.min' => 'The purpose must be at least 5 characters.',
            'transport_type.required' => 'The transport type is required.',
            'employee_id.required' => 'An employee must be selected.',
            'vehicle_id.required_if' => 'A vehicle is required when using company vehicle.',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateEmployeeExists($validator);
            $this->validateVehicleAvailable($validator);
            $this->validateVehicleOwner($validator);
        });
    }

    private function validateEmployeeExists(Validator $validator): void
    {
        $employee = \App\Models\Employee::find($this->employee_id);

        if (!$employee) {
            $validator->errors()->add('employee_id', 'Selected employee not found.');
            return;
        }

        if (!$employee->is_active) {
            $validator->errors()->add('employee_id', 'Selected employee is inactive.');
        }
    }

    private function validateVehicleAvailable(Validator $validator): void
    {
        if (!$this->vehicle_id) {
            return;
        }

        $vehicle = \App\Models\Vehicle::find($this->vehicle_id);

        if (!$vehicle) {
            $validator->errors()->add('vehicle_id', 'Selected vehicle not found.');
            return;
        }

        if (!$vehicle->is_active) {
            $validator->errors()->add('vehicle_id', 'Selected vehicle is inactive.');
            return;
        }

        // Check if vehicle is already in use for the same date
        $conflict = \App\Models\PassSlip::where('vehicle_id', $vehicle->id)
            ->where('date', $this->date)
            ->whereIn('status', [
                PassSlipStatus::APPROVED,
                PassSlipStatus::DEPARTED,
            ])
            ->exists();

        if ($conflict) {
            $validator->errors()->add('vehicle_id', 'This vehicle is already booked for the selected date.');
        }
    }

    private function validateVehicleOwner(Validator $validator): void
    {
        if (!$this->vehicle_id || !$this->employee_id) {
            return;
        }

        $transportType = $this->transport_type;
        $vehicle = \App\Models\Vehicle::find($this->vehicle_id);

        // Personal vehicles must belong to the employee
        if ($transportType === 'personal_vehicle' && $vehicle?->owner_id !== $this->employee_id) {
            $validator->errors()->add('vehicle_id', 'Personal vehicle must belong to the employee.');
        }

        // Company vehicles should not have an owner
        if ($transportType === 'company_vehicle' && $vehicle?->owner_id !== null) {
            $validator->errors()->add('vehicle_id', 'Company vehicles cannot have personal owners.');
        }
    }
}