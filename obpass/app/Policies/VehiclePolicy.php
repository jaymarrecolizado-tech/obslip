<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiclePolicy
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
        return $user->hasPermissionTo('manage.vehicles');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('manage.vehicles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage.vehicles');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('manage.vehicles');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('manage.vehicles');
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
