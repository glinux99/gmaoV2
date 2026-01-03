<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
                // 2. Ensuite les types d'équipements (Nécessaire pour le catalogue)
            EquipmentTypeSeeder::class,
            // 1. D'abord les dépendances (Permission, Role, User, Region)
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            RegionSeeder::class,
            ZoneSeeder::class,



            // 3. Enfin les équipements (qui utilisent tout ce qui est au-dessus)
            EquipmentSeed::class,
        ]);
    }
}
