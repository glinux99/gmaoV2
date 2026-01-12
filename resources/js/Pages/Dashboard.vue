<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

// PrimeVue Components
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import ProgressBar from 'primevue/progressbar';
import StatusDurationChart from './StatusDurationChart.vue';

const props = defineProps({
    // Counts & Totals
    usersCount: Number,
    activeTasksCount: Number,
    timeSpent: String,
    averageInterventionTime: String,
    backlogTasksCount: Number,
    backlogHours: Number,

    // Performance Metrics (KPIs)
    preventiveMaintenanceRate: Number,
    mttr: Number,
    mtbf: Number,
    preventiveComplianceRate: Number,
    availabilityRate: { type: Number, default: 94.5 }, // New: Taux de disponibilité
    oee: { type: Number, default: 78.2 }, // New: Overall Equipment Effectiveness (TRS)

    // Financials
    budgetTotal: Number,
    expensesTotal: Number,
    maintenanceCostDistribution: Object,

    // Charts Data
    sparklineData: Object,
    sparePartsMovement: Object,
    tasksByStatus: Object,
    tasksByPriority: Object,
    monthlyVolumeData: Object,
    failuresByType: Object,
    topFailingEquipmentsChart: Object,
    maintenanceStatusDurationChart: Object,

    // Filters & Context
    filters: Object,
    equipments: Array,
    zones: Array,
});

const { t } = useI18n();

// --- Logic: Filters ---
const selectedEquipment = ref(props.filters.equipment_id);
const selectedZone = ref(props.filters.zone_id);
const isLoading = ref(false);

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('dashboard.index'), {
        equipment_id: selectedEquipment.value,
        zone_id: selectedZone.value,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => isLoading.value = false
    });
};

// --- Utilities ---
const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// --- Chart Configurations ---
const globalChartOptions = computed(() => ({
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
            labels: { color: '#64748b', usePointStyle: true, font: { size: 11 } }
        },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            cornerRadius: 8,
        }
    }
}));

const sparklineItems = computed(() => {
    const data = props.sparklineData;
    return [
        { key: 'activeTasks', label: 'Work Orders', value: props.activeTasksCount || 0, icon: 'pi pi-briefcase', color: '#6366F1' },
        { key: 'backlog', label: 'Backlog', value: props.backlogTasksCount || 0, icon: 'pi pi-clock', color: '#F59E0B' },
        { key: 'timeSpent', label: 'Labors Hours', value: props.timeSpent || '0h', icon: 'pi pi-users', color: '#10B981' },
        { key: 'mttr', label: 'Avg. MTTR', value: (props.mttr || 0) + 'h', icon: 'pi pi-wrench', color: '#EF4444' }
    ].map(item => ({
        ...item,
        chartData: {
            labels: data?.[item.key]?.chartData?.labels || ['', '', '', '', '', ''],
            datasets: [{
                data: data?.[item.key]?.chartData?.datasets?.[0]?.data || [2, 5, 3, 8, 5, 9],
                borderColor: item.color,
                backgroundColor: item.color + '10',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 0
            }]
        }
    }));
});

const taskDistribution = computed(() => {
    const isStatus = tasksChartType.value === 'status';
    const source = isStatus ? props.tasksByStatus : props.tasksByPriority;
    return {
        labels: source?.labels || [],
        datasets: [{
            data: source?.data || [],
            backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#94a3b8'],
            hoverOffset: 15,
            borderWidth: 0
        }]
    };
});

const tasksChartType = ref('status');
const sparklineOptions = {
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    maintainAspectRatio: false,
    scales: { x: { display: false }, y: { display: false } }
};
</script>

