<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employees.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.edit');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function import(User $user): bool
    {
        return $user->can('employees.create') || $user->can('employees.manage');
    }

    public function export(User $user): bool
    {
        return $user->can('employees.view');
    }
}