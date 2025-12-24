<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import MultiSelect from 'primevue/multiselect';
import SplitButton from 'primevue/splitbutton';
import OverlayPanel from 'primevue/overlaypanel';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import { useToast } from "primevue/usetoast";

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
    interventionReasons: Array
});

const toast = useToast();
const opFilters = ref();
const isModalOpen = ref(false);
const deleteDialog = ref(false);
const dt = ref();
const op = ref();
const selectedItems = ref([]);
const lastAssignable = ref({ type: null, id: null });

// --- CONFIGURATION V11 ---
const allColumns = [
    { field: 'title', header: 'Référence', sortable: true },
    { field: 'status', header: 'Statut', sortable: true },
    { field: 'customer_code', header: 'Code Client', sortable: false },
    { field: 'client_name', header: 'Nom Client', sortable: false },
    { field: 'region_name', header: 'Région', sortable: false },
    { field: 'priority', header: 'Priorité', sortable: true },
    { field: 'scheduled_date', header: 'Échéance', sortable: true },
    { field: 'assigned_to_name', header: 'Assigné à', sortable: false },
];

const selectedColumnFields = ref(['title', 'status', 'client_name', 'priority', 'scheduled_date', 'assigned_to_name']);
const displayedColumns = computed(() => allColumns.filter(col => selectedColumnFields.value.includes(col.field)));

// --- LOGIQUE FILTRES ---
const search = ref(props.filters?.search || '');
const filterForm = ref({
    status: props.filters?.status || null,
    region_id: props.filters?.region_id || null,
    priority: props.filters?.priority || null,
});

const applyFilters = () => {
    router.get(route('interventions.index'), {
        search: search.value,
        ...filterForm.value
    }, { preserveState: true, replace: true });
};

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

// --- FORMULAIRE & ACTIONS ---
const form = useForm({
    id: null, title: '', description: '', status: 'pending',
    requested_by_user_id: null, requested_by_connection_id: null,
    assignable_type: null, assignable_id: null, region_id: null, zone_id: null,
    intervention_reason: '', priority: '', scheduled_date: null,
    gps_latitude: null, gps_longitude: null, is_validated: true,
});

const openCreate = () => {
    form.reset();
    form.title = `PLT-${Math.floor(Date.now() / 1000)}`;
    form.scheduled_date = new Date();
    isModalOpen.value = true;
};

const openEdit = (data) => {
    form.clearErrors();
    form.defaults({
        ...data,
        scheduled_date: data.scheduled_date ? new Date(data.scheduled_date) : null
    }).reset();
    isModalOpen.value = true;
};

const formattedList = computed(() => (props.interventionRequests?.data || []).map(ir => ({
    ...ir,
    client_name: ir.requested_by_connection ? `${ir.requested_by_connection.first_name} ${ir.requested_by_connection.last_name}` : '-',
    customer_code: ir.requested_by_connection ? ir.requested_by_connection.customer_code : '-',
    assigned_to_name: ir.assignable ? ir.assignable.name : 'Non assigné',
    region_name: ir.region?.designation || '-',
})));

const assignableTypes = [{ label: 'Technicien', value: 'App\\Models\\User' }, { label: 'Équipe', value: 'App\\Models\\Team' }];
const getAssignables = (type) => type === 'App\\Models\\User' ? props.users : (type === 'App\\Models\\Team' ? props.teams : []);

