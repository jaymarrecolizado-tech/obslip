<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $department = Department::factory()->create();
        $this->employee = User::factory()->create(['department_id' => $department->id]);
        $this->employee->assignRole('Employee');
    }

    public function test_employee_can_list_notifications(): void
    {
        // Create some notifications
        $this->employee->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['title' => 'Test Notification', 'message' => 'Hello'],
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_employee_can_mark_notification_as_read(): void
    {
        $notification = $this->employee->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['title' => 'Test', 'message' => 'Hello'],
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $this->employee->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['title' => 'Test 1', 'message' => 'Hello 1'],
        ]);
        $this->employee->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['title' => 'Test 2', 'message' => 'Hello 2'],
        ]);

        Sanctum::actingAs($this->employee);

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All notifications marked as read.',
            ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->employee->id,
            'read_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_mark_nonexistent_notification_returns_404(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->putJson('/api/v1/notifications/nonexistent-id/read');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Notification not found.',
            ]);
    }

    public function test_employee_can_register_device_token(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/device-tokens', [
            'token' => 'ExponentPushToken[xxxxxxx]',
            'platform' => 'ios',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Device token registered.',
            ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->employee->id,
            'token' => 'ExponentPushToken[xxxxxxx]',
            'platform' => 'ios',
        ]);
    }

    public function test_device_token_validation(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/v1/device-tokens', [
            'token' => 'test-token',
            'platform' => 'invalid_platform',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_device_token_upserts(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/v1/device-tokens', [
            'token' => 'ExponentPushToken[xxxxxxx]',
            'platform' => 'android',
        ]);

        $this->postJson('/api/v1/device-tokens', [
            'token' => 'ExponentPushToken[xxxxxxx]',
            'platform' => 'ios',
        ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->employee->id,
            'token' => 'ExponentPushToken[xxxxxxx]',
            'platform' => 'ios',
        ]);

        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_unauthenticated_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }
}
