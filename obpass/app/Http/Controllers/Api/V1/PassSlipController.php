<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PassSlipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PassSlipRequest;
use App\Http\Resources\PassSlipResource;
use App\Models\PassSlip;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PassSlipController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = PassSlip::with(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('employees', function ($q) use ($request) {
                $q->where('employees.id', $request->employee_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse(PassSlipResource::collection($paginator));
    }

    public function store(PassSlipRequest $request): JsonResponse
    {
        $this->authorize('create', PassSlip::class);

        $data = $request->validated();
        $data['creator_id'] = $request->user()->id;
        $data['status'] = PassSlipStatus::Draft;

        $employeeIds = $data['employees'] ?? [];
        unset($data['employees']);

        $passSlip = PassSlip::create($data);
        $passSlip->employees()->sync($employeeIds);
        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip created.',
            201
        );
    }

    public function show(PassSlip $passSlip): JsonResponse
    {
        $this->authorize('view', $passSlip);

        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);

        return $this->successResponse(new PassSlipResource($passSlip));
    }

    public function update(PassSlipRequest $request, PassSlip $passSlip): JsonResponse
    {
        $this->authorize('update', $passSlip);

        if ($passSlip->status !== PassSlipStatus::Draft) {
            return $this->errorResponse('Cannot edit a pass slip in this status.', 422);
        }

        $data = $request->validated();
        $employeeIds = $data['employees'] ?? null;
        unset($data['employees']);

        $passSlip->update($data);

        if ($employeeIds !== null) {
            $passSlip->employees()->sync($employeeIds);
        }

        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip updated.'
        );
    }

    public function destroy(PassSlip $passSlip): JsonResponse
    {
        $this->authorize('delete', $passSlip);

        if (! in_array($passSlip->status, [PassSlipStatus::Draft])) {
            return $this->errorResponse('Only draft pass slips can be deleted.', 422);
        }

        $passSlip->employees()->detach();
        $passSlip->delete();

        return $this->successResponse(null, 'Pass slip deleted.');
    }

    public function submit(PassSlip $passSlip, NotificationService $notifications): JsonResponse
    {
        $this->authorize('submit', $passSlip);

        if (! $passSlip->submit()) {
            return $this->errorResponse('Only draft or returned pass slips can be submitted.', 422);
        }

        $notifications->notifyEmployeeOnSubmit($passSlip);

        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);
        $notifications->notifySupervisorOnSubmit($passSlip);

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip submitted for approval.'
        );
    }

    public function approve(PassSlip $passSlip, NotificationService $notifications): JsonResponse
    {
        $this->authorize('approve', $passSlip);

        if (! $passSlip->approve($passSlip->relationLoaded('approver') ? $passSlip->approver : request()->user())) {
            return $this->errorResponse('Only submitted pass slips can be approved.', 422);
        }

        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);
        $notifications->notifyEmployeeOnApprove($passSlip);
        $notifications->notifyGuardsOnApprove($passSlip);

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip approved.'
        );
    }

    public function returnSlip(PassSlip $passSlip, Request $request, NotificationService $notifications): JsonResponse
    {
        $this->authorize('returnSlip', $passSlip);

        $request->validate(['returned_reason' => ['required', 'string', 'max:500']]);

        if (! $passSlip->returnWithReason($request->user(), $request->returned_reason)) {
            return $this->errorResponse('Only submitted pass slips can be returned.', 422);
        }

        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);
        $notifications->notifyEmployeeOnReturn($passSlip);

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip returned.'
        );
    }

    public function cancel(Request $request, PassSlip $passSlip): JsonResponse
    {
        $this->authorize('cancel', $passSlip);

        if (! $passSlip->cancel($request->user())) {
            return $this->errorResponse('Cannot cancel this pass slip.', 422);
        }

        $passSlip->load(['creator', 'employees', 'department', 'vehicle', 'supervisor', 'approver']);

        return $this->successResponse(
            new PassSlipResource($passSlip),
            'Pass slip cancelled.'
        );
    }

    public function pdf(PassSlip $passSlip, PdfService $pdfService): Response|JsonResponse
    {
        $terminalStatuses = [
            PassSlipStatus::Approved,
            PassSlipStatus::Departed,
            PassSlipStatus::Arrived,
            PassSlipStatus::CertificateSubmitted,
            PassSlipStatus::Verified,
            PassSlipStatus::Completed,
        ];

        if (! in_array($passSlip->status, $terminalStatuses)) {
            return $this->errorResponse('PDF is only available for approved or later status.', 422);
        }

        $path = $pdfService->generatePassSlipPdf($passSlip);

        if (! $passSlip->pdf_path) {
            $passSlip->update(['pdf_path' => $path]);
        }

        $contents = Storage::disk('public')->get($path);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$passSlip->slip_number}.pdf\"",
        ]);
    }

    public function verifyQr(string $qr_code)
    {
        $passSlip = PassSlip::where('qr_code', $qr_code)
            ->with(['employees', 'department'])
            ->first();

        return view('verify.index', ['passSlip' => $passSlip]);
    }
}