const submit = () => {
    const url = form.id ? route('interventions.update', form.id) : route('interventions.store');
    form.post(url, { onSuccess: () => isModalOpen.value = false });
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Interventions" />
        <Toast />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                        Interventions <span class="text-indigo-600">Field Service</span>
                    </h1>
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">Opérations & Maintenance Réseau</p>
                </div>
                <div class="flex gap-2">
                    <Button label="Nouvelle Intervention" icon="pi pi-plus"
                            class="p-button-indigo shadow-lg shadow-indigo-200" @click="openCreate" />
                </div>
            </div>

            <div class="bg-white/70 backdrop-blur-md border border-white shadow-sm rounded-2xl p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex gap-2">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" placeholder="Rechercher une référence..."
                                       class="p-inputtext-sm border-none bg-slate-100 rounded-xl w-64" @input="applyFilters" />
                        </IconField>
                        <Button icon="pi pi-filter" :label="activeFiltersCount > 0 ? activeFiltersCount.toString() : 'Filtres'"
                                class="p-button-text p-button-secondary p-button-sm font-bold" @click="opFilters.toggle($event)" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Button v-if="selectedItems.length" icon="pi pi-trash" label="Supprimer"
                                class="p-button-danger p-button-text p-button-sm animate-fadein" @click="deleteDialog = true" />
                        <Button icon="pi pi-columns" class="p-button-text p-button-secondary" @click="op.toggle($event)" />
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="formattedList" v-model:selection="selectedItems" dataKey="id"
                           :rows="10" paginator class="p-datatable-sm quantum-table"
                           paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                           currentPageReportTemplate="{first} à {last} sur {totalRecords}">

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                        <template #header>
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ col.header }}</span>
                        </template>

                        <template #body="{ data, field }">
                            <span v-if="field === 'title'" class="font-bold text-slate-800 tracking-tight">{{ data.title }}</span>

                            <Tag v-else-if="field === 'status'" :value="data.status"
                                 :severity="getStatusSeverity(data.status)" class="uppercase text-[9px] px-2" />

                            <div v-else-if="field === 'priority'" class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: getPriorityColor(data.priority) }"></div>
                                <span class="text-xs font-medium">{{ data.priority }}</span>
                            </div>

                            <div v-else-if="field === 'assigned_to_name'" class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600">
                                    {{ data.assigned_to_name.charAt(0) }}
                                </div>
                                <span class="text-xs">{{ data.assigned_to_name }}</span>
                            </div>

                            <span v-else class="text-slate-600 text-sm">{{ data[field] }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" headerStyle="width: 5rem">
                        <template #body="{ data }">
                            <Button icon="pi pi-arrow-right" class="p-button-rounded p-button-text p-button-secondary"
                                    @click="openEdit(data)" v-tooltip.left="'Gérer'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="isModalOpen" modal :header="form.id ? 'Détails Intervention' : 'Création de Ticket'"
                class="quantum-dialog w-full max-w-4xl" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">

            <div class="p-2">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <div class="md:col-span-7 space-y-6">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">Origine de la demande</label>
                            <div class="flex gap-4">
                                <div class="flex-1 p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center gap-3"
                                     :class="requester_type === 'client' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white'"
                                     @click="requester_type = 'client'">
                                    <i class="pi pi-users"></i>
                                    <span class="text-xs font-bold">Client Externe</span>
                                </div>
                                <div class="flex-1 p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center gap-3"
                                     :class="requester_type === 'agent' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white'"
                                     @click="requester_type = 'agent'">
                                    <i class="pi pi-user"></i>
                                    <span class="text-xs font-bold">Agent Interne</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <Dropdown v-model="form.requested_by_connection_id" :options="connectionsList"
                                          optionLabel="search_label" optionValue="id" filter placeholder="Rechercher..."
                                          class="w-full quantum-input" v-if="requester_type === 'client'" />
                                <Dropdown v-model="form.requested_by_user_id" :options="props.users"
                                          optionLabel="name" optionValue="id" filter class="w-full quantum-input" v-else />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 mb-1 block">Sujet / Description</label>
                                <InputText v-model="form.title" class="w-full font-bold mb-2" readonly placeholder="Référence auto" />
                                <textarea v-model="form.description" rows="4"
                                          class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm"
                                          placeholder="Expliquez le problème technique ici..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-5 space-y-4">
                        <div class="p-4 bg-indigo-900 rounded-2xl text-white shadow-xl">
                            <h4 class="text-xs font-black uppercase tracking-widest mb-4 opacity-70">Assignation & Délai</h4>
                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase">Priorité</label>
                                    <Dropdown v-model="form.priority" :options="props.priorities" class="w-full bg-white/10 border-none text-white rounded-lg" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase">Assigné à</label>
                                    <Dropdown v-model="form.assignable_id" :options="getAssignables(form.assignable_type)"
                                              optionLabel="name" optionValue="id" class="w-full bg-white/10 border-none text-white rounded-lg" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase">Échéance</label>
                                    <Calendar v-model="form.scheduled_date" class="w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                             <div class="p-3 bg-slate-100 rounded-xl text-center">
                                <span class="block text-[8px] font-black uppercase text-slate-400">Région</span>
                                <span class="text-[10px] font-bold">{{ props.regions.find(r => r.id === form.region_id)?.designation || 'N/A' }}</span>
                             </div>
                             <div class="p-3 bg-slate-100 rounded-xl text-center">
                                <span class="block text-[8px] font-black uppercase text-slate-400">Zone</span>
                                <span class="text-[10px] font-bold">{{ props.zones.find(z => z.id === form.zone_id)?.title || 'N/A' }}</span>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-2">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="isModalOpen = false" />
                    <Button label="Enregistrer l'Intervention" icon="pi pi-save"
                            class="p-button-indigo px-6 shadow-lg shadow-indigo-100" @click="submit" :loading="form.processing" />
                </div>
            </template>
        </Dialog>

        <OverlayPanel ref="op" class="quantum-overlay">
            <div class="p-2 space-y-3">
                <span class="text-[10px] font-black uppercase text-slate-400 block border-b pb-2">Colonnes actives</span>
                <MultiSelect v-model="selectedColumnFields" :options="allColumns" optionLabel="header" optionValue="field"
                             display="chip" class="w-64 quantum-multiselect" />
            </div>
        </OverlayPanel>

    </AppLayout>
</template>

<style scoped>
/* STYLE V11 CUSTOM TOKENS */
.p-button-indigo {
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
