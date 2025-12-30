<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Label;
use App\Models\SparePart;
use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentSeed extends Seeder
{
    public function run(): void
    {
        // Créer les types s'ils n'existent pas encore
        if (EquipmentType::count() === 0) {
            $this->call(EquipmentTypeSeeder::class);
        }

        // Créer 20 équipements (ils piocheront au hasard dans les types)
        Equipment::factory()->count(20)->create();

        Label::factory()->count(10)->create();
        SparePart::factory()->count(10)->create();
    }
}
