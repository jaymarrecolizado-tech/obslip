<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\DevicePlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->check();
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'string', Rule::enum(DevicePlatform::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Rate limit: max 5 tokens per platform per user
        $existingCount = \App\Models\DeviceToken::where('user_id', $this->user()->id)
            ->where('platform', $this->platform)
            ->count();

        if ($existingCount >= 5) {
            abort(429, 'Maximum device tokens reached. Please remove old tokens from your device.');
        }
    }
}