<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Permissions (20) ---
        $permissions = [
            // Pass Slip permissions
            'pass_slip.create',
            'pass_slip.edit_own',
            'pass_slip.cancel_own',
            'pass_slip.approve',
            'pass_slip.return',
            'pass_slip.view_all',
            'pass_slip.view_own',
            'pass_slip.log_departure',
            'pass_slip.log_arrival',
            'pass_slip.scan_qr',

            // Management permissions
            'manage.users',
            'manage.employees',
            'manage.vehicles',
            'manage.departments',

            // Reporting & Audit
            'view.audit_logs',
            'view.reports',

            // Certificate
            'certificate.submit',
            'certificate.verify',

            // Settings & Notifications
            'manage.settings',
            'manage.notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // --- Roles & Permission Assignments ---
        $rolePermissions = [
            'Admin' => [
                'pass_slip.create',
                'pass_slip.edit_own',
                'pass_slip.cancel_own',
                'pass_slip.view_all',
                'pass_slip.view_own',
                'manage.users',
                'manage.employees',
                'manage.vehicles',
                'manage.departments',
                'view.audit_logs',
                'view.reports',
                'certificate.submit',
                'certificate.verify',
                'manage.settings',
                'manage.notifications',
            ],

            'HR' => [
                'pass_slip.create',
                'pass_slip.edit_own',
                'pass_slip.cancel_own',
                'pass_slip.view_all',
                'pass_slip.view_own',
                'manage.employees',
                'manage.vehicles',
                'view.audit_logs',
                'view.reports',
                'certificate.submit',
                'certificate.verify',
                'manage.notifications',
            ],

            'Supervisor' => [
                'pass_slip.create',
                'pass_slip.edit_own',
                'pass_slip.cancel_own',
                'pass_slip.approve',
                'pass_slip.return',
                'pass_slip.view_own',
                'view.reports',
                'certificate.submit',
            ],

            'Guard' => [
                'pass_slip.view_all',   // scoped to today only
                'pass_slip.view_own',
                'pass_slip.log_departure',
                'pass_slip.log_arrival',
                'pass_slip.scan_qr',
            ],

            'Employee' => [
                'pass_slip.create',
                'pass_slip.edit_own',
                'pass_slip.cancel_own',
                'pass_slip.view_own',
                'view.reports',
                'certificate.submit',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
