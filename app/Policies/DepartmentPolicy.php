<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('departments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('departments.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('departments.edit');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('departments.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('departments.manage');
    }

    public function setHead(User $user, Department $department): bool
    {
        return $user->hasRole('admin');
    }

    public function viewEmployees(User $user, Department $department): bool
    {
        return $user->can('employees.view');
    }

    public function viewPassSlips(User $user, Department $department): bool
    {
        return $user->can('pass_slips.view_all');
    }
}