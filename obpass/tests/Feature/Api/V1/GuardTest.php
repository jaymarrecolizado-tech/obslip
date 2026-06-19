<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\PassSlipStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuardTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;
    protected User $guard;
    protected User $employee;
    protected Employee $employeeRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();

        $this->guard = User::factory()->create();
        $this->guard->assignRole('Guard');

        $this->employee = User::factory()->create(['department_id' => $this->department->id]);
        $this->employee->assignRole('Employee');

        $this->employeeRecord = Employee::factory()->create([
            'department_id' => $this->department->id,
        ]);
    }

    public function test_guard_can_search_slip_by_number(): void
    {
        $slip = PassSlip::factory()->approved()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->getJson('/api/v1/guard/search-slip?slip_number=' . $slip->slip_number);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['slip_number' => $slip->slip_number],
            ]);
    }

    public function test_guard_search_returns_404_for_nonexistent_slip(): void
    {
        Sanctum::actingAs($this->guard);

        $response = $this->getJson('/api/v1/guard/search-slip?slip_number=OB-2026-9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pass slip not found.',
            ]);
    }

    public function test_guard_search_requires_slip_number(): void
    {
        Sanctum::actingAs($this->guard);

        $response = $this->getJson('/api/v1/guard/search-slip');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slip_number']);
    }

    public function test_guard_can_scan_qr(): void
    {
        $slip = PassSlip::factory()->approved()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson('/api/v1/guard/scan-qr', [
            'qr_code' => $slip->qr_code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $slip->id],
            ]);
    }

    public function test_guard_scan_returns_404_for_invalid_qr(): void
    {
        Sanctum::actingAs($this->guard);

        $response = $this->postJson('/api/v1/guard/scan-qr', [
            'qr_code' => 'invalid-qr-code',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid QR code.',
            ]);
    }

    public function test_guard_scan_requires_qr_code(): void
    {
        Sanctum::actingAs($this->guard);

        $response = $this->postJson('/api/v1/guard/scan-qr', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['qr_code']);
    }

    public function test_guard_can_log_departure(): void
    {
        $slip = PassSlip::factory()->approved()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson("/api/v1/guard/log-departure/{$slip->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Departure logged.',
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'id' => $slip->id,
            'status' => PassSlipStatus::Departed->value,
        ]);
    }

    public function test_guard_cannot_log_departure_for_non_approved_slip(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson("/api/v1/guard/log-departure/{$slip->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only approved pass slips can be departed.',
            ]);
    }

    public function test_guard_can_log_arrival(): void
    {
        $slip = PassSlip::factory()->departed()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson("/api/v1/guard/log-arrival/{$slip->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Arrival logged.',
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'id' => $slip->id,
            'status' => PassSlipStatus::Arrived->value,
        ]);
    }

    public function test_guard_cannot_log_arrival_for_non_departed_slip(): void
    {
        $slip = PassSlip::factory()->approved()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson("/api/v1/guard/log-arrival/{$slip->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only departed pass slips can have arrival logged.',
            ]);
    }

    public function test_guard_cannot_approve_slips(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/approve");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_guard_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/guard/search-slip?slip_number=OB-2026-0001');

        $response->assertStatus(401);
    }

    public function test_duration_calculation_on_arrival(): void
    {
        $departureTime = now()->subHours(3);
        $slip = PassSlip::factory()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'status' => PassSlipStatus::Departed,
            'departure_time' => $departureTime,
        ]);

        Sanctum::actingAs($this->guard);

        $response = $this->postJson("/api/v1/guard/log-arrival/{$slip->id}");

        $response->assertStatus(200);

        $slip->refresh();
        $this->assertNotNull($slip->duration_hours);
        $this->assertGreaterThan(0, (float) $slip->duration_hours);
    }

    public function test_non_guard_cannot_log_departure(): void
    {
        $slip = PassSlip::factory()->approved()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee); // Employee, not Guard

        $response = $this->postJson("/api/v1/guard/log-departure/{$slip->id}");

        $response->assertStatus(403);
    }

    public function test_non_guard_cannot_scan_qr(): void
    {
        Sanctum::actingAs($this->employee); // Employee, not Guard

        $response = $this->postJson('/api/v1/guard/scan-qr', ['qr_code' => 'anything']);

        $response->assertStatus(403);
    }
}
