<script setup>
import { useLayout } from '@/sakai/layout/composables/layout';
import { onBeforeMount, ref, watch } from 'vue';
import NavLink from "@/Components/NavLink.vue";

import { useI18n } from 'vue-i18n';
const { layoutState, setActiveMenuItem, onMenuToggle } = useLayout();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    index: {
        type: Number,
        default: 0
    },
    root: {
        type: Boolean,
        default: true
    },
    parentItemKey: {
        type: String,
        default: null
    }
});
const itemClick = (event, item) => {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    // AJOUT : Empêche le scroll si c'est un parent de sous-menu ou une commande
    if (!item.to || item.items) {
        event.preventDefault();
    }

    if ((item.to || item.url) && (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)) {
        onMenuToggle();
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }

    const foundItemKey = item.items ? (isActiveMenu.value ? props.parentItemKey : itemKey.value) : itemKey.value;

    setActiveMenuItem(foundItemKey);
};
const isActiveMenu = ref(false);
const itemKey = ref(null);
const { t } = useI18n();

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? props.parentItemKey + '-' + props.index : String(props.index);

    const activeItem = layoutState.activeMenuItem;

    isActiveMenu.value = activeItem === itemKey.value || activeItem ? activeItem.startsWith(itemKey.value + '-') : false;
});

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(itemKey.value + '-');
    }
);


// const checkActiveRoute = (item) => {
//     return route.path === item.to;
// };
</script>

<template>
    <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
        <div v-if="root && item.visible !== false" class="layout-menuitem-root-text">{{ t(item.label) }}</div>

        <a v-if="(!item.to || item.items) && item.visible !== false"
           :href="item.url"
           @click.prevent="itemClick($event, item)"
           :class="item.class"
           :target="item.target"
           tabindex="0">
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ t(item.label) }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items"></i>
        </a>

        <nav-link v-if="item.to && !item.items && item.visible !== false"
                  @click="itemClick($event, item)"
                  :href="item.to"
                  :class="[item.class, { 'active-route': $page.url === item.to }]"
                  preserve-scroll>
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ t(item.label) }}</span>
        </nav-link>

        <Transition v-if="item.items && item.visible !== false" name="layout-submenu">
            <ul v-show="root ? true : isActiveMenu" class="layout-submenu">
                <app-menu-item v-for="(child, i) in item.items"
                               :key="child.label"
                               :index="i"
                               :item="child"
                               :parentItemKey="itemKey"
                               :root="false">
                </app-menu-item>
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped></style>
