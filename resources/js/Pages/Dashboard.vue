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
    kpis: { label: 'Indicateurs Clés (KPIs)', visible: true },
    sparklines: { label: 'Mini-Graphiques', visible: true },
    workOrders: { label: "File d'attente", visible: true },
    stockAlerts: { label: 'Alertes Stock', visible: true },
    preventiveCalendar: { label: 'Calendrier Préventif', visible: true },
    riskMatrix: { label: 'Matrice de Risques', visible: true },
    technicianEfficiency: { label: 'Efficacité Techniciens', visible: true },
    mainChart: { label: 'Flux de Maintenance', visible: false },
    statusDuration: { label: 'Durée par Statut', visible: true },
    statusDoughnut: { label: 'Répartition par Statut', visible: false },
    budget: { label: 'Finances', visible: true },
    criticalityAnalysis: { label: 'Analyse Criticité', visible: true },
    recentInterventions: { label: 'Interventions Récentes', visible: true },
    stockFlow: { label: 'Flux de Stock', visible: true },
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
        label: 'Réinitialiser',
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

const kpiData = ref([
    { label: 'Taux Disponibilité', value: '98.4', unit: '%', icon: 'pi-bolt', color: 'indigo', trend: 2.1, progress: 98 },
    { label: 'MTBF Global', value: '156', unit: 'hrs', icon: 'pi-sync', color: 'emerald', trend: 12, progress: 85 },
    { label: 'TRS (OEE)', value: '82.1', unit: '%', icon: 'pi-chart-bar', color: 'amber', trend: -1.4, progress: 82 },
    { label: 'Backlog Maintenance', value: '24', unit: 'WO', icon: 'pi-clock', color: 'rose', trend: 5, progress: 40 },
    { label: 'Conformité Préventif', value: '94', unit: '%', icon: 'pi-check-circle', color: 'blue', trend: 0.8, progress: 94 }
]);

const sparklineOptions = {
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    maintainAspectRatio: false,
    scales: { x: { display: false }, y: { display: false } }
};

// --- DEMO DATA (can be moved to props or fetched) ---
const workOrders = ref([
    { id: 'WO-8821', asset: 'Presse Hydraulique P1', location: 'Zone A', priority: 'CRITIQUE', technician: 'M. Chen', tech_img: 'https://i.pravatar.cc/50?u=1', progress: 85 },
    { id: 'WO-8825', asset: 'Robot KUKA R2', location: 'Cellule 4', priority: 'HAUTE', technician: 'S. Ramos', tech_img: 'https://i.pravatar.cc/50?u=2', progress: 45 },
    { id: 'WO-8830', asset: 'Convoyeur Central', location: 'Zone B', priority: 'MOYENNE', technician: 'J. Doe', tech_img: 'https://i.pravatar.cc/50?u=3', progress: 10 },
    { id: 'WO-8832', asset: 'Groupe Froid CF1', location: 'Toit Nord', priority: 'BASSE', technician: 'A. Smith', tech_img: 'https://i.pravatar.cc/50?u=4', progress: 0 },
    { id: 'WO-8840', asset: 'CNC Haas VF2', location: 'Usinage', priority: 'CRITIQUE', technician: 'M. Chen', tech_img: 'https://i.pravatar.cc/50?u=1', progress: 95 }
]);

const spareParts = ref([
    { name: 'Roulement SKF 6204', ref: 'SKF-001-X', stock: 3, min: 10 },
    { name: 'Filtre Air Industriel', ref: 'FLT-882-B', stock: 45, min: 20 },
    { name: 'Capteur Proximité M12', ref: 'SEN-OMR-04', stock: 2, min: 5 },
    { name: 'Huile Hydraulique 20L', ref: 'OIL-VG46', stock: 8, min: 15 }
]);

const budgetBreakdown = ref([
    { name: 'Pièces Détachées', percent: 42 },
    { name: 'Main d\'œuvre Interne', percent: 35 },
    { name: 'Sous-traitance', percent: 18 },
    { name: 'Consommables Énergie', percent: 5 }
]);

const technicians = ref([
    { name: 'Marc Chen', load: 92, completed: 14, backlog: 2, img: 'https://i.pravatar.cc/50?u=1' },
    { name: 'Sarah Ramos', load: 45, completed: 8, backlog: 5, img: 'https://i.pravatar.cc/50?u=2' },
    { name: 'Jean Dupont', load: 78, completed: 11, backlog: 3, img: 'https://i.pravatar.cc/50?u=3' }
]);

