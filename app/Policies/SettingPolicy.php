<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage') || $user->hasRole('admin');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('settings.manage') || $user->hasRole('admin');
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->can('settings.manage') && $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage') && $user->hasRole('admin');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->can('settings.manage') && $user->hasRole('admin');
    }
}