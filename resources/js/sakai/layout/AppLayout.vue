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

watch(isSidebarActive, (newVal) => {
    if (newVal) {
        bindOutsideClickListener();
    } else {
        unbindOutsideClickListener();
    }
});

const containerClass = computed(() => {
    return {
        'layout-overlay': layoutConfig.menuMode === 'overlay',
        'layout-static': layoutConfig.menuMode === 'static',
        'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
        'layout-overlay-active': layoutState.overlayMenuActive,
        'layout-mobile-active': layoutState.staticMenuMobileActive
    };
});
const bindOutsideClickListener = () => {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) {
                resetMenu();
            }
        };
        document.addEventListener('click', outsideClickListener.value);
    }
};
const unbindOutsideClickListener = () => {
    if (outsideClickListener.value) {
        document.removeEventListener('click', outsideClickListener);
        outsideClickListener.value = null;
    }
};
const isOutsideClicked = (event) => {
    const sidebarEl = document.querySelector('.layout-sidebar');
    const topbarEl = document.querySelector('.layout-menu-button');

    return !(sidebarEl.isSameNode(event.target) || sidebarEl.contains(event.target) || topbarEl.isSameNode(event.target) || topbarEl.contains(event.target));
};

// --- Gestion des Filtres de Date ---
const { t } = useI18n();

const page = usePage();

// 1. Récupérer les filtres depuis les props partagées par Inertia
const filters = computed(() => page.props.filters || {});

// 2. Initialisation des dates
const getInitialDateRange = () => {
    const startDate = filters.value.startDate ? new Date(filters.value.startDate) : null;
    const endDate = filters.value.endDate ? new Date(filters.value.endDate) : null;
    return startDate && endDate ? [startDate, endDate] : null;
};

// 3. Les `ref` pour l'état du filtre
const dateRange = ref(getInitialDateRange());
const filterType = ref(filters.value.filterType || 'this_month');

// 4. Fonction de calcul des plages prédéfinies
const updateDateRange = () => {
    let startDate, endDate;
    const today = new Date();

    switch (filterType.value) {
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'last_week':
            const dayOfWeek = today.getDay(); // 0=Dimanche, 1=Lundi...
            const lastSunday = new Date(today);
            lastSunday.setDate(today.getDate() - dayOfWeek); // Aller au dimanche de la semaine en cours
            endDate = new Date(lastSunday);
            endDate.setDate(lastSunday.getDate() - 1); // Dimanche de la semaine passée
            startDate = new Date(endDate);
            startDate.setDate(endDate.getDate() - 6); // Lundi de la semaine passée
            break;
        default: // 'custom'
            return;
    }

    if (startDate && endDate) {
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(23, 59, 59, 999);
        dateRange.value = [startDate, endDate];
    }
};

// 5. Cycle de vie et écouteurs
onMounted(() => {
    if (!filters.value.startDate && !filters.value.endDate) {
       //  updateDateRange();
    }
});

watch(filterType, (newFilterType) => {
    if (newFilterType !== 'custom') {
        updateDateRange();
    }
});

watch(dateRange, (newDates) => {
    if (newDates && newDates[0] && newDates[1]) {
        const newStartDate = newDates[0].toISOString().split('T')[0];
        const newEndDate = newDates[1].toISOString().split('T')[0];

        const currentStartDate = filters.value.startDate;
        const currentEndDate = filters.value.endDate;

        if (newStartDate !== currentStartDate || newEndDate !== currentEndDate) {
            router.get(page.url, {
                start_date: newStartDate,
                end_date: newEndDate,
                filterType: filterType.value,
            }, { preserveState: true, replace: true, preserveScroll: true });
        }
    }
}, { deep: true });
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>
        <div class="layout-main-container">
            <div class="px-0 py-4">
                <div class="flex items-center justify-end gap-2 p-3 bg-white rounded-lg shadow-sm border">
                    <i class="pi pi-calendar text-xl text-gray-600"></i>
                    <h4 class="font-semibold text-gray-700 m-0 hidden md:block">{{ t('period') }} :</h4>

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
                        placeholder="Sélectionner une période" class="w-full md:w-48" />

                    <Calendar v-if="filterType === 'custom'" v-model="dateRange" selectionMode="range" :manualInput="false" dateFormat="dd/mm/yy" placeholder="Plage personnalisée" class="w-full md:w-64" />
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
