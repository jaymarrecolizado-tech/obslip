<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use App\Models\Department;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassSlipFactory extends Factory
{
    protected $model = PassSlip::class;

    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'purpose' => fake()->sentence(),
            'transport_type' => fake()->randomElement(TransportType::cases()),
            'status' => PassSlipStatus::Draft,
            'creator_id' => User::factory(),
            'supervisor_id' => null,
            'approver_id' => null,
            'department_id' => Department::factory(),
            'vehicle_id' => null,
            'departure_time' => null,
            'arrival_time' => null,
            'approved_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'returned_reason' => null,
            'duration_hours' => null,
            'is_emergency' => false,
            'pdf_path' => null,
            'qr_code' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PassSlipStatus::Draft]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => PassSlipStatus::Submitted]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => PassSlipStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function departed(): static
    {
        return $this->state(fn () => [
            'status' => PassSlipStatus::Departed,
            'departure_time' => now()->subHours(2),
        ]);
    }

    public function arrived(): static
    {
        return $this->state(fn () => [
            'status' => PassSlipStatus::Arrived,
            'departure_time' => now()->subHours(4),
            'arrival_time' => now()->subHours(2),
            'duration_hours' => 2.0,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => PassSlipStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn () => ['is_emergency' => true]);
    }
}
