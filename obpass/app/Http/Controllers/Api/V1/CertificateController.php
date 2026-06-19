<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\CertificateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CertificateRequest;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Models\PassSlip;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificateController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = Certificate::with(['passSlip.employees', 'passSlip.department', 'submittedBy', 'verifiedBy'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse(CertificateResource::collection($paginator));
    }

    public function store(CertificateRequest $request, NotificationService $notifications): JsonResponse
    {
        $this->authorize('create', Certificate::class);

        $certificate = Certificate::create(array_merge(
            $request->validated(),
            [
                'status' => CertificateStatus::Submitted,
                'submitted_by' => $request->user()->id,
            ]
        ));
        $certificate->load(['passSlip.employees', 'passSlip.department', 'submittedBy']);

        $passSlip = $certificate->passSlip;
        if ($passSlip) {
            $passSlip->submitCertificate();
            $notifications->notifyHrOnCertificate($passSlip);
        }

        return $this->successResponse(
            new CertificateResource($certificate),
            'Certificate submitted.',
            201
        );
    }

    public function show(Certificate $certificate): JsonResponse
    {
        $certificate->load(['passSlip.employees', 'passSlip.department', 'submittedBy', 'verifiedBy']);

        return $this->successResponse(new CertificateResource($certificate));
    }

    public function update(CertificateRequest $request, Certificate $certificate): JsonResponse
    {
        $certificate->update($request->validated());
        $certificate->load(['passSlip.employees', 'passSlip.department', 'submittedBy', 'verifiedBy']);

        return $this->successResponse(
            new CertificateResource($certificate),
            'Certificate updated.'
        );
    }

    public function verify(Certificate $certificate, Request $request, NotificationService $notifications): JsonResponse
    {
        $this->authorize('verify', $certificate);

        if ($certificate->status !== CertificateStatus::Submitted) {
            return $this->errorResponse('Only submitted certificates can be verified.', 422);
        }

        $certificate->update([
            'status' => CertificateStatus::Verified,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);
        $certificate->load(['passSlip.employees', 'passSlip.department', 'submittedBy', 'verifiedBy']);

        $passSlip = $certificate->passSlip;
        if ($passSlip) {
            $passSlip->verify();
            $notifications->notifyEmployeeOnVerify($passSlip);
        }

        return $this->successResponse(
            new CertificateResource($certificate),
            'Certificate verified.'
        );
    }

    public function pdf(Certificate $certificate, PdfService $pdfService): Response|JsonResponse
    {
        if ($certificate->status === CertificateStatus::Draft) {
            return $this->errorResponse('Certificate must be submitted before generating PDF.', 422);
        }

        $path = $pdfService->generateCertificatePdf($certificate);

        $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($path);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"certificate_{$certificate->id}.pdf\"",
        ]);
    }
}
