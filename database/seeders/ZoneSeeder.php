<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Zone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = Region::whereIn('code', ['GOM', 'RUT', 'BEN', 'MAT'])->get()->keyBy('code');

        $zonesData = [
            // Zones pour Goma
            ['region_code' => 'GOM', 'number' => 1, 'title' => 'Centre Ville'],
            ['region_code' => 'GOM', 'number' => 2, 'title' => 'Les Volcans'],
            ['region_code' => 'GOM', 'number' => 3, 'title' => 'Himbi'],
            ['region_code' => 'GOM', 'number' => 4, 'title' => 'Kyeshero'],
            ['region_code' => 'GOM', 'number' => 5, 'title' => 'Ndosho'],

            // Zone de votre exemple
            ['region_code' => 'MAT', 'number' => 19, 'title' => 'NYARUBANDE'],

            // Zones pour Rutshuru
            ['region_code' => 'RUT', 'number' => 1, 'title' => 'Kiwanja'],
            ['region_code' => 'RUT', 'number' => 2, 'title' => 'Rutshuru Centre'],

            // Zones pour Beni
            ['region_code' => 'BEN', 'number' => 1, 'title' => 'Boikene'],
            ['region_code' => 'BEN', 'number' => 2, 'title' => 'Matonge'],
        ];

        foreach ($zonesData as $zoneData) {
            $region = $regions->get($zoneData['region_code']);

            if ($region) {
                $nomenclature = "{$region->code} - Zone {$zoneData['number']} / {$zoneData['title']}";

                Zone::firstOrCreate(
                    ['nomenclature' => $nomenclature],
                    [

                        'title' => $zoneData['title'],
                        'number' => $zoneData['number'],
                        'region_id' => $region->id,
                    ]
                );
            }
        }
    }
}
