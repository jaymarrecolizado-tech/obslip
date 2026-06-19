<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
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
        return $user->hasPermissionTo('manage.departments');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('manage.departments');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage.departments');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('manage.departments');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('manage.departments');
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
