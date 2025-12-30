<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Database\Factories\EquipmentTypeFactory;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. On récupère le nombre total d'éléments définis dans le catalogue de la Factory
        $count = EquipmentTypeFactory::getCatalogCount();

        // 2. On crée exactement ce nombre d'entrées
        // La Factory s'occupe de faire défiler les noms (Poste de transformation, etc.)
        EquipmentType::factory()->count($count)->create();

        $this->command->info("$count types d'équipements ont été insérés avec succès.");
    }
}
