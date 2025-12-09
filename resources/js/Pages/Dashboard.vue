<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, watch, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
// Assurez-vous d'importer les composants PrimeVue nécessaires
import Card from 'primevue/card';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
    users: Number,
    roles: Number,
    activeTasks: Number,
    timeSpent: String,
    averageInterventionTime: String,
    permissions: Number,
    filters: Object,
    sparePartsMovement: Object, // Données pour le graphique des pièces détachées
    tasksByStatus: Object, // Données pour le graphique des tâches par statut
    tasksByPriority: Object, // Données pour le graphique des tâches par priorité

    // --- NOUVELLES PROPS (pour intégrer les données de l'image) ---
    depensesPiecesDetachees: Number,
    depensesPrestation: Number,
    perteEstimee: Number,
    monthlyVolumeData: Object,
    failuresByType: Object,
    interventionsByType: Object,
    // -----------------------------------------------------------------
});

// --- Filtre par date ---
const dateRange = ref();
const filterType = ref(props.filters?.filterType || 'this_month'); // Valeur par défaut ou depuis les props

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
            const lastWeekStart = new Date(today);
            lastWeekStart.setDate(today.getDate() - today.getDay() - 6);
            startDate = lastWeekStart;

            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate() - today.getDay() - 0);
            endDate = lastWeekEnd;
            break;
        default: // 'custom'
            return; // Ne rien faire, laisse le calendrier gérer
    }

    if (startDate && endDate) {
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(23, 59, 59, 999);
        dateRange.value = [startDate, endDate];
    }
};

onMounted(() => {
    // Initialise la plage de dates si elle n'est pas déjà définie par Inertia
    if (!dateRange.value) {
        updateDateRange();
    }
});

// Écouteur pour le type de filtre
watch(filterType, () => {
    if (filterType.value !== 'custom') {
        updateDateRange();
    }
});


// Surveiller les changements de l'intervalle de dates et recharger les données
watch(dateRange, (newDates) => {
    if (newDates && newDates[0] && newDates[1]) {
        router.get(route('dashboard'), {
            start_date: newDates[0].toISOString().split('T')[0],
            end_date: newDates[1].toISOString().split('T')[0],
        }, { preserveState: true, replace: true });
    }
}, {
    deep: true // pour surveiller les changements à l'intérieur du tableau
});

// --- Fonctions de préparation des données pour les graphiques (Charts) ---

// 1. Fonction pour générer des données de graphique de barre simples (Sparkline)
const generateSparklineData = (color) => {
    // Données simulées pour la forme du sparkline
    const data = [10, 5, 20, 15, 25, 10, 30, 20, 12, 18, 22];
    return {
        labels: data.map((_, i) => i + 1),
        datasets: [
            {
                data: data,
                backgroundColor: color,
                borderRadius: 4,
            }
        ]
    };
};

// Options pour un graphique Sparkline (cache les axes et la légende)
const sparklineOptions = {
    plugins: { legend: { display: false } },
    maintainAspectRatio: false,
    scales: {
        x: { display: false },
        y: { display: false }
    }
};

// 2. Préparation des données pour les quatre cartes principales (Sparklines)
const sparklineItems = ref([
    {
        label: 'Earnings',
        value: `$${props.users ?? 0}`,
        metric: '30.6%',
        icon: 'pi pi-wallet',
        chartData: generateSparklineData('rgba(59, 130, 246, 0.7)'), // Bleu
        changeColor: 'text-green-500',
    },
    {
        label: 'Active Tasks',
        value: props.activeTasks ?? 0,
        metric: '30.6%',
        icon: 'pi pi-check-square',
        chartData: generateSparklineData('rgba(245, 158, 11, 0.7)'), // Orange
        changeColor: 'text-red-500',
    },
    {
        label: 'Time Spent (hrs)',
        value: props.timeSpent ?? '0h',
        metric: '30.6%',
        icon: 'pi pi-hourglass',
        chartData: generateSparklineData('rgba(16, 185, 129, 0.7)'), // Vert
        changeColor: 'text-green-500',
    },
    {
        label: 'Avg Intervention Time',
        value: props.averageInterventionTime ?? '0s',
        metric: '30.6%',
        icon: 'pi pi-clock',
        chartData: generateSparklineData('rgba(239, 68, 68, 0.7)'), // Rouge
        changeColor: 'text-red-500',
    },
]);

// 3. Préparation des données pour le graphique de répartition des tâches
const tasksChartType = ref('status'); // 'status' ou 'priority'
const tasksChartFilterOptions = ref([
    { label: 'Par Statut', value: 'status' },
    { label: 'Par Priorité', value: 'priority' }
]);

