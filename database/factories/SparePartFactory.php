<?php

namespace Database\Factories;

use App\Models\Label;
use App\Models\Region;
use App\Models\Unity;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SparePart>
 */
class SparePartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quantity' => $this->faker->numberBetween(1, 100),
            'min_quantity' => $this->faker->numberBetween(1, 10),
            'location' => $this->faker->word,
            'region_id' => Region::factory(),

            'user_id' => User::factory(),
            'label_id' => Label::factory(),
            'reference' => $this->faker->unique()->ean8,
            'price' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
