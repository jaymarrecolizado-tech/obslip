<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ScanQrRequest;
use App\Http\Requests\Api\V1\SearchSlipRequest;
use App\Http\Resources\Api\V1\PassSlipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardController extends Controller
{
    public function searchSlip(SearchSlipRequest $request): JsonResponse
    {
        $this->authorize('guard.search_slip');

        $passSlips = \App\Models\PassSlip::query()
            ->today()
            ->forGuard()
            ->search($request->query)
            ->with(['employee', 'department'])
            ->limit(10)
            ->get();

        return $this->successResponse(
            PassSlipResource::collection($passSlips)
        );
    }

    public function scanQr(ScanQrRequest $request): JsonResponse
    {
        $this->authorize('guard.scan_qr');

        $passSlip = \App\Models\PassSlip::where('qr_code', $request->qr_code)
            ->with(['employee', 'department', 'vehicle'])
            ->firstOrFail();

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'QR code scanned successfully.'
        );
    }

    public function logDeparture(
        \App\Models\PassSlip $passSlip,
        \App\Http\Requests\Api\V1\LogDepartureRequest $request
    ): JsonResponse {
        $this->authorize('guard.log_departure');

        $success = $passSlip->transitionTo(
            \App\Enums\PassSlipStatus::DEPARTED,
            Auth::user()
        );

        if (!$success) {
            return $this->errorResponse('Failed to log departure.');
        }

        // Notify supervisor
        if ($passSlip->department?->head_id) {
            $passSlip->department->head->notify(
                new \App\Notifications\PassSlipDepartedNotification($passSlip)
            );
        }

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Departure logged successfully.'
        );
    }

    public function logArrival(
        \App\Models\PassSlip $passSlip,
        \App\Http\Requests\Api\V1\LogArrivalRequest $request
    ): JsonResponse
    {
        $this->authorize('guard.log_arrival');

        $success = $passSlip->transitionTo(
            \App\Enums\PassSlipStatus::ARRIVED,
            Auth::user()
        );

        if (!$success) {
            return $this->errorResponse('Failed to log arrival.');
        }

        // Notify supervisor
        if ($passSlip->department?->head_id) {
            $passSlip->department->head->notify(
                new \App\Notifications\PassSlipArrivedNotification($passSlip)
            );
        }

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Arrival logged successfully.'
        );
    }

    public function todayActivity(): JsonResponse
    {
        $this->authorize('guard.search_slip');

        $activity = \App\Models\PassSlip::query()
            ->today()
            ->whereIn('status', [
                \App\Enums\PassSlipStatus::APPROVED,
                \App\Enum\PassSlipStatus::DEPARTED,
                \App\Enum\PassSlipStatus::ARRIVED,
            ])
            ->with(['employee', 'department'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($slip) {
                return [
                    'id' => $slip->id,
                    'slip_number' => $slip->slip_number,
                    'employee_name' => $slip->employee->full_name,
                    'department' => $slip->department?->name,
                    'status' => $slip->status->value,
                    'status_label' => $slip->status->getLabel(),
                    'departure_time' => $slip->departure_time?->toIso8601String(),
                    'arrival_time' => $slip->arrival_time?->toIso8601String(),
                    'duration' => $slip->duration_display,
                ];
            });

        return $this->successResponse($activity);
    }
}