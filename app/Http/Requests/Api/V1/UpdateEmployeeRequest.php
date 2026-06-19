<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.edit');
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;

        return [
            'employee_number' => ['sometimes', 'string', 'max:20', "unique:employees,employee_number,{$employeeId}"],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'suffix' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'department_id' => ['sometimes', 'uuid', 'exists:departments,id'],
            'position' => ['sometimes', 'string', 'max:100'],
            'date_hired' => ['sometimes', 'nullable', 'date'],
            'employment_status' => ['sometimes', 'string', Rule::enum(\App\Enums\EmploymentStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->department_id) {
                $department = \App\Models\Department::find($this->department_id);

                if (!$department?->is_active) {
                    $validator->errors()->add('department_id', 'Selected department is inactive.');
                }
            }

            if ($this->email) {
                $employeeId = $this->route('employee')->id;
                $exists = \App\Models\User::where('email', $this->email)
                    ->whereNot('id', $employee->user_id ?? '')
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('email', 'This email is already associated with a user account.');
                }
            }
        });
    }
}