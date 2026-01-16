<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

// --- IMPORTS ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import ProgressBar from 'primevue/progressbar';
import Menu from 'primevue/menu';
import StatusDurationChart from './StatusDurationChart.vue';

// --- PROPS ---
const props = defineProps({
    // Counts & Totals
    usersCount: Number,
    activeTasksCount: Number,
    timeSpent: String,
    averageInterventionTime: String,
    backlogTasksCount: Number,
    backlogHours: Number,

    awaitingWorkOrdersCount: Number,
    inProgressWorkOrdersCount: Number,
    // Performance Metrics (KPIs)
    preventiveMaintenanceRate: Number,
    mttr: Number,
    mtbf: Number,
    preventiveComplianceRate: Number,
    availabilityRate: { type: Number, default: 94.5 }, // New: Taux de disponibilité
    completedLast24hCount: Number,
    oee: { type: Number, default: 78.2 }, // New: Overall Equipment Effectiveness (TRS)

    // Financials
    budgetTotal: Number,
    expensesTotal: Number,
    maintenanceCostDistribution: Object,

    // Charts Data
    sparklineData: Object,
    sparePartsMovement: Object,
    stockRotationData: Object, // Nouvelle prop pour les données de rotation
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

    // New dynamic data from controller
    workOrders: Array,
    alertSpareParts: Array,
    technicianEfficiency: Array,
    calendarEvents: Array,
    recentInterventions: Array,
    urgentWorkOrdersCount: Number,
    riskMatrixData: Object,
    totalStockIn: Number, // NOUVEAU
    totalStockOut: Number, // NOUVEAU
    averageClosureTime: Number,
    teamEfficiencyChange: Number,
    mainChartData: Object, // NOUVEAU
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// --- I18N ---
const { t } = useI18n();

// --- STATE ---
const isLoading = ref(false);
const selectedEquipment = ref(props.filters.equipment_id);
const selectedZone = ref(props.filters.zone_id);
const tasksChartType = ref('status');
const menu = ref();

// --- DEFAULT STATE ---
const defaultSectionsState = {
    kpis: { label: t('dashboard.sections.kpis'), visible: true },
    sparklines: { label: t('dashboard.sections.sparklines'), visible: true },
    workOrders: { label: t('dashboard.sections.workOrders'), visible: true },
    stockAlerts: { label: t('dashboard.sections.stockAlerts'), visible: true },
    preventiveCalendar: { label: t('dashboard.sections.preventiveCalendar'), visible: true },
    riskMatrix: { label: t('dashboard.sections.riskMatrix'), visible: true },
    technicianEfficiency: { label: t('dashboard.sections.technicianEfficiency'), visible: true },
    mainChart: { label: t('dashboard.sections.mainChart'), visible: false },
    statusDuration: { label: t('dashboard.sections.statusDuration'), visible: true },
    statusDoughnut: { label: t('dashboard.sections.statusDoughnut'), visible: false },
    budget: { label: t('dashboard.sections.budget'), visible: true },
    criticalityAnalysis: { label: t('dashboard.sections.criticalityAnalysis'), visible: true },
    recentInterventions: { label: t('dashboard.sections.recentInterventions'), visible: true },
    stockFlow: { label: t('dashboard.sections.stockFlow'), visible: true },
};
// Section visibility state
const sections = ref({
    kpis: { label: 'Indicateurs Clés (KPIs)', visible: true },
    sparklines: { label: 'Mini-Graphiques', visible: true },
    workOrders: { label: "File d'attente", visible: true },
    stockAlerts: { label: 'Alertes Stock', visible: true },
    preventiveCalendar: { label: 'Calendrier Préventif', visible: true },
    riskMatrix: { label: 'Matrice de Risques', visible: true },
    technicianEfficiency: { label: 'Efficacité Techniciens', visible: true },
    mainChart: { label: 'Flux de Maintenance', visible: false }, // Hidden by default as example
    statusDuration: { label: 'Durée par Statut', visible: true },
    statusDoughnut: { label: 'Répartition par Statut', visible: false }, // Hidden by default as example
    budget: { label: 'Finances', visible: true },
    criticalityAnalysis: { label: 'Analyse Criticité', visible: true },
    recentInterventions: { label: 'Interventions Récentes', visible: true },
    stockFlow: { label: 'Flux de Stock', visible: true },
});

const sectionMenuItems = computed(() =>
    [
        ...Object.entries(sections.value).map(([key, value]) => ({
        label: value.label,
        command: () => toggleSection(key),
        key: key
    })),
    { separator: true },
    {
        label: t('dashboard.sections.reset'),
        icon: 'pi pi-refresh',
        command: resetSections
    }
    ]
);

// --- COMPUTED ---
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
        { key: 'activeTasks', label: t('dashboard.sparklines.workOrders'), value: data?.activeTasks?.value || 0, icon: 'pi pi-briefcase', color: '#6366F1' },
        { key: 'backlog', label: t('dashboard.sparklines.backlog'), value: data?.backlog?.value || 0, icon: 'pi pi-clock', color: '#F59E0B' },
        { key: 'timeSpent', label: t('dashboard.sparklines.laborHours'), value: data?.timeSpent?.value || '0h', icon: 'pi pi-users', color: '#10B981' },
        { key: 'averageInterventionTime', label: t('dashboard.sparklines.avgMttr'), value: data?.averageInterventionTime?.value || '0m', icon: 'pi pi-wrench', color: '#EF4444' }
    ].map(item => ({
        ...item,
        chartData: {
            // Assurer un minimum de 6 labels pour un affichage correct, même avec peu de données
            labels: (data?.[item.key]?.chartData?.labels || []).concat(Array(Math.max(0, 6 - (data?.[item.key]?.chartData?.labels?.length || 0))).fill('')),
            datasets: [{
                // Assurer un minimum de 6 points de données, en complétant avec la dernière valeur ou 0
                data: (data?.[item.key]?.chartData?.datasets?.[0]?.data || []).concat(
                    Array(Math.max(0, 6 - (data?.[item.key]?.chartData?.datasets?.[0]?.data?.length || 0)))
                    .fill(data?.[item.key]?.chartData?.datasets?.[0]?.data?.slice(-1)[0] ?? 0)
                ),
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

const kpiData = ref([
    { label: t('dashboard.kpis.availabilityRate'), value: '98.4', unit: '%', icon: 'pi-bolt', color: 'primary', trend: 2.1, progress: 98 },
    { label: t('dashboard.kpis.globalMtbf'), value: '156', unit: 'hrs', icon: 'pi-sync', color: 'emerald', trend: 12, progress: 85 },
    { label: t('dashboard.kpis.oee'), value: '82.1', unit: '%', icon: 'pi-chart-bar', color: 'amber', trend: -1.4, progress: 82 },
    { label: t('dashboard.kpis.maintenanceBacklog'), value: '24', unit: 'WO', icon: 'pi-clock', color: 'rose', trend: 5, progress: 40 },
    { label: t('dashboard.kpis.preventiveCompliance'), value: '94', unit: '%', icon: 'pi-check-circle', color: 'blue', trend: 0.8, progress: 94 }
]);

const sparklineOptions = {
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    maintainAspectRatio: false,
    scales: { x: { display: false }, y: { display: false } }
};

// --- CHART CONFIGS ---
const performanceChartOptions = {
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'top', labels: { usePointStyle: true, font: { weight: 'bold' } } }
    },
    scales: {
        y: { beginAtZero: false, grid: { color: '#F1F5F9' } },
        x: { grid: { display: false } }
    }
};

const stockRotationData = computed(() => {
    const source = props.stockRotationData;
    return {
        labels: source?.labels || ['S1', 'S2', 'S3', 'S4'],
        datasets: [{
            label: t('dashboard.stockRotation.entries'),
            backgroundColor: '#6366F1',
            data: source?.entries || [],
            borderRadius: 5
        }, {
            label: t('dashboard.stockRotation.exits'),
            backgroundColor: '#F43F5E',
            data: source?.exits || [],
            borderRadius: 5
        }]
    };
});

const radarOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { r: { grid: { color: '#F1F5F9' }, pointLabels: { font: { size: 10, weight: 'bold' } } } }
};

const miniChartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { display: false }, y: { display: false } }
};

// --- METHODS ---
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

const toggleSection = (sectionKey) => {
    sections.value[sectionKey].visible = !sections.value[sectionKey].visible;
    localStorage.setItem('dashboardSections', JSON.stringify(sections.value));
};

const resetSections = () => {
    sections.value = JSON.parse(JSON.stringify(defaultSectionsState)); // Deep copy
    localStorage.setItem('dashboardSections', JSON.stringify(sections.value));
};

const toggleMenu = (event) => {
    menu.value.toggle(event);
};

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    const savedSections = localStorage.getItem('dashboardSections');
    if (savedSections) {
        // Merge saved state with default state to prevent errors if new sections are added
        const saved = JSON.parse(savedSections);
        sections.value = Object.keys(defaultSectionsState).reduce((acc, key) => {
            acc[key] = saved[key] || defaultSectionsState[key];
            return acc;
        }, {});
    }
});

