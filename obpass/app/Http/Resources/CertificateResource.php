<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pass_slip_id' => $this->pass_slip_id,
            'pass_slip' => new PassSlipResource($this->whenLoaded('passSlip')),
            'type' => $this->type?->value,
            'office_name' => $this->office_name,
            'representative_name' => $this->representative_name,
            'representative_position' => $this->representative_position,
            'representative_contact' => $this->representative_contact,
            'time_from' => $this->time_from?->format('H:i'),
            'time_to' => $this->time_to?->format('H:i'),
            'signature_path' => $this->signature_path,
            'attachment_path' => $this->attachment_path,
            'status' => $this->status?->value,
            'submitted_by' => $this->submitted_by,
            'submittedBy' => new UserResource($this->whenLoaded('submittedBy')),
            'verified_by' => $this->verified_by,
            'verifiedBy' => new UserResource($this->whenLoaded('verifiedBy')),
            'verified_at' => $this->verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
