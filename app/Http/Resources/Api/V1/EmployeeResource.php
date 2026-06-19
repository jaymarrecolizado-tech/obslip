<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'suffix' => $this->suffix,
            'full_name' => $this->full_name,
            'full_name_with_suffix' => $this->full_name_with_suffix,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'date_hired' => $this->date_hired?->format('Y-m-d'),
            'employment_status' => $this->employment_status->value,
            'employment_status_label' => $this->employment_status->getLabel(),
            'is_active' => $this->is_active,
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'department_id' => $this->department_id,
            'vehicles' => VehicleResource::collection($this->whenLoaded('vehicles')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}