const calendarEvents = ref([
    { month: 'JAN', day: '14', title: 'Révision Annuelle TGBT', duration: '4h', team: 'Équipe Élec' },
    { month: 'JAN', day: '16', title: 'Lubrification Presse P1', duration: '1h', team: 'Maint. N1' },
    { month: 'JAN', day: '19', title: 'Calibration Capteurs L3', duration: '2h', team: 'Qualité' }
]);

// --- CHART CONFIGS ---
const performanceChartData = {
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    datasets: [
        {
            label: 'Disponibilité Actifs (%)',
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 4,
            fill: true,
            tension: 0.4,
            data: [98, 97, 99, 95, 98, 99, 98]
        },
        {
            label: 'Productivité (OEE)',
            borderColor: '#10B981',
            borderWidth: 3,
            borderDash: [5, 5],
            data: [82, 80, 85, 84, 88, 81, 83]
        }
    ]
};

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

const stockRotationData = {
    labels: ['W1', 'W2', 'W3', 'W4'],
    datasets: [{
        label: 'Entrées',
        backgroundColor: '#6366F1',
        data: [40, 60, 20, 90],
        borderRadius: 5
    }, {
        label: 'Sorties',
        backgroundColor: '#F43F5E',
        data: [30, 45, 55, 30],
        borderRadius: 5
    }]
};

const riskMatrixData = {
    labels: ['Vibration', 'Chaleur', 'Cycle', 'Bruit', 'Élec'],
    datasets: [
        { label: 'Presse P1', backgroundColor: 'rgba(239, 68, 68, 0.2)', borderColor: '#EF4444', data: [80, 40, 90, 30, 50] },
        { label: 'CNC Haas', backgroundColor: 'rgba(99, 102, 241, 0.2)', borderColor: '#6366F1', data: [20, 30, 40, 20, 80] }
    ]
};

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

const showWODialog = ref(false);
</script>

