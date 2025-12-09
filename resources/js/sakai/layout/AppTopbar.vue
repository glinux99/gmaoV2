<script setup>
import { useLayout } from '@/sakai/layout/composables/layout';
import AppConfigurator from './AppConfigurator.vue';
import NavLink from "@/Components/NavLink.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { usePage } from '@inertiajs/vue3';


const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
const page = usePage();
const user = page.props.auth.user;

</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="onMenuToggle">
                <i class="pi pi-bars"></i>
            </button>
            <nav-link href="/dashboard" class="layout-topbar-logo">
                <img src="/assets/media/logos/logo.png" alt="logo Virunga Energies" class="h-10" />

                <span></span>
            </nav-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
                    <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <div class="relative">
                        <button
                            type="button"
                            class="layout-topbar-action"
                            v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true, }"
                        >
                            <template v-if="user && (user.avatar || user.profile_photo_url)">

                                <img
                                    :src="user.avatar || user.profile_photo_url"
                                    alt="Avatar"
                                    class="w-8 h-8 object-cover rounded-full"
                                >
                            </template>
                            <template v-else>
                                <i class="pi pi-user text-xl"></i>
                            </template>
                            <span class="ml-2">{{ user?.name || 'Profil' }}</span>
                        </button>

                        <div class="hidden bg-white shadow-xl absolute right-0 mt-2 w-64 py-2 rounded-lg border border-gray-100 z-50">

                            <div class="flex items-center p-4 border-b mb-2">
                                <div class="w-10 h-10 mr-3">
                                    <img
                                        :src="(user && (user.avatar || user.profile_photo_url)) ? (user.avatar || user.profile_photo_url) : '/assets/media/avatars/blank.png'"
                                        alt="Avatar"
                                        class="w-full h-full object-cover rounded-full"
                                    >
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">
                                        {{ user.name }}
                                        <span v-if="user.roles && user.roles.length > 0" class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full text-green-700 bg-green-100">{{ user.roles[0].name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-600">{{ user.email }}</div>
                                </div>
                            </div>

    <NavLink href="/profile" class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="pi pi-id-card mr-3 text-lg"></i> Mon profile
                            </NavLink>

                             <NavLink href="/settings" class="w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="pi pi-cog mr-3 text-lg"></i> Paramètres du compte
                                </NavLink>

                            <!-- Option de langue, si nécessaire, peut être réactivée avec une logique appropriée -->
                            <!-- <div class="flex items-center justify-between px-4 py-2 text-sm text-gray-700">
                                <div class="flex items-center">
                                    <i class="pi pi-globe mr-3 text-lg"></i> Langue
                                </div>
                                <span class="inline-flex items-center px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg">Français <img src="https://flagcdn.com/w20/fr.png" alt="FR Flag" class="ml-2 h-3 border border-gray-300"></span>
                            </div> -->

                            <div class="border-t my-2"></div>

                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50"
                            >
                                <i class="pi pi-sign-out mr-3 text-lg"></i> Se deconnecter
                            </DropdownLink>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </div>
</template>
