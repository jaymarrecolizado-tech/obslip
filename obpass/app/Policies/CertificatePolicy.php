<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificatePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['certificate.submit', 'certificate.verify']);
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->hasAnyPermission(['certificate.submit', 'certificate.verify']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('certificate.submit');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $certificate->submitted_by === $user->id
            && $certificate->status->value === 'draft';
    }

    public function verify(User $user, Certificate $certificate): bool
    {
        return $user->hasPermissionTo('certificate.verify');
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
