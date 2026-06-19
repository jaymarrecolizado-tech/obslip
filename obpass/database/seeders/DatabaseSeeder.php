<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingSeeder::class,
        ]);

        // Create default admin user
        $adminRole = Role::findByName('Admin', 'web');

        $admin = User::firstOrCreate(
            ['email' => 'admin@dict.gov.ph'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'is_active' => true,
                'position' => 'System Administrator',
            ]
        );

        $admin->syncRoles($adminRole);
    }
}
