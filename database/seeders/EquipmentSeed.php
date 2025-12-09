<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Label;
use App\Models\SparePart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquipmentSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Générer 20 équipements
        Equipment::factory()->count(20)->create();

        // Générer 10 étiquettes
        Label::factory()->count(10)->create();

        // Générer 20 pièces de rechange
        SparePart::factory()->count(20)->create();
    }
}
