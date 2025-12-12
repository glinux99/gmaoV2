<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, watch, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
// Assurez-vous d'importer les composants PrimeVue nécessaires
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
    // Métriques des Sparklines (Renommées pour correspondre au contrôleur)
    usersCount: Number,
    activeTasksCount: Number,
    timeSpent: String,
    averageInterventionTime: String,
    rolesCount: Number,
    permissionsCount: Number,
    sparklineData: Object, // Contient les données 'metric', 'chartData', etc.

    // Filtres
    filters: Object,

    // Données graphiques
    sparePartsMovement: Object,
    tasksByStatus: Object,
    tasksByPriority: Object,

    // Données Financières
    depensesPiecesDetachees: Number,
    budgetTotal: Number,
    depensesPrestation: Number,
    expensesTotal: Number,

    // Données des visualisations détaillées
    monthlyVolumeData: Object,
    failuresByType: Object,
    interventionsByType: Object,
});

// --- Gestion des Filtres de Date ---

// 1. Initialisation des dates
const initialStartDate = props.filters?.startDate ? new Date(props.filters.startDate) : null;
const initialEndDate = props.filters?.endDate ? new Date(props.filters.endDate) : null;
const initialDateRange = initialStartDate && initialEndDate ? [initialStartDate, initialEndDate] : null;

// Les `ref` pour l'état du filtre
const dateRange = ref(initialDateRange);
const filterType = ref(props.filters?.filterType || 'this_month');

// 2. Fonction de calcul des plages prédéfinies
const updateDateRange = () => {
    let startDate, endDate;
    const today = new Date();

    switch (filterType.value) {
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'last_week':
            // Semaine civile précédente complète (Lundi au Dimanche)
            const dayOfWeek = today.getDay();
            const lastWeekEnd = new Date(today);
            // Si aujourd'hui est Dimanche (0), on enlève 7 jours pour aller au Dimanche dernier, sinon on enlève le jour de la semaine (1=Lundi -> 1 jour)
            lastWeekEnd.setDate(today.getDate() - (dayOfWeek === 0 ? 7 : dayOfWeek));

            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate() - 6);

            startDate = lastWeekStart;
            endDate = lastWeekEnd;
            break;
        default: // 'custom'
            return;
    }

    if (startDate && endDate) {
        // Fixer les heures pour couvrir la journée complète
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(23, 59, 59, 999);
        dateRange.value = [startDate, endDate];
    }
};

// 3. Cycle de vie et écouteurs

onMounted(() => {
    // Si aucun filtre n'est passé par Inertia, initialiser la plage par défaut au montage
    if (!props.filters?.startDate && !props.filters?.endDate) {
        updateDateRange();
    }
});

// Écoute les changements dans le Dropdown (sauf 'custom')
watch(filterType, () => {
    if (filterType.value !== 'custom') {
        updateDateRange();
    }
});

// Écoute les changements dans la plage de dates (Calendar ou updateDateRange)
// Déclenche la requête Inertia pour recharger le dashboard avec les nouvelles données
watch(dateRange, (newDates) => {
    if (newDates && newDates[0] && newDates[1]) {
        // Formatage des dates au format ISO (YYYY-MM-DD) pour le backend
        const newStartDate = newDates[0].toISOString().split('T')[0];
        const newEndDate = newDates[1].toISOString().split('T')[0];

        // Comparaison pour éviter un rechargement si les dates sont identiques
        const currentStartDate = props.filters?.startDate;
        const currentEndDate = props.filters?.endDate;

        if (newStartDate !== currentStartDate || newEndDate !== currentEndDate) {
            router.get(route('dashboard.index'), {
                start_date: newStartDate, // Paramètre pour le contrôleur
                end_date: newEndDate,     // Paramètre pour le contrôleur
                filterType: filterType.value, // Maintient l'état du dropdown
            }, { preserveState: true, replace: true, preserveScroll: true });
        }
    }
}, {
    deep: true
});

