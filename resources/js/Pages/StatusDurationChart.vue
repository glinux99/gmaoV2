<template>
    <Card class="border-none shadow-sm rounded-3xl overflow-hidden">
        <template #title>
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-6 bg-purple-500 rounded-full"></div>
                    <span class="text-xl font-black text-slate-800">{{ chartData.title }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Dropdown v-model="equipmentModel" :options="equipments" optionLabel="designation" optionValue="id"
                        placeholder="Filtrer par équipement" showClear
                        class="!rounded-xl !text-sm w-full md:w-auto" />
                    <Dropdown v-model="zoneModel" :options="zones" optionLabel="title" optionValue="id"
                        placeholder="Filtrer par zone" showClear
                        class="!rounded-xl !text-sm w-full md:w-auto" />
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2 ml-5">{{ chartData.subtitle }}</p>
        </template>
        <template #content>
            <div class="h-[350px] mt-4">
                <Chart type="bar" :data="barChartData" :options="barChartOptions" class="h-full" />
            </div>
        </template>
    </Card>
</template>

<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import Chart from 'primevue/chart';

const props = defineProps({
    chartData: {
        type: Object,
        required: true,
    },
    equipments: {
        type: Array,
        default: () => []
    },
    zones: {
        type: Array,
        default: () => []
    },
    selectedEquipment: {
        type: [Number, String],
        default: null
    },
    selectedZone: {
        type: [Number, String],
        default: null
    },
});

const barChartData = computed(() => ({
    labels: props.chartData.labels || [],
    datasets: [
        {
            label: 'Heures passées',
            backgroundColor: '#8B5CF6',
            borderColor: '#8B5CF6',
            borderRadius: 6,
            data: props.chartData.data || [],
        },
    ],
}));

const barChartOptions = computed(() => ({
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
        legend: {
            display: false, // On cache la légende car le titre est suffisant
        },
    },
    scales: {
        y: {
            ticks: {
                color: '#475569',
            },
            grid: {
                color: '#e2e8f0',
                borderDash: [5, 5],
            },
        },
        x: {
            ticks: {
                color: '#475569',
            },
            grid: {
                display: false,
            },
        },
    },
}));

const emit = defineEmits(['update:selectedEquipment', 'update:selectedZone']);

const equipmentModel = computed({
    get: () => props.selectedEquipment,
    set: (value) => emit('update:selectedEquipment', value)
});
const zoneModel = computed({
    get: () => props.selectedZone,
    set: (value) => emit('update:selectedZone', value)
});
</script>
