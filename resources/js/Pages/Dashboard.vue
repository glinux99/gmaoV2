<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

// --- PRIMEVUE IMPORTS ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Divider from 'primevue/divider';
import ProgressBar from 'primevue/progressbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Menu from 'primevue/menu';
import Timeline from 'primevue/timeline';
import Badge from 'primevue/badge';
import Dropdown from 'primevue/dropdown';
import Tooltip from 'primevue/tooltip';

// --- DIRECTIVES ---
const vTooltip = Tooltip;

const { t } = useI18n();

// --- PROPS (fournies par le contrôleur) ---
const props = defineProps({
    stats: {
        type: Object,
        required: true
    },
    recentProjects: Array,
    recentMessages: Array,
    timelineEvents: Array,
    storage: Object
});

// --- ÉTAT LOCAL (sélecteurs) ---
const dateRange = ref(null);
const selectedPeriod = ref({ name: 'Ce mois', code: 'TM' });
const periods = ref([
    { name: 'Aujourd\'hui', code: 'TD' },
    { name: 'Cette semaine', code: 'TW' },
    { name: 'Ce mois', code: 'TM' },
    { name: 'Cette année', code: 'TY' }
]);

const actionMenuRef = ref(null);
const selectedProject = ref(null);

// Les données arrivent du serveur, pas de chargement simulé
const isLoading = ref(false);

// --- KPIs principaux (calculés à partir des stats) ---
const kpis = computed(() => [
    { id: 1, label: 'Visiteurs Uniques', value: props.stats.visitors.toLocaleString(), trend: props.stats.visitorTrend, icon: 'pi pi-globe', color: 'blue', bg: 'bg-blue-50', text: 'text-blue-600' },
    { id: 2, label: 'Publications Actives', value: props.stats.posts, trend: props.stats.postTrend, icon: 'pi pi-megaphone', color: 'indigo', bg: 'bg-indigo-50', text: 'text-indigo-600' },
    { id: 3, label: 'Documents Sécurisés', value: props.stats.documents.toLocaleString(), trend: props.stats.documentTrend, icon: 'pi pi-folder', color: 'emerald', bg: 'bg-emerald-50', text: 'text-emerald-600' },
    { id: 4, label: 'Projets en Cours', value: props.stats.projects, trend: props.stats.projectTrend, icon: 'pi pi-briefcase', color: 'orange', bg: 'bg-orange-50', text: 'text-orange-600' }
]);

const secondaryKpis = computed(() => [
    { label: 'Membres Actifs', value: props.stats.members, icon: 'pi pi-users', color: 'purple' },
    { label: 'Nouveaux Messages', value: props.stats.messages, icon: 'pi pi-envelope', color: 'rose' },
    { label: 'Espace Stockage', value: `${props.storage?.usedPercent || 0}%`, icon: 'pi pi-cloud', color: 'cyan' },
    { label: 'Taux d\'Engagement', value: '24.8%', icon: 'pi pi-chart-line', color: 'teal' }
]);

// --- CHARTS CONFIGURATION (données statiques pour l'instant) ---
const lineChartData = ref({});
const lineChartOptions = ref({});
const pieChartData = ref({});
const pieChartOptions = ref({});

const initCharts = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--text-color');
    const textColorSecondary = documentStyle.getPropertyValue('--text-color-secondary');
    const surfaceBorder = documentStyle.getPropertyValue('--surface-border');

    lineChartData.value = {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
        datasets: [
            {
                label: 'Visiteurs',
                data: [650, 840, 780, 950, 1100, 1400, 1300, 1800, 2100, 1900, 2300, 2600],
                fill: true,
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderColor: documentStyle.getPropertyValue('--indigo-500'),
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: documentStyle.getPropertyValue('--indigo-500'),
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Interactions (Docs/Médias)',
                data: [400, 500, 450, 600, 750, 900, 850, 1200, 1500, 1300, 1700, 1900],
                fill: true,
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                borderColor: documentStyle.getPropertyValue('--emerald-500'),
                borderDash: [5, 5],
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4
            }
        ]
    };

    lineChartOptions.value = {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: { position: 'top', align: 'end', labels: { color: textColor, usePointStyle: true, boxWidth: 8 } },
            tooltip: { mode: 'index', intersect: false, backgroundColor: 'rgba(15, 23, 42, 0.9)', titleColor: '#fff', padding: 12, borderRadius: 8 }
        },
        scales: {
            x: { ticks: { color: textColorSecondary }, grid: { display: false, drawBorder: false } },
            y: { ticks: { color: textColorSecondary }, grid: { color: surfaceBorder, borderDash: [4, 4], drawBorder: false } }
        },
        interaction: { mode: 'nearest', axis: 'x', intersect: false }
    };

    pieChartData.value = {
        labels: ['Actualités', 'Pages', 'Médias', 'Documents'],
        datasets: [{
            data: [35, 15, 20, 30],
            backgroundColor: [
                documentStyle.getPropertyValue('--indigo-500'),
                documentStyle.getPropertyValue('--blue-500'),
                documentStyle.getPropertyValue('--orange-500'),
                documentStyle.getPropertyValue('--emerald-500')
            ],
            hoverBackgroundColor: [
                documentStyle.getPropertyValue('--indigo-400'),
                documentStyle.getPropertyValue('--blue-400'),
                documentStyle.getPropertyValue('--orange-400'),
                documentStyle.getPropertyValue('--emerald-400')
            ],
            borderWidth: 0
        }]
    };

    pieChartOptions.value = {
        maintainAspectRatio: false,
        cutout: '75%',
        plugins: {
            legend: { position: 'bottom', labels: { color: textColor, usePointStyle: true, padding: 20 } }
        }
    };
};