const tasksDistributionChartData = computed(() => {
    const dataSet = tasksChartType.value === 'status' ? props.tasksByStatus : props.tasksByPriority;
    const defaultData = {
        labels: ['Label A', 'Label B', 'Label C'],
        datasets: [{
            data: [30, 50, 20],
            backgroundColor: ['#42A5F5', '#FFA726', '#66BB6A', '#EF5350', '#AB47BC'],
            hoverBackgroundColor: ['#64B5F6', '#FFB74D', '#81C784', '#E57373', '#BA68C8']
        }]
    };

    if (!dataSet || !dataSet.labels || !dataSet.data) {
        return defaultData;
    }

    return {
        labels: dataSet.labels,
        datasets: [{ ...defaultData.datasets[0], data: dataSet.data }]
    };
});
const permissionsChartData = ref({
    labels: ['Read', 'Write', 'Delete'],
    datasets: [
        {
            label: 'Permissions by Type',
            data: [props.permissions ?? 100, 50, 20],
            backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)'],
            borderColor: ['rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(255, 206, 86)'],
            borderWidth: 1
        }
    ]
});

const barChartOptions = ref({
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
});

// 4. Préparation des données pour le graphique des mouvements de pièces détachées
const sparePartsChartData = ref({
    labels: props.sparePartsMovement?.labels ?? ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet'],
    datasets: [
        {
            label: 'Pièces entrantes',
            data: props.sparePartsMovement?.entries ?? [65, 59, 80, 81, 56, 55, 40],
            fill: false,
            borderColor: '#42A5F5', // Bleu
            tension: 0.4
        },
        {
            label: 'Pièces sortantes',
            data: props.sparePartsMovement?.exits ?? [28, 48, 40, 19, 86, 27, 90],
            fill: false,
            borderColor: '#FFA726', // Orange
            tension: 0.4
        }
    ]
});

const lineChartOptions = ref({
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: '#495057' }
        }
    },
});


// -----------------------------------------------------------------
// --- NOUVELLE LOGIQUE POUR L'IMAGE AJOUTÉE ---
// -----------------------------------------------------------------

// Graphique 1 : Pannes par type de défaut (Doughnut)
const failuresChartData = computed(() => {
    const dataSet = props.failuresByType;
    const defaultColors = ['#059669', '#EF4444', '#F97316', '#3B82F6', '#6366F1']; // Vert, Rouge, Orange, Bleu, Indigo

    if (!dataSet || !dataSet.labels || !dataSet.data) {
        return {
            labels: ['Usure', 'Dommage', 'Surcharge', 'Électronique'],
            datasets: [{
                data: [40, 30, 20, 10],
                backgroundColor: defaultColors,
            }]
        };
    }

    return {
        labels: dataSet.labels,
        datasets: [{
            data: dataSet.data,
            backgroundColor: defaultColors.slice(0, dataSet.labels.length),
        }]
    };
});

// Graphique 2 : Interventions par type (Barres)
const interventionsChartData = computed(() => {
    const dataSet = props.interventionsByType;
    const defaultData = {
        labels: ['Remplacement', 'Réglage', 'Entretien', 'Dépannage', 'Appel Hotline', 'Analyse', 'Amélioration'],
        data: [40, 184, 72, 121, 192, 33, 161]
    };

    if (!dataSet || !dataSet.labels || !dataSet.data) {
        return {
            labels: defaultData.labels,
            datasets: [{
                label: 'Nombre d\'Interventions',
                data: defaultData.data,
                backgroundColor: 'rgba(59, 130, 246, 0.8)', // Bleu cohérent
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 4
            }]
        };
    }

    return {
        labels: dataSet.labels,
        datasets: [{
            label: 'Nombre d\'Interventions',
            data: dataSet.data,
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1,
            borderRadius: 4
        }]
    };
});

