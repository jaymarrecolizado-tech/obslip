<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pass_slips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slip_number')->unique();
            $table->date('date');
            $table->text('purpose');
            $table->enum('transport_type', ['company_vehicle', 'personal_vehicle', 'public_transport', 'on_foot'])->default('company_vehicle');
            $table->enum('status', [
                'draft', 'submitted', 'returned', 'approved', 'departed', 
                'arrived', 'certificate_submitted', 'verified', 'completed', 'cancelled'
            ])->default('draft');
            $table->uuid('creator_id');
            $table->uuid('supervisor_id')->nullable();
            $table->uuid('approver_id')->nullable();
            $table->uuid('employee_id');
            $table->uuid('department_id');
            $table->uuid('vehicle_id')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('returned_reason')->nullable();
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->boolean('is_emergency')->default(false);
            $table->string('pdf_path')->nullable();
            $table->string('qr_code')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('restrict');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('restrict');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');

            $table->index('status');
            $table->index('date');
            $table->index('employee_id');
            $table->index('department_id');
            $table->index('qr_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pass_slips');
    }
};