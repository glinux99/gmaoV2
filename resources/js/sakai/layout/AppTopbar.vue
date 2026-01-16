<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useLayout } from '@/sakai/layout/composables/layout';
import { updatePreset, updateSurfacePalette } from '@primevue/themes';
import Aura from '@primevue/themes/aura';
import Lara from '@primevue/themes/lara';
import AppConfigurator from './AppConfigurator.vue';
import NavLink from "@/Components/NavLink.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

/**
 * LOGIQUE DE GESTION DU LAYOUT ET DES THÈMES
 */
const { layoutConfig, setPrimary, setSurface, setPreset, isDarkTheme, setMenuMode, onMenuToggle, toggleDarkMode } = useLayout();
const { t, locale } = useI18n();
const page = usePage();
const user = page.props.auth.user;

// Configuration des Presets PrimeVue
const presets = { Aura, Lara };
const preset = ref(layoutConfig.preset);

// Configuration des Modes de Menu avec traduction réactive
const menuModeOptions = computed(() => [
    { label: t('topbar.menuModes.static'), value: 'static' },
    { label: t('topbar.menuModes.overlay'), value: 'overlay' }
]);

// Options de thèmes avec traduction réactive via computed
const menuThemes = computed(() => [
    { name: 'white', color: '#ffffff', label: t('topbar.themes.light') },
    { name: 'dark', color: '#0f172a', label: t('topbar.themes.dark') },
    { name: 'primary', color: 'var(--p-primary-500)', label: t('topbar.themes.accent') }
]);

