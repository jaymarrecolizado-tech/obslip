<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationCollection extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'unread_count' => $this->collection->whereNull('read_at')->count(),
        ];
    }
}