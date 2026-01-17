<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import Textarea from 'primevue/textarea';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import SelectButton from 'primevue/selectbutton';
import RadioButton from 'primevue/radiobutton';
import MultiSelect from 'primevue/multiselect';
import SplitButton from 'primevue/splitbutton';
import OverlayPanel from 'primevue/overlaypanel';
import { useConfirm } from 'primevue/useconfirm';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import { useToast } from "primevue/usetoast";
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

const props = defineProps({
    interventionRequests: Object,
    filters: Object,
    users: Array,
    teams: Array,
    connections: Array,
    regions: Array,
    zones: Array,
    statuses: Array,
    priorities: Array,
    interventionReasons: Array,
    categories: Array, // e.g., ['Réseau', 'Support Technique', 'Client']
    technicalComplexities: Array, // e.g., ['Pas complexe', 'Peu complexe', ...]
});
const REASONS_CONFIG = {
    "Dépannage Réseau Urgent": { category: "Réseau", complexity: "Moyennement complexe", priority: "Haute", min: 72, max: 72 },
    "Réparation Éclairage Public": { category: "Réseau", complexity: "Moyennement complexe", priority: "Moyenne", min: 4, max: 24 },
    "Entretien Réseau Planifié": { category: "Réseau", complexity: "Très complexe", priority: "Moyenne", min: 24, max: 72 },
    "Incident Majeur Réseau": { category: "Réseau", complexity: "Moyennement complexe", priority: "Haute", min: 4, max: 48 },
    "Support Achat MobileMoney": { category: "Support Technique", complexity: "Peu complexe", priority: "Basse", min: 1, max: 1 },
    "Support Achat Token Impossible": { category: "Support Technique", complexity: "Pas complexe", priority: "Basse", min: 0.5, max: 1 },
    "Aide Recharge (Sans clavier)": { category: "Support Technique / Client", complexity: "Pas complexe", priority: "Basse", min: 0.25, max: 1 },
    "Élagage Réseau": { category: "Réseau", complexity: "Peu complexe", priority: "Moyenne", min: 72, max: 72 },
    "Réparation Chute de Tension": { category: "Réseau", complexity: "Moyennement complexe", priority: "Moyenne", min: 4, max: 24 },
    "Coupure Individuelle (CI)": { category: "Réseau", complexity: "Peu complexe", priority: "Moyenne", min: 2, max: 4 },
    "CI Équipement Client": { category: "Réseau", complexity: "Peu complexe", priority: "Moyenne", min: 2, max: 4 },
    "CI Équipement Virunga": { category: "Réseau", complexity: "Peu complexe", priority: "Moyenne", min: 2, max: 4 },
    "CI Vol de Câble": { category: "Réseau", complexity: "Moyennement complexe", priority: "Moyenne", min: 4, max: 72 },
    "Dépannage Clavier Client": { category: "Client", complexity: "Pas complexe", priority: "Basse", min: 0.5, max: 1 },
    "Réparation Compteur Limité": { category: "Client", complexity: "Pas complexe", priority: "Basse", min: 0.5, max: 1 },
    "Rétablissement Déconnexion": { category: "Client", complexity: "Pas complexe", priority: "Basse", min: 0.5, max: 1 },
    "Déplacement Câble (Planifié)": { category: "Réseau", complexity: "Peu complexe", priority: "Moyenne", min: 8, max: 72 },
    "Déplacement Poteau (Planifié)": { category: "Réseau", complexity: "Peu complexe", priority: "Moyenne", min: 24, max: 72 },
    "Reconnexion Client": { category: "Client", complexity: "Pas complexe", priority: "Basse", min: 0.5, max: 1 },
    "Support Envoi Formulaire": { category: "Support Technique", complexity: "Pas complexe", priority: "Basse", min: 0.1, max: 0.5 },
    "Intervention Non-Classifiée": { category: "Support / Autres", complexity: "Pas complexe", priority: "Basse", min: 0.5, max: 24 }
};
const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();
const opFilters = ref();
const isModalOpen = ref(false);
const deleteDialog = ref(false);
const dt = ref();
const op = ref();
const selectedItems = ref([]);
const lastAssignable = ref({ type: null, id: null });
// const loading = ref(false);
const isImportModalOpen = ref(false);
const requester_type = ref('client');