// --- UTILITIES ---
const priorityClass = (p) => {
    const base = "px-3 py-1 rounded-full text-[10px] font-black ";
    switch(p) {
        case 'CRITIQUE': return base + "bg-rose-100 text-rose-600";
        case 'HAUTE': return base + "bg-amber-100 text-amber-600";
        default: return base + "bg-slate-100 text-slate-600";
    }
};

const getPrioritySeverity = (priority) => {
    switch (priority) {
        case 'CRITIQUE': return 'danger';
        case 'HAUTE': return 'warning';
        case 'MOYENNE': return 'info';
        default: return 'secondary';
    }
};

const showWODialog = ref(false);
</script>

<template>
    <AppLayout>
        <Head title="MaintenX Enterprise ERP - v2.0" />

        <div class="dashboard-container p-4 lg:p-8 bg-[#F1F5F9] min-h-screen font-sans">

           <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-5">
                    <div class="brand-icon bg-primary-600 p-4 rounded-3xl shadow-xl shadow-primary-100">
                        <i class="pi pi-bolt text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ t('dashboard.title') }}</h1>
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <i class="pi pi-calendar text-xs"></i>
                            <span>{{ t('dashboard.generatedAt', { time: new Date().toLocaleTimeString() }) }}
          </span>
                            <Tag :value="t('dashboard.liveData')" severity="success" class="ml-2 !text-[10px] !px-2" pulse />
                        </div>
                    </div>
                </div>

                 <div class="glass-panel flex flex-wrap items-center gap-2 p-3 bg-white/70 backdrop-blur-xl border border-white rounded-[2.5rem] shadow-xl shadow-slate-200/50">
                    <div class="flex items-center gap-3 px-4 py-2 border-r border-slate-100">
                        <i class="pi pi-filter-fill text-primary-500"></i>
                        <span class="text-sm font-black text-slate-700">{{ t('dashboard.filters.title') }}</span>
                    </div>

                    <Dropdown v-model="selectedZone" :options="zones" optionLabel="title" :placeholder="t('dashboard.filters.zonePlaceholder')" class="custom-dropdown w-48" @change="applyFilters" />
                    <Dropdown v-model="selectedEquipment" :options="equipments" optionLabel="designation" :placeholder="t('dashboard.filters.equipmentPlaceholder')" class="custom-dropdown w-48" @change="applyFilters" />

                    <Button icon="pi pi-sync" @click="applyFilters" :loading="isLoading" class="p-button-rounded p-button-primary shadow-lg hover:scale-105 active:scale-95 transition-all" />

                    <Divider layout="vertical" class="hidden lg:block" />

                    <Button icon="pi pi-eye" @click="toggleMenu" aria-haspopup="true" aria-controls="overlay_menu" class="p-button-rounded p-button-text" v-tooltip.bottom="t('dashboard.manageSections')" />
                    <Menu ref="menu" id="overlay_menu" :model="sectionMenuItems" :popup="true">
                        <template #item="{ item, props }">
                            <a v-ripple class="flex items-center" v-bind="props.action">
                                <Checkbox v-if="item.key" :modelValue="sections[item.key].visible" :binary="true" class="mr-2" />
                                <span class="text-sm">{{ item.label }}</span>
                            </a>
                        </template>
                    </Menu>

                    <div class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 p-2 rounded-xl transition-colors">
                        <Avatar icon="pi pi-bell" class="p-overlay-badge" shape="circle">
                            <Tag value="3" severity="danger" class="absolute -top-1 -right-1" />
                        </Avatar>
                    </div>
                </div>
            </header>
            <section v-show="sections.kpis.visible" data-section-id="kpis" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 section-container">

                <div v-for="kpi in [
                    { label: t('dashboard.kpis.availability'), value: availabilityRate, unit: '%', icon: 'pi-power-off', color: 'primary', desc: t('dashboard.kpis.availabilityDesc') },
                    { label: t('dashboard.kpis.preventiveRate'), value: preventiveMaintenanceRate, unit: '%', icon: 'pi-verified', color: 'emerald', desc: t('dashboard.kpis.preventiveRateDesc') },
                    { label: t('dashboard.kpis.mtbf'), value: mtbf, unit: 'j', icon: 'pi-sync', color: 'amber', desc: t('dashboard.kpis.mtbfDesc') },
                    { label: t('dashboard.kpis.oee'), value: oee, unit: '%', icon: 'pi-percentage', color: 'red', desc: t('dashboard.kpis.oeeDesc') }
                ]" :key="kpi.label" class="kpi-card group">
                    <div @dblclick="toggleSection('kpis')" class="relative bg-white p-6 rounded-xl border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-xl hover:-translate-y-1 section-title">
                        <div :class="`absolute -right-4 -top-4 w-24 h-24 bg-${kpi.color}-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500`"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div :class="`p-3 rounded-xl bg-${kpi.color}-500 text-white shadow-lg`">
                          <i :class="`pi ${kpi.icon}`"></i></div>
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

            <section v-show="sections.sparklines.visible" data-section-id="sparklines" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 section-container">
                <div v-for="item in sparklineItems" :key="item.label" @dblclick="toggleSection('sparklines')" class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between section-title">

                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ item.label }}</span>
                        <Tag :value="item.value" :style="{ backgroundColor: item.color + '20', color: item.color }" class="!rounded-xl" />
                    </div>
                    <div class="h-16 w-full">
                        <Chart type="line" :data="item.chartData" :options="sparklineOptions" class="h-full" />
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-12 gap-8 mb-8">
                <Card v-show="sections.workOrders.visible" data-section-id="workOrders" class="col-span-12 xl:col-span-7 !rounded-xl border-none shadow-xl shadow-slate-200/40 bg-white section-container">

                    <template #title>
                        <div @dblclick="toggleSection('workOrders')" class="flex justify-between items-center px-4 pt-2 section-title">
                            <span class="text-lg font-black text-slate-800">{{ t('dashboard.workOrders.title') }}</span>
                            <div class="flex gap-2">
                                <Tag :value="t('dashboard.workOrders.urgent', { count: props.urgentWorkOrdersCount || 0 })" severity="danger" rounded />
                                <Tag :value="t('dashboard.workOrders.inProgress', { count: props.inProgressWorkOrdersCount || 0 })" severity="info" rounded />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="props.workOrders" scrollable scrollHeight="450px" class="p-datatable-sm custom-erp-table">
                            <Column field="id" :header="t('dashboard.workOrders.columns.id')" class="font-black text-primary-600 w-20"></Column>
                            <Column field="asset" :header="t('dashboard.workOrders.columns.equipment')">
                                <template #body="{data}">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700">{{ data.asset }}</span>
                                        <span class="text-[9px] text-slate-400">{{ data.location }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="priority" :header="t('dashboard.workOrders.columns.priority')">
                                <template #body="{data}">
                                    <span :class="priorityClass(data.priority)">{{ data.priority }}</span>
                                </template>
                            </Column>
                            <Column field="technician" :header="t('dashboard.workOrders.columns.assigned')">
                                <template #body="{data}">
                                    <div class="flex items-center gap-2">
                                        <Avatar :image="data.tech_img" shape="circle" size="small" />
                                        <span class="text-xs font-medium">{{ data.technician }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="progress" :header="t('dashboard.workOrders.columns.progress')">
                                <template #body="{data}">
                                    <Knob v-model="data.progress" :size="35" readonly strokeWidth="8" rangeColor="#F1F5F9" valueColor="#6366F1" />
                                </template>
                            </Column>
                            <Column :header="t('dashboard.workOrders.columns.actions')">
                                <template #body>
                                    <Button icon="pi pi-external-link" class="p-button-rounded p-button-text p-button-sm" />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <Card v-show="sections.stockAlerts.visible" data-section-id="stockAlerts" class="col-span-12 xl:col-span-5 !rounded-xl border-none shadow-xl shadow-slate-200/40 bg-white overflow-hidden section-container">

                    <template #title>
                        <div @dblclick="toggleSection('stockAlerts')" class="px-4 pt-2 font-black text-slate-800 section-title">{{ t('dashboard.stockAlerts.title') }}</div>
                    </template>
                    <template #content>
                        <div class="space-y-4 px-2">
                            <div v-for="part in props.alertSpareParts" :key="part.reference" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 group hover:border-primary-200 transition-colors ">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-xl">
                                        📦
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 text-sm">{{ part.reference }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">{{ t('dashboard.stockAlerts.ref') }} {{ part.reference }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black" :class="part.quantity < part.min_quantity ? 'text-rose-500' : 'text-slate-600'">
                                        {{ part.quantity }} / {{ part.min_quantity }} <span class="text-[9px]">{{ t('dashboard.stockAlerts.units') }}</span>
                                    </p>
                                    <Button v-if="part.quantity < part.min_quantity" :label="t('dashboard.stockAlerts.order')" class="p-button-text p-button-xs !p-0 font-bold text-primary-600" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-primary-50/50 rounded-xl">
                            <h4 class="text-[10px] font-black text-primary-400 uppercase mb-4 text-center">{{ t('dashboard.stockRotation.title') }}</h4>
                            <div class="h-[120px]">
                                <Chart type="bar" :data="stockRotationData" :options="miniChartOptions" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>



            <div class="grid grid-cols-12 gap-8 mb-8">
                <Card v-show="sections.criticalityAnalysis.visible" data-section-id="criticalityAnalysis" class="col-span-12 xl:col-span-7 !rounded-xl border-none shadow-xl shadow-slate-200/50 bg-white section-container">
                    <template #title>
                        <div @dblclick="toggleSection('criticalityAnalysis')" class="flex items-center gap-3 px-2 pt-2 section-title">
                            <i class="pi pi-exclamation-triangle text-rose-500 text-xl"></i>
                            <span class="text-lg font-black text-slate-800">{{ t('dashboard.criticalityAnalysis.title') }}</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[400px]">
                            <Chart type="bar" :data="{
                                labels: props.topFailingEquipmentsChart?.labels || ['CNC-01', 'POMPE-P2', 'TURBINE-X', 'COMP-04', 'LIGNE-3'], // Keep as is, data driven
                                datasets: [{
                                    label: t('dashboard.criticalityAnalysis.failuresCount'),
                                    data: props.topFailingEquipmentsChart?.data || [15, 12, 9, 8, 5],
                                    backgroundColor: ['#EF4444', '#F43F5E', '#FB7185', '#FDA4AF', '#FECDD3'],
                                    borderRadius: 12,
                                    barThickness: 40
                                }]
                            }" :options="{
                                ...mainChartOptions,
                                indexAxis: 'y',
                                plugins: { legend: { display: false } },
                                scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
                            }" class="h-full" />
                        </div>
                    </template>
                </Card>

                <Card v-show="sections.recentInterventions.visible" data-section-id="recentInterventions" class="col-span-12 xl:col-span-5 !rounded-xl border-none shadow-xl shadow-slate-200/50 bg-white overflow-hidden section-container">
                    <template #title>
                        <div @dblclick="toggleSection('recentInterventions')" class="flex justify-between items-center px-4 pt-2 section-title">
                            <span class="text-lg font-black text-slate-800">{{ t('dashboard.recentInterventions.title') }}</span>
                            <Button :label="t('dashboard.recentInterventions.seeAll')" class="p-button-text p-button-sm font-bold" />
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="props.recentInterventions || []" :rows="5" class="p-datatable-sm custom-table">
                            <Column field="equipment" :header="t('dashboard.recentInterventions.columns.asset')">
                                <template #body="slotProps">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center">
                                            <i class="pi pi-box text-slate-500 text-xs"></i>
                                        </div>
                                        <span class="font-bold text-slate-700 text-xs">{{ slotProps.data.equipment }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="priority" :header="t('dashboard.recentInterventions.columns.priority')">
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.priority" :severity="getPrioritySeverity(slotProps.data.priority)" class="!text-[9px] !px-2" />
                                </template>
                            </Column>
                            <Column field="technician" :header="t('dashboard.recentInterventions.columns.tech')">
                                <template #body="slotProps">
                                    <AvatarGroup>
                                        <Avatar :image="slotProps.data.tech_image" shape="circle" size="small" v-tooltip.top="slotProps.data.technician" />
                                    </AvatarGroup>
                                </template>
                            </Column>
                            <Column field="status" :header="t('dashboard.recentInterventions.columns.status')">
                                <template #body="slotProps">
                                    <i :class="['pi', slotProps.data.status === 'Completed' ? 'pi-check-circle text-emerald-500' : 'pi-spin pi-spinner text-amber-500']"></i>
                                 </template>
                            </Column>
                        </DataTable>

                        <div class="mt-8 p-4 bg-primary-50 rounded-3xl flex justify-around items-center" >
                            <div class="text-center">
                                <p class="text-[9px] font-black text-primary-400 uppercase">{{ t('dashboard.recentInterventions.inProgress') }}</p>
                                <p class="text-lg font-black text-primary-700">{{ props.inProgressWorkOrdersCount || 0 }}</p>
                            </div>
                            <div class="w-px h-8 bg-primary-200"></div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-primary-400 uppercase">{{ t('dashboard.recentInterventions.pending') }}</p>
                                <p class="text-lg font-black text-primary-700">{{ props.awaitingWorkOrdersCount || 0 }}</p>
                            </div>
                            <div class="w-px h-8 bg-primary-200"></div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-primary-400 uppercase">{{ t('dashboard.recentInterventions.closed24h') }}</p>
                                <p class="text-lg font-black text-primary-700">{{ props.completedLast24hCount || 0 }}</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            <div>
                 <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 section-container">
                <div v-show="sections.preventiveCalendar.visible" data-section-id="preventiveCalendar" class="bg-white p-8 rounded-xl shadow-xl border border-slate-100 ">
                    <div @dblclick="toggleSection('preventiveCalendar')" class="flex justify-between items-center mb-6 section-title">
                        <h3 class="font-black text-slate-800 tracking-tight">{{ t('dashboard.preventiveCalendar.title') }}</h3>
                        <i class="pi pi-calendar-plus text-primary-500 text-xl cursor-pointer"></i>
                    </div>
                    <div class="space-y-4">
                        <div v-for="event in props.calendarEvents" :key="event.title" class="flex gap-4 items-start p-3 hover:bg-slate-50 rounded-xl transition-colors">
                            <div class="flex flex-col items-center justify-center min-w-[50px] py-2 bg-primary-50 rounded-xl text-primary-600">
                                <span class="text-xs font-black uppercase">{{ event.month }}</span>
                                <span class="text-xl font-[1000]">{{ event.day }}</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">{{ event.title }}</h4>
                                <p class="text-xs text-slate-400">{{ event.duration }} • {{ event.team }}</p>
                                <div class="flex gap-1 mt-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-[9px] font-black text-slate-400 uppercase">{{ t('dashboard.preventiveCalendar.confirmed') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="sections.riskMatrix.visible" data-section-id="riskMatrix" class="bg-white p-8 rounded-xl shadow-xl border border-slate-100 ">
                    <h3 @dblclick="toggleSection('riskMatrix')" class="font-black text-slate-800 mb-6 section-title">{{ t('dashboard.riskMatrix.title') }}</h3>
                    <div class="h-[300px]">
                        <Chart type="radar" :data="props.riskMatrixData" :options="radarOptions" />
                    </div>
                    <div class="mt-4 p-4 bg-rose-50 rounded-xl">
                        <p class="text-[10px] text-rose-600 font-black uppercase mb-1"><i class="pi pi-exclamation-circle"></i> {{ t('dashboard.riskMatrix.criticalRisk') }}</p>
                        <p class="text-xs font-bold text-rose-900">{{ t('dashboard.riskMatrix.criticalRiskExample') }}</p>
                    </div>
                </div>

                <div v-show="sections.technicianEfficiency.visible" data-section-id="technicianEfficiency" class="bg-white p-8 rounded-xl shadow-xl border border-slate-100 ">
                    <h3 @dblclick="toggleSection('technicianEfficiency')" class="font-black text-slate-800 mb-6 section-title">{{ t('dashboard.technicianEfficiency.title') }}</h3>
                    <div class="space-y-6">
                        <div v-for="user in props.technicianEfficiency" :key="user.name">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-2">
                                    <Avatar :image="user.img" shape="circle" />
                                    <span class="text-sm font-bold text-slate-700">{{ user.name }}</span>
                                </div>
                                <span class="text-xs font-black text-primary-600">{{ user.load }}% Load</span>
                            </div>
                            <ProgressBar :value="user.load" :showValue="false" class="!h-2 !bg-slate-100">
                                <template #default>
                                    <div class="h-full bg-primary-500 rounded-full" :style="{width: user.load + '%'}"></div>
                                </template>
                            </ProgressBar>
                            <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                                <span>{{ t('dashboard.technicianEfficiency.completed', { count: user.completed }) }}</span>
                                <span>{{ t('dashboard.technicianEfficiency.pending', { count: user.backlog }) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <div class="grid grid-cols-12 gap-6 mb-10 items-stretch mt-8">
                <div class="col-span-12 lg:col-span-8 space-y-6">
                    <Card v-show="sections.mainChart.visible"
                        data-section-id="mainChart"
                        class="!rounded-xl border-none shadow-lg shadow-slate-200/50 bg-white overflow-hidden transition-all duration-300">
                        <template #title>
                            <div @dblclick="toggleSection('mainChart')" class="flex justify-between items-center p-5 cursor-pointer hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-1.5 h-8 bg-primary-600 rounded-full"></div>
                                    <span class="text-xl font-black text-slate-800 tracking-tight">{{ t('dashboard.mainChart.title') }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-secondary hover:bg-primary-50" v-tooltip.top="t('dashboard.mainChart.exportPdf')" />
                                    <Button icon="pi pi-ellipsis-h" class="p-button-rounded p-button-text p-button-secondary" />
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[400px] px-4 pb-2">
                                <Chart type="bar" :data="props.mainChartData" :options="performanceChartOptions" class="h-full" />
                            </div>

                            <div class="grid grid-cols-3 divide-x divide-slate-100 bg-slate-50/80 border-t border-slate-100 mt-4">
                                <div class="p-5 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('dashboard.mainChart.availabilityRatio') }}</p>
                                    <p class="text-2xl font-black text-primary-600">{{ props.availabilityRate || 0 }}%</p>
                                </div>
                                <div class="p-5 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('dashboard.mainChart.avgClosure') }}</p>
                                    <p class="text-2xl font-black text-slate-800">{{ props.averageClosureTime || 0 }} <span class="text-xs font-medium text-slate-500">hrs</span></p>
                                </div>
                                <div class="p-5 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ t('dashboard.mainChart.teamEfficiency') }}</p>
                                    <p class="text-2xl font-black" :class="props.teamEfficiencyChange >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                                        {{ props.teamEfficiencyChange >= 0 ? '+' : '' }}{{ props.teamEfficiencyChange || 0 }}%
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- <div v-show="sections.statusDuration.visible"
                        data-section-id="statusDuration"
                        class="bg-white p-4 rounded-xl shadow-lg shadow-slate-200/50 cursor-pointer hover:ring-1 ring-primary-200 transition-all"
                        @dblclick="toggleSection('statusDuration')">
                        <StatusDurationChart
                            :chart-data="maintenanceStatusDurationChart"
                            :equipments="equipments"
                            :zones="zones"
                        />
                    </div> -->
                </div>

                <div class="col-span-12 lg:col-span-4 space-y-6">
                    <!-- <Card v-show="sections.statusDoughnut.visible"
                        data-section-id="statusDoughnut"
                        class="!rounded-xl border-none shadow-lg shadow-slate-200/50 bg-white overflow-hidden h-fit">
                        <template #title>
                            <div @dblclick="toggleSection('statusDoughnut')" class="flex justify-between items-center p-5 cursor-pointer border-b border-slate-50">
                                <span class="text-lg font-bold text-slate-800">{{ t('dashboard.statusDoughnut.title') }}</span>
                                <i class="pi pi-info-circle text-slate-300"></i>
                            </div>
                        </template>
                        <template #content>
                            <div class="relative flex flex-col items-center py-8">
                                <div class="h-[280px] w-full">
                                    <Chart type="doughnut" :data="doughnutData" :options="{
                                        cutout: '78%',
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: true, position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11, weight: '600' } } } }
                                    }" />
                                </div>
                                <div class="absolute inset-0 flex flex-col items-center justify-center pt-[-40px] pointer-events-none">
                                    <span class="text-5xl font-[1000] text-slate-800 tracking-tighter">{{ activeTasksCount }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">{{ t('dashboard.statusDoughnut.inProgress') }}</span>
                                </div>
                            </div>
                        </template>
                    </Card> -->

                    <div v-show="sections.budget.visible"
                        data-section-id="budget"
                        class="bg-[#0F172A] p-7 rounded-xl text-white shadow-2xl relative overflow-hidden group">
                        <div class="absolute -top-4 -right-4 p-8 opacity-10 group-hover:rotate-12 group-hover:scale-110 transition-transform duration-500">
                            <i class="pi pi-wallet text-[7rem]"></i>
                        </div>

                        <h3 @dblclick="toggleSection('budget')" class="text-slate-400 text-[10px] font-black uppercase tracking-[0.3em] mb-8 cursor-pointer flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-primary-500 rounded-full"></span>
                            {{ t('dashboard.budget.title') }}
                        </h3>

                        <div class="mb-8 relative z-10">
                            <div class="flex justify-between items-end mb-3">

                                <span class="text-4xl font-black tracking-tighter">{{ formatCurrency(props.expensesTotal) }}</span>
                                <span class="text-slate-400 text-xs font-medium mb-1">/ {{ formatCurrency(props.budgetTotal) }}</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-primary-500 via-indigo-500 to-purple-500 transition-all duration-1000"
                                    :style="{ width: (props.expensesTotal / props.budgetTotal) * 100 + '%' }"></div>
                            </div>
                        </div>

                        <div class="space-y-3 relative z-10" v-if="props.maintenanceCostDistribution?.items">
                            <div v-for="item in props.maintenanceCostDistribution.items" :key="item.label"
                                class="flex justify-between items-center bg-white/5 backdrop-blur-sm p-3.5 rounded-xl border border-white/5 hover:border-white/10 hover:bg-white/10 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-2.5 h-2.5 rounded-full shadow-[0_0_8px_rgba(255,255,255,0.2)]" :class="item.color"></div>
                                    <span class="text-xs font-semibold text-slate-300">{{ item.label }}</span>
                                </div>
                                <span class="text-sm font-black text-white">{{ item.value }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <section v-show="sections.stockFlow.visible" data-section-id="stockFlow" class="mt-10 section-container">
                <Card class="!rounded-xl border-none shadow-xl shadow-slate-200/50 bg-white overflow-hidden">
                    <template #content>
                        <div @dblclick="toggleSection('stockFlow')" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center p-4 section-title">
                            <div class="lg:col-span-4">
                                <h3 class="text-2xl font-black text-slate-800 mb-2">{{ t('dashboard.stockFlow.title') }}</h3>
                                <p class="text-slate-400 text-sm mb-6 font-medium">{{ t('dashboard.stockFlow.description') }}</p>
                                <div class="space-y-4">
                                    <div class="flex justify-between p-4 bg-emerald-50 rounded-xl border border-emerald-100 transition hover:shadow-lg hover:border-emerald-200">
                                        <span class="text-emerald-700 font-bold">{{ t('dashboard.stockFlow.restock') }}</span>
                                        <span class="font-black text-emerald-800">+{{ props.totalStockIn || 0 }} art.</span>
                                    </div>
                                    <div class="flex justify-between p-4 bg-rose-50 rounded-xl border border-rose-100 transition hover:shadow-lg hover:border-rose-200">
                                        <span class="text-rose-700 font-bold">{{ t('dashboard.stockFlow.consumption') }}</span>
                                        <span class="font-black text-rose-800">-{{ props.totalStockOut || 0 }} art.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="lg:col-span-8 h-[300px]">
                                <Chart type="line" :data="{
                                    labels: props.sparePartsMovement?.labels || ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'], // Keep as is, data driven
                                    datasets: [
                                        { label: t('dashboard.stockFlow.entries'), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, data: props.sparePartsMovement?.entries || [20, 45, 30, 60], tension: 0.4 },
                                        { label: t('dashboard.stockFlow.exits'), borderColor: '#EF4444', data: props.sparePartsMovement?.exits || [15, 30, 45, 25], tension: 0.4 }
                                    ]
                                }" :options="{ ...mainChartOptions, plugins: { legend: { position: 'bottom' } } }" class="h-full" />
                                <Chart type="line" :data="stockFlowChartData" :options="{ ...globalChartOptions, plugins: { legend: { position: 'bottom' } } }" class="h-full" />
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

        </div>

        <Dialog v-model:visible="showWODialog" :header="t('dashboard.createWoDialog.title')" :modal="true" class="w-full max-w-2xl">
            </Dialog>

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
.gmao-dashboard {
    animation: dashboardEnter 1s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes dashboardEnter {
    from { opacity: 0; transform: scale(0.98) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* --- GLASSMORPHISM & CUSTOM DROPDOWNS --- */
.custom-dropdown {
    @apply !bg-slate-50 !border-none !rounded-xl !shadow-none;
}

:deep(.p-dropdown-label) {
    @apply !text-xs !font-black !text-slate-600 !py-3;
}

/* --- KPI CARDS EFFECTS --- */
.kpi-card-advanced:nth-child(1) { animation-delay: 0.1s; }
.kpi-card-advanced:nth-child(2) { animation-delay: 0.2s; }
.kpi-card-advanced:nth-child(3) { animation-delay: 0.3s; }
.kpi-card-advanced:nth-child(4) { animation-delay: 0.4s; }

/* --- TABLE CUSTOMIZATION --- */
:deep(.custom-table .p-datatable-thead > tr > th) {
    @apply !bg-white !text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest !border-b !border-slate-100 !py-4;
}

:deep(.custom-table .p-datatable-tbody > tr) {
    @apply !bg-white hover:!bg-slate-50 !transition-colors;
}

:deep(.custom-table .p-datatable-tbody > tr > td) {
    @apply !border-none !py-4;
}

/* --- SCROLLBAR --- */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 10px;
}

/* --- CHART TOOLTIP OVERRIDE --- */
:deep(.p-chart) {
    @apply !transition-all !duration-500;
}

/* --- OVERLAY PANEL --- */
.custom-overlay {
    @apply !rounded-xl !border-none !shadow-2xl;
}
/* Custom Scrollbar for ERP feel */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.custom-erp-table :deep(.p-datatable-thead > tr > th) {
    background: #ffffff;
    color: #94a3b8;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1.5rem 1rem;
    border: none;
}

.custom-erp-table :deep(.p-datatable-tbody > tr) {
    background: transparent;
    transition: all 0.2s;
}

.custom-erp-table :deep(.p-datatable-tbody > tr:hover) {
    background: #f8fafc;
    transform: scale(1.002);
}

.p-button-primary {
    background: #4f46e5;
    border: none;
}

.p-button-primary:hover {
    background: #4338ca;
}

:deep(.p-menu .p-menuitem-content) {
    border-radius: 8px;
}

.section-title {
    cursor: pointer;
    position: relative;
}

.section-title:hover::after {
    content: '\e960'; /* PrimeIcons eye-slash icon */
    font-family: 'primeicons';
    position: absolute;
    top: 50%;
    right: 1.5rem;
    transform: translateY(-50%);
    color: #94a3b8;
    opacity: 0.6;
}
</style>
