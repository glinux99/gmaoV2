<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'Dashboard',
        items: [{ label: 'Acceuil', icon: 'pi pi-fw pi-home', to: '/dashboard' }]
    },
    {
        label: 'Gestions des Actifs',
        items: [
            { label: 'Inventaires', icon: 'pi pi-fw pi-sync', to: '/spare-part-movements', /*can: 'inventories'*/ },
            // { label: 'Sortie', icon: 'pi pi-fw pi-arrow-circle-up', to: '/sorties', /*can: 'sorties'*/ },
            // { label: 'Entrée', icon: 'pi pi-fw pi-arrow-circle-down', to: '/entries', /*can: 'entries'*/ },
            { label: 'Piece detachees', icon: 'pi pi-fw pi-book', to: '/spare-parts', /*can: 'categories'*/ }
       , { label: 'Equipements', icon: 'pi pi-fw pi-cog', to: '/equipments', /*can: 'equipment'*/ }

        ]
    },
    {
        label: 'Gestion de taches',
        items: [
            { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda', /*can: 'projects'*/ },
            { label: 'Mes activités', icon: 'pi pi-fw pi-list', to: '/activities', /*can: 'projects'*/ },
            { label: 'Ordre de Travail', icon: 'pi pi-fw pi-check-square', to: '/tasks', /*can: 'projects'*/ },
            { label: 'Plan de maintenance', icon: 'pi pi-fw pi-wrench', to: '/maintenances', /*can: 'projects'*/ },
          ]
    },
    {
        label: 'Gestion des equipes',
        items: [
            { label: 'Equipes', icon: 'pi pi-fw pi-users', to: '/teams', /*can: 'teams'*/ },
            { label: 'Techniciens', icon: 'pi pi-fw pi-user', to: '/technicians', /*can: 'techniciens'*/ }
        ]
    },

    {
        label: 'SYSTEME CONFIGURATION',
        items: [
            {
                label: 'Gestion utilisateurs',
                icon: 'pi pi-fw pi-shield',
                /*can: ['read user', 'read role', 'read permission'],*/
                items: [
                    {
                        label: 'Utilisateurs',
                        to: '/users',
                        /*can: 'read user'*/
                    },
                    {
                        label: 'Roles',
                        to: '/roles',
                        /*can: 'read role'*/
                    },
                    {
                        label: 'Permissions',
                        to: '/permissions',
                        /*can: 'read permission'*/
                    }
                ]
            },
            {
                label: 'Rapports',
                icon: 'pi pi-fw pi-chart-bar',
                to: '/repports',
                /*can: 'repports'*/
            },
            {
                label: 'Maps',
                icon: 'pi pi-fw pi-map-marker',
                to: '/googlemaps'
            },
            {
                label: 'Region',
                icon: 'pi pi-fw pi-map',
                to: '/regions'
            },
            {
                label: 'Engins roulants',
                icon: 'pi pi-fw pi-car',
                to: '/engins'
            },
                        { label: 'Unités', icon: 'pi pi-fw pi-tag', to: '/unities', /*can: 'unities'*/ }
, {
                label: 'Labels',
                icon: 'pi pi-fw pi-pencil',
                to: '/labels',
                /*can: 'priority'*/
            },
            {
                label: 'Priorité du système',
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
