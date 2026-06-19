<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassSlipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'purpose' => fake()->randomElement([
                'Client meeting',
                'Site inspection',
                'Conference attendance',
                'Training session',
                'Official business trip',
                'Government transaction',
                'Document submission',
            ]),
            'transport_type' => fake()->randomElement(TransportType::cases()),
            'status' => fake()->randomElement(PassSlipStatus::cases()),
            'is_emergency' => fake()->boolean(10), // 10% chance
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PassSlipStatus::DRAFT,
        ]);
    }

    public function submitted(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PassSlipStatus::SUBMITTED,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PassSlipStatus::APPROVED,
            'approved_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function departed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PassSlipStatus::DEPARTED,
            'departure_time' => fake()->dateTimeBetween('-3 hours', '-1 hour'),
        ]);
    }

    public function arrived(): self
    {
        $departure = fake()->dateTimeBetween('-8 hours', '-2 hours');
        $arrival = (clone $departure)->addHours(fake()->numberBetween(2, 6));

        return $this->state(fn (array $attributes) => [
            'status' => PassSlipStatus::ARRIVED,
            'departure_time' => $departure,
            'arrival_time' => $arrival,
            'duration_hours' => $departure->diffInMinutes($arrival) / 60,
        ]);
    }

    public function emergency(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_emergency' => true,
        ]);
    }
}