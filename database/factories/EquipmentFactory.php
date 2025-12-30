<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    private static $catalogs = [
        'G' => [
            'brands' => ['SDMO', 'Kohler', 'Caterpillar'],
            'models' => ['J110C3 Diesel', 'T1000 Pacific', 'XQ2000 Power'],
            'powers' => [100, 250, 400, 800],
            'unit' => 'kVA',
            'docs' => 'https://www.kohler-sdmo.com/'
        ],
        'Q' => [
            'brands' => ['Schneider Electric', 'Legrand', 'ABB'],
            'models' => ['MasterPact MTZ2', 'Compact NSX100', 'Acti9 iC60N'],
            'powers' => [16, 63, 160, 630],
            'unit' => 'A',
            'docs' => 'https://www.se.com/'
        ],
        'T' => [
            'brands' => ['ABB', 'Siemens', 'Schneider Electric'],
            'models' => ['Trihal Sec', 'Minera Huile', 'VD4-S High Voltage'],
            'powers' => [160, 400, 1000],
            'unit' => 'kVA',
            'docs' => 'https://new.abb.com/'
        ],
        'M' => [
            'brands' => ['Siemens', 'Leroy-Somer', 'WEG'],
            'models' => ['SIMOTICS SD', 'LS2-Series High Eff', 'W22 Magnet'],
            'powers' => [5.5, 11, 37, 75],
            'unit' => 'kW',
            'docs' => 'https://www.siemens.com/'
        ],
        'P' => [
            'brands' => ['Socomec', 'Janitza', 'Fluke'],
            'models' => ['Diris A-40', 'Countis E21', 'UMG 96RM'],
            'powers' => [0.1, 0.5, 1.0],
            'unit' => 'V',
            'docs' => 'https://www.socomec.fr/'
        ]
    ];

    public function definition(): array
    {
        // --- RANDOM INTELLIGENT ---
        // On récupère tous les types existants en mémoire et on en pioche un au hasard
        $types = EquipmentType::all();

        // Sécurité : si aucun type n'existe (seeder pas lancé), on en crée un
        $type = $types->isEmpty() ? EquipmentType::factory()->create() : $types->random();

        $catalog = self::$catalogs[$type->prefix] ?? [
            'brands' => ['Legrand'], 'models' => ['Standard'], 'powers' => [100], 'unit' => 'A', 'docs' => 'https://legrand.fr'
        ];

        $brand = $this->faker->randomElement($catalog['brands']);
        $model = $this->faker->randomElement($catalog['models']);
        $power = $this->faker->randomElement($catalog['powers']);

        return [
            'equipment_type_id' => $type->id,
            'region_id' => Region::inRandomOrder()->first()?->id ?? Region::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),

            'tag' => $type->prefix . (int)$power . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'designation' => $type->name . ' ' . $model,
            'brand' => $brand,
            'model' => $model,
            'serial_number' => strtoupper($this->faker->unique()->bothify('??-####-####-2025')),
            'status' => $this->faker->randomElement(['en service', 'en panne', 'en maintenance']),
            'location' => $this->faker->randomElement(['TGBT Principal', 'Local Technique Nord', 'Cellule HT']),

            'puissance' => $power,
            'unit' => $catalog['unit'],
            'price' => $power * $this->faker->numberBetween(30, 120),

            'technical_file_url' => $catalog['docs'],
            'last_maintenance_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'next_maintenance_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'purchase_date' => $this->faker->dateTimeBetween('-10 years', '-1 year'),

            'specifications' => json_encode([
                'ip_rating' => $this->faker->randomElement(['IP20', 'IP54', 'IP66']),
                'standard' => 'IEC 60947',
                'phase' => 'Triphasé 400V',
                'criticite' => $this->faker->randomElement(['Critique', 'Haute', 'Moyenne'])
            ]),
        ];
    }
}
