<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'head' => UserResource::make($this->whenLoaded('head')),
            'head_id' => $this->head_id,
            'employees_count' => $this->whenCounted('employees'),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}