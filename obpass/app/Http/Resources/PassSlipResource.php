<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassSlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slip_number' => $this->slip_number,
            'date' => $this->date?->toDateString(),
            'purpose' => $this->purpose,
            'transport_type' => $this->transport_type?->value,
            'is_emergency' => $this->is_emergency,
            'status' => $this->status?->value,
            'creator_id' => $this->creator_id,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'employees' => EmployeeResource::collection($this->whenLoaded('employees')),
            'department_id' => $this->department_id,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'supervisor_id' => $this->supervisor_id,
            'supervisor' => new UserResource($this->whenLoaded('supervisor')),
            'approver_id' => $this->approver_id,
            'approver' => new UserResource($this->whenLoaded('approver')),
            'departure_time' => $this->departure_time?->toISOString(),
            'arrival_time' => $this->arrival_time?->toISOString(),
            'duration_hours' => $this->duration_hours,
            'returned_reason' => $this->returned_reason,
            'qr_code' => $this->when($request->is('api/*'), $this->qr_code),
            'pdf_path' => $this->pdf_path,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
