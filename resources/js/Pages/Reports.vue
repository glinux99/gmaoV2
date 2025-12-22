<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// PrimeVue component imports
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';

const props = defineProps({
    reports: Object,
    filters: Object,
    dataSources: {
        type: Array,
        default: () => [
            { label: 'Tâches par Statut', value: 'tasks_by_status' },
            { label: 'Tâches par Priorité', value: 'tasks_by_priority' },
            { label: 'Interventions par Type', value: 'interventions_by_type' },
            { label: 'Pannes par Type de Défaut', value: 'failures_by_type' },
            { label: 'Mouvements des Pièces Détachées', value: 'spare_parts_movement' },
            { label: 'Volume Mensuel des Interventions', value: 'monthly_volume' },
            { label: 'KPI - Nombre d\'utilisateurs', value: 'kpi_users_count' },
            { label: 'KPI - Tâches Actives', value: 'kpi_active_tasks' },
        ]
    }
});

const toast = useToast();
const confirm = useConfirm();

const reportDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    name: '',
    description: '',
    chart_type: 'bar',
    data_source: null,
    grid_options: {
        col_span: 6,
        row_span: 1,
    },
});

const chartTypes = ref([ // Corrected variable name from 'chartType' to 'chartTypes'
    { label: 'Barres', value: 'bar', icon: 'pi pi-chart-bar' },
    { label: 'Ligne', value: 'line', icon: 'pi pi-chart-line' },
    { label: 'Circulaire (Doughnut)', value: 'doughnut', icon: 'pi pi-chart-pie' },
    { label: 'Tarte (Pie)', value: 'pie', icon: 'pi pi-chart-pie' },
    { label: 'Indicateur Clé (KPI)', value: 'kpi', icon: 'pi pi-bolt' },
]);

// Helper for displaying preview icons
const getChartIcon = (type) => {
    const chart = chartTypes.value.find(c => c.value === type);
    return chart ? chart.icon : 'pi pi-file';
};

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    reportDialog.value = true;
};

const editReport = (report) => {
    form.id = report.id;
    form.name = report.name;
    form.description = report.description;
    form.chart_type = report.chart_type;
    form.data_source = report.data_source;
    form.grid_options = {
        col_span: report.grid_options?.col_span || 6,
        row_span: report.grid_options?.row_span || 1,
    };
    editing.value = true;
    reportDialog.value = true;
};

const saveReport = () => {
    submitted.value = true;
    if (!form.name || !form.chart_type || !form.data_source) {
        toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez remplir les champs obligatoires.', life: 3000 });
        return;
    }

    const url = editing.value ? route('reports.update', form.id) : route('reports.store');
    form.submit(editing.value ? 'put' : 'post', url, {
        onSuccess: () => {
            reportDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Configuration enregistrée', life: 3000 });
        }
    });
};