// --- STATISTIQUES ---
const stats = computed(() => {
    const data = props.interventionRequests.data || [];
    return {
        total: props.interventionRequests.total,
        pending: data.filter(ir => ir.status === 'pending').length,
        in_progress: data.filter(ir => ir.status === 'in_progress').length,
        completed: data.filter(ir => ir.status === 'completed').length,
    };
});

// --- CONFIGURATION V11 ---
const allColumns = computed(() => [
    { field: 'title', header: t('interventions.table.reference'), sortable: true },
    { field: 'intervention_reason', header: t('interventions.table.reason'), sortable: true },
    { field: 'status', header: t('interventions.table.status'), sortable: true },
    { field: 'customer_code', header: t('interventions.table.customerCode'), sortable: false },
    { field: 'client_name', header: t('interventions.table.clientName'), sortable: false },
    { field: 'region_name', header: t('interventions.table.region'), sortable: false },
    { field: 'priority', header: t('interventions.table.priority'), sortable: true },
    { field: 'technical_complexity', header: t('interventions.table.complexity'), sortable: false },
    { field: 'scheduled_date', header: t('interventions.table.deadline'), sortable: true },
    { field: 'completed_date', header: t('interventions.table.completedAt'), sortable: false },
    { field: 'assigned_to_name', header: t('interventions.table.assignedTo'), sortable: false },
    { field: 'category', header: t('interventions.table.category'), sortable: false },
    { field: 'description', header: t('interventions.table.description'), sortable: false },
]);

const selectedColumnFields = ref(['title', 'status', 'client_name', 'priority', 'scheduled_date', 'assigned_to_name', 'intervention_reason']); // Gardez les clés ici
const displayedColumns = computed(() => allColumns.value.filter(col => selectedColumnFields.value.includes(col.field)));

// --- LOGIQUE FILTRES ---
const lazyParams = ref({
    first: props.interventionRequests.from - 1,
    rows: props.interventionRequests.per_page,
    sortField: 'created_at',
    sortOrder: -1,
    filters: {
        'global': { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
        'status': { value: props.filters?.status || null, matchMode: FilterMatchMode.EQUALS },
        'priority': { value: props.filters?.priority || null, matchMode: FilterMatchMode.EQUALS },
    }
});

const loadLazyData = (event) => {
    loading.value = true;
    const { first, rows, sortField, sortOrder, filters } = { ...lazyParams.value, ...event };

    router.get(route('interventions.index'), {
        per_page: rows,
        page: (first / rows) + 1,
        sortField: sortField,
        sortOrder: sortOrder === 1 ? 'asc' : 'desc',
        filters: {
            global: { value: filters.global.value },
            status: { value: filters.status.value },
            priority: { value: filters.priority.value },
        }
    }, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const activeFiltersCount = computed(() => {
    return Object.values(filters.value).filter(f => f.value !== null && f.value !== '').length - 1; // Exclude global
});

// --- STYLISATION DES BADGES (V11 Design) ---
const getStatusSeverity = (status) => {
    switch (status?.toLowerCase()) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        case 'in_progress': return 'info';
        default: return 'secondary';
    }
};

const getPriorityColor = (priority) => {
    switch (priority?.toLowerCase()) {
        case 'haute': return '#ef4444';
        case 'moyenne': return '#f59e0b';
        case 'basse': return '#10b981';
        default: return '#64748b';
    }
};

const getComplexitySeverity = (complexity) => {
    switch (complexity) {
        case 'Pas complexe': return 'success';
        case 'Peu complexe': return 'info';
        case 'Moyennement complexe': return 'warning';
        case 'Très complexe': return 'danger';
        default: return 'secondary';
    }
};

// --- FORMULAIRE & ACTIONS ---
const form = useForm({
    id: null, title: '', description: '', status: 'pending', // Core fields
    requested_by_user_id: null, requested_by_connection_id: null, // Requester
    assignable_type: null, assignable_id: null, // Assignable
    region_id: null, zone_id: null, // Location
    intervention_reason: null, category: null, technical_complexity: null, priority: null, // Classification
    min_time_hours: null, max_time_hours: null, // Time estimation
    scheduled_date: null, completed_date: null, // Dates
    comments: null, resolution_notes: null, // Notes
    gps_latitude: null, gps_longitude: null, // GPS
    is_validated: false, // Validation
});
const editing =ref(false);

const importForm = useForm({
    file: null,
});


const openCreate = () => {
    form.reset();
    form.title = `PLT-${Math.floor(Date.now() / 1000)}`;
    form.scheduled_date = new Date().toISOString().slice(0, 10); // Format YYYY-MM-DD
     form.is_validated=true;
        editing.value = false;
    isModalOpen.value = true;
};

const openImportModal = () => {
    importForm.reset();
    isImportModalOpen.value = true;
};

const openEdit = (data) => {
    form.clearErrors();
    form.defaults({
        ...data,
        scheduled_date: data.scheduled_date ? new Date(data.scheduled_date).toISOString().slice(0, 10) : null,
        completed_date: data.completed_date ? new Date(data.completed_date).toISOString().slice(0, 10) : null,
    }).reset();
       editing.value = true;
    form.is_validated = form.is_validated ? true : false;
    isModalOpen.value = true;
};

const connectionsList = computed(() => {
    return props.connections.map(conn => ({ ...conn, search_label: `${conn.first_name} ${conn.last_name} (${conn.customer_code})` }));
});


const formattedList = computed(() => (props.interventionRequests?.data || []).map(ir => ({
    ...ir,
    client_name: ir.requested_by_connection ? `${ir.requested_by_connection.first_name} ${ir.requested_by_connection.last_name}` : t('interventions.notApplicable'),
    customer_code: ir.requested_by_connection ? ir.requested_by_connection.customer_code : '-',
    assigned_to_name: ir.assignable ? ir.assignable.name : t('interventions.notAssigned'),
    region_name: ir.region?.designation || '-',
})));

const assignableTypes = computed(() => [{ label: t('interventions.assignableTypes.technician'), value: 'App\\Models\\User' }, { label: t('interventions.assignableTypes.team'), value: 'App\\Models\\Team' }]);
const getAssignables = (type) => type === 'App\\Models\\User' ? props.users : (type === 'App\\Models\\Team' ? props.teams : []);
const loading = ref(false);
const labelDialog = ref(false);

const submit = () => {
    console.log("form.id");
    console.log(form.id);

    const url = form.id ? route('interventions.update', form.id) : route('interventions.store');


     loading.value = true;
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Région enregistrée', life: 3000 });
            isModalOpen.value = false ;
        }, onFinish: () => { loading.value = false; }
    });
};

