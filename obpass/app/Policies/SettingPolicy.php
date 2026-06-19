<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingPolicy
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
        return $user->hasPermissionTo('manage.settings');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('manage.settings');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage.settings');
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('manage.settings');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('manage.settings');
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
