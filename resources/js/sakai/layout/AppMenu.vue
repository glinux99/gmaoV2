<script setup>
import { ref } from 'vue';
import { useLayout } from '@/sakai/layout/composables/layout';
import AppMenuItem from './AppMenuItem.vue';

const { resetMenu } = useLayout();

const model = ref([

    // ==================== CONTENU DU SITE (BLOG & PAGES) ====================
    {
        label: 'Contenu du site',
        icon: 'pi pi-fw pi-file',
        items: [
             {
        label: 'Dashboard',
        icon: 'pi pi-fw pi-home',
        to: '/dashboard'
    },
            { label: 'Articles / Actualités', icon: 'pi pi-fw pi-megaphone', to: '/posts' },
            { label: 'Catégories d\'articles', icon: 'pi pi-fw pi-tags', to: '/categories' },
            // { label: 'Pages statiques', icon: 'pi pi-fw pi-file', to: '/pages' },
            // { label: 'Médias (images)', icon: 'pi pi-fw pi-image', to: '/media' },
            // { label: 'Documents PDF', icon: 'pi pi-fw pi-folder', to: '/documents' }
        ]
    },
    // ==================== PAGE D'ACCUEIL (DYNAMIQUE) ====================
    {
        label: 'Page d\'accueil',
        icon: 'pi pi-fw pi-home',
        items: [
            { label: 'Sliders héro', icon: 'pi pi-fw pi-images', to: '/admin/hero-slides' },
            { label: 'Piliers d\'intervention', icon: 'pi pi-fw pi-heart', to: '/admin/initiatives' },
            { label: 'Projets terrain', icon: 'pi pi-fw pi-briefcase', to: '/admin/projects' },
            { label: 'Membres de l\'équipe', icon: 'pi pi-fw pi-users', to: '/teams' },
            { label: 'Statistiques globales', icon: 'pi pi-fw pi-chart-line', to: '/admin/settings?tab=stats' }
        ]
    },
    // ==================== RELATIONS PUBLIQUES ====================
    {
        label: 'Relations publiques',
        icon: 'pi pi-fw pi-globe',
        items: [
            { label: 'Partenaires', icon: 'pi pi-fw pi-building', to: '/admin/partners' },
            { label: 'Témoignages', icon: 'pi pi-fw pi-comment', to: '/admin/testimonials' },
            { label: 'Donateurs & Dons', icon: 'pi pi-fw pi-heart-fill', to: '/admin/donors' },
            // { label: 'FAQ (questions/réponses)', icon: 'pi pi-fw pi-question-circle', to: '/admin/faq' }
        ]
    },
    // ==================== COMMUNICATION ====================
    {
        label: 'Communication',
        icon: 'pi pi-fw pi-envelope',
        items: [
            { label: 'Contacts', icon: 'pi pi-fw pi-inbox', to: '/contacts' },
            { label: 'Messages de contact', icon: 'pi pi-fw pi-inbox', to: '/messages' },
            { label: 'Demandes de bénévolat', icon: 'pi pi-fw pi-heart', to: '/admin/volunteers' },
            { label: 'Newsletter', icon: 'pi pi-fw pi-send', to: '/admin/newsletters' }
        ]
    },
    // ==================== GESTION D'ÉQUIPE & UTILISATEURS ====================
    {
        label: 'Utilisateurs & rôles',
        icon: 'pi pi-fw pi-shield',
        items: [
            { label: 'Utilisateurs', icon: 'pi pi-fw pi-user', to: '/users' },
            { label: 'Rôles', icon: 'pi pi-fw pi-users', to: '/roles' },
            { label: 'Permissions', icon: 'pi pi-fw pi-lock', to: '/permissions' },
            { label: 'Équipes', icon: 'pi pi-fw pi-sitemap', to: '/teams' }
        ]
    },
    // ==================== CONFIGURATION SYSTÈME ====================
    {
        label: 'Configuration',
        icon: 'pi pi-fw pi-cog',
        items: [
            { label: 'Paramètres généraux', icon: 'pi pi-fw pi-sliders-h', to: '/admin/settings' },
            // { label: 'Médias & stockage', icon: 'pi pi-fw pi-database', to: '/media-settings' },
            // { label: 'Sauvegardes', icon: 'pi pi-fw pi-download', to: '/backups' }
        ]
    }
]);
</script>

<template>
    <ul class="layout-menu">
        <!-- Bouton de fermeture pour mobile -->
        <li class="flex justify-end p-3 lg:hidden">
            <button @click="resetMenu" class="flex items-center justify-center w-10 h-10 rounded-full text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors duration-200" aria-label="Fermer le menu">
                <i class="pi pi-times text-xl"></i>
            </button>
        </li>
        <template v-for="(item, i) in model" :key="item.label + i">
            <app-menu-item v-if="!item.separator" :item="item" :root="true" :index="i" />
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
