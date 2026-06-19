<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (empty($tableNames)) {
            return;
        }

        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $teams && $table->unsignedBigInteger($columnNames['team_foreign_key'] ?? 'team_id')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->uuid($columnNames['permission_foreign_key']);
            $table->uuid($columnNames['model_foreign_key']);
            $table->string('model_type');
            $teams && $table->unsignedBigInteger($columnNames['team_foreign_key'] ?? 'team_id')->nullable();
            $table->primary([
                $columnNames['permission_foreign_key'],
                $columnNames['model_foreign_key'],
                'model_type',
                $columnNames['team_foreign_key'],
            ], 'model_has_permissions_permission_model_type_primary');

            $table->foreign($columnNames['permission_foreign_key'])
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->uuid($columnNames['role_foreign_key']);
            $table->uuid($columnNames['model_foreign_key']);
            $table->string('model_type');
            $teams && $table->unsignedBigInteger($columnNames['team_foreign_key'] ?? 'team_id')->nullable();
            $table->primary([
                $columnNames['role_foreign_key'],
                $columnNames['model_foreign_key'],
                'model_type',
                $columnNames['team_foreign_key'],
            ], 'model_has_roles_role_model_type_primary');

            $table->foreign($columnNames['role_foreign_key'])
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->uuid($columnNames['permission_foreign_key']);
            $table->uuid($columnNames['role_foreign_key']);

            $table->foreign($columnNames['permission_foreign_key'])
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($columnNames['role_foreign_key'])
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([
                $columnNames['permission_foreign_key'],
                $columnNames['role_foreign_key'],
            ], 'role_has_permissions_permission_role_primary');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return;
        }

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};