const submitImport = () => {
    importForm.post(route('interventions.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Importation démarrée avec succès.', life: 3000 });
            router.reload({ only: ['interventionRequests'] }); // Recharger les données de la table
        },
        onError: (errors) => {
            const errorDetail = errors.file || 'Une erreur est survenue lors de l\'importation.';
            toast.add({ severity: 'error', summary: 'Erreur d\'importation', detail: errorDetail, life: 5000 });
        }
    });
};
watch(() => form.requested_by_connection_id, (newVal) => {
    if (newVal) {
        const connection = props.connections.find(conn => conn.id === newVal);
        if (connection) {
            form.region_id = connection.region_id;
            form.zone_id = connection.zone_id;
        }
    }
});
watch(() => form.intervention_reason, (newReason) => {
    if (newReason && REASONS_CONFIG[newReason]) {
        const config = REASONS_CONFIG[newReason];

        // Remplissage automatique
        form.category = config.category;
        form.technical_complexity = config.complexity;
        form.priority = config.priority;
        form.min_time_hours = config.min;
        form.max_time_hours = config.max;

        toast.add({
            severity: 'info',
            summary: 'Auto-configuration',
            detail: `Paramètres appliqués pour : ${newReason}`,
            life: 2000
        });
    }
});
const interventionStatuses = ref( ['pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'Non validé']);

const exportCSV = () => {
    dt.value.exportCSV();
};

const onPage = (event) => {
    loadLazyData(event);
};
</script>

<template>
    <AppLayout>
        <Head :title="t('interventions.title')" />
        <Toast />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-map-marker text-2xl text-white"></i>
                    </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase" v-html="t('interventions.headTitle')">
                    </h1>
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('interventions.subtitle') }}</p>
                </div>
                </div>
                <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button :label="t('common.import')" icon="pi pi-upload" severity="secondary" @click="openImportModal" class="rounded-lg" />                    <Button :label="t('interventions.addNew')" icon="pi pi-plus"

                            class=" shadow-lg shadow-primary-200" @click="openCreate" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-box text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('interventions.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 flex items-center justify-center"><i class="pi pi-clock text-2xl text-amber-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.pending }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('interventions.stats.pending') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-spin pi-spinner text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.in_progress }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('interventions.stats.in_progress') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-check-circle text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.completed }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('interventions.stats.completed') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="formattedList" v-model:selection="selectedItems" dataKey="id" ref="dt"
                           :totalRecords="interventionRequests.total" :loading="loading" lazy
                           v-model:filters="lazyParams.filters" filterDisplay="menu" :globalFilterFields="['title', 'client_name', 'status', 'priority', 'region_name']"
                           :rows="interventionRequests.per_page" paginator class="p-datatable-sm quantum-table" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                           @page="onPage($event)" @sort="onPage($event)" @filter="onPage($event)"
                           :currentPageReportTemplate="t('interventions.table.report')">
                    <template #header>

                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="lazyParams.filters['global'].value" @keydown.enter="onPage($event)" :placeholder="t('interventions.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Dropdown v-model="lazyParams.rows" :options="[10, 20, 50, 100]" @change="loadLazyData" class="p-inputtext-sm" />
                                <Button v-if="selectedItems.length" icon="pi pi-trash" :label="t('interventions.deleteSelected')" class="p-button-danger p-button-text p-button-sm animate-fadein" @click="deleteDialog = true" />
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="lazyParams.filters.global.value = ''" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="'Colonnes'" />
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="p-8 text-center text-slate-500">
                            {{ t('interventions.table.noRecords') }}
                        </div>
                    </template>
                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable" :pt="{ header: { class: 'whitespace-nowrap' } }" :filterField="col.field">
                        <template #body="{ data, field }">
                            <span v-if="field === 'title'" class="font-bold text-slate-800 tracking-tight">{{ data.title }}</span>

                            <Tag v-else-if="field === 'status'" :value="data.status"
                                 :severity="getStatusSeverity(data.status)" class="uppercase text-[9px] px-2" />

                            <div v-else-if="field === 'priority'" class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: getPriorityColor(data.priority) }"></div>
                                <span class="text-xs font-medium">{{ data.priority }}</span>
                            </div>

                            <Tag v-else-if="field === 'technical_complexity'" :value="data.technical_complexity"
                                 :severity="getComplexitySeverity(data.technical_complexity)" class="text-[9px] px-2" />

                            <span v-else-if="field === 'scheduled_date' || field === 'completed_date'" class="text-slate-600 text-sm font-mono">{{ data[field] ? new Date(data[field]).toLocaleDateString() : '-' }}</span>

                            <div v-else-if="field === 'assigned_to_name'" class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-[10px] font-bold text-primary-600">
                                    {{ data.assigned_to_name.charAt(0) }}
                                </div>
                                <span class="text-xs">{{ data.assigned_to_name }}</span>
                            </div>

                            <span v-else class="text-slate-600 text-sm truncate" :title="data[field]">{{ data[field] }}</span>
                        </template>

                        <template #filter="{ filterModel, filterCallback }" v-if="['title', 'client_name', 'region_name'].includes(col.field)">
                            <InputText v-model="filterModel.constraints[0].value" type="text" @input="filterCallback()" class="p-column-filter" :placeholder="`Filtrer par ${col.header}`" />
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'status'">
                            <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="props.statuses" placeholder="Statut" class="p-column-filter" showClear />
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'priority'">
                            <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="props.priorities" placeholder="Priorité" class="p-column-filter" showClear />
                        </template>

                    </Column>

                    <Column :header="t('interventions.table.actions')" headerStyle="width: 5rem">
                        <template #body="{ data }">
                            <Button icon="pi pi-arrow-right" class="p-button-rounded p-button-text p-button-secondary"
                                    @click="openEdit(data)" v-tooltip.left="t('interventions.tooltips.manage')" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <OverlayPanel ref="op" class="p-4">
            <div class="font-semibold mb-3">{{ t('common.columnSelector.title') }}</div>
            <MultiSelect
                v-model="selectedColumnFields"
                :options="allColumns"
                optionLabel="header"
                optionValue="field"
                display="chip"
                :placeholder="t('common.columnSelector.placeholder')"
                class="w-full max-w-xs"  />
        </OverlayPanel>

        <Dialog v-model:visible="isModalOpen" modal :header="false" :closable="false"
                class="quantum-dialog w-full max-w-6xl" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">

            <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center shadow-lg relative z-50">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-blue-500/20 rounded-lg border border-blue-500/30">
                        <i class="pi pi-shield text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-sm font-black uppercase tracking-widest text-white leading-none">{{ form.id ? t('interventions.dialog.editTitle') : t('interventions.dialog.createTitle') }}</h2>
                        <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1 italic">{{ t('interventions.dialog.gmaoConsole') }}</span>
                    </div>
                </div>


                   <div class="flex items-center gap-6">
                <div class="flex flex-col items-end mr-4">
                    <span class="text-[9px] font-bold text-slate-400 uppercase mb-1">Status de l'intervention</span>
                    <SelectButton v-model="form.status" :options="interventionStatuses" class="p-selectbutton-sm custom-dark-priority" />
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isModalOpen = false" class="text-white hover:bg-white/10" />
            </div>
            </div>


            <div class="p-2">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4">
                    <div class="md:col-span-4 space-y-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 h-full">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">{{ t('interventions.dialog.origin') }}</label>
                            <div class="flex gap-4">
                                <div class="flex-1 p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center gap-3"
                                     :class="requester_type === 'client' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white'"
                                     @click="requester_type = 'client'">
                                    <i class="pi pi-users"></i>
                                    <span class="text-xs font-bold">{{ t('interventions.dialog.externalClient') }}</span>
                                </div>
                                <div class="flex-1 p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center gap-3"
                                     :class="requester_type === 'agent' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white'"
                                     @click="requester_type = 'agent'">
                                    <i class="pi pi-user"></i>
                                    <span class="text-xs font-bold">{{ t('interventions.dialog.internalAgent') }}</span>
                                </div>
                            </div>

                            <div class="mt-4">

                                <Dropdown v-model="form.requested_by_connection_id" :options="connectionsList"
                                          optionLabel="search_label" optionValue="id" filter :placeholder="t('interventions.dialog.searchPlaceholder')"
                                          class="w-full quantum-input" v-if="requester_type === 'client'" />
                                <Dropdown v-model="form.requested_by_user_id" :options="props.users"
                                          optionLabel="name" optionValue="id" filter class="w-full quantum-input" v-else />
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-4 space-y-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">{{ t('interventions.dialog.subjectDescription') }}</label>
                            <InputText v-model="form.title" class="w-full font-bold mb-2" readonly :placeholder="t('interventions.dialog.autoReference')" />
                            <Textarea v-model="form.description" rows="4"
                                      class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-primary-500 text-sm"
                                      :placeholder="t('interventions.dialog.problemPlaceholder')"></Textarea>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">{{  t('interventions.dialog.classification') }}</label>
                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">{{  t('interventions.dialog.interventionReason') }}</label>
                                    <Dropdown
    v-model="form.intervention_reason"
    :options="Object.keys(REASONS_CONFIG)"
    placeholder="Sélectionner une raison"
    class="w-full"
    filter
