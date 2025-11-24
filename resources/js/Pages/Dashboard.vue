
<script setup>
    import AppLayout from "@/sakai/layout/AppLayout.vue";
    import { ref } from 'vue';

    const props = defineProps({
        users: Number,
        roles: Number,
        activeTasks: Number,
        timeSpent: String,
        averageInterventionTime: String,
        permissions: Number,
    });

    const items = ref([
        {
            label: 'User',
            value: props.users ?? 0,
            icon: 'pi pi-users',
            iconBgColor: 'bg-blue-100 dark:bg-blue-400/10',
            iconColor: 'text-blue-500'
        },
        {
            label: 'Tâches Actives',
            value: props.activeTasks?? 0,
            icon: 'pi pi-check-square',
            iconBgColor: 'bg-purple-100 dark:bg-purple-400/10',
            iconColor: 'text-purple-500'
        },
        {
            label: 'Temps Passé',
            value: props.timeSpent?? 0,
            icon: 'pi pi-hourglass',
            iconBgColor: 'bg-green-100 dark:bg-green-400/10',
            iconColor: 'text-green-500'
        },
        {
            label: 'Temps d\'Intervention Moyen',
            value: props.averageInterventionTime?? 0,
            icon: 'pi pi-clock',
            iconBgColor: 'bg-teal-100 dark:bg-teal-400/10',
            iconColor: 'text-teal-500'
        },
        {
            label: 'Role',
            value: props.roles?? 0,
            icon: 'pi pi-unlock',
            iconBgColor: 'bg-orange-100 dark:bg-orange-400/10',
            iconColor: 'text-orange-500'
        },
        {
            label: 'Permission',
            value: props.permissions?? 0,
            icon: 'pi pi-key',
            iconBgColor: 'bg-cyan-100 dark:bg-cyan-400/10',
            iconColor: 'text-cyan-500'
        }
    ]);
</script>

<template>
    <app-layout>
        <div class="grid grid-cols-12 gap-8">
            <div v-for="(item, index) in items" :key="index" class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div class="card mb-0 h-full">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-4">{{ item.label }}</span>
                            <div class="layout-menuitem-root-text font-medium text-3xl text-primary">{{ item.value }}</div>
                        </div>
                        <div
                            class="flex items-center justify-center rounded-border"
                            :class="item.iconBgColor"
                            style="width: 2.5rem; height: 2.5rem"
                        >
                            <i :class="[item.icon, item.iconColor]" class="!text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            </div>

    </app-layout>
</template>

<style scoped lang="scss">

</style>
