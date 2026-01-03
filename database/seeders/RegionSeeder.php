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
                'puissance_centrale' => 13.6,
                'code' => 'MAT',
            ],
            [
                'designation' => 'Centrale de Luviro',
                'type_centrale' => 'Hydroélectrique',
                'puissance_centrale' => 2.8,
                'code' => 'LUV',
            ],
            [
                'designation' => 'Centrale de Mutwanga',
                'type_centrale' => 'Hydroélectrique',
                'puissance_centrale' => 0.4,
                'code' => 'MUT',
            ],
            [
                'designation' => 'Réseau de Goma',
                'type_centrale' => 'Réseau de distribution',
                'puissance_centrale' => null,
                'code' => 'GOM',
            ],
            [
                'designation' => 'Réseau de Rutshuru',
                'type_centrale' => 'Réseau de distribution',
                'puissance_centrale' => null,
                'code' => 'RUT',
            ],
            [
                'designation' => 'Réseau de Beni',
                'type_centrale' => 'Réseau de distribution',
                'puissance_centrale' => null,
                'code' => 'BEN',
            ],
            [
                'designation' => 'Bureau Central Goma',
                'type_centrale' => 'Administration',
                'puissance_centrale' => null,
                'code' => 'BCG',
            ],
            [
                'designation' => 'Atelier Central Goma',
                'type_centrale' => 'Maintenance',
                'puissance_centrale' => null,
                'code' => 'ACG',
            ],
            [
                'designation' => 'Magasin Central Goma',
                'type_centrale' => 'Logistique',
                'puissance_centrale' => null,
                'code' => 'MCG',
            ],
            [
                'designation' => 'Site de Rumangabo',
                'type_centrale' => 'Site militaire/opérationnel',
                'puissance_centrale' => null,
                'code' => 'RUM',
            ],
        ];

        foreach ($regionsData as $region) {
            Region::firstOrCreate(
                ['designation' => $region['designation']],
                $region
            );
        }
    }
}
