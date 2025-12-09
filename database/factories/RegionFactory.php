<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'designation' => $this->faker->unique()->company(),
            'type_centrale' => $this->faker->randomElement(['Hydroélectrique', 'Réseau de distribution', 'Administration', 'Maintenance', 'Logistique']),
            'puissance_centrale' => $this->faker->optional()->randomFloat(2, 0.4, 15.0),
        ];
    }
}