<template>
    <AppLayout>
        <Head title="MaintenX Enterprise ERP - v2.0" />

        <div class="dashboard-container p-4 lg:p-8 bg-[#F1F5F9] min-h-screen font-sans">

           <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-5">
                    <div class="brand-icon bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100">
                        <i class="pi pi-bolt text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">MaintenX Dashboard</h1>
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <i class="pi pi-calendar text-xs"></i>
                            <span>System • généré à {{ new Date().toLocaleTimeString() }}
          </span>
                            <Tag value="Live Data" severity="success" class="ml-2 !text-[10px] !px-2" pulse />
                        </div>
                    </div>
                </div>

                 <div class="glass-panel flex flex-wrap items-center gap-2 p-3 bg-white/70 backdrop-blur-xl border border-white rounded-[2.5rem] shadow-xl shadow-slate-200/50">
                    <div class="flex items-center gap-3 px-4 py-2 border-r border-slate-100">
                        <i class="pi pi-filter-fill text-indigo-500"></i>
                        <span class="text-sm font-black text-slate-700">FILTRES</span>
                    </div>

                    <Dropdown v-model="selectedZone" :options="zones" optionLabel="name" placeholder="Zone Industrielle" class="custom-dropdown w-48" @change="applyFilters" />
                    <Dropdown v-model="selectedEquipment" :options="equipments" optionLabel="name" placeholder="Équipement" class="custom-dropdown w-48" @change="applyFilters" />

                    <Button icon="pi pi-sync" @click="applyFilters" :loading="isLoading" class="p-button-rounded p-button-indigo shadow-lg hover:scale-105 active:scale-95 transition-all" />

                    <Divider layout="vertical" class="hidden lg:block" />

                    <Button icon="pi pi-eye" @click="toggleMenu" aria-haspopup="true" aria-controls="overlay_menu" class="p-button-rounded p-button-text" v-tooltip.bottom="'Gérer les sections'" />
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
                    { label: 'Disponibilité', value: availabilityRate, unit: '%', icon: 'pi-power-off', color: 'indigo', desc: 'Temps de prod. réel' },
                    { label: 'Taux Préventif', value: preventiveMaintenanceRate, unit: '%', icon: 'pi-verified', color: 'emerald', desc: 'Objectif: > 80%' },
                    { label: 'MTBF', value: mtbf, unit: 'j', icon: 'pi-sync', color: 'amber', desc: 'Fiabilité équipement' },
                    { label: 'OEE / TRS', value: oee, unit: '%', icon: 'pi-percentage', color: 'rose', desc: 'Efficacité globale' }
                ]" :key="kpi.label" class="kpi-card group">
                    <div @click="toggleSection('kpis')" class="relative bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-xl hover:-translate-y-1 section-title">
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

            <section v-show="sections.sparklines.visible" data-section-id="sparklines" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 section-container">
                <div v-for="item in sparklineItems" :key="item.label" @click="toggleSection('sparklines')" class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col justify-between section-title">

                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ item.label }}</span>
                        <Tag :value="item.value" :style="{ backgroundColor: item.color + '20', color: item.color }" class="!rounded-lg" />
                    </div>
                    <div class="h-16 w-full">
                        <Chart type="line" :data="item.chartData" :options="sparklineOptions" class="h-full" />
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-12 gap-8 mb-8">
                <Card v-show="sections.workOrders.visible" data-section-id="workOrders" class="col-span-12 xl:col-span-7 !rounded-[3rem] border-none shadow-xl shadow-slate-200/40 bg-white section-container">

                    <template #title>
                        <div @click="toggleSection('workOrders')" class="flex justify-between items-center px-4 pt-2 section-title">
                            <span class="text-lg font-black text-slate-800">File d'Attente Interventions</span>
                            <div class="flex gap-2">
                                <Tag value="12 Urgent" severity="danger" rounded />
                                <Tag value="45 En Cours" severity="info" rounded />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="workOrders" scrollable scrollHeight="450px" class="p-datatable-sm custom-erp-table">
                            <Column field="id" header="ID" class="font-black text-indigo-600 w-20"></Column>
                            <Column field="asset" header="ÉQUIPEMENT">
                                <template #body="{data}">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700">{{ data.asset }}</span>
                                        <span class="text-[9px] text-slate-400">{{ data.location }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="priority" header="PRIORITÉ">
                                <template #body="{data}">
                                    <span :class="priorityClass(data.priority)">{{ data.priority }}</span>
                                </template>
                            </Column>
                            <Column field="technician" header="ASSIGNÉ">
                                <template #body="{data}">
                                    <div class="flex items-center gap-2">
                                        <Avatar :image="data.tech_img" shape="circle" size="small" />
                                        <span class="text-xs font-medium">{{ data.technician }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="progress" header="AVANCEMENT">
                                <template #body="{data}">
                                    <Knob v-model="data.progress" :size="35" readonly strokeWidth="8" rangeColor="#F1F5F9" valueColor="#6366F1" />
                                </template>
                            </Column>
                            <Column header="ACTIONS">
                                <template #body>
                                    <Button icon="pi pi-external-link" class="p-button-rounded p-button-text p-button-sm" />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <Card v-show="sections.stockAlerts.visible" data-section-id="stockAlerts" class="col-span-12 xl:col-span-5 !rounded-[3rem] border-none shadow-xl shadow-slate-200/40 bg-white overflow-hidden section-container">

                    <template #title>
                        <div @click="toggleSection('stockAlerts')" class="px-4 pt-2 font-black text-slate-800 section-title">Alerte Stock Critique</div>
                    </template>
                    <template #content>
                        <div class="space-y-4 px-2">
                            <div v-for="part in spareParts" :key="part.name" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-indigo-200 transition-colors ">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-xl">
                                        📦
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 text-sm">{{ part.name }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">REF: {{ part.ref }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black" :class="part.stock < part.min ? 'text-rose-500' : 'text-slate-600'">
                                        {{ part.stock }} / {{ part.min }} <span class="text-[9px]">UNITÉS</span>
                                    </p>
                                    <Button v-if="part.stock < part.min" label="Commander" class="p-button-text p-button-xs !p-0 font-bold text-indigo-600" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-indigo-50/50 rounded-[2rem]">
                            <h4 class="text-[10px] font-black text-indigo-400 uppercase mb-4 text-center">Rotation de Stock (30j)</h4>
                            <div class="h-[120px]">
                                <Chart type="bar" :data="stockRotationData" :options="miniChartOptions" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 section-container">
                <div v-show="sections.preventiveCalendar.visible" data-section-id="preventiveCalendar" class="bg-white p-8 rounded-[3rem] shadow-xl border border-slate-100 ">
                    <div @click="toggleSection('preventiveCalendar')" class="flex justify-between items-center mb-6 section-title">
                        <h3 class="font-black text-slate-800 tracking-tight">Calendrier Préventif</h3>
                        <i class="pi pi-calendar-plus text-indigo-500 text-xl cursor-pointer"></i>
                    </div>
                    <div class="space-y-4">
                        <div v-for="event in calendarEvents" :key="event.title" class="flex gap-4 items-start p-3 hover:bg-slate-50 rounded-2xl transition-colors">
                            <div class="flex flex-col items-center justify-center min-w-[50px] py-2 bg-indigo-50 rounded-xl text-indigo-600">
                                <span class="text-xs font-black uppercase">{{ event.month }}</span>
                                <span class="text-xl font-[1000]">{{ event.day }}</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">{{ event.title }}</h4>
                                <p class="text-xs text-slate-400">{{ event.duration }} • {{ event.team }}</p>
                                <div class="flex gap-1 mt-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-[9px] font-black text-slate-400 uppercase">Confirmé</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="sections.riskMatrix.visible" data-section-id="riskMatrix" class="bg-white p-8 rounded-[3rem] shadow-xl border border-slate-100 ">
                    <h3 @click="toggleSection('riskMatrix')" class="font-black text-slate-800 mb-6 section-title">Matrice de Risque Actifs</h3>
                    <div class="h-[300px]">
                        <Chart type="radar" :data="riskMatrixData" :options="radarOptions" />
                    </div>
                    <div class="mt-4 p-4 bg-rose-50 rounded-2xl">
                        <p class="text-[10px] text-rose-600 font-black uppercase mb-1"><i class="pi pi-exclamation-circle"></i> Risque Critique</p>
                        <p class="text-xs font-bold text-rose-900">Groupe Hydraulique G2 : Vibration hors tolérance (8.2mm/s)</p>
                    </div>
                </div>

                <div v-show="sections.technicianEfficiency.visible" data-section-id="technicianEfficiency" class="bg-white p-8 rounded-[3rem] shadow-xl border border-slate-100 ">
                    <h3 @click="toggleSection('technicianEfficiency')" class="font-black text-slate-800 mb-6 section-title">Efficacité Technicienne</h3>
                    <div class="space-y-6">
                        <div v-for="user in technicians" :key="user.name">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-2">
                                    <Avatar :image="user.img" shape="circle" />
                                    <span class="text-sm font-bold text-slate-700">{{ user.name }}</span>
                                </div>
                                <span class="text-xs font-black text-indigo-600">{{ user.load }}% Load</span>
                            </div>
                            <ProgressBar :value="user.load" :showValue="false" class="!h-2 !bg-slate-100">
                                <template #default>
                                    <div class="h-full bg-indigo-500 rounded-full" :style="{width: user.load + '%'}"></div>
                                </template>
                            </ProgressBar>
                            <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                                <span>{{ user.completed }} WO Terminés</span>
                                <span>{{ user.backlog }} en attente</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
                 <div class="grid grid-cols-12 gap-8 mb-10">

                <div class="col-span-12 lg:col-span-8 space-y-8 mt-8">
                    <Card v-show="sections.mainChart.visible" data-section-id="mainChart" class="main-card !rounded-[3rem] border-none shadow-xl shadow-slate-200/50 bg-white overflow-hidden section-container">
                        <template #title>
                            <div @click="toggleSection('mainChart')" class="flex justify-between items-center p-4 section-title">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-8 bg-indigo-600 rounded-full"></div>
                                    <span class="text-xl font-black text-slate-800">Flux de Maintenance & Temps d'Arrêt</span>
                                </div>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Exporter PDF'" />
                                    <Button icon="pi pi-ellipsis-h" class="p-button-rounded p-button-text p-button-secondary" />
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[450px] p-2">
                                <Chart type="bar" :data="mainChartData" :options="mainChartOptions" class="h-full" />
                            </div>
                            <div class="grid grid-cols-3 gap-4 p-6 bg-slate-50 rounded-b-[3rem] border-t border-slate-100">
                                <div class="text-center border-r border-slate-200">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Ratio Disponibilité</p>
                                    <p class="text-xl font-black text-indigo-600">98.5%</p>
                                </div>
                                <div class="text-center border-r border-slate-200">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Temps Moyen Clôture</p>
                                    <p class="text-xl font-black text-slate-800">3.4 <span class="text-xs">hrs</span></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Efficacité Équipe</p>
                                    <p class="text-xl font-black text-emerald-500">+12%</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                    <div v-show="sections.statusDuration.visible" data-section-id="statusDuration" class="status-duration-container bg-white p-2 rounded-[3rem] shadow-xl shadow-slate-200/50 section-container section-title" @click="toggleSection('statusDuration')">
                        <StatusDurationChart
                            :chart-data="maintenanceStatusDurationChart"
                            :equipments="equipments"
                            :zones="zones"
                        />
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <Card v-show="sections.statusDoughnut.visible" data-section-id="statusDoughnut" class="!rounded-[3rem] border-none shadow-xl shadow-slate-200/50 bg-white overflow-hidden section-container">
                        <template #title>
                            <div @click="toggleSection('statusDoughnut')" class="flex justify-between items-center px-4 pt-4 section-title">
                                <span class="text-lg font-black text-slate-800">Charge par Statut</span>
                                <i class="pi pi-info-circle text-slate-300"></i>
                            </div>
                        </template>
                        <template #content>
                            <div class="relative flex flex-col items-center py-6">
                                <div class="h-[300px] w-full">
                                    <Chart type="doughnut" :data="doughnutData" :options="{
                                        cutout: '82%',
                                        plugins: { legend: { display: true, position: 'bottom', labels: { usePointStyle: true, font: { size: 11, weight: '700' } } } }
                                    }" />
                                </div>
                                <div class="absolute top-[40%] flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-5xl font-[1000] text-slate-800 leading-none">{{ activeTasksCount }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Work Orders</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <div v-show="sections.budget.visible" data-section-id="budget" class="budget-card bg-[#0F172A] p-8 rounded-[3rem] text-white shadow-2xl relative overflow-hidden group section-container">
                        <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-125 transition-transform">
                            <i class="pi pi-wallet text-[6rem]"></i>
                        </div>
                        <h3 @click="toggleSection('budget')" class="text-slate-400 text-xs font-black uppercase tracking-widest mb-6 section-title">Finances Maintenance</h3>

                        <div class="mb-8">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-3xl font-black">{{ formatCurrency(expensesTotal) }}</span>
                                <span class="text-indigo-400 font-bold text-sm">Target: 80%</span>
                            </div>
                            <ProgressBar :value="75" :showValue="false" class="!h-3 !bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                            </ProgressBar>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    <span class="text-sm font-medium">Pièces Détachées</span>
                                </div>
                                <span class="font-bold">42%</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                    <span class="text-sm font-medium">Main d'œuvre</span>
                                </div>
                                <span class="font-bold">38%</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                    <span class="text-sm font-medium">Sous-traitance</span>
                                </div>
                                <span class="font-bold">20%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <Card v-show="sections.criticalityAnalysis.visible" data-section-id="criticalityAnalysis" class="col-span-12 xl:col-span-7 !rounded-[3rem] border-none shadow-xl shadow-slate-200/50 bg-white section-container">
                    <template #title>
                        <div @click="toggleSection('criticalityAnalysis')" class="flex items-center gap-3 px-2 pt-2 section-title">
                            <i class="pi pi-exclamation-triangle text-rose-500 text-xl"></i>
                            <span class="text-lg font-black text-slate-800">Analyse de Criticité Équipements</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="h-[400px]">
                            <Chart type="bar" :data="{
                                labels: props.topFailingEquipmentsChart?.labels || ['CNC-01', 'POMPE-P2', 'TURBINE-X', 'COMP-04', 'LIGNE-3'],
                                datasets: [{
                                    label: 'Nombre de pannes',
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

                <Card v-show="sections.recentInterventions.visible" data-section-id="recentInterventions" class="col-span-12 xl:col-span-5 !rounded-[3rem] border-none shadow-xl shadow-slate-200/50 bg-white overflow-hidden section-container">
                    <template #title>
                        <div @click="toggleSection('recentInterventions')" class="flex justify-between items-center px-4 pt-2 section-title">
                            <span class="text-lg font-black text-slate-800">Flux d'Interventions</span>
                            <Button label="Voir Tout" class="p-button-text p-button-sm font-bold" />
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="props.recentInterventions || []" :rows="5" class="p-datatable-sm custom-table">
                            <Column field="equipment" header="ACTIF">
                                <template #body="slotProps">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                                            <i class="pi pi-box text-slate-500 text-xs"></i>
                                        </div>
                                        <span class="font-bold text-slate-700 text-xs">{{ slotProps.data.equipment }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="priority" header="PRIORITÉ">
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.priority" :severity="getPrioritySeverity(slotProps.data.priority)" class="!text-[9px] !px-2" />
                                </template>
                            </Column>
                            <Column field="technician" header="TECH">
                                <template #body="slotProps">
                                    <AvatarGroup>
                                        <Avatar :image="slotProps.data.tech_image" shape="circle" size="small" v-tooltip.top="slotProps.data.technician" />
                                    </AvatarGroup>
                                </template>
                            </Column>
                            <Column field="status" header="ÉTAT">
                                <template #body="slotProps">
                                    <i :class="['pi', slotProps.data.status === 'Completed' ? 'pi-check-circle text-emerald-500' : 'pi-spin pi-spinner text-amber-500']"></i>
                                </template>
                            </Column>
                        </DataTable>

                        <div class="mt-8 p-4 bg-indigo-50 rounded-3xl flex justify-around items-center">
                            <div class="text-center">
                                <p class="text-[9px] font-black text-indigo-400 uppercase">En cours</p>
                                <p class="text-lg font-black text-indigo-700">14</p>
                            </div>
                            <div class="w-px h-8 bg-indigo-200"></div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-indigo-400 uppercase">En attente</p>
                                <p class="text-lg font-black text-indigo-700">08</p>
                            </div>
                            <div class="w-px h-8 bg-indigo-200"></div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-indigo-400 uppercase">Clôturés (24h)</p>
                                <p class="text-lg font-black text-indigo-700">22</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <section v-show="sections.stockFlow.visible" data-section-id="stockFlow" class="mt-10 section-container">
                <Card class="!rounded-[3rem] border-none shadow-xl shadow-slate-200/50 bg-white overflow-hidden">
                    <template #content>
                        <div @click="toggleSection('stockFlow')" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center p-4 section-title">
                            <div class="lg:col-span-4">
                                <h3 class="text-2xl font-black text-slate-800 mb-2">Flux Stocks & Pièces</h3>
                                <p class="text-slate-400 text-sm mb-6 font-medium">Analyse des entrées/sorties de composants critiques sur les 30 derniers jours.</p>
                                <div class="space-y-4">
                                    <div class="flex justify-between p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                                        <span class="text-emerald-700 font-bold">Réapprovisionnement</span>
                                        <span class="font-black text-emerald-800">+124 art.</span>
                                    </div>
                                    <div class="flex justify-between p-4 bg-rose-50 rounded-2xl border border-rose-100">
                                        <span class="text-rose-700 font-bold">Consommation</span>
                                        <span class="font-black text-rose-800">-89 art.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="lg:col-span-8 h-[300px]">
                                <Chart type="line" :data="{
                                    labels: props.sparePartsMovement?.labels || ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                                    datasets: [
                                        { label: 'Entrées', borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, data: props.sparePartsMovement?.entries || [20, 45, 30, 60], tension: 0.4 },
                                        { label: 'Sorties', borderColor: '#EF4444', data: props.sparePartsMovement?.exits || [15, 30, 45, 25], tension: 0.4 }
                                    ]
                                }" :options="{ ...mainChartOptions, plugins: { legend: { position: 'bottom' } } }" class="h-full" />
                            </div>
                        </div>
                    </template>
                </Card>
            </section>
            <footer class="mt-12 flex justify-between items-center text-slate-400 text-[10px] font-bold uppercase tracking-widest bg-white/50 p-6 rounded-[2rem]">
                <div class="flex items-center gap-4">
                    <span>© 2026 MAINTENX TECHNOLOGIES</span>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <span class="text-indigo-500">Node Status: Operational</span>
                </div>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-indigo-500 transition-colors">Documentation API</a>
                    <a href="#" class="hover:text-indigo-500 transition-colors">Support Support</a>
                    <span class="text-slate-300">v10.4.2-STABLE</span>
                </div>
            </footer>
        </div>

        <Dialog v-model:visible="showWODialog" header="Créer un Ordre de Travail" :modal="true" class="w-full max-w-2xl">
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
    @apply !bg-slate-50 !border-none !rounded-2xl !shadow-none;
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
    @apply !rounded-[2rem] !border-none !shadow-2xl;
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

.p-button-indigo {
    background: #4f46e5;
    border: none;
}

.p-button-indigo:hover {
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
