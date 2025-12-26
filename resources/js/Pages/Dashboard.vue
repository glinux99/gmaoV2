<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
// Assurez-vous d'importer les composants PrimeVue nécessaires
import Card from 'primevue/card';
import Chart from 'primevue/chart';

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

const { t } = useI18n();

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
            label: t('dashboard.users'),
            value: props.usersCount ?? 0,
            metric: data?.users?.metric ?? '0%',
            icon: 'pi pi-users',
            chartData: formatSparklineData(data?.users?.chartData),
            changeColor: (data?.users?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
        {
            label: t('dashboard.active_tasks'),
            value: props.activeTasksCount ?? 0,
            metric: data?.activeTasks?.metric ?? '0%',
            icon: 'pi pi-check-square',
            chartData: formatSparklineData(data?.activeTasks?.chartData),
            changeColor: (data?.activeTasks?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
        {
            label: t('dashboard.time_spent_h'),
            value: data?.timeSpent?.value ?? '0h',
            metric: data?.timeSpent?.metric ?? '0%',
            icon: 'pi pi-hourglass',
            chartData: formatSparklineData(data?.timeSpent?.chartData),
            changeColor: (data?.timeSpent?.metric?.startsWith('-') ? 'text-red-500' : 'text-green-500'),
        },
        {
            label: t('dashboard.avg_intervention_time'),
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
    { label: t('dashboard.by_status'), value: 'status' },
    { label: t('dashboard.by_priority'), value: 'priority' }
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
            label: t('dashboard.incoming_parts'),
            data: props.sparePartsMovement?.entries ?? [],
            fill: false,
            borderColor: '#42A5F5',
            tension: 0.4
        },
        {
            label: t('dashboard.outgoing_parts'),
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
            label: t('dashboard.interventions_count'),
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
                label: t('dashboard.stopped'),
                backgroundColor: '#EF4444',
                data: dataSet?.stopped || [],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: t('dashboard.degraded'),
                backgroundColor: '#FBBF24',
                data: dataSet?.degraded || [],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: t('dashboard.improvement'),
                backgroundColor: '#3B82F6',
                data: dataSet?.improvement || [],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'line',
                label: t('dashboard.avg_resolution_time'),
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
                text: t('dashboard.interventions_count'),
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
                text: t('dashboard.delay_hours'),
                color: '#EF4444'
            }
        }
    }
});
</script>
<template>
    <app-layout>
        <div class="grid grid-cols-12 gap-6">

            <h3 class="col-span-12 text-xl font-semibold mt-2">💸 {{ t('dashboard.financial_tracking') }}</h3>

            <div v-if="props.budgetTotal !== undefined" class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>{{ t('dashboard.total_budget') }}</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.budgetTotal?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) ?? '0 €' }}</div>
                        <i class="pi pi-wrench text-5xl text-orange-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <div v-if="props.depensesPrestation !== undefined" class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>{{ t('dashboard.service_expenses') }}</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.depensesPrestation?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) ?? '0 €' }}</div>
                        <i class="pi pi-briefcase text-5xl text-green-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <div v-if="props.expensesTotal !== undefined" class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>{{ t('dashboard.total_validated_expenses') }}</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.expensesTotal?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) ?? '0 €' }}</div>
                        <i class="pi pi-exclamation-triangle text-5xl text-red-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <hr class="col-span-12" />

            <h3 class="col-span-12 text-xl font-semibold mt-2">📊 {{ t('dashboard.key_metrics_overview') }}</h3>
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
                            <span class="text-gray-500 ml-2">{{ t('dashboard.from_last_period') }}</span>
                        </div>
                    </template>
                </Card>
            </div>

            <hr class="col-span-12" />

            <h3 class="col-span-12 text-xl font-semibold mt-2">📈 {{ t('dashboard.detailed_visualizations') }}</h3>

            <div v-if="monthlyVolumeChartData.labels.length > 0" class="col-span-12">
                <Card>
                    <template #title>{{ t('dashboard.monthly_intervention_volume_title') }}</template>
                    <template #subtitle>{{ t('dashboard.monthly_intervention_volume_subtitle') }}</template>
                    <template #content>
                        <div class="h-96">
                            <Chart type="bar" :data="monthlyVolumeChartData" :options="comboChartOptions" class="h-full" :borderRadius="4" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="sparePartsChartData.labels.length > 0" class="col-span-12">

                <Card>
                    <template #title>{{ t('dashboard.spare_parts_movement_title') }}</template>
                    <template #subtitle>{{ t('dashboard.spare_parts_movement_subtitle') }}</template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="line" :data="sparePartsChartData" :options="lineChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="failuresChartData.labels.length > 0" class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>{{ t('dashboard.failures_by_type_title') }}</template>
                    <template #subtitle>{{ t('dashboard.failures_by_type_subtitle') }}</template>
                    <template #content>
                        <div class="flex justify-center h-80">
                            <Chart type="doughnut" :data="failuresChartData" :options="{ maintainAspectRatio: false }" />
                        </div>
                    </template>
                </Card>
            </div>

            <div v-if="interventionsChartData.datasets[0]?.data.length > 0" class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>{{ t('dashboard.interventions_by_type_title') }}</template>
                    <template #subtitle>{{ t('dashboard.interventions_by_type_subtitle', { count: interventionsChartData.datasets[0]?.data.reduce((a, b) => a + b, 0) ?? 0 }) }}</template>
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
                            <span>{{ t('dashboard.tasks_distribution_title') }}</span>
                            <Dropdown
                                v-model="tasksChartType"
                                :options="tasksChartFilterOptions"
                                optionLabel="label"
                                optionValue="value"
                                :placeholder="t('dashboard.filter_by')" class="w-1/2 md:w-auto" />
                        </div>
                    </template>
                    <template #subtitle>{{ t('dashboard.view_by', { type: tasksChartType === 'status' ? t('dashboard.status') : t('dashboard.priority') }) }}</template>
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
