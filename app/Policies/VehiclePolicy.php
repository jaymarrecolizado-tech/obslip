<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vehicles.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.edit');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('vehicles.manage');
    }

    public function assignOwner(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.manage');
    }
}