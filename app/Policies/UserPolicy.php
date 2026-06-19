<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        // Cannot delete own account
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('users.delete');
    }

    public function manageRoles(User $user, User $model): bool
    {
        return $user->can('users.manage') && $user->hasRole('admin');
    }

    public function managePermissions(User $user, User $model): bool
    {
        return $user->can('users.manage') && $user->hasRole('admin');
    }

    public function toggleActive(User $user, User $model): bool
    {
        // Cannot deactivate own account
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('users.manage');
    }
}