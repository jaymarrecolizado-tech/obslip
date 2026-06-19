<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SearchSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('guard.search_slip');
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }
}