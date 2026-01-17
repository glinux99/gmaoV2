<script setup>
import { useLayout } from '@/sakai/layout/composables/layout';
import { computed, ref, watch, onMounted } from 'vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import { router, usePage } from '@inertiajs/vue3';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import { useI18n } from 'vue-i18n';

const { layoutConfig, layoutState, isSidebarActive, resetMenu } = useLayout();
const outsideClickListener = ref(null);

// --- GESTION DU SCROLL PROFESSIONNELLE ---

// Variable pour savoir si on vient de cliquer sur un lien du menu
const isNavigatingToNewPage = ref(false);

router.on('before', (event) => {
    // Si l'URL change vraiment (nouvelle page), on autorise la remontée
    // Si c'est juste un paramètre de filtre (?start_date=...), on reste stable
    const isFilter = event.detail.visit.data?.hasOwnProperty('start_date');
    isNavigatingToNewPage.value = !isFilter;

    // Sauvegarde du menu
    const sidebarEl = document.querySelector('.layout-sidebar');
    if (sidebarEl) sessionStorage.setItem('sidebar-scroll', sidebarEl.scrollTop);
});

router.on('finish', () => {
    // 1. Remonter la PAGE uniquement si c'est une nouvelle navigation
    if (isNavigatingToNewPage.value) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 2. Restaurer le MENU (toujours)
    const sidebarEl = document.querySelector('.layout-sidebar');
    const scrollPos = sessionStorage.getItem('sidebar-scroll');
    if (sidebarEl && scrollPos) {
        sidebarEl.scrollTop = scrollPos;
    }
});

// --- LOGIQUE SAKAI ---
watch(isSidebarActive, (newVal) => {
    if (newVal) bindOutsideClickListener();
    else unbindOutsideClickListener();
});

const containerClass = computed(() => ({
    'layout-overlay': layoutConfig.menuMode === 'overlay',
    'layout-static': layoutConfig.menuMode === 'static',
    'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
    'layout-overlay-active': layoutState.overlayMenuActive,
    'layout-mobile-active': layoutState.staticMenuMobileActive
}));

const bindOutsideClickListener = () => {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) resetMenu();
        };
        document.addEventListener('click', outsideClickListener.value);
    }
};

const unbindOutsideClickListener = () => {
    if (outsideClickListener.value) {
        document.removeEventListener('click', outsideClickListener.value);
        outsideClickListener.value = null;
    }
};

const isOutsideClicked = (event) => {
    const sidebarEl = document.querySelector('.layout-sidebar');
    const topbarEl = document.querySelector('.layout-menu-button');
    if (!sidebarEl || !topbarEl) return false;
    return !(sidebarEl.isSameNode(event.target) || sidebarEl.contains(event.target) || topbarEl.isSameNode(event.target) || topbarEl.contains(event.target));
};

// --- FILTRES DE DATE ---
const { t } = useI18n();
const page = usePage();
const filters = computed(() => page.props.filters || {});
const dateRange = ref(null);
const filterType = ref(filters.value.filterType || 'this_month');

watch(dateRange, (newDates) => {
    if (newDates?.[0] && newDates?.[1]) {
        const start = newDates[0].toISOString().split('T')[0];
        const end = newDates[1].toISOString().split('T')[0];

        router.get(page.url, {
            start_date: start,
            end_date: end,
            filterType: filterType.value,
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true // IMPORTANT: Ne bouge pas l'écran quand on filtre
        });
    }
}, { deep: true });
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>

        <div class="layout-main-container">
            <div class="px-0 py-4 sticky top-0 z-10 bg-[#f8fafc]/80 backdrop-blur-md">
                <div class="flex items-center justify-end gap-2 p-3 bg-white rounded-lg shadow-sm border">
                    <Dropdown
                        v-model="filterType"
                        :options="[
                            { label: t('date_filters.this_month'), value: 'this_month' },
                            { label: t('date_filters.last_month'), value: 'last_month' },
                            { label: t('date_filters.last_week'), value: 'last_week' },
                            { label: t('date_filters.custom'), value: 'custom' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full md:w-48"
                    />
                    <Calendar v-if="filterType === 'custom'" v-model="dateRange" selectionMode="range" dateFormat="dd/mm/yy" class="w-full md:w-64" />
                </div>
            </div>

            <div class="layout-main">
                <slot></slot>
            </div>

            <app-footer></app-footer>
        </div>

        <div class="layout-mask animate-fadein"></div>
    </div>
    <Toast />
</template>

<style>
/* Sidebar indépendante */
.layout-sidebar {
    overflow-y: auto;
    max-height: 100vh;
    position: fixed;
}

/* Scroll fluide uniquement pour la page */
html {
    scroll-behavior: smooth;
}
</style>
