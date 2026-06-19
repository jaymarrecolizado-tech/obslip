<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pass_slips', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'status']);
        });

        Schema::table('pass_slips', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('pass_slips', function (Blueprint $table) {
            $table->uuid('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->index(['employee_id', 'status']);
        });
    }
};
