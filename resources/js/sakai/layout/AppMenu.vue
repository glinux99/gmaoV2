<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    { label: 'menu.dashboard.title',
        items: [
            { label: 'menu.dashboard.home', icon: 'pi pi-fw pi-home', to: '/dashboard' },
            { label: 'menu.dashboard.analytics', icon: 'pi pi-fw pi-chart-line', to: '/analytics', }

        ]
    },
    { label: 'menu.assetManagement.title',
        items: [
            { label: 'menu.assetManagement.inventories', icon: 'pi pi-fw pi-sync', to: '/stock-movements', /*can: 'inventories'*/ },
            // { label: 'menu.assetManagement.inventories', icon: 'pi pi-fw pi-sync', to: '/spare-part-movements', /*can: 'inventories'*/ },
            // { label: 'Sortie', icon: 'pi pi-fw pi-arrow-circle-up', to: '/sorties', /*can: 'sorties'*/ },
            // { label: 'Entrée', icon: 'pi pi-fw pi-arrow-circle-down', to: '/entries', /*can: 'entries'*/ },
            { label: 'menu.assetManagement.spareParts', icon: 'pi pi-fw pi-book', to: '/spare-parts', /*can: 'categories'*/ },
            { label: 'menu.assetManagement.equipments', icon: 'pi pi-fw pi-cog', to: '/equipments', /*can: 'equipment'*/ },
            { label: 'menu.assetManagement.networks', icon: 'pi pi-fw pi-sitemap', to: '/networks', /*can: 'equipment'*/ },
                   {
                label: 'menu.systemConfiguration.meters',
                icon: 'pi pi-fw pi-bolt',
                to: '/meters',
            },
            {
                label: 'menu.systemConfiguration.keyboards',
                icon: 'pi pi-fw pi-key',
                to: '/keypads',
            },

        ]
    },
    { label: 'menu.taskManagement.title',
        items: [
            { label: 'menu.taskManagement.agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda', /*can: 'projects'*/ },
            { label: 'menu.taskManagement.myActivities', icon: 'pi pi-fw pi-list', to: '/activities', /*can: 'projects'*/ },
            { label: 'menu.taskManagement.workOrder', icon: 'pi pi-fw pi-check-square', to: '/tasks', /*can: 'projects'*/ },
            { label: 'menu.taskManagement.interventionRequests', icon: 'pi pi-fw pi-exclamation-circle', to: '/interventions', /*can: 'projects'*/ },
            { label: 'menu.taskManagement.maintenancePlan', icon: 'pi pi-fw pi-wrench', to: '/maintenances', /*can: 'projects'*/ },
            { label: 'menu.taskManagement.connections', icon: 'pi pi-fw pi-sitemap', to: '/connections', /*can: 'projects'*/ },
            { label: 'menu.taskManagement.expenses', icon: 'pi pi-fw pi-money-bill', to: '/expenses', /*can: 'expenses'*/ },
          ]
    },
    { label: 'menu.teamManagement.title',
        items: [
            { label: 'menu.teamManagement.teams', icon: 'pi pi-fw pi-users', to: '/teams', /*can: 'teams'*/ },
            { label: 'menu.teamManagement.technicians', icon: 'pi pi-fw pi-user', to: '/technicians', /*can: 'techniciens'*/ }
        ]
    },

    { label: 'menu.hrManagement.title',
        items: [
            {
                label: 'menu.hrManagement.employees',
                icon: 'pi pi-fw pi-users',
                to: '/employees',
                /*can: 'employees'*/
            },
            {
                label: 'menu.hrManagement.leaves',
                icon: 'pi pi-fw pi-calendar-times',
                to: '/leaves',
                /*can: 'leaves'*/
            },
            // {
            //     label: 'menu.hrManagement.attendances',
            //     icon: 'pi pi-fw pi-clock',
            //     to: '/attendances',
            //     /*can: 'attendances'*/
            // },
            {
                label: 'menu.hrManagement.payroll',
                icon: 'pi pi-fw pi-money-bill',
                to: '/payroll',
                /*can: 'payroll'*/

            }
        ]
    },
    { label: 'menu.systemConfiguration.title',
        items: [
            {
                label: 'menu.systemConfiguration.userManagement',
                icon: 'pi pi-fw pi-shield',
                /*can: ['read user', 'read role', 'read permission'],*/
                items: [
                    {
                        label: 'menu.systemConfiguration.users',
                        to: '/users',
                        /*can: 'read user'*/
                    },
                    {
                        label: 'menu.systemConfiguration.roles',
                        to: '/roles',
                        /*can: 'read role'*/
                    },
                    {
                        label: 'menu.systemConfiguration.permissions',
                        to: '/permissions',
                        /*can: 'read permission'*/
                    }
                ]
            },
            {
                label: 'menu.systemConfiguration.reports',
                icon: 'pi pi-fw pi-chart-bar',
                to: '/reports',
                /*can: 'repports'*/
            },
            {
                label: 'menu.systemConfiguration.maps',
                icon: 'pi pi-fw pi-map-marker',
                to: '/googlemaps'
            },
            {
                label: 'menu.systemConfiguration.regions',
                icon: 'pi pi-fw pi-map',
                to: '/regions',
            },
             {
                label: 'menu.systemConfiguration.zones',
                icon: 'pi pi-fw pi-map-marker',
                to: '/zones'
            },
            { label: 'menu.systemConfiguration.rollingStock',
                icon: 'pi pi-fw pi-car',
                to: '/engins'
            },
            { label: 'menu.systemConfiguration.units', icon: 'pi pi-fw pi-tag', to: '/unities', /*can: 'unities'*/ },
            { label: 'menu.systemConfiguration.labels',
                icon: 'pi pi-fw pi-pencil',
                to: '/labels',
                /*can: 'priority'*/
            },
            {
                label: 'menu.systemConfiguration.systemPriority',
                icon: 'pi pi-fw pi-sort-amount-up',
                to: '/priorities',
                /*can: 'priority'*/
            },


        ]
    }
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :root="true" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
