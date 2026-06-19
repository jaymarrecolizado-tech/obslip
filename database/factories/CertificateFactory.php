<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(CertificateType::cases()),
            'office_name' => fake()->company(),
            'representative_name' => fake()->name(),
            'representative_position' => fake()->jobTitle(),
            'representative_contact' => fake()->phoneNumber(),
            'time_from' => fake()->time(),
            'time_to' => fake()->time(),
            'status' => CertificateStatus::DRAFT,
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => CertificateStatus::SUBMITTED,
        ]);
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => CertificateStatus::VERIFIED,
            'verified_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function physical(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => CertificateType::PHYSICAL,
        ]);
    }

    public function digital(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => CertificateType::DIGITAL,
        ]);
    }

    public function hybrid(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => CertificateType::HYBRID,
        ]);
    }
}