<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permissions CRUD pour une gestion fine
        $entities = [
            'user', 'role', 'permission', // Administration
            'equipment', 'equipment-type', 'region', 'zone', // Actifs & Structure
            'task', 'activity', 'maintenance', // Tâches & Maintenance
            'intervention-request', // Demandes d'intervention
            'stock-movement', 'spare-part', // Stock
            'network', // Schémas de réseau
            'team', // Équipes
            'connection', // Raccordements clients
            'report-template', // Modèles de rapport
        ];

        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}-{$entity}"]);
            }
        }

        // Permissions spécifiques supplémentaires
        $specificPermissions = [
            // Interventions
            'assign-intervention-request',
            'validate-intervention-request',
            'cancel-intervention-request',

            // Équipements
            'update-equipment-quantity',
            'bulk-delete-equipment',

            // Stock
            'create-stock-entry',
            'create-stock-exit',
            'create-stock-transfer',
            'bulk-delete-stock-movement',

            // Tâches & Activités
            'assign-task',
            'assign-activity',

            // Import/Export
            'import-connections',
            'export-equipments',
            'export-tasks',
            'export-stock-movements',
        ];

        foreach ($specificPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