// --- Logique Graphiques ---

// Options pour un graphique Sparkline
const sparklineOptions = {
    plugins: { legend: { display: false } },
    maintainAspectRatio: false,
    elements: {
        point: { radius: 0 }
    },
    scales: {
        x: { display: false },
        y: { display: false }
    }
};

// Préparation des données pour les quatre cartes principales (Sparklines)
const sparklineItems = computed(() => {
    const data = props.sparklineData;

    const formatSparklineData = (chartData) => {
        if (!chartData || !chartData.datasets || chartData.datasets.length === 0) return chartData;

        const formattedData = JSON.parse(JSON.stringify(chartData));
        formattedData.datasets[0] = {
            ...formattedData.datasets[0],
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            fill: true,
            tension: 0.4,
            type: 'line'
        };
        return formattedData;
    };

    return [
        {
            label: 'Utilisateurs',
            value: props.usersCount ?? 0,
            metric: data?.users?.metric ?? '0%',
            icon: 'pi pi-users',
            chartData: formatSparklineData(data?.users?.chartData),
            changeColor: (data?.users?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
        {
            label: 'Tâches Actives',
            value: props.activeTasksCount ?? 0,
            metric: data?.activeTasks?.metric ?? '0%',
            icon: 'pi pi-check-square',
            chartData: formatSparklineData(data?.activeTasks?.chartData),
            changeColor: (data?.activeTasks?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
        {
            label: 'Temps Passé (h)',
            value: data?.timeSpent?.value ?? '0h',
            metric: data?.timeSpent?.metric ?? '0%',
            icon: 'pi pi-hourglass',
            chartData: formatSparklineData(data?.timeSpent?.chartData),
            changeColor: (data?.timeSpent?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
        {
            label: 'Tps Moyen Interv.',
            value: data?.averageInterventionTime?.value ?? '0m',
            metric: data?.averageInterventionTime?.metric ?? '0%',
            icon: 'pi pi-clock',
            chartData: formatSparklineData(data?.averageInterventionTime?.chartData),
            changeColor: (data?.averageInterventionTime?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
    ];
});


// 3. Préparation des données pour le graphique de répartition des tâches
const tasksChartType = ref('status');
const tasksChartFilterOptions = ref([
    { label: 'Par Statut', value: 'status' },
    { label: 'Par Priorité', value: 'priority' }
]);

const tasksDistributionChartData = computed(() => {
    const dataSet = tasksChartType.value === 'status' ? props.tasksByStatus : props.tasksByPriority;
    const defaultData = { labels: [], datasets: [{ data: [], backgroundColor: ['#42A5F5', '#FFA726', '#66BB6A', '#EF5350', '#AB47BC'], hoverBackgroundColor: ['#64B5F6', '#FFB74D', '#81C784', '#E57373', '#BA68C8'] }] };

    if (!dataSet || !dataSet.labels || !dataSet.data) {
        return defaultData;
    }

    return {
        labels: dataSet.labels,
        datasets: [{ ...defaultData.datasets[0], data: dataSet.data }]
    };
});

// 4. Préparation des données pour le graphique des mouvements de pièces détachées
const sparePartsChartData = computed(() => ({
    labels: props.sparePartsMovement?.labels ?? [],
    datasets: [
        {
            label: 'Pièces entrantes',
            data: props.sparePartsMovement?.entries ?? [],
            fill: false,
            borderColor: '#42A5F5',
            tension: 0.4
        },
        {
            label: 'Pièces sortantes',
            data: props.sparePartsMovement?.exits ?? [],
            fill: false,
            borderColor: '#FFA726',
            tension: 0.4
        }
    ]
}));

const lineChartOptions = ref({
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: '#495057' }
        }
    },
});


// --- Logique pour les graphiques détaillés ---

const failuresChartData = computed(() => {
    const dataSet = props.failuresByType;
    const defaultColors = ['#4F46E5', '#EF4444', '#F97316', '#10B981', '#6366F1'];

    if (!dataSet || !dataSet.labels || !dataSet.data) {
        return { labels: [], datasets: [{ data: [], backgroundColor: defaultColors }] };
    }

    return {
        labels: dataSet.labels,
        datasets: [{
            data: dataSet.data,
            backgroundColor: defaultColors.slice(0, dataSet.labels.length),
        }]
    };
});

const interventionsChartData = computed(() => ({
    labels: props.interventionsByType?.labels ?? [],
    datasets: [
        {
            label: 'Nombre d\'Interventions',
            data: props.interventionsByType?.data ?? [],
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1,
            borderRadius: 4
        }
    ]
}));

const interventionsChartOptions = ref({
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
});

const monthlyVolumeChartData = computed(() => {
    const dataSet = props.monthlyVolumeData;

    return {
        labels: dataSet?.labels ?? [],
        datasets: [
            {
                type: 'bar',
                label: 'Stoppée',
                backgroundColor: '#EF4444',
                data: dataSet?.stopped || [],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: 'Dégradée',
                backgroundColor: '#FBBF24',
                data: dataSet?.degraded || [],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: 'Amélioration',
                backgroundColor: '#3B82F6',
                data: dataSet?.improvement || [],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'line',
                label: 'Délai de résolution moyen',
                borderColor: '#EF4444',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                data: dataSet?.resolutionTime || [],
                yAxisID: 'y1'
            }
        ]}
});

const comboChartOptions = ref({
    maintainAspectRatio: false,
    elements: {
        bar: {
            borderWidth: 1,
            borderColor: '#e9ecf2',
            borderRadius: 7
        }
    },
    plugins: {
        legend: {
            labels: { color: '#495057' }
        }
    },
    scales: {
        x: {
            stacked: true,
            ticks: { color: '#6B7280' },
            grid: { display: false }
        },
        y: {
            type: 'linear',
            display: true,
            position: 'left',
            stacked: true,
            ticks: {
                color: '#6B7280',
                beginAtZero: true,
            },
            title: {
                display: true,
                text: 'Nombre d\'interventions',
                color: '#6B7280'
            }
        },
        y1: {
            type: 'linear',
            display: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: {
                color: '#EF4444',
                callback: function(value) {
                    return value + 'h';
                },
            },
            title: {
                display: true,
                text: 'Délai (Heures)',
                color: '#EF4444'
            }
        }
    }
});
</script>
<template>
    <app-layout>
        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12">
                <div class="flex items-center justify-end gap-2 p-4 bg-white rounded-lg shadow-sm border">
                    <i class="pi pi-calendar text-xl text-gray-600"></i>
                    <h4 class="font-semibold text-gray-700 m-0">Filtrer par période :</h4>

                    <Dropdown
                        v-model="filterType"
                        :options="[
                            { label: 'Ce mois-ci', value: 'this_month' },
                            { label: 'Mois dernier', value: 'last_month' },
                            { label: 'Dernière semaine', value: 'last_week' },
                            { label: 'Personnalisé', value: 'custom' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Sélectionner une période" class="w-full md:w-auto" />

                    <span v-if="filterType === 'custom'" class="mx-2 text-gray-600">Plage personnalisée:</span>
                    <Calendar v-model="dateRange" selectionMode="range" :manualInput="false" dateFormat="dd/mm/yy" placeholder="Sélectionner une période" class="w-full md:w-auto" />
                </div>
            </div>

            <hr class="col-span-12" />

            <h3 class="col-span-12 text-xl font-semibold mt-2">💸 Suivi Financier</h3>

            <div v-if="props.budgetTotal !== undefined" class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>Budget Total </template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.budgetTotal?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) ?? '0 €' }}</div>
                        <i class="pi pi-wrench text-5xl text-orange-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <div v-if="props.depensesPrestation !== undefined" class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>Dépenses de prestation</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.depensesPrestation?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) ?? '0 €' }}</div>
                        <i class="pi pi-briefcase text-5xl text-green-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <div v-if="props.expensesTotal !== undefined" class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>Total Dépense validée</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.expensesTotal?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) ?? '0 €' }}</div>
                        <i class="pi pi-exclamation-triangle text-5xl text-red-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <hr class="col-span-12" />

            <h3 class="col-span-12 text-xl font-semibold mt-2">📊 Key Metrics Overview</h3>
            <div v-for="(item, index) in sparklineItems" :key="index" class="col-span-12 sm:col-span-6 lg:col-span-3">
                <Card class="p-4 border shadow-sm h-full">
                    <template #title>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500 font-medium text-sm">{{ item.label }}</span>
                            <i :class="[item.icon]" class="text-xl text-gray-400"></i>
                        </div>
                    </template>
                    <template #content>
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-3xl font-bold text-gray-800">{{ item.value }}</div>
                            <div class="w-24 h-8">
                                <Chart v-if="item.chartData" type="line" :data="item.chartData" :options="sparklineOptions" class="h-full w-full" />
                            </div>
                        </div>

                        <div class="flex items-center text-sm">
                            <i :class="item.changeColor === 'text-green-500' ? 'pi pi-arrow-up-right text-xs mr-1' : 'pi pi-arrow-down-right text-xs mr-1'"></i>
                            <span :class="item.changeColor" class="font-medium">{{ item.metric.replace('-', '') }}</span>
                            <span class="text-gray-500 ml-2">from last period</span>
                        </div>
                    </template>
                </Card>
            </div>

            <hr class="col-span-12" />

            <h3 class="col-span-12 text-xl font-semibold mt-2">📈 Detailed Visualizations</h3>

            <div v-if="monthlyVolumeChartData.labels.length > 0" class="col-span-12">
                <Card>
                    <template #title>Volume mensuel des interventions et délai moyen de résolution</template>
                    <template #subtitle>Statut et temps de résolution sur la période</template>
                    <template #content>
                        <div class="h-96">
                            <Chart type="bar" :data="monthlyVolumeChartData" :options="comboChartOptions" class="h-full" :borderRadius="4" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="sparePartsChartData.labels.length > 0" class="col-span-12">

                <Card>
                    <template #title>Mouvements des Pièces Détachées</template>
                    <template #subtitle>Entrées et sorties sur la période sélectionnée</template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="line" :data="sparePartsChartData" :options="lineChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="failuresChartData.labels.length > 0" class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>Pannes par type de défaut</template>
                    <template #subtitle>Répartition des causes de défauts</template>
                    <template #content>
                        <div class="flex justify-center h-80">
                            <Chart type="doughnut" :data="failuresChartData" :options="{ maintainAspectRatio: false }" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="interventionsChartData.datasets[0]?.data.length > 0" class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>Interventions par type</template>
                    <template #subtitle>Total des actions menées : {{ interventionsChartData.datasets[0]?.data.reduce((a, b) => a + b, 0) ?? 0 }}</template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="bar" :data="interventionsChartData" :options="interventionsChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="tasksDistributionChartData.labels.length > 0" class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>
                        <div class="flex justify-between items-center">
                            <span>Répartition des Tâches</span>
                            <Dropdown
                                v-model="tasksChartType"
                                :options="tasksChartFilterOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Filtrer par" class="w-1/2 md:w-auto" />
                        </div>
                    </template>
                    <template #subtitle>Vue par {{ tasksChartType === 'status' ? 'statut' : 'priorité' }}</template>
                    <template #content>
                        <div class="flex justify-center h-80">
                            <Chart type="doughnut" :data="tasksDistributionChartData" :options="{ maintainAspectRatio: false }" />
                        </div>
                    </template>
                </Card>
            </div>

        </div>
    </app-layout>
</template>

<style scoped>
/* Les styles spécifiques pour la mise en page vont ici si nécessaire */
</style>
