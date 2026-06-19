<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'full_name' => $this->full_name,
            'is_active' => $this->is_active,
            'owner' => EmployeeResource::make($this->whenLoaded('owner')),
            'owner_id' => $this->owner_id,
            'is_company_vehicle' => $this->owner_id === null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}