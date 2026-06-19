<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ScanQrRequest;
use App\Http\Requests\Api\V1\SearchSlipRequest;
use App\Http\Resources\Api\V1\PassSlipCollection;
use App\Http\Resources\Api\V1\PassSlipResource;
use App\Models\PassSlip;
use App\Notifications\PassSlipApprovedNotification;
use App\Notifications\PassSlipArrivedNotification;
use App\Notifications\PassSlipReturnedNotification;
use App\Notifications\PassSlipDepartedNotification;
use App\Services\PassSlipService;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PassSlipController extends Controller
{
    public function __construct(
        private PassSlipService $passSlipService,
        private PdfService $pdfService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = PassSlip::query()
            ->with(['employee', 'department', 'vehicle', 'creator']);

        // Filter by role
        if ($user->can('pass_slips.view_all')) {
            // Admin/HR - view all
        } elseif ($user->can('pass_slips.view_today_only')) {
            // Guard - only today's approved/departed
            $query->today()->forGuard();
        } else {
            // Others - view own or department slips
            if ($user->hasRole('supervisor')) {
                $query->byDepartment($user->department_id);
            } else {
                $query->where('creator_id', $user->id);
            }
        }

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus(\App\Enums\PassSlipStatus::from($request->status));
        }
        if ($request->has('date')) {
            $query->byDate($request->date);
        }
        if ($request->has('from') && $request->has('to')) {
            $query->byDateRange($request->from, $request->to);
        }
        if ($request->has('department_id')) {
            $query->byDepartment($request->department_id);
        }
        if ($request->has('employee_id')) {
            $query->byEmployee($request->employee_id);
        }
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $passSlips = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(min($request->get('per_page', 15), 100));

        return $this->successResponse(
            new PassSlipCollection($passSlips)
        );
    }

    public function show(PassSlip $passSlip): JsonResponse
    {
        $this->authorize('view', $passSlip);

        $passSlip->load([
            'employee.department',
            'department.head',
            'vehicle.owner',
            'creator',
            'supervisor',
            'approver',
            'certificates.submittedBy',
            'certificates.verifiedBy',
            'auditLogs.user',
        ]);

        return $this->successResponse(
            new PassSlipResource($passSlip)
        );
    }

    public function store(\App\Http\Requests\Api\V1\StorePassSlipRequest $request): JsonResponse
    {
        $passSlip = $this->passSlipService->create(
            $request->validated(),
            Auth::id()
        );

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip created successfully.',
            201
        );
    }

    public function update(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\UpdatePassSlipRequest $request
    ): JsonResponse {
        $this->authorize('update', $passSlip);

        $passSlip = $this->passSlipService->update(
            $passSlip,
            $request->validated()
        );

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip updated successfully.'
        );
    }

    public function destroy(PassSlip $passSlip): JsonResponse
    {
        $this->authorize('delete', $passSlip);

        $passSlip->delete();

        return $this->successResponse(
            null,
            'Pass slip deleted successfully.'
        );
    }

    public function submit(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\SubmitPassSlipRequest $request
    ): JsonResponse {
        $this->authorize('submit', $passSlip);

        $success = $passSlip->transitionTo(
            \App\Enums\PassSlipStatus::SUBMITTED,
            Auth::user()
        );

        if (!$success) {
            return $this->errorResponse('Failed to submit pass slip.');
        }

        // Notify supervisor
        if ($passSlip->department?->head_id) {
            $passSlip->department->head->notify(
                new \App\Notifications\PassSlipSubmittedNotification($passSlip)
            );
        }

        // Notify employee
        $passSlip->employee->user?->notify(
            new \App\Notifications\PassSlipSubmittedNotification($passSlip)
        );

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip submitted successfully.'
        );
    }

    public function approve(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\ApprovePassSlipRequest $request
    ): JsonResponse {
        $this->authorize('approve', $passSlip);

        $success = $passSlip->transitionTo(
            \App\Enums\PassSlipStatus::APPROVED,
            Auth::user()
        );

        if (!$success) {
            return $this->errorResponse('Failed to approve pass slip.');
        }

        // Generate PDF
        $this->pdfService->generatePassSlipPdf($passSlip);

        // Notify employee
        $passSlip->employee->user?->notify(
            new PassSlipApprovedNotification($passSlip)
        );

        // Notify guards
        $guards = \App\Models\User::role('guard')->get();
        foreach ($guards as $guard) {
            $guard->notify(
                new PassSlipApprovedNotification($passSlip)
            );
        }

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip approved successfully.'
        );
    }

    public function return(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\ReturnPassSlipRequest $request
    ): JsonResponse {
        $this->authorize('return', $passSlip);

        $passSlip->returned_reason = $request->reason;

        $success = $passSlip->transitionTo(
            \App\Enums\PassSlipStatus::RETURNED,
            Auth::user(),
            $request->reason
        );

        if (!$success) {
            return $this->errorResponse('Failed to return pass slip.');
        }

        // Notify employee
        $passSlip->employee->user?->notify(
            new PassSlipReturnedNotification($passSlip, $request->reason)
        );

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip returned successfully.'
        );
    }

    public function cancel(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\CancelPassSlipRequest $request
    ): JsonResponse {
        $this->authorize('cancel', $passSlip);

        $success = $passSlip->transitionTo(
            \App\Enums\PassSlipStatus::CANCELLED,
            Auth::user(),
            $request->reason ?? 'Cancelled by user'
        );

        if (!$success) {
            return $this->errorResponse('Failed to cancel pass slip.');
        }

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip cancelled successfully.'
        );
    }

    public function pdf(PassSlip $passSlip): JsonResponse
    {
        $this->authorize('downloadPdf', $passSlip);

        if (!$passSlip->pdf_path) {
            // Generate PDF if not exists
            $this->pdfService->generatePassSlipPdf($passSlip);
            $passSlip->refresh();
        }

        return $this->successResponse([
            'pdf_url' => $passSlip->pdf_url,
            'pdf_path' => $passSlip->pdf_path,
        ]);
    }

    public function search(SearchSlipRequest $request): JsonResponse
    {
        $passSlips = PassSlip::query()
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
        $passSlip = PassSlip::where('qr_code', $request->qr_code)
            ->with(['employee', 'department'])
            ->firstOrFail();

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'QR code scanned successfully.'
        );
    }

    public function logDeparture(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\LogDepartureRequest $request
    ): JsonResponse {
        $this->authorize('logDeparture', $passSlip);

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
                new PassSlipDepartedNotification($passSlip)
            );
        }

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Departure logged successfully.'
        );
    }

    public function logArrival(
        PassSlip $passSlip,
        \App\Http\Requests\Api\V1\LogArrivalRequest $request
    ): JsonResponse
    {
        $this->authorize('logArrival', $passSlip);

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
                new PassSlipArrivedNotification($passSlip)
            );
        }

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Arrival logged successfully.'
        );
    }
}