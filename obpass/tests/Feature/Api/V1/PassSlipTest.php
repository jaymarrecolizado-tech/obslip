<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PassSlipTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;
    protected User $employee;
    protected User $supervisor;
    protected User $hr;
    protected User $admin;
    protected Employee $employeeRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();
        $this->employee = User::factory()->create(['department_id' => $this->department->id]);
        $this->employee->assignRole('Employee');

        $this->supervisor = User::factory()->create(['department_id' => $this->department->id]);
        $this->supervisor->assignRole('Supervisor');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('HR');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->employeeRecord = Employee::factory()->create([
            'department_id' => $this->department->id,
        ]);
    }

    public function test_employee_can_create_pass_slip(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/pass-slips', [
            'date' => now()->toDateString(),
            'purpose' => 'Official business meeting',
            'transport_type' => TransportType::PersonalVehicle->value,
            'department_id' => $this->department->id,
            'employees' => [$this->employeeRecord->id],
            'supervisor_id' => $this->supervisor->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip created.',
            ])
            ->assertJsonStructure([
                'data' => ['id', 'slip_number', 'date', 'purpose', 'status'],
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'status' => PassSlipStatus::Draft->value,
            'creator_id' => $this->employee->id,
        ]);
    }

    public function test_create_pass_slip_validates_required_fields(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/pass-slips', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date', 'purpose', 'transport_type', 'department_id', 'employees']);
    }

    public function test_create_pass_slip_validates_employees_exist(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/pass-slips', [
            'date' => now()->toDateString(),
            'purpose' => 'Test',
            'transport_type' => TransportType::PersonalVehicle->value,
            'department_id' => $this->department->id,
            'employees' => ['nonexistent-id'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employees.0']);
    }

    public function test_employee_can_view_own_pass_slips(): void
    {
        $slip = PassSlip::factory()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson('/api/v1/pass-slips');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_employee_can_view_own_pass_slip_detail(): void
    {
        $slip = PassSlip::factory()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson("/api/v1/pass-slips/{$slip->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $slip->id],
            ]);
    }

    public function test_employee_can_update_own_draft_slip(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->putJson("/api/v1/pass-slips/{$slip->id}", [
            'date' => now()->toDateString(),
            'purpose' => 'Updated purpose',
            'transport_type' => TransportType::PersonalVehicle->value,
            'department_id' => $this->department->id,
            'employees' => [$this->employeeRecord->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip updated.',
            ]);
    }

    public function test_employee_cannot_update_submitted_slip(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->putJson("/api/v1/pass-slips/{$slip->id}", [
            'date' => now()->toDateString(),
            'purpose' => 'Updated purpose',
            'transport_type' => TransportType::PersonalVehicle->value,
            'department_id' => $this->department->id,
            'employees' => [$this->employeeRecord->id],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot edit a pass slip in this status.',
            ]);
    }

    public function test_employee_can_delete_own_draft_slip(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->deleteJson("/api/v1/pass-slips/{$slip->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip deleted.',
            ]);

        $this->assertSoftDeleted('pass_slips', ['id' => $slip->id]);
    }

    public function test_employee_cannot_delete_submitted_slip(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->deleteJson("/api/v1/pass-slips/{$slip->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only draft pass slips can be deleted.',
            ]);
    }

    public function test_employee_can_submit_draft_slip(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip submitted for approval.',
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'id' => $slip->id,
            'status' => PassSlipStatus::Submitted->value,
        ]);
    }

    public function test_employee_cannot_submit_already_submitted_slip(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/submit");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only draft pass slips can be submitted.',
            ]);
    }

    public function test_supervisor_can_approve_submitted_slip(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        Sanctum::actingAs($this->supervisor);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip approved.',
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'id' => $slip->id,
            'status' => PassSlipStatus::Approved->value,
        ]);
    }

    public function test_supervisor_cannot_approve_draft_slip(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->supervisor);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/approve");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only submitted pass slips can be approved.',
            ]);
    }

    public function test_supervisor_can_return_submitted_slip(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        Sanctum::actingAs($this->supervisor);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/return", [
            'returned_reason' => 'Missing details in purpose',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip returned.',
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'id' => $slip->id,
            'status' => PassSlipStatus::Returned->value,
            'returned_reason' => 'Missing details in purpose',
        ]);
    }

    public function test_return_requires_reason(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->supervisor);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/return", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['returned_reason']);
    }

    public function test_employee_can_cancel_own_draft_slip(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pass slip cancelled.',
            ]);

        $this->assertDatabaseHas('pass_slips', [
            'id' => $slip->id,
            'status' => PassSlipStatus::Cancelled->value,
        ]);
    }

    public function test_employee_can_cancel_own_submitted_slip(): void
    {
        $slip = PassSlip::factory()->submitted()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/cancel");

        $response->assertStatus(200);
    }

    public function test_cannot_cancel_approved_slip(): void
    {
        $slip = PassSlip::factory()->approved()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson("/api/v1/pass-slips/{$slip->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot cancel this pass slip.',
            ]);
    }

    public function test_pass_slip_auto_generates_slip_number(): void
    {
        $slip = PassSlip::factory()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        $this->assertNotEmpty($slip->slip_number);
        $this->assertMatchesRegularExpression('/^OB-\d{4}-\d{4}$/', $slip->slip_number);
    }

    public function test_pass_slip_auto_generates_qr_code(): void
    {
        $slip = PassSlip::factory()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        $this->assertNotEmpty($slip->qr_code);
    }

    public function test_full_lifecycle_draft_to_completed(): void
    {
        // Create
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        // Submit
        $this->assertTrue($slip->submit());
        $this->assertEquals(PassSlipStatus::Submitted, $slip->fresh()->status);

        // Approve
        $this->assertTrue($slip->approve($this->supervisor));
        $this->assertEquals(PassSlipStatus::Approved, $slip->fresh()->status);

        // Depart
        $this->assertTrue($slip->depart());
        $this->assertEquals(PassSlipStatus::Departed, $slip->fresh()->status);

        // Arrive
        $this->assertTrue($slip->arrive());
        $this->assertEquals(PassSlipStatus::Arrived, $slip->fresh()->status);

        // Submit Certificate
        $this->assertTrue($slip->submitCertificate());
        $this->assertEquals(PassSlipStatus::CertificateSubmitted, $slip->fresh()->status);

        // Verify
        $this->assertTrue($slip->verify());
        $this->assertEquals(PassSlipStatus::Verified, $slip->fresh()->status);

        // Complete
        $this->assertTrue($slip->complete());
        $this->assertEquals(PassSlipStatus::Completed, $slip->fresh()->status);
    }

    public function test_cannot_skip_workflow_steps(): void
    {
        $slip = PassSlip::factory()->draft()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        // Cannot approve a draft
        $this->assertFalse($slip->approve($this->supervisor));

        // Cannot depart a draft
        $this->assertFalse($slip->depart());

        // Cannot arrive a draft
        $this->assertFalse($slip->arrive());
    }

    public function test_unauthenticated_cannot_access_pass_slips(): void
    {
        $response = $this->getJson('/api/v1/pass-slips');

        $response->assertStatus(401);
    }

    public function test_employee_cannot_view_other_employee_slip(): void
    {
        $otherEmployee = User::factory()->create();
        $otherEmployee->assignRole('Employee');

        $slip = PassSlip::factory()->create([
            'creator_id' => $otherEmployee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson("/api/v1/pass-slips/{$slip->id}");

        $response->assertStatus(403);
    }

    public function test_hr_can_view_all_pass_slips(): void
    {
        PassSlip::factory()->count(3)->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->hr);

        $response = $this->getJson('/api/v1/pass-slips');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_all_pass_slips(): void
    {
        PassSlip::factory()->count(3)->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/pass-slips');

        $response->assertStatus(200);
    }

    public function test_emergency_slip(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/pass-slips', [
            'date' => now()->toDateString(),
            'purpose' => 'Emergency field visit',
            'transport_type' => TransportType::CompanyVehicle->value,
            'department_id' => $this->department->id,
            'employees' => [$this->employeeRecord->id],
            'is_emergency' => true,
        ]);

        $response->assertStatus(201);

        $slip = PassSlip::where('creator_id', $this->employee->id)->first();
        $this->assertTrue($slip->is_emergency);
    }

    public function test_pass_slip_with_vehicle(): void
    {
        $vehicle = \App\Models\Vehicle::factory()->create([
            'owner_id' => $this->employeeRecord->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/pass-slips', [
            'date' => now()->toDateString(),
            'purpose' => 'Vehicle trip',
            'transport_type' => TransportType::CompanyVehicle->value,
            'department_id' => $this->department->id,
            'employees' => [$this->employeeRecord->id],
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertStatus(201);

        $slip = PassSlip::where('creator_id', $this->employee->id)->first();
        $this->assertEquals($vehicle->id, $slip->vehicle_id);
    }
}