<template>
    <AppLayout>
        <div class="dashboard-container p-4 lg:p-8 bg-slate-50 min-h-screen">

            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-5">
                    <div class="brand-icon bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100">
                        <i class="pi pi-bolt text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">MaintenX Dashboard</h1>
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <i class="pi pi-calendar text-xs"></i>
                            <span>{{ new Date().toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) }}</span>
                            <Tag value="Live Data" severity="success" class="ml-2 !text-[10px] !px-2" pulse />
                        </div>
                    </div>
                </div>

                <div class="filter-panel bg-white p-3 rounded-3xl shadow-sm border border-slate-200 flex flex-wrap gap-3 items-center">
                    <div class="flex items-center gap-2 px-3">
                        <i class="pi pi-filter text-indigo-500"></i>
                        <span class="text-sm font-bold text-slate-700">Filtres :</span>
                    </div>
                    <Dropdown
                        v-model="selectedZone"
                        :options="zones"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Toutes les Zones"
                        class="w-48 border-none bg-slate-50 !rounded-2xl"
                        @change="applyFilters"
                    />
                    <Dropdown
                        v-model="selectedEquipment"
                        :options="equipments"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Equipement"
                        class="w-48 border-none bg-slate-50 !rounded-2xl"
                        @change="applyFilters"
                    />
                    <Button icon="pi pi-refresh" @click="applyFilters" :loading="isLoading" class="p-button-rounded p-button-text text-slate-400" />
                </div>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <div v-for="kpi in [
                    { label: 'Disponibilité', value: availabilityRate, unit: '%', icon: 'pi-power-off', color: 'indigo', desc: 'Temps de prod. réel' },
                    { label: 'Taux Préventif', value: preventiveMaintenanceRate, unit: '%', icon: 'pi-verified', color: 'emerald', desc: 'Objectif: > 80%' },
                    { label: 'MTBF', value: mtbf, unit: 'j', icon: 'pi-sync', color: 'amber', desc: 'Fiabilité équipement' },
                    { label: 'OEE / TRS', value: oee, unit: '%', icon: 'pi-percentage', color: 'rose', desc: 'Efficacité globale' }
                ]" :key="kpi.label" class="kpi-card group">
                    <div class="relative bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-xl hover:-translate-y-1">
                        <div :class="`absolute -right-4 -top-4 w-24 h-24 bg-${kpi.color}-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500`"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div :class="`p-3 rounded-2xl bg-${kpi.color}-500 text-white shadow-lg`"><i :class="`pi ${kpi.icon}`"></i></div>
                            <i class="pi pi-ellipsis-v text-slate-300"></i>
                        </div>
                        <h3 class="text-slate-500 text-xs font-black uppercase tracking-widest mb-1">{{ kpi.label }}</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-slate-800">{{ kpi.value }}</span>
                            <span class="text-lg font-bold text-slate-400">{{ kpi.unit }}</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-3 font-medium">{{ kpi.desc }}</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="item in sparklineItems" :key="item.label" class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ item.label }}</span>
                        <Tag :value="item.value" :style="{ backgroundColor: item.color + '20', color: item.color }" class="!rounded-lg" />
                    </div>
                    <div class="h-16 w-full">
                        <Chart type="line" :data="item.chartData" :options="sparklineOptions" class="h-full" />
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-12 gap-8">

                <Card class="col-span-12 xl:col-span-8 !rounded-[2.5rem] border-none shadow-sm overflow-hidden">
                    <template #title>
                        <div class="flex items-center justify-between px-2">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-8 bg-indigo-500 rounded-full"></div>
                                <span class="text-xl font-black text-slate-800">Volume & Temps de Résolution</span>
                            </div>
                            <div class="flex gap-2">
                                <Tag value="Analytique" severity="info" rounded />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[450px] mt-6">
                            <Chart type="bar" :data="comboChartData" :options="{
                                ...globalChartOptions,
                                scales: {
                                    y: { stacked: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                                    y1: { position: 'right', grid: { display: false } },
                                    x: { grid: { display: false } }
                                }
                            }" />
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 xl:col-span-4 !rounded-[2.5rem] border-none shadow-sm flex flex-col">
                    <template #title>
                        <div class="flex justify-between items-center px-2">
                            <span class="text-xl font-black text-slate-800">Distribution</span>
                            <Dropdown v-model="tasksChartType" :options="[{l:'Statut',v:'status'},{l:'Priorité',v:'priority'}]" optionLabel="l" optionValue="v" class="!rounded-xl p-dropdown-sm bg-slate-50 border-none" />
                        </div>
                    </template>
                    <template #content>
                        <div class="flex-1 flex flex-col justify-center relative min-h-[350px]">
                            <Chart type="doughnut" :data="taskDistribution" :options="{ ...globalChartOptions, cutout: '75%' }" class="h-[280px]" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-[-20px]">
                                <span class="text-5xl font-black text-slate-800 leading-none">{{ activeTasksCount }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Work Orders</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-8 px-4">
                                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Moy. Clôture</p>
                                    <p class="text-lg font-black text-slate-700">4.2 <span class="text-xs">h</span></p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Ratio Respect</p>
                                    <p class="text-lg font-black text-indigo-600">92 <span class="text-xs">%</span></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 lg:col-span-5 !rounded-[2.5rem] border-none shadow-sm">
                    <template #title><span class="text-lg font-black text-slate-800">Budget vs Dépenses Realisées</span></template>
                    <template #content>
                        <div class="space-y-6">
                            <div class="flex justify-between items-end">
                                <div>
                                    <h4 class="text-4xl font-black text-slate-800">{{ formatCurrency(expensesTotal) }}</h4>
                                    <p class="text-slate-400 text-sm font-medium mt-1">Total consommé sur {{ formatCurrency(budgetTotal) }}</p>
                                </div>
                                <div class="text-right">
                                    <Tag :value="Math.round((expensesTotal / budgetTotal) * 100) + '%'" severity="warning" rounded />
                                </div>
                            </div>
                            <ProgressBar :value="Math.round((expensesTotal / budgetTotal) * 100)" :showValue="false" class="h-3 rounded-full bg-slate-100" />

                            <Divider type="dashed" />

                            <div class="h-[250px]">
                                <Chart type="pie" :data="costDistributionData" :options="globalChartOptions" />
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 lg:col-span-7 !rounded-[2.5rem] border-none shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-exclamation-triangle text-rose-500"></i>
                            <span class="text-lg font-black text-slate-800">Top 5 Équipements Critiques (Fréquence de panne)</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[380px]">
                            <Chart type="bar" :data="topFailingEquipmentsData" :options="{
                                ...globalChartOptions,
                                indexAxis: 'y',
                                plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: '#f8fafc' } }, y: { grid: { display: false } } }
                            }" />
                        </div>
                    </template>
                </Card>

                <Card class="col-span-12 !rounded-[2.5rem] border-none shadow-sm">
                    <template #title>
                        <div class="flex items-center justify-between w-full px-2">
                            <span class="text-lg font-black text-slate-800">Flux de Rotation des Pièces Détachées</span>
                            <Button label="Voir Inventaire" icon="pi pi-external-link" class="p-button-text p-button-sm font-bold" />
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[300px]">
                            <Chart type="line" :data="sparePartsChartData" :options="{
                                ...globalChartOptions,
                                elements: { line: { tension: 0.4, borderWidth: 3 }, point: { radius: 4, hoverRadius: 6 } }
                            }" />
                        </div>
                    </template>
                </Card>

                <div class="col-span-12 mt-4">
                    <StatusDurationChart
                        v-if="maintenanceStatusDurationChart"
                        :chart-data="maintenanceStatusDurationChart"
                        :equipments="equipments"
                        :zones="zones"
                        v-model:selectedEquipment="selectedEquipment"
                        v-model:selectedZone="selectedZone"
                    />
                </div>
            </div>

            <footer class="mt-12 text-center text-slate-400 text-sm font-medium pb-8">
                &copy; 2026 Maintenance Intelligence System • Rapport généré à {{ new Date().toLocaleTimeString() }}
            </footer>
        </div>

    </AppLayout>
</template>

<style scoped>
.dashboard-container {
    animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

:deep(.p-card) {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease;
}

:deep(.p-card:hover) {
    transform: translateY(-4px);
}

:deep(.p-progressbar .p-progressbar-value) {
    background: linear-gradient(90deg, #6366f1, #a855f7);
    border-radius: 10px;
}

:deep(.p-dropdown) {
    border: 1px solid #f1f5f9;
}

.kpi-card i {
    font-size: 1.25rem;
}
</style>
