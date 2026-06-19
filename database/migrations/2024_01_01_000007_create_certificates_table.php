<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pass_slip_id');
            $table->enum('type', ['physical', 'digital', 'hybrid'])->default('physical');
            $table->string('office_name');
            $table->string('representative_name');
            $table->string('representative_position');
            $table->string('representative_contact')->nullable();
            $table->time('time_from');
            $table->time('time_to');
            $table->string('signature_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['draft', 'submitted', 'verified'])->default('draft');
            $table->uuid('submitted_by');
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pass_slip_id')->references('id')->on('pass_slips')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            $table->index('status');
            $table->index('pass_slip_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};