/></div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[9px] font-bold uppercase text-slate-500">{{  t('interventions.dialog.category') }}</label>
                                        <Dropdown v-model="form.category" :options="props.categories" class="w-full" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[9px] font-bold uppercase text-slate-500">{{  t('interventions.dialog.priority') }}</label>
                                        <Dropdown v-model="form.priority" :options="props.priorities" class="w-full" />
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">Complexité Technique</label>
                                    <Dropdown v-model="form.technical_complexity" :options="props.technicalComplexities" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-4 space-y-4">
                        <div class="p-4 bg-slate-900 rounded-xl text-white shadow-xl">
                            <h4 class="text-xs font-black uppercase tracking-widest mb-4 opacity-70">{{ t('interventions.dialog.assignmentDeadline') }}</h4>
                            <div class="space-y-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase">Type d'entité</label>
                                    <Dropdown v-model="form.assignable_type" :options="assignableTypes" optionLabel="label" optionValue="value" class="w-full bg-white/10 border-none text-white rounded-lg" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase">Assigné à</label>
                                    <Dropdown v-model="form.assignable_id" :options="getAssignables(form.assignable_type)" :disabled="!form.assignable_type"
                                              optionLabel="name" optionValue="id" filter class="w-full bg-white/10 border-none text-white rounded-lg" />
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">Planification & Suivi</label>
                            <div class="space-y-4">
                             <div class="grid grid-cols-2 gap-x-4 mb-4 w-full">

    <div class="flex flex-col gap-1.5 min-w-0"> <label class="text-[9px] font-bold uppercase text-slate-500 tracking-wider truncate">
            Temps Min (h)
        </label>
        <InputNumber v-model="form.min_time_hours" class="w-full" inputClass="w-full" />
    </div>

    <div class="flex flex-col gap-1.5 min-w-0">
        <label class="text-[9px] font-bold uppercase text-slate-500 tracking-wider truncate">
            Temps Max (h)
        </label>
        <InputNumber v-model="form.max_time_hours" class="w-full" inputClass="w-full" />
    </div>

