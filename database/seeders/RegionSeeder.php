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


        ];

        foreach ($regionsData as $region) {
            Region::firstOrCreate(
                ['designation' => $region['designation']],
                $region
            );
        }
    }
}
