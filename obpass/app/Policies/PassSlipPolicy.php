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

        // Ownership only; status enforcement (draft-only) is handled by the controller.
        return $passSlip->creator_id === $user->id;
    }

    public function delete(User $user, PassSlip $passSlip): bool
    {
        if (!$user->hasPermissionTo('pass_slip.cancel_own')) {
            return false;
        }

        // Ownership only; status enforcement (draft-only) is handled by the controller.
        return $passSlip->creator_id === $user->id;
    }

    public function submit(User $user, PassSlip $passSlip): bool
    {
        // The creator, an admin, or the slip's department supervisor may submit/resubmit.
        return $passSlip->creator_id === $user->id
            || $user->hasRole('Admin')
            || ($user->hasRole('Supervisor') && $passSlip->department_id === $user->department_id);
    }

    public function cancel(User $user, PassSlip $passSlip): bool
    {
        // Admins may cancel any non-terminal slip.
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Supervisors may cancel slips in their own department.
        if ($user->hasRole('Supervisor') && $passSlip->department_id === $user->department_id) {
            return true;
        }

        // Employees may cancel their own slips (status scope enforced by the model).
        return $user->hasPermissionTo('pass_slip.cancel_own')
            && $passSlip->creator_id === $user->id;
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

        // Status enforcement (submitted-only) is handled by the model.
        return $passSlip->department_id === $user->department_id;
    }

    public function logDeparture(User $user, PassSlip $passSlip): bool
    {
        // Guard-only; status enforcement (approved-only) is handled by the model.
        return $user->hasPermissionTo('pass_slip.log_departure');
    }

    public function logArrival(User $user, PassSlip $passSlip): bool
    {
        // Guard-only; status enforcement (departed-only) is handled by the model.
        return $user->hasPermissionTo('pass_slip.log_arrival');
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