// Graphique 3 : Volume mensuel (Graphique Combiné Barres empilées + Ligne)
const monthlyVolumeChartData = computed(() => {
    const dataSet = props.monthlyVolumeData;

    // Définir ici les options de style pour les datasets
    const defaultData = {
        labels: ['11/22', '12/22', '01/23', '02/23', '03/23', '04/23', '05/23', '06/23', '07/23', '08/23', '09/23', '10/23'],
        datasets: [
            // Données pour les barres empilées
            {
                type: 'bar',
                label: 'Stoppée',
                backgroundColor: '#EF4444', // Rouge
                data: dataSet?.stopped || [6, 5, 5, 5, 4, 10, 5, 8, 3, 8, 5, 6],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: 'Dégradée',
                backgroundColor: '#FBBF24', // Jaune/Orange
                data: dataSet?.degraded || [11, 13, 12, 15, 15, 14, 15, 12, 4, 7, 14, 5],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: 'Amélioration',
                backgroundColor: '#3B82F6', // Bleu
                data: dataSet?.improvement || [10, 13, 8, 13, 13, 3, 3, 15, 7, 7, 8, 7],
                stack: 'Stack 0',
                yAxisID: 'y'
            },
            // Données pour la ligne (Délai de résolution)
            {
                type: 'line',
                label: 'Délai de résolution moyen',
                borderColor: '#EF4444', // Ligne rouge
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                data: dataSet?.resolutionTime || [23, 23, 22, 33, 33, 42, 42, 42, 27, 36, 33, 33], // en heures
                yAxisID: 'y1'
            }
        ]
    };

    return {
        labels: defaultData.labels,
        datasets: defaultData.datasets
    };
});

// Options du graphique combiné (Gestion des deux axes Y)
const comboChartOptions = ref({
    maintainAspectRatio: false,
    elements: {
        bar: {
            borderWidth: 1,                 // 1.5 pixels d'épaisseur
            borderColor: '#e9ecf2',           // Couleur de la bordure (Gris foncé/noir)
            borderRadius: 7                // Facultatif : pour arrondir les coins si ce n'est pas déjà fait par la prop du composant
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
                stepSize: 5 // Exemple d'incrément
            },
            title: {
                display: true,
                text: 'Nombre d\'interventions',
                color: '#6B7280'
            }
        },
        y1: { // Le deuxième axe Y pour la ligne (Délai de résolution)
            type: 'linear',
            display: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: {
                color: '#EF4444', // Couleur de l'axe pour correspondre à la ligne
                callback: function(value) {
                    return value + 'h'; // Afficher en heures
                },
                stepSize: 5
            },
            title: {
                display: true,
                text: 'Délai (Heures)',
                color: '#EF4444'
            }
        }
    }
});

// -----------------------------------------------------------------

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

            <div class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>Dépenses de pièces détachées</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.depensesPiecesDetachees?.toLocaleString() ?? '0' }} €</div>
                        <i class="pi pi-wrench text-5xl text-orange-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <div class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>Dépenses de prestation</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.depensesPrestation?.toLocaleString() ?? '0' }} €</div>
                        <i class="pi pi-briefcase text-5xl text-green-500 opacity-20 absolute right-4 bottom-4"></i>
                        </template>
                </Card>
            </div>

            <div class="col-span-12 sm:col-span-4">
                <Card class="h-full">
                    <template #title>Perte estimée</template>
                    <template #content>
                        <div class="text-3xl font-bold text-gray-800">{{ props.perteEstimee?.toLocaleString() ?? '0' }} €</div>
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
                                <Chart type="bar" :data="item.chartData" :options="sparklineOptions" class="h-full w-full" />
                            </div>
                        </div>

                        <div class="flex items-center text-sm">
                            <i :class="item.changeColor === 'text-green-500' ? 'pi pi-arrow-up-right text-xs mr-1' : 'pi pi-arrow-down-right text-xs mr-1'"></i>
                            <span :class="item.changeColor" class="font-medium">{{ item.metric }}</span>
                            <span class="text-gray-500 ml-2">from last month</span>
                        </div>
                    </template>
                </Card>
            </div>

            <hr class="col-span-12" />

            <h3 class="col-span-12 text-xl font-semibold mt-2">📈 Detailed Visualizations</h3>

            <div class="col-span-12">
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

            <div class="col-span-12">
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

            <div class="col-span-12 lg:col-span-6">
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

            <div class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>Interventions par type</template>
                    <template #subtitle>Total des actions menées : {{ interventionsChartData.datasets[0]?.data.reduce((a, b) => a + b, 0) ?? 0 }}</template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="bar" :data="interventionsChartData" :options="barChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="col-span-12 lg:col-span-6">
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

            <div class="col-span-12 lg:col-span-6">
                <Card>
                    <template #title>Permission Types</template>
                    <template #subtitle>Total Permissions: {{ props.permissions ?? 170 }}</template>
                    <template #content>
                        <div class="h-80">
                            <Chart type="bar" :data="permissionsChartData" :options="barChartOptions" class="h-full" />
                        </div>
                    </template>
                </Card>
            </div>

        </div>
    </app-layout>
</template>

<style scoped>
/* Les styles spécifiques pour la mise en page vont ici si nécessaire */
/* J'ai retiré lang="scss" ici pour corriger l'erreur de compilation */
</style>
