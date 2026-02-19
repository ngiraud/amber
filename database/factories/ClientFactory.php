<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => null,
            'contacts' => null,
            'notes' => null,
        ];
    }

    public function withAddress(): static
    {
        return $this->state(fn () => [
            'address' => [
                'line_1' => fake()->streetAddress(),
                'line_2' => null,
                'city' => fake()->city(),
                'zip' => fake()->postcode(),
            ],
        ]);
    }

    public function withContacts(): static
    {
        return $this->state(fn () => [
            'contacts' => [
                'phone' => fake()->phoneNumber(),
                'email' => fake()->companyEmail(),
            ],
        ]);
    }
}
