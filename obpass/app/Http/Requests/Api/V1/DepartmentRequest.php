<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,' . $this->route('department')?->id],
            'code' => ['nullable', 'string', 'max:20', 'unique:departments,code,' . $this->route('department')?->id],
            'description' => ['nullable', 'string', 'max:500'],
            'head_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
