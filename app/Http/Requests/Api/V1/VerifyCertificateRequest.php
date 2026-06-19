<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\CertificateStatus;
use Illuminate\Foundation\Http\FormRequest;

class VerifyCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('certificates.verify');
    }

    public function rules(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $certificate = $this->route('certificate');

        if ($certificate->status !== CertificateStatus::SUBMITTED) {
            abort(400, 'Only submitted certificates can be verified.');
        }
    }
}