const deleteReport = (report) => {
    confirm.require({
        message: `Supprimer le widget "${report.name}" ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('reports.destroy', report.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Rapport retiré du dashboard', life: 3000 }),
            });
        },
    });
};

const handleSearch = () => {
    router.get(route('reports.index'), { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <AppLayout title="Dashboard Builder">
        <Head title="Configuration Dashboard" />
        <Toast />
        <ConfirmDialog />

        <div class="p-4"> <!-- Added padding to the main container -->
            <Toolbar class="mb-5 shadow-sm border-round-xl bg-white">
                <template #start>
                    <div class="flex align-items-center gap-3">
                        <i class="pi pi-th-large text-primary text-2xl"></i>
                        <h2 class="m-0 font-bold text-900">Dashboard Builder</h2>
                    </div>
                </template>
                <template #end>
                    <div class="flex gap-3">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" placeholder="Filtrer les widgets..." @input="handleSearch" class="border-round-lg" />
                        </IconField>
                        <Button label="Ajouter un Widget" icon="pi pi-plus" class="p-button-primary border-round-lg" @click="openNew" />
                    </div>
                </template>
            </Toolbar>

            <div class="dashboard-container">
                <div
                    v-for="report in reports.data"
                    :key="report.id"
                    class="report-widget shadow-1 hover:shadow-4 transition-all transition-duration-200"
                    :style="{
                        gridColumn: `span ${report.grid_options?.col_span || 6}`,
                        gridRow: `span ${report.grid_options?.row_span || 1}`
                    }"
                >
                    <div class="widget-inner">
                        <div class="widget-header">
                            <div class="flex flex-column">
                                <span class="text-900 font-bold text-lg line-height-2">{{ report.name }}</span>
                                <small class="text-500 font-medium">{{ report.data_source }}</small>
                            </div>
                            <div class="widget-actions">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="editReport(report)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="deleteReport(report)" />
                            </div>
                        </div>

                        <div class="widget-body">
                            <div class="preview-placeholder">
                                <i :class="getChartIcon(report.chart_type)" class="preview-icon"></i>
                                <Tag :value="report.chart_type" severity="info" class="text-xs uppercase" />
                            </div>
                        </div>

                        <div class="widget-footer">
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-arrows-h text-400"></i>
                                <span class="text-sm text-600">Largeur : {{ report.grid_options?.col_span }}/12</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="reports.data.length === 0" class="col-12 flex flex-column align-items-center justify-content-center p-8 bg-white border-round-xl border-dashed border-2 border-300">
                    <i class="pi pi-chart-line text-400 text-6xl mb-4"></i>
                    <p class="text-xl text-500 font-medium">Aucun widget configuré. Commencez par en créer un !</p>
                    <Button label="Créer mon premier widget" icon="pi pi-plus" class="mt-3" @click="openNew" />
                </div>
            </div>
        </div>

        <Dialog v-model:visible="reportDialog" modal :header="editing ? 'Modifier le Widget' : 'Nouveau Widget'" :style="{ width: '50rem' }" class="p-fluid border-round-xl">
            <div class="grid mt-2">
                <div class="col-12 mb-4">
                    <label class="block font-bold mb-2">Nom du Widget</label>
                    <InputText v-model.trim="form.name" placeholder="Ex: Volume de ventes mensuel" :class="{ 'p-invalid': submitted && !form.name }" />
                    <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                </div>

                <div class="col-12 mb-4">
                    <label class="block font-bold mb-2">Description</label>
                    <Textarea v-model="form.description" rows="2" placeholder="Expliquez ce que ce widget affiche..." />
                </div>

                <div class="col-12 md:col-6 mb-4">
                    <label class="block font-bold mb-2">Type d'Affichage</label> <!-- Corrected label text -->
                    <Dropdown v-model="form.chart_type" :options="chartTypes" optionLabel="label" optionValue="value" placeholder="Type de graphique">
                        <template #option="slotProps">
                            <div class="flex align-items-center gap-2">
                                <i :class="slotProps.option.icon"></i>
                                <span>{{ slotProps.option.label }}</span>
                            </div>
                        </template>
                    </Dropdown>
                </div>

                <div class="col-12 md:col-6 mb-4">
                    <label class="block font-bold mb-2">Source de Données</label>
                    <Dropdown v-model="form.data_source" :options="props.dataSources" optionLabel="label" optionValue="value" placeholder="Données à extraire" filter />
                </div>

                <div class="col-12">
                    <div class="surface-100 p-3 border-round-lg mb-4">
                        <h4 class="m-0 mb-3 flex align-items-center gap-2">
                            <i class="pi pi-sliders-h text-primary"></i>
                            Mise en page du Dashboard
                        </h4>
                        <div class="grid">
                            <div class="col-12 md:col-6">
                                <label class="block font-semibold mb-2">Largeur (Colonnes 1-12)</label>
                                <InputNumber v-model="form.grid_options.col_span" :min="1" :max="12" showButtons buttonLayout="horizontal" incrementButtonIcon="pi pi-plus" decrementButtonIcon="pi pi-minus" />
                                <small class="text-500">12 = Pleine largeur, 6 = Moitié</small>
                            </div>
                            <div class="col-12 md:col-6">
                                <label class="block font-semibold mb-2">Importance (Hauteur)</label>
                                <InputNumber v-model="form.grid_options.row_span" :min="1" :max="4" showButtons buttonLayout="horizontal" incrementButtonIcon="pi pi-plus" decrementButtonIcon="pi pi-minus" />
                                <small class="text-500">1 = Standard, 2+ = Plus haut</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Annuler" icon="pi pi-times" @click="reportDialog = false" class="p-button-text p-button-secondary" />
                <Button :label="editing ? 'Mettre à jour le dashboard' : 'Ajouter au dashboard'" icon="pi pi-check" @click="saveReport" :loading="form.processing" class="p-button-primary" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Conteneur de la grille dynamique */
.dashboard-container { /* Corrected class name from .dashboard-grid to .dashboard-container */
    display: grid;
    grid-template-columns: repeat(12, 1fr); /* Grille de 12 colonnes */
    grid-auto-rows: minmax(180px, auto); /* Hauteur de base des lignes */
    gap: 1.5rem;
    align-items: start;
}

/* Carte de widget */
.report-widget {
    background: #ffffff;
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid #edf2f7;
    height: 100%;
}

.widget-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.widget-header {
    padding: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid #f8fafc;
}

.widget-body {
    flex-grow: 1;
    padding: 1rem;
    background: #fcfdfe;
    display: flex; /* Added display flex to widget-body */
}
</style>
