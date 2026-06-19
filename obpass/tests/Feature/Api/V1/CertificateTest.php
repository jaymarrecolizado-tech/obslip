<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PassSlipStatus;
use App\Models\Certificate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;
    protected User $employee;
    protected User $hr;
    protected Employee $employeeRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();

        $this->employee = User::factory()->create(['department_id' => $this->department->id]);
        $this->employee->assignRole('Employee');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('HR');

        $this->employeeRecord = Employee::factory()->create([
            'department_id' => $this->department->id,
        ]);
    }

    protected function createArrivedSlip(): PassSlip
    {
        return PassSlip::factory()->arrived()->create([
            'creator_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);
    }

    public function test_employee_can_submit_certificate(): void
    {
        $slip = $this->createArrivedSlip();

        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/certificates', [
            'pass_slip_id' => $slip->id,
            'type' => CertificateType::Physical->value,
            'office_name' => 'DICT Regional Office',
            'representative_name' => 'Juan Dela Cruz',
            'representative_position' => 'Regional Director',
            'representative_contact' => '09171234567',
            'time_from' => '08:00',
            'time_to' => '17:00',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Certificate submitted.',
            ]);

        $this->assertDatabaseHas('certificates', [
            'pass_slip_id' => $slip->id,
            'status' => CertificateStatus::Submitted->value,
        ]);

        // Pass slip should advance to CertificateSubmitted
        $slip->refresh();
        $this->assertEquals(PassSlipStatus::CertificateSubmitted, $slip->status);
    }

    public function test_submit_certificate_validates_required_fields(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/certificates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'pass_slip_id', 'type', 'office_name',
                'representative_name', 'representative_position',
                'time_from', 'time_to',
            ]);
    }

    public function test_hr_can_verify_certificate(): void
    {
        $slip = $this->createArrivedSlip();
        $certificate = Certificate::factory()->submitted()->create([
            'pass_slip_id' => $slip->id,
        ]);
        $slip->submitCertificate();

        Sanctum::actingAs($this->hr);

        $response = $this->postJson("/api/v1/certificates/{$certificate->id}/verify");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Certificate verified.',
            ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => CertificateStatus::Verified->value,
        ]);

        // Pass slip should advance to Verified
        $slip->refresh();
        $this->assertEquals(PassSlipStatus::Verified, $slip->status);
    }

    public function test_cannot_verify_already_verified_certificate(): void
    {
        $slip = $this->createArrivedSlip();
        $certificate = Certificate::factory()->verified()->create([
            'pass_slip_id' => $slip->id,
        ]);

        Sanctum::actingAs($this->hr);

        $response = $this->postJson("/api/v1/certificates/{$certificate->id}/verify");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only submitted certificates can be verified.',
            ]);
    }

    public function test_employee_can_view_certificates(): void
    {
        $slip = $this->createArrivedSlip();
        Certificate::factory()->submitted()->create([
            'pass_slip_id' => $slip->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson('/api/v1/certificates');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_employee_can_view_certificate_detail(): void
    {
        $slip = $this->createArrivedSlip();
        $certificate = Certificate::factory()->submitted()->create([
            'pass_slip_id' => $slip->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson("/api/v1/certificates/{$certificate->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $certificate->id],
            ]);
    }

    public function test_certificate_pdf_requires_submitted_status(): void
    {
        $slip = $this->createArrivedSlip();
        $certificate = Certificate::factory()->draft()->create([
            'pass_slip_id' => $slip->id,
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson("/api/v1/certificates/{$certificate->id}/pdf");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Certificate must be submitted before generating PDF.',
            ]);
    }

    public function test_unauthenticated_cannot_access_certificates(): void
    {
        $response = $this->getJson('/api/v1/certificates');

        $response->assertStatus(401);
    }

    public function test_full_certificate_lifecycle(): void
    {
        $slip = $this->createArrivedSlip();

        // Create certificate
        $certificate = Certificate::factory()->draft()->create([
            'pass_slip_id' => $slip->id,
        ]);

        // Submit
        $this->assertTrue($certificate->submit($this->employee));
        $this->assertEquals(CertificateStatus::Submitted, $certificate->fresh()->status);

        // Verify
        $this->assertTrue($certificate->verify($this->hr));
        $this->assertEquals(CertificateStatus::Verified, $certificate->fresh()->status);
    }

    public function test_cannot_submit_already_submitted_certificate(): void
    {
        $slip = $this->createArrivedSlip();
        $certificate = Certificate::factory()->submitted()->create([
            'pass_slip_id' => $slip->id,
        ]);

        $this->assertFalse($certificate->submit($this->employee));
    }

    public function test_cannot_verify_unsubmitted_certificate(): void
    {
        $slip = $this->createArrivedSlip();
        $certificate = Certificate::factory()->draft()->create([
            'pass_slip_id' => $slip->id,
        ]);

        $this->assertFalse($certificate->verify($this->hr));
    }
}
