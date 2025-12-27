<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
// Imports PrimeVue indispensables
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';

const props = defineProps({
    usersCount: Number,
    activeTasksCount: Number,
    timeSpent: String,
    averageInterventionTime: String,
    sparklineData: Object,
    sparePartsMovement: Object,
    tasksByStatus: Object,
    tasksByPriority: Object,
    budgetTotal: Number,
    depensesPrestation: Number,
    expensesTotal: Number,
    monthlyVolumeData: Object,
    failuresByType: Object,
    interventionsByType: Object,
});

const { t } = useI18n();

// --- Utilitaires de Formatage ---
const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// --- Configuration des Graphiques ---

const pieOptions = {
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6 } }
    },
    maintainAspectRatio: false,
    cutout: '70%'
};

const sparklineOptions = {
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    maintainAspectRatio: false,
    elements: { point: { radius: 0 }, line: { borderWidth: 2.5 } },
    scales: { x: { display: false }, y: { display: false } }
};

const sparklineItems = computed(() => {
    const data = props.sparklineData;
    const items = [
        { key: 'users', label: t('dashboard.users'), value: props.usersCount || 0, icon: 'pi pi-users', color: '#3B82F6' },
        { key: 'activeTasks', label: t('dashboard.active_tasks'), value: props.activeTasksCount || 0, icon: 'pi pi-check-circle', color: '#10B981' },
        { key: 'timeSpent', label: 'Heures Totales', value: data?.timeSpent?.value || '0h', icon: 'pi pi-stopwatch', color: '#F59E0B' },
        { key: 'averageInterventionTime', label: 'Délai Moyen', value: data?.averageInterventionTime?.value || '0m', icon: 'pi pi-bolt', color: '#8B5CF6' }
    ];

    return items.map(item => {
        const chartSource = data?.[item.key]?.chartData;
        return {
            ...item,
            metric: data?.[item.key]?.metric || '0%',
            chartData: {
                labels: chartSource?.labels || ['', '', '', '', ''],
                datasets: [{
                    data: chartSource?.datasets?.[0]?.data || [0, 0, 0, 0, 0],
                    borderColor: item.color,
                    backgroundColor: item.color + '15',
                    fill: true,
                    tension: 0.5
                }]
            }
        };
    });
});

const comboChartData = computed(() => ({
    labels: props.monthlyVolumeData?.labels || [],
    datasets: [
        { type: 'bar', label: 'Panne Totale', backgroundColor: '#EF4444', data: props.monthlyVolumeData?.stopped || [], borderRadius: 6 },
        { type: 'bar', label: 'Dégradé', backgroundColor: '#F59E0B', data: props.monthlyVolumeData?.degraded || [], borderRadius: 6 },
        { type: 'line', label: 'Tps Résolution (h)', borderColor: '#6366F1', data: props.monthlyVolumeData?.resolutionTime || [], yAxisID: 'y1', tension: 0.4, borderDash: [5, 5] }
    ]
}));

const failuresChartData = computed(() => ({
    labels: props.failuresByType?.labels || [],
    datasets: [{
        data: props.failuresByType?.data || [],
        backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#EC4899']
    }]
}));

const tasksChartType = ref('status');
const tasksDistributionData = computed(() => {
    const source = tasksChartType.value === 'status' ? props.tasksByStatus : props.tasksByPriority;
    return {
        labels: source?.labels || [],
        datasets: [{
            data: source?.data || [],
            backgroundColor: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#F43F5E']
        }]
    };
});
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-slate-50 min-h-screen space-y-8">

            <section>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="bg-primary-600 p-3 rounded-2xl shadow-primary-200 shadow-lg">
                            <i class="pi pi-chart-bar text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Tableau de Bord</h1>
                            <p class="text-slate-500 font-medium">Analyse temps réel de la maintenance</p>
                        </div>
                    </div>

                    <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                        <div class="px-4 py-2 border-r border-slate-100">
                            <p class="text-[10px] uppercase font-bold text-slate-400 leading-tight">Budget Total</p>
                            <p class="text-lg font-black text-slate-800">{{ formatCurrency(budgetTotal) }}</p>
                        </div>
                        <div class="px-4 py-2">
                            <p class="text-[10px] uppercase font-bold text-slate-400 leading-tight">Dépenses Validées</p>
                            <p class="text-lg font-black text-emerald-600">{{ formatCurrency(expensesTotal) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="item in sparklineItems" :key="item.label"
                     class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div :style="{ backgroundColor: item.color + '15', color: item.color }" class="p-3 rounded-2xl group-hover:scale-110 transition-transform">
                            <i :class="item.icon" class="text-xl"></i>
                        </div>
                        <Tag :value="item.metric" :severity="item.metric.toString().startsWith('-') ? 'danger' : 'success'" rounded />
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ item.label }}</p>
                            <h2 class="text-3xl font-black text-slate-800 mt-1">{{ item.value }}</h2>
                        </div>
                        <div class="w-24 h-12">
                            <Chart type="line" :data="item.chartData" :options="sparklineOptions" class="w-full h-full" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-12 gap-6">
                <Card class="col-span-12 lg:col-span-8 border-none shadow-sm rounded-3xl overflow-hidden">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-6 bg-primary-500 rounded-full"></div>
                            <span class="text-xl font-black text-slate-800">Volume & Performance Mensuelle</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[400px] mt-4">
                            <Chart type="bar" :data="comboChartData" :options="{
                                ...pieOptions,
                                cutout: '0%',
                                plugins: { legend: { position: 'top', align: 'end' } },
                                scales: {
                                    y: { stacked: true, grid: { borderDash: [5, 5] } },
                                    y1: { position: 'right', grid: { display: false } }
                                }
                            }" class="h-full" />
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 lg:col-span-4 border-none shadow-sm rounded-3xl">
                    <template #title>
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-black text-slate-800">Tâches</span>
                            <Dropdown v-model="tasksChartType" :options="[{label:'Statut', value:'status'}, {label:'Priorité', value:'priority'}]"
                                     optionLabel="label" optionValue="value" class="!rounded-xl !text-sm" />
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[320px] relative flex items-center justify-center">
                            <Chart type="doughnut" :data="tasksDistributionData" :options="pieOptions" class="h-full w-full" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-4xl font-black text-slate-800">{{ activeTasksCount || 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Total Actif</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 md:col-span-6 border-none shadow-sm rounded-3xl">
                    <template #title><span class="text-lg font-black text-slate-800">Types de Défaillances</span></template>
                    <template #content>
                        <div class="h-[300px]">
                            <Chart type="pie" :data="failuresChartData" :options="pieOptions" class="h-full" />
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 md:col-span-6 border-none shadow-sm rounded-3xl">
                    <template #title><span class="text-lg font-black text-slate-800">Flux Pièces Détachées</span></template>
                    <template #content>
                        <div class="h-[300px]">
                            <Chart type="line" :data="{
                                labels: props.sparePartsMovement?.labels || [],
                                datasets: [
                                    { label: 'Entrées', borderColor: '#10B981', data: props.sparePartsMovement?.entries || [], tension: 0.4, fill: true, backgroundColor: 'rgba(16,185,129,0.05)' },
                                    { label: 'Sorties', borderColor: '#EF4444', data: props.sparePartsMovement?.exits || [], tension: 0.4 }
                                ]
                            }" :options="{ maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }" class="h-full" />
                        </div>
                    </template>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card) {
    background: #ffffff;
    border-radius: 1.5rem;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05) !important;
}
:deep(.p-card-body) {
    padding: 1.5rem;
}
:deep(.p-card-title) {
    margin-bottom: 0;
}
</style>
