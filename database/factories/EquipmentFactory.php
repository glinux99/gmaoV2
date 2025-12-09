<?php

namespace Database\Factories;

use App\Models\EquipmentType;
use App\Models\Label;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag' => $this->faker->unique()->bothify('EQ-####'),
            'designation' => $this->faker->words(3, true),
            'brand' => $this->faker->company,
            'model' => $this->faker->word,
            'serial_number' => $this->faker->unique()->ean13,
            'status' => $this->faker->randomElement(['en service', 'en panne', 'en maintenance', 'hors service']),
            'location' => $this->faker->address,
            'purchase_date' => $this->faker->date(),
            'warranty_end_date' => $this->faker->dateTimeBetween('+1 month', '+5 years'),
            'equipment_type_id' => EquipmentType::factory(),
            'region_id' => Region::factory(),
            'user_id' => User::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->randomFloat(2, 100, 10000),
            'puissance' => $this->faker->randomFloat(2, 0.5, 500),
        ];
    }
}
