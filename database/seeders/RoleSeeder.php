<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin - Accès total
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->givePermissionTo([
            'create-user', 'read-user', 'update-user', 'delete-user',
            'create-role', 'read-role', 'update-role', 'delete-role',
            'create-permission', 'read-permission', 'update-permission', 'delete-permission',
        ]);
        // Le Super Admin reçoit toutes les permissions existantes dans le système.
        // C'est une bonne pratique pour s'assurer qu'il a toujours un accès total.


        // Admin - Gestionnaire principal des opérations
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            // Gestion utilisateurs (sans gestion des rôles/permissions)
            'create-user', 'read-user', 'update-user', 'delete-user',
            'read-role', 'read-permission',
            // Gestion complète des entités principales
            'create-equipment', 'read-equipment', 'update-equipment', 'delete-equipment',
            'create-task', 'read-task', 'update-task', 'delete-task', 'assign-task',
            'create-activity', 'read-activity', 'update-activity', 'delete-activity', 'assign-activity',
            'create-maintenance', 'read-maintenance', 'update-maintenance', 'delete-maintenance',
            'create-intervention-request', 'read-intervention-request', 'update-intervention-request', 'delete-intervention-request',
            'assign-intervention-request', 'validate-intervention-request', 'cancel-intervention-request',
            'create-stock-movement', 'read-stock-movement', 'update-stock-movement', 'delete-stock-movement', 'bulk-delete-stock-movement',
            'create-network', 'read-network', 'update-network', 'delete-network',
            'create-team', 'read-team', 'update-team', 'delete-team',
            'import-connections', 'read-connection', 'update-connection', 'delete-connection',
            'create-report-template', 'read-report-template', 'update-report-template', 'delete-report-template',
            'export-equipments', 'export-tasks', 'export-stock-movements',
        ]);

        // Opérateur - Centre d'appel, création des demandes
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->givePermissionTo([
            'read-user', // Pour assigner
            'read-team', // Pour assigner
            'read-region', 'read-zone',
            'read-connection',
            'create-intervention-request',
            'read-intervention-request',
            'update-intervention-request', // Peut modifier une demande avant validation
        ]);

        // Technicien - Exécute les tâches sur le terrain
        $technician = Role::firstOrCreate(['name' => 'technician']);
        $technician->givePermissionTo([
            'read-task',                // Voir ses tâches assignées
            'update-task',              // Mettre à jour le statut, ajouter des notes
            'read-activity',            // Voir ses activités
            'update-activity',          // Compléter les instructions, marquer comme terminée
            'read-equipment',           // Consulter les détails d'un équipement
            'read-spare-part',          // Voir les pièces disponibles
            'create-stock-exit',        // Déclarer une sortie de pièce pour une réparation
            'read-intervention-request',// Consulter les détails des demandes
            'update-intervention-request', // Mettre à jour le statut (ex: 'en cours')
        ]);

        // Magasinier - Gère le stock
        $magasinier = Role::firstOrCreate(['name' => 'magasinier']);
        $magasinier->givePermissionTo([
            'read-stock-movement', 'create-stock-movement', 'update-stock-movement', 'delete-stock-movement',
            'bulk-delete-stock-movement',
            'create-stock-entry', 'create-stock-exit', 'create-stock-transfer', // Actions spécifiques de stock
            'read-spare-part', 'create-spare-part', 'update-spare-part', 'delete-spare-part',
            'read-equipment', // Pour voir les équipements en stock
            'update-equipment-quantity', // Pour ajuster les quantités
        ]);

        // Ingénieur Réseau - Conçoit les schémas
        $networkEngineer = Role::firstOrCreate(['name' => 'network-engineer']);
        $networkEngineer->givePermissionTo([
            'create-network', 'read-network', 'update-network', 'delete-network',
            'read-equipment', 'read-equipment-type',
            'read-region', 'read-zone',
        ]);

        // Visiteur - Accès en lecture seule
        $visitor = Role::firstOrCreate(['name' => 'visitor']);
        $visitor->givePermissionTo([
            'read-equipment',
            'read-equipment-type',
            'read-task',
            'read-maintenance',
            'read-network',
            'read-region',
        ]);
         $superadmin->givePermissionTo(Permission::all());
    }
}
