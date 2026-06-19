<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PassSlipStatus;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PassSlipPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['pass_slip.view_all', 'pass_slip.view_own']);
    }

    public function view(User $user, PassSlip $passSlip): bool
    {
        // Admin, HR see all; Guard sees today only
        if ($user->hasPermissionTo('pass_slip.view_all')) {
            if ($user->hasRole('Guard')) {
                return $passSlip->date->isToday();
            }
            return true;
        }

        // Supervisor sees all slips in their department
        if ($user->hasRole('Supervisor') && $passSlip->department_id === $user->department_id) {
            return true;
        }

        // Employee sees own slips (as creator, supervisor, or approver)
        return $passSlip->creator_id === $user->id
            || $passSlip->supervisor_id === $user->id
            || $passSlip->approver_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('pass_slip.create');
    }

    public function update(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.edit_own')) {
            return false;
        }

        // Can only edit own drafts
        return $passSlip->creator_id === $user->id
            && $passSlip->status === PassSlipStatus::Draft;
    }

    public function delete(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.cancel_own')) {
            return false;
        }

        // Employee can cancel only Draft or Submitted slips they created
        return $passSlip->creator_id === $user->id
            && in_array($passSlip->status, [PassSlipStatus::Draft, PassSlipStatus::Submitted]);
    }

    public function approve(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.approve')) {
            return false;
        }

        // Supervisor can only approve submitted slips from their own department
        if ($passSlip->status !== PassSlipStatus::Submitted) {
            return false;
        }

        return $passSlip->department_id === $user->department_id;
    }

    public function returnSlip(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.return')) {
            return false;
        }

        if ($passSlip->status !== PassSlipStatus::Submitted) {
            return false;
        }

        return $passSlip->department_id === $user->department_id;
    }

    public function logDeparture(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.log_departure')) {
            return false;
        }

        return $passSlip->status === PassSlipStatus::Approved;
    }

    public function logArrival(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.log_arrival')) {
            return false;
        }

        return $passSlip->status === PassSlipStatus::Departed;
    }

    public function scanQr(User $user, PassSlip $passSlip): bool
    {
        return $user->hasPermissionTo('pass_slip.scan_qr');
    }

    public function forceDelete(User $user): bool
    {
        return false; // Soft deletes only
    }
}
