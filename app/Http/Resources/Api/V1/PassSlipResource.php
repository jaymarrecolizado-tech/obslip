<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\PassSlipStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassSlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slip_number' => $this->slip_number,
            'date' => $this->date->format('Y-m-d'),
            'purpose' => $this->purpose,
            'transport_type' => $this->transport_type->value,
            'transport_label' => $this->transport_type->getLabel(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getColor(),
            'is_emergency' => $this->emergency,
            'duration_hours' => $this->duration_hours,
            'duration_display' => $this->duration,
            'returned_reason' => $this->returned_reason,

            // Timestamps
            'departure_time' => $this->departure_time?->toIso8601String(),
            'arrival_time' => $this->arrival_time?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relations
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'supervisor' => UserResource::make($this->whenLoaded('supervisor')),
            'approver' => UserResource::make($this->whenLoaded('approver')),
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'certificates' => CertificateResource::collection($this->whenLoaded('certificates')),

            // Actions
            'can' => [
                'edit' => $this->user()?->can('update', $this->resource) ?? false,
                'cancel' => $this->user()?->can('cancel', $this->resource) ?? false,
                'submit' => $this->status === PassSlipStatus::DRAFT,
                'approve' => $this->user()?->can('approve', $this->resource) ?? false,
                'return' => $this->user()?->can('return', $this->resource) ?? false,
                'log_departure' => $this->user()?->can('logDeparture', $this->resource) ?? false,
                'log_arrival' => $this->user()?->can('logArrival', $this->resource) ?? false,
                'download_pdf' => $this->user()?->can('downloadPdf', $this->resource) ?? false,
            ],

            // URLs
            'pdf_url' => $this->pdf_url,
            'qr_code_url' => $this->qr_code_url,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'can_transition_to' => collect(PassSlipStatus::cases())
                    ->filter(fn ($status) => $this->status->canTransitionTo($status))
                    ->map(fn ($status) => $status->value)
                    ->values(),
            ],
        ];
    }
}