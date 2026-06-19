<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
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
        return $user->hasPermissionTo('manage.employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('manage.employees');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage.employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('manage.employees');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('manage.employees');
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
