<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PassSlipStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PassSlipResource;
use App\Models\PassSlip;
use App\Services\NotificationService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardController extends Controller
{
    use ApiResponses;

    public function searchSlip(Request $request): JsonResponse
    {
        $request->validate(['slip_number' => ['required', 'string']]);

        $passSlip = PassSlip::where('slip_number', $request->slip_number)
            ->with(['employees', 'department', 'vehicle'])
            ->first();

        if (! $passSlip) {
            return $this->errorResponse('Pass slip not found.', 404);
        }

        return $this->successResponse(new PassSlipResource($passSlip));
    }

    public function scanQr(Request $request): JsonResponse
    {
        $request->validate(['qr_code' => ['required', 'string']]);

        $passSlip = PassSlip::where('qr_code', $request->qr_code)
            ->with(['employees', 'department', 'vehicle'])
            ->first();

        if (! $passSlip) {
            return $this->errorResponse('Invalid QR code.', 404);
        }

        return $this->successResponse(new PassSlipResource($passSlip));
    }

    public function logDeparture(Request $request, PassSlip $pass_slip, NotificationService $notifications): JsonResponse
    {
        if (! $pass_slip->depart()) {
            return $this->errorResponse('Only approved pass slips can be departed.', 422);
        }

        $pass_slip->load(['employees', 'department', 'vehicle']);
        $notifications->notifySupervisorOnDepart($pass_slip);

        return $this->successResponse(
            new PassSlipResource($pass_slip),
            'Departure logged.'
        );
    }

    public function logArrival(Request $request, PassSlip $pass_slip, NotificationService $notifications): JsonResponse
    {
        if (! $pass_slip->arrive()) {
            return $this->errorResponse('Only departed pass slips can have arrival logged.', 422);
        }

        $pass_slip->load(['employees', 'department', 'vehicle']);
        $notifications->notifySupervisorOnArrive($pass_slip);

        return $this->successResponse(
            new PassSlipResource($pass_slip),
            'Arrival logged.'
        );
    }
}
