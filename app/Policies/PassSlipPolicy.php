<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PassSlip;
use App\Models\User;
use App\Enums\PassSlipStatus;

class PassSlipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pass_slips.view_all') || $user->can('pass_slips.view_today_only');
    }

    public function view(User $user, PassSlip $passSlip): bool
    {
        // Admin and HR can view any slip
        if ($user->can('pass_slips.view_all')) {
            return true;
        }

        // Guard can only view today's slips with approved/departed status
        if ($user->can('pass_slips.view_today_only')) {
            return $passSlip->date->isToday()
                && in_array($passSlip->status, [PassSlipStatus::APPROVED, PassSlipStatus::DEPARTED], true);
        }

        // Users can view their own slips
        if ($user->can('pass_slips.view_own')) {
            return $passSlip->creator_id === $user->id
                || $passSlip->employee_id === optional($user->employee)->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('pass_slips.create');
    }

    public function update(User $user, PassSlip $passSlip): bool
    {
        // Can only edit draft slips
        if ($passSlip->status !== PassSlipStatus::DRAFT) {
            return false;
        }

        // Creator can edit their own drafts
        if ($passSlip->creator_id === $user->id) {
            return $user->can('pass_slips.edit_own_draft');
        }

        // Admin can edit any draft
        return $user->can('pass_slips.edit_own_draft') && $user->hasRole('admin');
    }

    public function delete(User $user, PassSlip $passSlip): bool
    {
        return $user->can('pass_slips.delete');
    }

    public function submit(User $user, PassSlip $passSlip): bool
    {
        // Must be in draft status
        if ($passSlip->status !== PassSlipStatus::DRAFT) {
            return false;
        }

        // Only creator can submit
        return $passSlip->creator_id === $user->id;
    }

    public function approve(User $user, PassSlip $passSlip): bool
    {
        if (!$user->can('pass_slips.approve')) {
            return false;
        }

        return $passSlip->canBeApprovedBy($user);
    }

    public function return(User $user, PassSlip $passSlip): bool
    {
        if (!$user->can('pass_slips.return')) {
            return false;
        }

        return $passSlip->canBeReturnedBy($user);
    }

    public function cancel(User $user, PassSlip $passSlip): bool
    {
        if (!$user->can('pass_slips.cancel_own')) {
            return false;
        }

        return $passSlip->canBeCancelledBy($user);
    }

    public function downloadPdf(User $user, PassSlip $passSlip): bool
    {
        return $this->view($user, $passSlip);
    }

    public function logDeparture(User $user, PassSlip $passSlip): bool
    {
        if (!$user->can('guard.log_departure')) {
            return false;
        }

        return $passSlip->status === PassSlipStatus::APPROVED;
    }

    public function logArrival(User $user, PassSlip $passSlip): bool
    {
        if (!$user->can('guard.log_arrival')) {
            return false;
        }

        return $passSlip->status === PassSlipStatus::DEPARTED;
    }

    public function viewCertificate(User $user, PassSlip $passSlip): bool
    {
        return $user->can('certificates.view')
            && $this->view($user, $passSlip);
    }
}