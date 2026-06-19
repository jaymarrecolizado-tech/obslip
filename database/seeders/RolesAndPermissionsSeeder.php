<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();

        // Define permissions
        $permissions = [
            // Pass Slip permissions
            'pass_slips.create',
            'pass_slips.edit_own_draft',
            'pass_slips.cancel_own',
            'pass_slips.approve',
            'pass_slips.return',
            'pass_slips.view_all',
            'pass_slips.view_own',
            'pass_slips.view_today_only',
            'pass_slips.delete',

            // Guard permissions
            'guard.log_departure',
            'guard.log_arrival',
            'guard.scan_qr',
            'guard.search_slip',

            // User management
            'users.manage',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Employee management
            'employees.manage',
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',

            // Vehicle management
            'vehicles.manage',
            'vehicles.view',
            'vehicles.create',
            'vehicles.edit',
            'vehicles.delete',

            // Department management
            'departments.manage',
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // Audit and reports
            'audit_logs.view',
            'reports.view',
            'reports.generate',
            'reports.export',

            // Certificates
            'certificates.submit',
            'certificates.verify',
            'certificates.view',
            'certificates.manage',
            'certificates.edit',
            'certificates.delete',

            // System management
            'settings.manage',
            'settings.view',
            'notifications.send',
            'notifications.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Define role permissions mapping
        $rolePermissions = [
            'admin' => [
                'pass_slips.*',
                'guard.*',
                'users.*',
                'employees.*',
                'vehicles.*',
                'departments.*',
                'audit_logs.view',
                'reports.*',
                'certificates.*',
                'settings.*',
                'notifications.*',
            ],
            'hr' => [
                'pass_slips.create',
                'pass_slips.view_all',
                'pass_slips.view_own',
                'pass_slips.cancel_own',
                'pass_slips.edit_own_draft',
                'pass_slips.view_today_only',
                'employees.*',
                'vehicles.*',
                'audit_logs.view',
                'reports.view',
                'reports.export',
                'certificates.submit',
                'certificates.verify',
                'certificates.view',
                'certificates.edit',
                'settings.view',
            ],
            'supervisor' => [
                'pass_slips.create',
                'pass_slips.view_own',
                'pass_slips.view_all',
                'pass_slips.approve',
                'pass_slips.return',
                'pass_slips.cancel_own',
                'pass_slips.edit_own_draft',
                'pass_slips.view_today_only',
                'certificates.submit',
                'certificates.view',
                'certificates.edit',
                'reports.view',
                'reports.export',
            ],
            'guard' => [
                'pass_slips.view_today_only',
                'guard.log_departure',
                'guard.log_arrival',
                'guard.scan_qr',
                'guard.search_slip',
            ],
            'employee' => [
                'pass_slips.create',
                'pass_slips.view_own',
                'pass_slips.cancel_own',
                'pass_slips.edit_own_draft',
                'certificates.submit',
                'certificates.view',
                'certificates.edit',
            ],
        ];

        // Create roles and assign permissions
        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            // Convert wildcard permissions to actual permissions
            $actualPermissions = [];
            foreach ($perms as $perm) {
                if (str_ends_with($perm, '.*')) {
                    $prefix = str_replace('.*', '', $perm);
                    foreach ($permissions as $fullPerm) {
                        if (str_starts_with($fullPerm, $prefix)) {
                            $actualPermissions[] = $fullPerm;
                        }
                    }
                } else {
                    if (in_array($perm, $permissions, true)) {
                        $actualPermissions[] = $perm;
                    }
                }
            }

            $role->syncPermissions($actualPermissions);
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}