// --- UTILS ---
const getStatusSeverity = (status) => {
    switch (status) {
        case 'Terminé': return 'success';
        case 'En cours': return 'info';
        case 'Planifié': return 'warning';
        case 'En pause': return 'danger';
        default: return 'secondary';
    }
};

const menuItems = ref([
    { label: 'Voir les détails', icon: 'pi pi-eye', command: () => goTo(`/projects/${selectedProject.value?.id}`) },
    { label: 'Modifier', icon: 'pi pi-pencil' },
    { separator: true },
    { label: 'Archiver', icon: 'pi pi-box', class: 'text-orange-500' }
]);

const toggleMenu = (event, data) => {
    selectedProject.value = data;
    actionMenuRef.value.toggle(event);
};

const goTo = (url) => router.visit(url);

// --- LIFECYCLE ---
onMounted(() => {
    initCharts();
});
</script>

<template>
    <AppLayout>
        <Head title="Dashboard Pro Ultra" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pb-24 pt-8 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                    <div class="absolute top-12 -left-24 w-72 h-72 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
                </div>

                <div class="max-w-screen-2xl mx-auto relative z-10">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <Badge value="Administration" severity="success" class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30" />
                                <span class="text-slate-400 text-sm font-medium"><i class="pi pi-calendar mr-1"></i> {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                            </div>
                            <h1 class="text-3xl lg:text-4xl font-black text-white tracking-tight">Vue d'ensemble de l'organisation</h1>
                            <p class="text-slate-400 mt-2 text-lg">Gérez vos contenus, projets et équipes depuis cet espace centralisé.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 bg-slate-800/50 p-2 rounded-2xl border border-slate-700/50 backdrop-blur-md">
                            <Dropdown v-model="selectedPeriod" :options="periods" optionLabel="name" class="w-full md:w-48 !bg-transparent !border-none !text-white !shadow-none" />
                            <Divider layout="vertical" class="hidden md:block !border-slate-600" />
                            <Button icon="pi pi-cloud-upload" label="Upload" class="p-button-rounded p-button-text !text-slate-300 hover:!text-white" v-tooltip.bottom="'Ajouter un document'" @click="goTo('/documents/create')" />
                            <Button icon="pi pi-plus" label="Créer" class="p-button-rounded bg-indigo-500 hover:bg-indigo-600 border-none shadow-lg shadow-indigo-500/30 text-white px-6 font-bold" @click="goTo('/posts/create')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-16 relative z-20 space-y-8">
                <!-- KPI GRID -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <Card v-for="kpi in kpis" :key="kpi.id" class="dashboard-card border-none shadow-xl shadow-slate-200/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-indigo-500/10 group cursor-pointer">
                        <template #content>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ kpi.label }}</p>
                                    <div class="flex items-end gap-3">
                                        <h2 class="text-4xl font-black text-slate-800">{{ kpi.value }}</h2>
                                        <span :class="['text-sm font-bold flex items-center mb-1', kpi.trend > 0 ? 'text-emerald-500' : 'text-rose-500']">
                                            <i :class="['pi text-xs mr-1', kpi.trend > 0 ? 'pi-arrow-up' : 'pi-arrow-down']"></i>
                                            {{ Math.abs(kpi.trend) }}%
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2">vs le mois dernier</p>
                                </div>
                                <div :class="`w-14 h-14 rounded-2xl flex items-center justify-center ${kpi.bg} ${kpi.text} transition-transform duration-500 group-hover:rotate-12`">
                                    <i :class="`${kpi.icon} text-2xl`"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- CHARTS & SECONDARY STATS SECTION -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Main Chart -->
                    <Card class="xl:col-span-8 dashboard-card border border-slate-100 shadow-lg shadow-slate-200/40">
                        <template #title>
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                        <i class="pi pi-chart-line text-xl"></i>
                                    </div>
                                    <span class="font-bold text-xl text-slate-800">Trafic & Interactions</span>
                                </div>
                                <div class="flex gap-2">
                                    <Button label="Exporter" icon="pi pi-download" class="p-button-outlined p-button-secondary p-button-sm" />
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[400px] mt-4 w-full">
                                <Chart type="line" :data="lineChartData" :options="lineChartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>

                    <!-- Right Column: Pie Chart & Mini KPIs -->
                    <div class="xl:col-span-4 flex flex-col gap-6">
                        <!-- Distribution Chart -->
                        <Card class="dashboard-card border border-slate-100 shadow-lg shadow-slate-200/40 flex-1">
                            <template #title>
                                <span class="font-bold text-lg text-slate-800">Répartition des Contenus</span>
                            </template>
                            <template #content>
                                <div class="h-[250px] relative flex items-center justify-center">
                                    <Chart type="doughnut" :data="pieChartData" :options="pieChartOptions" class="h-full w-full" />
                                    <div class="absolute flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-3xl font-black text-slate-800">{{ stats.posts + stats.documents }}</span>
                                        <span class="text-xs text-slate-500 font-medium uppercase">Total</span>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <!-- Mini KPIs (Grid 2x2) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div v-for="item in secondaryKpis" :key="item.label" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center group cursor-pointer hover:bg-slate-50 transition">
                                <i :class="`${item.icon} text-2xl text-${item.color}-500 mb-2 group-hover:scale-110 transition-transform`"></i>
                                <span class="text-2xl font-black text-slate-800">{{ item.value }}</span>
                                <span class="text-xs font-medium text-slate-500">{{ item.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLES & TIMELINE SECTION -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Projets Table (8 cols) -->
                    <Card class="xl:col-span-8 dashboard-card border border-slate-100 shadow-lg shadow-slate-200/40">
                        <template #title>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-xl text-slate-800">Projets Récents</span>
                                <Button label="Voir tout" class="p-button-text p-button-sm" @click="goTo('/projects')" />
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="recentProjects" class="p-datatable-lg" responsiveLayout="scroll" :rowHover="true" stripedRows>
                                <Column field="id" header="ID" style="width: 10%">
                                    <template #body="{ data }">
                                        <span class="text-slate-500 font-mono text-sm">{{ data.id }}</span>
                                    </template>
                                </Column>
                                <Column field="name" header="Nom du Projet" style="width: 30%">
                                    <template #body="{ data }">
                                        <span class="font-bold text-slate-800">{{ data.name }}</span>
                                    </template>
                                </Column>
                                <Column header="Équipe" style="width: 15%">
                                    <template #body="{ data }">
                                        <AvatarGroup class="custom-avatar-group">
                                            <Avatar v-for="(member, idx) in data.team.slice(0, 3)" :key="idx" :label="member" shape="circle" class="bg-indigo-100 text-indigo-700 font-bold border-2 border-white" size="small" />
                                            <Avatar v-if="data.team.length > 3" :label="`+${data.team.length - 3}`" shape="circle" class="bg-slate-100 text-slate-600 font-bold border-2 border-white" size="small" />
                                        </AvatarGroup>
                                    </template>
                                </Column>
                                <Column field="progress" header="Progression" style="width: 20%">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <ProgressBar :value="data.progress" :showValue="false" class="w-full h-2" :color="data.progress === 100 ? '#10b981' : '#6366f1'" />
                                            <span class="text-xs font-bold text-slate-600 w-8">{{ data.progress }}%</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="status" header="Statut" style="width: 15%">
                                    <template #body="{ data }">
                                        <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="uppercase text-[10px] tracking-wider px-2 py-1" rounded />
                                    </template>
                                </Column>
                                <Column style="width: 10%; text-align: right">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-plain" @click="toggleMenu($event, data)" />
                                    </template>
                                </Column>
                            </DataTable>
                            <Menu ref="actionMenuRef" :model="menuItems" :popup="true" />
                        </template>
                    </Card>

                    <!-- Sidebar Widgets (4 cols) -->
                    <div class="xl:col-span-4 space-y-6">
                        <!-- Messages & Communications -->
                        <Card class="dashboard-card border border-slate-100 shadow-lg shadow-slate-200/40">
                            <template #title>
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-lg text-slate-800">Messages Non Lus</span>
                                    <Badge :value="stats.messages" severity="danger" />
                                </div>
                            </template>
                            <template #content>
                                <ul class="m-0 p-0 list-none space-y-4">
                                    <li v-for="msg in recentMessages" :key="msg.id" class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                                        <div class="relative">
                                            <Avatar :image="msg.avatar" :label="!msg.avatar ? msg.sender.charAt(0) : ''" shape="circle" size="large" :class="!msg.avatar ? 'bg-indigo-100 text-indigo-700' : ''" />
                                            <span v-if="msg.unread" class="absolute top-0 right-0 w-3 h-3 bg-rose-500 rounded-full border-2 border-white"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-baseline mb-1">
                                                <span class="font-bold text-slate-800 truncate">{{ msg.sender }}</span>
                                                <span class="text-xs text-slate-400 whitespace-nowrap ml-2">{{ msg.time }}</span>
                                            </div>
                                            <p :class="['text-sm truncate', msg.unread ? 'font-semibold text-slate-700' : 'text-slate-500']">{{ msg.subject }}</p>
                                        </div>
                                    </li>
                                </ul>
                                <Divider />
                                <Button label="Ouvrir la messagerie" icon="pi pi-external-link" class="p-button-text w-full text-indigo-600" @click="goTo('/messages')" />
                            </template>
                        </Card>

                        <!-- Activity Timeline -->
                        <Card class="dashboard-card border border-slate-100 shadow-lg shadow-slate-200/40">
                            <template #title>
                                <span class="font-bold text-lg text-slate-800">Historique des Activités</span>
                            </template>
                            <template #content>
                                <Timeline :value="timelineEvents" class="custom-timeline mt-4">
                                    <template #marker="slotProps">
                                        <span class="flex w-8 h-8 items-center justify-center text-white rounded-full z-10 shadow-sm" :style="{ backgroundColor: slotProps.item.color }">
                                            <i :class="slotProps.item.icon + ' text-sm'"></i>
                                        </span>
                                    </template>
                                    <template #content="slotProps">
                                        <div class="mb-6 ml-2">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="font-bold text-slate-800 text-sm">{{ slotProps.item.status }}</span>
                                                <span class="text-xs text-slate-400 font-medium">{{ slotProps.item.date }}</span>
                                            </div>
                                            <p class="text-sm text-slate-600 m-0">{{ slotProps.item.desc }}</p>
                                        </div>
                                    </template>
                                </Timeline>
                            </template>
                        </Card>

                        <!-- Storage Widget -->
                        <Card class="bg-gradient-to-br from-slate-800 to-slate-900 text-white rounded-3xl border-none shadow-xl overflow-hidden relative">
                            <i class="pi pi-server absolute -bottom-6 -right-6 text-9xl text-white/5 rotate-12 pointer-events-none"></i>
                            <template #content>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="p-2 bg-white/10 rounded-xl backdrop-blur-sm">
                                            <i class="pi pi-database text-xl text-cyan-400"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-lg m-0">Espace Serveur</h3>
                                            <p class="text-slate-400 text-xs m-0">Documents & Médias</p>
                                        </div>
                                    </div>

                                    <div class="mb-2 flex justify-between items-end">
                                        <span class="text-3xl font-black text-white">{{ storage.used }}<span class="text-lg text-slate-400 font-medium"> GB</span></span>
                                        <span class="text-sm text-slate-400">/ {{ storage.total }} GB</span>
                                    </div>

                                    <ProgressBar :value="storage.usedPercent" :showValue="false" class="h-2 bg-slate-700 custom-progress-cyan mb-4" />

                                    <div class="grid grid-cols-2 gap-4 text-sm mt-6">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                                                <span class="text-slate-300">Documents</span>
                                            </div>
                                            <span class="font-bold">{{ storage.documents }} GB</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                                <span class="text-slate-300">Médias</span>
                                            </div>
                                            <span class="font-bold">{{ storage.media }} GB</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard-card {
    border-radius: 1.5rem !important;
    overflow: hidden;
}

:deep(.p-card-body) {
    padding: 1.5rem !important;
}

:deep(.p-card-title) {
    margin-bottom: 1rem;
}

/* Animations */
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}

/* DataTable Customizations */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: transparent;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #f1f5f9;
    padding: 1rem 0.5rem;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.2s;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.p-datatable .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 0.5rem;
}

/* Timeline Customizations */
:deep(.custom-timeline .p-timeline-event-opposite) {
    display: none;
}
:deep(.custom-timeline .p-timeline-event-content) {
    padding-bottom: 1.5rem;
}

/* Progress Bar Customizations */
:deep(.p-progressbar) {
    border-radius: 999px;
    background: #f1f5f9;
}
:deep(.p-progressbar .p-progressbar-value) {
    border-radius: 999px;
    transition: width 1s ease-in-out;
}
.custom-progress-cyan :deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #06b6d4, #3b82f6) !important;
}

/* Avatar Group Fixes */
:deep(.custom-avatar-group .p-avatar) {
    border: 2px solid #ffffff;
    margin-left: -0.5rem;
}
</style>
