<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { computed } from 'vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';

const props = defineProps({
    // La liste des configurations de rapport à afficher
    reportWidgets: {
        type: Array,
        required: true,
    },
    // Un objet contenant les données réelles pour chaque source de données
    // Exemple: { tasks_by_status: { labels: [...], data: [...] }, ... }
    reportData: {
        type: Object,
        required: true,
    }
});

// Options de base pour les graphiques, peuvent être étendues
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const kpiWidgets = computed(() => props.reportWidgets.filter(w => w.chart_type === 'kpi'));
const chartWidgets = computed(() => props.reportWidgets.filter(w => w.chart_type !== 'kpi'));

/**
 * Formate les données pour un widget spécifique.
 * @param {Object} widget - La configuration du widget.
 * @returns {Object} - Les données formatées pour le composant Chart de PrimeVue.
 */
const getChartDataForWidget = (widget) => {
    const data = props.reportData[widget.data_source];
    if (!data) {
        return { labels: ['Données non disponibles'], datasets: [{ data: [1] }] };
    }

    // Logique de formatage basée sur le type de graphique
    const baseDatasetOptions = {
        backgroundColor: ['#42A5F5', '#FFA726', '#66BB6A', '#EF5350', '#AB47BC', '#26C6DA', '#FFCA28'],
        borderColor: '#1E88E5',
    };

    if (widget.chart_type === 'line' || widget.chart_type === 'bar') {
        return {
            labels: data.labels || [],
            datasets: [{
                label: widget.name,
                data: data.data || [],
                ...baseDatasetOptions
            }]
        };
    }

    return {
        labels: data.labels || [],
        datasets: [{
            data: data.data || [],
            ...baseDatasetOptions
        }]
    };
};

const getGridClass = (widget) => {
    const colSpan = widget.grid_options?.col_span || 6;
    return `col-span-12 md:col-span-${colSpan}`;
};

const getRowStyle = (widget) => {
    const rowSpan = widget.grid_options?.row_span || 1;
    // Chaque unité de hauteur correspond à 24rem (environ 384px)
    return { minHeight: `${rowSpan * 24}rem` };
};

</script>

<template>
    <AppLayout title="Analyse et Rapports">
        <Head title="Analyse" />

        <div class="grid grid-cols-12 gap-6">

            <!-- Section pour les indicateurs clés (KPI) -->
            <div v-for="widget in kpiWidgets" :key="widget.id" :class="getGridClass(widget)">
                <Card class="h-full flex flex-col justify-center items-center text-center p-4">
                    <template #title><span class="text-lg font-semibold text-gray-500">{{ widget.name }}</span></template>
                    <template #content>
                        <div class="text-5xl font-bold text-primary-600 mt-2">
                            {{ reportData[widget.data_source]?.value ?? 'N/A' }}
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Section pour les graphiques -->
            <div v-for="widget in chartWidgets" :key="widget.id" :class="getGridClass(widget)">
                <Card class="h-full">
                    <template #title>{{ widget.name }}</template>
                    <template #subtitle>{{ widget.description }}</template>
                    <template #content>
                        <div class="relative" :style="getRowStyle(widget)">
                            <Chart :type="widget.chart_type" :data="getChartDataForWidget(widget)" :options="chartOptions" class="absolute top-0 left-0 w-full h-full" />
                        </div>
                    </template>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