</div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase">{{ t('interventions.dialog.deadline') }}</label>
                                    <Calendar v-model="form.scheduled_date" class="w-full" dateFormat="yy-mm-dd" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                             <div class="p-3 bg-slate-100 rounded-xl text-center">
                                <span class="block text-[8px] font-black uppercase text-slate-400">{{ t('interventions.dialog.region') }}</span>
                                <span class="text-[10px] font-bold">{{ props.regions.find(r => r.id === form.region_id)?.designation || t('interventions.notApplicable') }}</span>
                             </div>
                             <div class="p-3 bg-slate-100 rounded-xl text-center">
                                <span class="block text-[8px] font-black uppercase text-slate-400">{{ t('interventions.dialog.zone') }}</span>
                                <span class="text-[10px] font-bold">{{ props.zones.find(z => z.id === form.zone_id)?.title || t('interventions.notApplicable') }}</span>
                             </div>
                        </div>
                    </div>
 <div class="md:col-span-12 flex items-center gap-2 mt-4">
 <Checkbox v-model="form.is_validated" :binary="true" inputId="is_validated" />
 <label for="is_validated" class="text-sm font-medium text-slate-700">
 {{ t('interventions.dialog.validateIntervention') }}
 </label>
 </div>


                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-2">
                    <Button :label="t('interventions.dialog.cancel')" icon="pi pi-times" class="p-button-text p-button-secondary" @click="isModalOpen = false" />
                    <Button :label="t('interventions.dialog.save')" icon="pi pi-save"
                            class="px-6 shadow-lg shadow-primary-100" @click="submit" :loading="form.processing" />
                </div>
            </template>
        </Dialog>

        <!-- Boîte de dialogue d'importation -->
        <Dialog v-model:visible="isImportModalOpen" modal :header="t('connections.importDialog.title')" :style="{ width: '450px' }">
            <div class="flex flex-col gap-6 p-4">
                <div class="flex items-center justify-center border-2 border-dashed border-slate-300 rounded-lg p-8">
                    <div class="text-center">
                        <i class="pi pi-upload text-4xl text-slate-400 mb-3"></i>
                        <p class="font-semibold text-slate-700">{{ t('connections.importDialog.chooseFile') }}</p>
                        <input type="file" @input="importForm.file = $event.target.files[0]" class="mt-4" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" />
                        <small class="p-error" v-if="importForm.errors.file">{{ importForm.errors.file }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    :label="t('common.cancel')"
                    icon="pi pi-times"
                    text
                    @click="isImportModalOpen = false"
                />
                <Button
                    :label="t('connections.importDialog.startImport')"
                    icon="pi pi-check"
                    @click="submitImport"
                    :loading="importForm.processing"
                    :disabled="!importForm.file"
                />
            </template>
        </Dialog>


    </AppLayout>
</template>

<style scoped>
/* STYLE V11 CUSTOM TOKENS */
.p-button-primary {
    background: #4f46e5;
    border: none;
    color: white;
    font-weight: 700;
    border-radius: 12px;
}

.card-v11 :deep(.p-datatable-thead > tr > th) {
    background: #fdfdfd;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.card-v11 :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s;
}

.card-v11 :deep(.p-datatable-tbody > tr:hover) {
    background: #f8faff !important;
}

.quantum-input :deep(.p-inputtext) {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

/* Transitions */
.animate-fadein {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
