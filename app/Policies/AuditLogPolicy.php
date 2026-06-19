<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit_logs.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->can('audit_logs.view');
    }

    public function export(User $user): bool
    {
        return $user->can('audit_logs.view');
    }
}