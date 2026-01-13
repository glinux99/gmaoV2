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
        $regions = Region::whereIn('code', ['GOM', 'MAT', 'MUT', 'LUB'])->get()->keyBy('code');

        $zonesData = [
            // Zones pour Goma
            ['region_code' => 'GOM', 'number' => 1, 'title' => 'DON BOSCO'],
            ['region_code' => 'GOM', 'number' => 1, 'title' => 'C.S CHOIX DE BON'],
            ['region_code' => 'GOM', 'number' => 1, 'title' => 'c/KI SALLE'],
            ['region_code' => 'GOM', 'number' => 2, 'title' => 'KI TRENTE'],
            ['region_code' => 'GOM', 'number' => 3, 'title' => 'ENTRE KIMUTI'],
            ['region_code' => 'GOM', 'number' => 4, 'title' => 'SAINT MARC'],
            ['region_code' => 'GOM', 'number' => 5, 'title' => 'TURUNGA'],
            ['region_code' => 'GOM', 'number' => 6, 'title' => 'SAINT BENOIT'],
            ['region_code' => 'GOM', 'number' => 7, 'title' => 'KIBWET VILLE'],
            ['region_code' => 'GOM', 'number' => 8, 'title' => 'CHRIST ROI'],
            ['region_code' => 'GOM', 'number' => 9, 'title' => 'KATINDO II'],
            ['region_code' => 'GOM', 'number' => 10, 'title' => 'CARITAS'],
            ['region_code' => 'GOM', 'number' => 11, 'title' => 'KATOYI'],
            ['region_code' => 'GOM', 'number' => 12, 'title' => 'DAVID SAEZ'],
            ['region_code' => 'GOM', 'number' => 13, 'title' => 'SHABA'],
            ['region_code' => 'GOM', 'number' => 14, 'title' => 'MOSQUEE KATINDO'],
            ['region_code' => 'GOM', 'number' => 15, 'title' => 'NYABUSHONGO'],
            ['region_code' => 'GOM', 'number' => 16, 'title' => 'CS LA JOIE'],
            ['region_code' => 'GOM', 'number' => 17, 'title' => 'HOPITAL BETHESDA'],
            ['region_code' => 'GOM', 'number' => 18, 'title' => 'FACULTE DE DROIT (ULPGL)'],
            ['region_code' => 'GOM', 'number' => 19, 'title' => 'NYARUBANDE'],
            ['region_code' => 'GOM', 'number' => 20, 'title' => 'SOTRAKI'],
            ['region_code' => 'GOM', 'number' => 21, 'title' => 'REGIDESO'],
            ['region_code' => 'GOM', 'number' => 21, 'title' => 'PALLOTINS'],
            ['region_code' => 'GOM', 'number' => 21, 'title' => 'DOMINIQUE KESER'],
            ['region_code' => 'GOM', 'number' => 22, 'title' => 'BIGOMBE'],
            ['region_code' => 'GOM', 'number' => 23, 'title' => 'INSTITUT MAJENGO'],
            ['region_code' => 'GOM', 'number' => 24, 'title' => 'BIRANDA'],
            ['region_code' => 'GOM', 'number' => 25, 'title' => 'ANAMAD'],
            ['region_code' => 'GOM', 'number' => 26, 'title' => 'TERRAIN SCOUT'],
            ['region_code' => 'GOM', 'number' => 27, 'title' => 'KWA SEMAHORE'],
            ['region_code' => 'GOM', 'number' => 28, 'title' => 'COMMANDANT BELGE'],
            ['region_code' => 'GOM', 'number' => 29, 'title' => 'CEBCE KATOYI'],
            ['region_code' => 'GOM', 'number' => 30, 'title' => 'BIMA'],
            ['region_code' => 'GOM', 'number' => 31, 'title' => 'KWA CHEF TSHAGANE'],
            ['region_code' => 'GOM', 'number' => 32, 'title' => 'EGLISE ORTHODOXE'],
            ['region_code' => 'GOM', 'number' => 33, 'title' => 'KUBA PM'],
            ['region_code' => 'GOM', 'number' => 34, 'title' => 'CENTRE HOSPITALIER HESHIMA'],
            ['region_code' => 'GOM', 'number' => 35, 'title' => 'KITUKU ABATOIR'],
            ['region_code' => 'GOM', 'number' => 36, 'title' => 'EGLISE ADVENTISTE/ SIEGE PROVINCIAL'],
            ['region_code' => 'GOM', 'number' => 37, 'title' => 'KWA KINGAMBA'],
            ['region_code' => 'GOM', 'number' => 38, 'title' => 'PARKING MASISI'],
            ['region_code' => 'GOM', 'number' => 39, 'title' => 'KU GAZ'],
            ['region_code' => 'GOM', 'number' => 40, 'title' => 'COMPLEXE SCOLAIRE FAIDA'],

            // Zones pour Mut
            ['region_code' => 'MUT', 'number' => 1, 'title' => 'CENTRE'],
            ['region_code' => 'MUT', 'number' => 2, 'title' => 'KYANIKA'],
            ['region_code' => 'MUT', 'number' => 3, 'title' => 'MUTSORA'],
            ['region_code' => 'MUT', 'number' => 4, 'title' => 'NZENGA'],
            ['region_code' => 'MUT', 'number' => 5, 'title' => 'ZONNING'],
            ['region_code' => 'MUT', 'number' => 6, 'title' => 'JONATHAN'],
            ['region_code' => 'MUT', 'number' => 7, 'title' => 'CECA'],



            // Zones pour Rutshuru (RUT)
            ['region_code' => 'MAT', 'number' => 1, 'title' => 'KIWANJBUHUNDA'],
            ['region_code' => 'MAT', 'number' => 2, 'title' => 'BUHUNDA'],
            ['region_code' => 'MAT', 'number' => 3, 'title' => 'KIWANJGREFAMU'],
            ['region_code' => 'MAT', 'number' => 4, 'title' => 'KIWANJKACHEMU'],
            ['region_code' => 'MAT', 'number' => 5, 'title' => 'KIWANJPONT BARIYANGA'],
            ['region_code' => 'MAT', 'number' => 6, 'title' => 'MABUNGO EUPHREM'],
            ['region_code' => 'MAT', 'number' => 7, 'title' => 'KIWANJESTEBAN'],
            ['region_code' => 'MAT', 'number' => 8, 'title' => 'BUTURANDE INSTITUT'],
            ['region_code' => 'MAT', 'number' => 9, 'title' => 'MABUNGO BRIEUC'],
            ['region_code' => 'MAT', 'number' => 10, 'title' => 'MABUNGO B'],
            ['region_code' => 'MAT', 'number' => 11, 'title' => 'KIWANJKIMBANGU'],
            ['region_code' => 'MAT', 'number' => 12, 'title' => 'NYONGERA'],
            ['region_code' => 'MAT', 'number' => 13, 'title' => 'MABUNGO ENTREE DOMAINE'],
            ['region_code' => 'MAT', 'number' => 14, 'title' => 'MABUNGO GABRIEL'],
            ['region_code' => 'MAT', 'number' => 15, 'title' => 'MABUNGO INSTITUT'],
            ['region_code' => 'MAT', 'number' => 16, 'title' => 'MABUNGO EGD'],
            ['region_code' => 'MAT', 'number' => 17, 'title' => 'BUTURANDE BUKOMA'],
            ['region_code' => 'MAT', 'number' => 18, 'title' => 'BUTURANDE MUHOKOZI'],
            ['region_code' => 'MAT', 'number' => 19, 'title' => 'KIWANJLUHESHE'],
            ['region_code' => 'MAT', 'number' => 20, 'title' => 'BUTURANDE JAMES'],
            ['region_code' => 'MAT', 'number' => 21, 'title' => 'BUTURANDE KISUBA'],
            ['region_code' => 'MAT', 'number' => 22, 'title' => 'BUTURANDE COSMA'],
            ['region_code' => 'MAT', 'number' => 23, 'title' => 'BUTURANDE CATHOLIQUE'],
            ['region_code' => 'MAT', 'number' => 24, 'title' => 'BUTURANDE STADE MASASI'],
            ['region_code' => 'MAT', 'number' => 25, 'title' => 'BUTURANDE MAPENDO C.S'],
            ['region_code' => 'MAT', 'number' => 26, 'title' => 'KIWANJACCO'],
            ['region_code' => 'MAT', 'number' => 27, 'title' => 'BUZITO'],
            ['region_code' => 'MAT', 'number' => 28, 'title' => 'INSTITUT MAPENDO'],
            ['region_code' => 'MAT', 'number' => 28, 'title' => 'PASTEUR ELIAS'], // Note: duplicate number, consider if this is intended
            ['region_code' => 'MAT', 'number' => 29, 'title' => 'BUNYANGULNORD'],
            ['region_code' => 'MAT', 'number' => 30, 'title' => 'BUNYANGULSUD'],
            ['region_code' => 'MAT', 'number' => 31, 'title' => 'MURAMBI SŒURS'],
            ['region_code' => 'MAT', 'number' => 32, 'title' => 'RUTSHURU HOPITAL'],
            ['region_code' => 'MAT', 'number' => 33, 'title' => ''], // Empty title, consider if this is intended
            ['region_code' => 'MAT', 'number' => 34, 'title' => 'KIRINGPOLICE'],
            ['region_code' => 'MAT', 'number' => 35, 'title' => 'KIRINGCENTRE'],
            ['region_code' => 'MAT', 'number' => 36, 'title' => 'KISISILE'],
            ['region_code' => 'MAT', 'number' => 37, 'title' => 'RUTSHURU STATION'],
            ['region_code' => 'MAT', 'number' => 38, 'title' => 'RUTSHURU FUKO'],
            ['region_code' => 'MAT', 'number' => 39, 'title' => 'RUTSHURU POLICE'],
            ['region_code' => 'MAT', 'number' => 40, 'title' => 'RUTSHURU STADE'],
            ['region_code' => 'MAT', 'number' => 41, 'title' => 'BURAYI'],
            ['region_code' => 'MAT', 'number' => 42, 'title' => 'RUBARE RUBONA'],
            ['region_code' => 'MAT', 'number' => 43, 'title' => 'RUBARE EGLISE'],
            ['region_code' => 'MAT', 'number' => 44, 'title' => 'RUBARE MAJENGO'],
            ['region_code' => 'MAT', 'number' => 45, 'title' => 'RUBARE KIGIRIMA'],
            ['region_code' => 'MAT', 'number' => 46, 'title' => 'RUBARE KANYATSI'],
            ['region_code' => 'MAT', 'number' => 47, 'title' => 'MATEBE'],
            ['region_code' => 'MAT', 'number' => 62, 'title' => 'KAKO CENTRE'],
            ['region_code' => 'MAT', 'number' => 63, 'title' => 'KALENGERUNHCR'],
            ['region_code' => 'MAT', 'number' => 64, 'title' => 'KALENGERMARASHI'],
            ['region_code' => 'MAT', 'number' => 65, 'title' => 'KALENGERROUTE TONGO'],
            ['region_code' => 'MAT', 'number' => 66, 'title' => 'BIRUMMARCHE'],
            ['region_code' => 'MAT', 'number' => 67, 'title' => 'BIRUMCENTRE DE SANTE'],
            ['region_code' => 'MAT', 'number' => 68, 'title' => 'KATALE'],
            ['region_code' => 'MAT', 'number' => 68, 'title' => 'KATALE AERODROME'], // Note: duplicate number, consider if this is intended
            ['region_code' => 'MAT', 'number' => 69, 'title' => 'BUVUNGA'],
            ['region_code' => 'MAT', 'number' => 70, 'title' => 'RUMANGABO VILLAGE'],
            ['region_code' => 'MAT', 'number' => 70, 'title' => 'RUMANGABO STATION'], // Note: duplicate number, consider if this is intended
            ['region_code' => 'MAT', 'number' => 71, 'title' => 'BUGOMBWA'],
            ['region_code' => 'MAT', 'number' => 72, 'title' => 'BUSHENGE'],
            ['region_code' => 'MAT', 'number' => 73, 'title' => 'KIKO'],
            ['region_code' => 'MAT', 'number' => 74, 'title' => 'RUGARI CENTRE'],
            ['region_code' => 'MAT', 'number' => 75, 'title' => 'EMERY'],
            ['region_code' => 'MAT', 'number' => 76, 'title' => 'KAKOMERO'],
            ['region_code' => 'MAT', 'number' => 77, 'title' => 'KIBUMBA'],
            ['region_code' => 'MAT', 'number' => 80, 'title' => 'KIBUMB ANTENNES'],
            ['region_code' => 'MAT', 'number' => 81, 'title' => 'BUHUMBA'],
            ['region_code' => 'MAT', 'number' => 90, 'title' => 'KIBATI'],
            ['region_code' => 'MAT', 'number' => 108, 'title' => 'STADE KIBATI'],
            ['region_code' => 'MAT', 'number' => 110, 'title' => 'BIZI'],
            ['region_code' => 'MAT', 'number' => 112, 'title' => 'KIBANDA'],
            ['region_code' => 'MAT', 'number' => 113, 'title' => 'ZONE INDUSTRIELLE'],
            ['region_code' => 'MAT', 'number' => 114, 'title' => 'CARRIGO'],


            // Zones pour Lubero
            ['region_code' => 'LUB', 'number' => 1, 'title' => 'BUZIBITI'],
            ['region_code' => 'LUB', 'number' => 1, 'title' => 'HOPITAL GEN. DE REFERENCE DE LUBERO'],
            ['region_code' => 'LUB', 'number' => 2, 'title' => 'DGRAD'],
            ['region_code' => 'LUB', 'number' => 3, 'title' => 'LUBERO STADE'],
            ['region_code' => 'LUB', 'number' => 4, 'title' => 'LUBERO ESCO'],
            ['region_code' => 'LUB', 'number' => 5, 'title' => 'PLAVUMA'],
            ['region_code' => 'LUB', 'number' => 5, 'title' => 'TAMU'],
            ['region_code' => 'LUB', 'number' => 6, 'title' => 'TUUNGANE'],
            ['region_code' => 'LUB', 'number' => 7, 'title' => 'EP. KIMBULU'],
            ['region_code' => 'LUB', 'number' => 8, 'title' => 'INSTITUT VUSUMBA'],
            ['region_code' => 'LUB', 'number' => 9, 'title' => 'SKL LUB-0b'],
            ['region_code' => 'LUB', 'number' => 10, 'title' => 'KATOLO'],
            ['region_code' => 'LUB', 'number' => 11, 'title' => 'ADVENTISTE'],
            ['region_code' => 'LUB', 'number' => 12, 'title' => 'KATOLO CENTRE'],
            ['region_code' => 'LUB', 'number' => 13, 'title' => 'MIKAELA'],
            ['region_code' => 'LUB', 'number' => 14, 'title' => 'HOPITAL GEN. DE REFERENCE DE MUSIENENE'],
            ['region_code' => 'LUB', 'number' => 15, 'title' => 'MONUMENT'],
            ['region_code' => 'LUB', 'number' => 16, 'title' => 'MUSIENENE ETAT'],
            ['region_code' => 'LUB', 'number' => 17, 'title' => 'SITE D\'IVINGU'],
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
