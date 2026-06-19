<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => 'EMP-' . fake()->unique()->numerify('######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional(0.3)->firstName(),
            'suffix' => fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'III', 'IV']),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->jobTitle(),
            'date_hired' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'employment_status' => fake()->randomElement(EmploymentStatus::cases()),
            'is_active' => true,
        ];
    }

    public function regular(): self
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::REGULAR,
        ]);
    }

    public function contractual(): self
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::CONTRACTUAL,
        ]);
    }
}