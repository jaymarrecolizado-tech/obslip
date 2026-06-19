<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certificate;
use App\Enums\CertificateStatus;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('certificates.view');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        // Can view if can view pass slip
        return $user->can('pass_slips.view_all')
            || $user->can('certificates.view')
            && ($certificate->passSlip->creator_id === $user->id
                || $certificate->submitted_by === $user->id);
    }

    public function create(User $user, Certificate $certificate = null): bool
    {
        // Can submit certificate for own pass slips
        if ($certificate && $certificate->passSlip->creator_id !== $user->id) {
            return false;
        }

        return $user->can('certificates.submit');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        // Can only update draft certificates
        if ($certificate->status !== CertificateStatus::DRAFT) {
            return false;
        }

        // Only submitter can edit
        return $certificate->submitted_by === $user->id;
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        // Can only delete draft certificates
        if ($certificate->status !== CertificateStatus::DRAFT) {
            return false;
        }

        // Only submitter can delete
        return $certificate->submitted_by === $user->id;
    }

    public function verify(User $user, Certificate $certificate): bool
    {
        if (!$user->can('certificates.verify')) {
            return false;
        }

        return $certificate->status === CertificateStatus::SUBMITTED;
    }

    public function downloadAttachment(User $user, Certificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function uploadAttachment(User $user, Certificate $certificate): bool
    {
        // Can upload if draft or just submitted
        if (!in_array($certificate->status, [CertificateStatus::DRAFT, CertificateStatus::SUBMITTED], true)) {
            return false;
        }

        // Only submitter can upload
        return $certificate->submitted_by === $user->id;
    }

    public function submit(User $user, Certificate $certificate): bool
    {
        if ($certificate->status !== CertificateStatus::DRAFT) {
            return false;
        }

        return $certificate->submitted_by === $user->id;
    }
}