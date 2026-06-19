<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.create');
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:20', 'unique:employees,employee_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'position' => ['required', 'string', 'max:100'],
            'date_hired' => ['nullable', 'date'],
            'employment_status' => ['required', 'string', Rule::enum(\App\Enums\EmploymentStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.unique' => 'An employee with this number already exists.',
            'department_id.required' => 'A department must be selected.',
            'position.required' => 'A position must be specified.',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDepartmentActive($validator);
            $this->validateUserEmailUnique($validator);
        });
    }

    private function validateDepartmentActive(Validator $validator): void
    {
        if (!$this->department_id) {
            return;
        }

        $department = \App\Models\Department::find($this->department_id);

        if (!$department?->is_active) {
            $validator->errors()->add('department_id', 'Selected department is inactive.');
        }
    }

    private function validateUserEmailUnique(Validator $validator): void
    {
        if (!$this->email) {
            return;
        }

        $exists = \App\Models\User::where('email', $this->email)->exists();

        if ($exists) {
            $validator->errors()->add('email', 'This email is already associated with a user account.');
        }
    }
}