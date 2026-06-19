<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        $submittedBy = User::factory()->create();

        return [
            'pass_slip_id' => PassSlip::factory(),
            'type' => fake()->randomElement(CertificateType::cases()),
            'office_name' => fake()->company() . ' Office',
            'representative_name' => fake()->name(),
            'representative_position' => fake()->jobTitle(),
            'representative_contact' => fake()->phoneNumber(),
            'time_from' => '08:00',
            'time_to' => '17:00',
            'signature_path' => null,
            'attachment_path' => null,
            'status' => CertificateStatus::Draft,
            'submitted_by' => $submittedBy,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => CertificateStatus::Draft]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => CertificateStatus::Submitted,
            'submitted_by' => User::factory(),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => CertificateStatus::Verified,
            'submitted_by' => User::factory(),
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }
}
