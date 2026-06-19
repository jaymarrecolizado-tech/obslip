<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCertificateRequest;
use App\Http\Requests\Api\V1\VerifyCertificateRequest;
use App\Http\Resources\Api\V1\CertificateCollection;
use App\Http\Resources\Api\V1\CertificateResource;
use App\Models\Certificate;
use App\Notifications\CertificateVerifiedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Certificate::query()->with(['passSlip.employee', 'submittedBy', 'verifiedBy']);

        if ($request->has('status')) {
            $query->byStatus(\App\Enums\CertificateStatus::from($request->status));
        }
        if ($request->has('pass_slip_id')) {
            $query->byPassSlip($request->pass_slip_id);
        }
        if ($request->has('unverified')) {
            $query->unverified();
        }

        $certificates = $query->orderBy('created_at', 'desc')
            ->paginate(min($request->get('per_page', 15), 100));

        return $this->successResponse(
            new CertificateCollection($certificates)
        );
    }

    public function show(Certificate $certificate): JsonResponse
    {
        $this->authorize('view', $certificate);

        $certificate->load(['passSlip.employee', 'submittedBy', 'verifiedBy']);

        return $this->successResponse(
            new CertificateResource($certificate)
        );
    }

    public function store(StoreCertificateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $passSlip = $request->route('pass_slip');

        $certificate = Certificate::create([
            'pass_slip_id' => $passSlip->id,
            'type' => $validated['type'],
            'office_name' => $validated['office_name'],
            'representative_name' => $validated['representative_name'],
            'representative_position' => $validated['representative_position'],
            'representative_contact' => $validated['representative_contact'],
            'time_from' => $validated['time_from'],
            'time_to' => $validated['time_to'],
            'status' => \App\Enums\CertificateStatus::DRAFT,
            'submitted_by' => Auth::id(),
        ]);

        // Handle signature image
        if ($request->hasFile('signature_image')) {
            $path = $request->file('signature_image')
                ->store('certificates/signatures', 'public');
            $certificate->signature_path = $path;
        }

        // Handle attachment
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')
                ->store('certificates/attachments', 'public');
            $certificate->attachment_path = $path;
        }

        // Submit immediately if type is digital or hybrid
        if (in_array($validated['type'], ['digital', 'hybrid'], true)) {
            $certificate->status = \App\Enums\CertificateStatus::SUBMITTED;
        }

        $certificate->save();

        // Notify HR and admin if submitted
        if ($certificate->status === \App\Enums\CertificateStatus::SUBMITTED) {
            $hrs = \App\Models\User::role('hr')->get();
            foreach ($hrs as $hr) {
                $hr->notify(new \App\Notifications\CertificateSubmittedNotification($certificate));
            }
        }

        return $this->successResponse(
            new CertificateResource($certificate),
            'Certificate created successfully.',
            201
        );
    }

    public function update(
        Certificate $certificate,
        Request $request
    ): JsonResponse {
        $this->authorize('update', $certificate);

        if ($certificate->status !== \App\Enums\CertificateStatus::DRAFT) {
            return $this->errorResponse('Only draft certificates can be updated.');
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:physical,digital,hybrid'],
            'office_name' => ['sometimes', 'string', 'max:255'],
            'representative_name' => ['sometimes', 'string', 'max:255'],
            'representative_position' => ['sometimes', 'string', 'max:255'],
            'representative_contact' => ['nullable', 'string', 'max:100'],
            'time_from' => ['sometimes', 'date_format:H:i'],
            'time_to' => ['sometimes', 'date_format:H:i', 'after:time_from'],
            'signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $certificate->update($validated);

        // Handle signature image
        if ($request->hasFile('signature_image')) {
            // Delete old signature
            if ($certificate->signature_path) {
                Storage::disk('public')->delete($certificate->signature_path);
            }
            $path = $request->file('signature_image')
                ->store('certificates/signatures', 'public');
            $certificate->signature_path = $path;
        }

        // Handle attachment
        if ($request->hasFile('attachment')) {
            // Delete old attachment
            if ($certificate->attachment_path) {
                Storage::disk('public')->delete($certificate->attachment_path);
            }
            $path = $request->file('attachment')
                ->store('certificates/attachments', 'public');
            $certificate->attachment_path = $path;
        }

        $certificate->save();

        return $this->successResponse(
            new CertificateResource($certificate),
            'Certificate updated successfully.'
        );
    }

    public function destroy(Certificate $certificate): JsonResponse
    {
        $this->authorize('delete', $certificate);

        // Delete files
        if ($certificate->signature_path) {
            Storage::disk('public')->delete($certificate->signature_path);
        }
        if ($certificate->attachment_path) {
            Storage::disk('public')->delete($certificate->attachment_path);
        }

        $certificate->delete();

        return $this->successResponse(
            null,
            'Certificate deleted successfully.'
        );
    }

    public function verify(
        Certificate $certificate,
        VerifyCertificateRequest $request
    ): JsonResponse {
        $this->authorize('verify', $certificate);

        $certificate->verified_by = Auth::id();
        $certificate->verified_at = now();
        $certificate->status = \App\Enums\CertificateStatus::VERIFIED;
        $certificate->save();

        // Notify employee
        $certificate->passSlip->employee->user?->notify(
            new CertificateVerifiedNotification($certificate)
        );

        // Complete the pass slip if not already completed
        if ($certificate->passSlip->status === \App\Enums\PassSlipStatus::ARRIVED) {
            $certificate->passSlip->transitionTo(
                \App\Enums\PassSlipStatus::COMPLETED,
                Auth::user()
            );

            // Notify employee
            $certificate->passSlip->employee->user?->notify(
                new \App\Notifications\PassSlipCompletedNotification($certificate->passSlip)
            );
        }

        return $this->successResponse(
            new CertificateResource($certificate),
            'Certificate verified successfully.'
        );
    }
}