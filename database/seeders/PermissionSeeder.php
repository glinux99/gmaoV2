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
        $permissions = [
            // Dashboard
            'view-dashboard',

            // User Management
            'create-user', 'read-user', 'update-user', 'delete-user',
            'create-role', 'read-role', 'update-role', 'delete-role',
            'create-permission', 'read-permission', 'update-permission', 'delete-permission',

            // Asset Management
            'create-equipment', 'read-equipment', 'update-equipment', 'delete-equipment', 'export-equipments', 'update-equipment-quantity',
            'create-network', 'read-network', 'update-network', 'delete-network',
            'read-meter', 'create-meter', 'update-meter', 'delete-meter',
            'read-keypad', 'create-keypad', 'update-keypad', 'delete-keypad',
            'read-spare-part', 'create-spare-part', 'update-spare-part', 'delete-spare-part',

            // Stock Management
            'create-stock-movement', 'read-stock-movement', 'update-stock-movement', 'delete-stock-movement', 'bulk-delete-stock-movement',
            'export-stock-movements', 'create-stock-entry', 'create-stock-exit', 'create-stock-transfer',

            // Task Management
            'create-task', 'read-task', 'update-task', 'delete-task', 'assign-task', 'export-tasks','bulk-delete-task',
            'create-activity', 'read-activity', 'update-activity', 'delete-activity', 'assign-activity',
            'create-maintenance', 'read-maintenance', 'update-maintenance', 'delete-maintenance',
            'create-intervention-request', 'read-intervention-request', 'update-intervention-request', 'delete-intervention-request',
            'assign-intervention-request', 'validate-intervention-request', 'cancel-intervention-request',
            'read-connection', 'update-connection', 'delete-connection', 'import-connections',
            'read-expense', 'create-expense', 'update-expense', 'delete-expense',

            // Team Management
            'create-team', 'read-team', 'update-team', 'delete-team',
            'read-technician',

            // HR Management
            'read-employee', 'create-employee', 'update-employee', 'delete-employee',
            'read-leave', 'create-leave', 'update-leave', 'delete-leave',
            'read-payroll', 'create-payroll', 'update-payroll', 'delete-payroll',

            // System Configuration
            'create-report-template', 'read-report-template', 'update-report-template', 'delete-report-template',
            'read-report',
            'read-region', 'create-region', 'update-region', 'delete-region',
            'read-zone', 'create-zone', 'update-zone', 'delete-zone',
            'read-engin', 'create-engin', 'update-engin', 'delete-engin',
            'read-map',
            'read-unit', 'create-unit', 'update-unit', 'delete-unit',
            'create-permission', 'read-permission', 'update-permission', 'delete-permission',
            'read-label', 'create-label', 'update-label', 'delete-label',
            'read-priority', 'create-priority', 'update-priority', 'delete-priority',
            'read-equipment-type',
            'impersonate-user'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
