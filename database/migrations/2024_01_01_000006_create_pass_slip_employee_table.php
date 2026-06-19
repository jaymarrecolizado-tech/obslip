<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pass_slip_employee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pass_slip_id');
            $table->uuid('employee_id');
            $table->timestamps();

            $table->foreign('pass_slip_id')->references('id')->on('pass_slips')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->unique(['pass_slip_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pass_slip_employee');
    }
};