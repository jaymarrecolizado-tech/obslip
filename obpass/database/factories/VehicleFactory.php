<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->unique()->bothify('???-####')),
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Nissan', 'Hyundai', 'Mitsubishi', 'Suzuki', 'Ford']),
            'model' => fake()->randomElement(['Vios', 'City', 'Navara', 'Tucson', 'Montero', 'Ertiga', 'Ranger']),
            'year' => fake()->numberBetween(2015, 2026),
            'color' => fake()->safeColorName(),
            'owner_id' => Employee::factory(),
            'is_active' => true,
        ];
    }
}
