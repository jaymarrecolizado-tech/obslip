<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->bothify('???-####')),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Mitsubishi', 'Ford', 'Isuzu', 'Nissan']),
            'model' => fake()->randomElement(['Vios', 'City', 'Montero', 'Ranger', 'D-Max', 'Navara']),
            'year' => fake()->numberBetween(2015, 2024),
            'color' => fake()->colorName(),
            'is_active' => true,
        ];
    }

    public function companyVehicle(): self
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => null,
        ]);
    }

    public function personalVehicle(): self
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => Employee::inRandomOrder()->first()?->id ?? Employee::factory()->create()->id,
        ]);
    }
}