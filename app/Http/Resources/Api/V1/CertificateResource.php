<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'office_name' => $this->office_name,
            'representative_name' => $this->representative_name,
            'representative_position' => $this->representative_position,
            'representative_contact' => $this->representative_contact,
            'time_from' => $this->time_from?->format('H:i'),
            'time_to' => $this->time_to?->format('H:i'),
            'signature_url' => $this->signature_url,
            'attachment_url' => $this->attachment_url,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getColor(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'submitted_by' => UserResource::make($this->whenLoaded('submittedBy')),
            'verified_by' => UserResource::make($this->whenLoaded('verifiedBy')),
            'pass_slip' => PassSlipResource::make($this->whenLoaded('passSlip')),
            'pass_slip_id' => $this->pass_slip_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            'can' => [
                'edit' => $this->status === \App\Enums\CertificateStatus::DRAFT,
                'delete' => $this->status === \App\Enums\CertificateStatus::DRAFT,
                'submit' => $this->status === \App\Enums\CertificateStatus::DRAFT,
                'verify' => $this->status === \App\Enums\CertificateStatus::SUBMITTED,
                'upload' => in_array($this->status->value, ['draft', 'submitted'], true),
            ],
        ];
    }
}