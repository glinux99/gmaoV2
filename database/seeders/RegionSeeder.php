<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regionsData = [
            [
                'designation' => 'Centrale de Matebe',
                'type_centrale' => 'Hydroélectrique',
                'puissance_centrale' => 13.6, // MW
            ],
            [
                'designation' => 'Centrale de Luviro',
                'type_centrale' => 'Hydroélectrique',
                'puissance_centrale' => 2.8, // MW
            ],
            [
                'designation' => 'Centrale de Mutwanga',
                'type_centrale' => 'Hydroélectrique',
                'puissance_centrale' => 0.4, // MW
            ],
            [
                'designation' => 'Réseau de Goma',
                'type_centrale' => 'Réseau de distribution',
                'puissance_centrale' => null,
            ],
            [
                'designation' => 'Réseau de Rutshuru',
                'type_centrale' => 'Réseau de distribution',
                'puissance_centrale' => null,
            ],
            [
                'designation' => 'Réseau de Beni',
                'type_centrale' => 'Réseau de distribution',
                'puissance_centrale' => null,
            ],
            [
                'designation' => 'Bureau Central Goma',
                'type_centrale' => 'Administration',
                'puissance_centrale' => null,
            ],
            [
                'designation' => 'Atelier Central Goma',
                'type_centrale' => 'Maintenance',
                'puissance_centrale' => null,
            ],
            [
                'designation' => 'Magasin Central Goma',
                'type_centrale' => 'Logistique',
                'puissance_centrale' => null,
            ],
            [
                'designation' => 'Site de Rumangabo',
                'type_centrale' => 'Site militaire/opérationnel',
                'puissance_centrale' => null,
            ],
        ];

        foreach ($regionsData as $region) {
            Region::firstOrCreate(
                ['designation' => $region['designation']],
                [
                    'type_centrale' => $region['type_centrale'],
                    'puissance_centrale' => $region['puissance_centrale'],
                ]
            );
        }
    }
}