// Palette de couleurs primaires ERP
const primaryColors = ref([
    { name: 'emerald', palette: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b', 950: '#022c22' } },
    { name: 'blue', palette: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a', 950: '#172554' } },
    { name: 'indigo', palette: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81', 950: '#1e1b4b' } },
    { name: 'orange', palette: { 50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12', 950: '#431407' } },
    { name: 'noir', palette: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a', 950: '#020617' } }
]);

/**
 * GESTION DES LANGUES
 */
const languages = [
    { name: 'Français', code: 'FR', flag: 'https://flagcdn.com/w40/fr.png' },
    { name: 'English', code: 'EN', flag: 'https://flagcdn.com/w40/gb.png' },
    { name: 'Swahili', code: 'SW', flag: 'https://flagcdn.com/w40/tz.png' },
    { name: 'Lingala', code: 'LN', flag: 'https://flagcdn.com/w40/cd.png' }
];

// On cherche la langue actuelle basée sur la locale
const currentLang = computed(() => {
    return languages.find(l => l.code.toLowerCase() === locale.value) || languages[0];
});

const searchQuery = ref('');

/**
 * MÉTHODES DE MISE À JOUR DU THÈME
 */
function getPresetExt() {
    const color = primaryColors.value.find((c) => c.name === layoutConfig.primary) || primaryColors.value[2];
    if (color.name === 'noir') {
        return { semantic: { primary: { 500: '{surface.950}' }, colorScheme: { light: { primary: { color: '{primary.500}' } } } } };
    }
    return { semantic: { primary: color.palette } };
}

function changeLanguage(lang) {
    locale.value = lang.code.toLowerCase();
}

/**
 * ÉCOUTEURS D'ÉVÉNEMENTS (RACCOURCIS CLAVIER)
 */
const handleKeyDown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('topbar-search')?.focus();
    }
};

onMounted(() => window.addEventListener('keydown', handleKeyDown));
onBeforeUnmount(() => window.removeEventListener('keydown', handleKeyDown));
</script>

<template>
    <header class="layout-topbar h-20 bg-white/90 backdrop-blur-md border-b border-gray-100 px-6 flex items-center justify-between sticky top-0 z-[100] shadow-sm transition-all duration-300">

        <div class="flex items-center gap-6 min-w-[240px]">
            <button
                class="group relative flex items-center justify-center w-11 h-11 rounded-xl bg-gray-50 hover:bg-indigo-600 border-none cursor-pointer transition-all duration-300 overflow-hidden shadow-sm"
                @click="onMenuToggle"
                :title="t('topbar.toggle_sidebar')"
            >
                <div class="flex flex-col gap-1.5 transition-all duration-300 group-hover:scale-110">
                    <span class="w-5 h-0.5 bg-gray-600 group-hover:bg-white rounded-full transition-colors"></span>
                    <span class="w-3 h-0.5 bg-gray-400 group-hover:bg-white/80 rounded-full transition-colors"></span>
                    <span class="w-5 h-0.5 bg-gray-600 group-hover:bg-white rounded-full transition-colors"></span>
                </div>
            </button>

            <nav-link href="/dashboard" class="flex items-center">
                <img src="/assets/media/logos/logo.png" alt="Virunga Energies ERP" class="h-9 w-auto hover:opacity-80 transition-opacity" />
            </nav-link>
        </div>

        <div class="hidden lg:flex flex-1 justify-center max-w-xl px-8">
            <div class="relative w-full group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center pointer-events-none">
                    <i class="pi pi-search text-gray-400 group-focus-within:text-indigo-600 transition-colors duration-300"></i>
                </div>
                <input
                    id="topbar-search"
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('topbar.search_placeholder')"
                    class="w-full bg-gray-100/50 border-2 border-transparent rounded-2xl py-2.5 pl-12 pr-20 text-sm focus:ring-0 focus:border-indigo-100 focus:bg-white transition-all duration-300 outline-none text-gray-700 font-medium"
                />
                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex gap-1 pointer-events-none opacity-60 group-focus-within:opacity-100 transition-opacity">
                    <kbd class="px-2 py-1 bg-white border border-gray-200 rounded-md text-[9px] font-black text-gray-500 shadow-sm uppercase tracking-tighter">Ctrl</kbd>
                    <kbd class="px-2 py-1 bg-white border border-gray-200 rounded-md text-[9px] font-black text-gray-500 shadow-sm uppercase">K</kbd>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="layout-config-menu flex items-center">
                <button type="button" class="layout-topbar-action p-2.5 rounded-xl hover:bg-gray-50 border-none bg-transparent cursor-pointer" @click="toggleDarkMode">
                    <i :class="['pi text-lg', { 'pi-moon text-indigo-500': isDarkTheme, 'pi-sun text-amber-500': !isDarkTheme }]"></i>
                </button>

                <div class="relative ml-1">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight p-2.5 rounded-xl hover:bg-gray-50 border-none bg-transparent cursor-pointer"
                    >
                        <i class="pi pi-palette text-gray-600 text-lg"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action lg:hidden p-2.5 rounded-xl hover:bg-gray-50 border-none bg-transparent cursor-pointer"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v text-gray-600"></i>
            </button>

            <div class="h-6 w-px bg-gray-200 mx-2"></div>

            <div class="relative">
                <button
                    v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                    class="flex items-center gap-2.5 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-xl transition-all cursor-pointer border-none"
                >
                    <img :src="currentLang.flag" class="w-5 h-3.5 rounded shadow-sm object-cover" />
                    <span class="text-[10px] font-black text-gray-700 uppercase">{{ currentLang.code }}</span>
                </button>
                <div class="hidden absolute right-0 mt-4 w-44 bg-white shadow-2xl rounded-2xl border border-gray-100 z-[120] p-1.5 overflow-hidden">
                    <button v-for="l in languages" :key="l.code" @click="changeLanguage(l)"
                        class="flex items-center gap-3 w-full p-2.5 rounded-xl hover:bg-indigo-50 transition-all border-none bg-transparent cursor-pointer group text-left">
                        <img :src="l.flag" class="w-5 h-3.5 rounded shadow-sm" />
                        <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-700">{{ l.name }}</span>
                    </button>
                </div>
            </div>

            <div class="relative ml-2">
                <button
                    v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                    class="flex items-center gap-3 p-1.5 rounded-2xl hover:bg-gray-50 transition-all border-none bg-transparent cursor-pointer group"
                >
                    <div class="relative">
                        <img :src="user?.avatar || user?.profile_photo_url || '/assets/media/avatars/blank.png'" class="w-10 h-10 rounded-xl object-cover ring-2 ring-white shadow-md border border-gray-100" />
                        <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-white"></span>
                        </span>
                    </div>
                    <div class="hidden xl:flex flex-col text-left">
                        <span class="text-[11px] font-black text-gray-900 leading-none uppercase tracking-tight">{{ user?.name }}</span>
                        <span class="text-[9px] font-bold text-emerald-500 mt-1 uppercase tracking-widest">{{ t('topbar.status.connected') }}</span>
                    </div>
                    <i class="pi pi-chevron-down text-[10px] text-gray-400 group-hover:text-indigo-600 transition-colors ml-1"></i>
                </button>

                <div class="hidden absolute right-0 mt-4 w-64 bg-white shadow-2xl rounded-2xl border border-gray-100 z-[130] overflow-hidden text-left">
                    <div class="p-6 bg-gradient-to-br from-gray-50 to-white text-center border-b border-gray-100">
                        <div class="relative inline-block mb-3">
                            <img :src="user?.avatar || user?.profile_photo_url || '/assets/media/avatars/blank.png'" :alt="user?.profile_photo_url"  class="w-16 h-16 rounded-2xl mx-auto shadow-xl border-2 border-white" />
                        </div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ user.name }}</h3>
                        <p class="text-[10px] text-gray-500 font-bold mt-1 lowercase">{{ user.email }}</p>
                    </div>

                    <div class="p-2.5 space-y-1">

                        <NavLink href="/settings" class="flex items-center gap-3 p-3 rounded-xl hover:bg-indigo-50 no-underline border-none text-gray-600 font-bold text-xs transition-colors group">
                            <i class="pi pi-user-edit text-indigo-400 group-hover:text-indigo-600"></i> {{ t('topbar.userMenu.edit_profile') }}
                        </NavLink>
                        <NavLink href="/settings" class="flex items-center gap-3 p-3 rounded-xl hover:bg-indigo-50 no-underline border-none text-gray-600 font-bold text-xs transition-colors group">
                            <i class="pi pi-shield text-gray-400 group-hover:text-indigo-600"></i> {{ t('topbar.userMenu.security') }}
                        </NavLink>

                        <div class="border-t border-gray-50 my-2 mx-3"></div>

                        <DropdownLink :href="route('logout')" method="post" as="button"
                            class="flex items-center gap-3 p-3 rounded-xl w-full border-none bg-transparent hover:bg-red-50 text-red-500 cursor-pointer font-black text-[10px] uppercase tracking-wider transition-colors">
                            <i class="pi pi-power-off"></i> {{ t('topbar.userMenu.logout') }}
                        </DropdownLink>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
/* Vos styles sont conservés sans modification */
.animate-scalein { animation: scalein 0.25s cubic-bezier(0.25, 1, 0.5, 1); }
.animate-fadeout { animation: fadeout 0.2s ease-in forwards; }

@keyframes scalein {
    0% { opacity: 0; transform: scale(0.96) translateY(-8px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes fadeout {
    0% { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(0.96); }
}

.animate-ping { animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite; }
@keyframes ping {
    75%, 100% { transform: scale(2); opacity: 0; }
}

input::placeholder {
    font-weight: 600;
    color: #94a3b8;
    transition: color 0.3s;
}
input:focus::placeholder {
    color: #cbd5e1;
}
</style>
