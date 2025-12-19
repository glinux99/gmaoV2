<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
import OverlayPanel from 'primevue/overlaypanel';
import Calendar from 'primevue/calendar';
import { useToast } from "primevue/usetoast";

const props = defineProps({
    interventionRequests: Object,
    filters: Object,
    users: Array,
    connections: Array,
    regions: Array,
    zones: Array,
    // On reçoit les listes depuis le backend ou on utilise les constantes ci-dessous
    statuses: Array,
    priorities: Array,
    interventionReasons: Array
});

// Constantes locales au cas où elles ne sont pas fournies par le backend
const REASONS = props.interventionReasons || [
    'Dépannage Réseau Urgent', 'Réparation Éclairage Public', 'Entretien Réseau Planifié',
    'Incident Majeur Réseau', 'Support Achat MobileMoney', 'Support Achat Token Impossible',
    'Aide Recharge (Sans clavier)', 'Élagage Réseau', 'Réparation Chute de Tension',
    'Coupure Individuelle (CI)', 'CI Équipement Client', 'CI Équipement Virunga',
    'CI Vol de Câble', 'Dépannage Clavier Client', 'Réparation Compteur Limité',
    'Rétablissement Déconnexion', 'Déplacement Câble (Planifié)', 'Déplacement Poteau (Planifié)',
    'Reconnexion Client', 'Support Envoi Formulaire', 'Intervention Non-Classifiée'
];

const toast = useToast();
const opFilters = ref();
const isModalOpen = ref(false);
const deleteDialog = ref(false);
const dt = ref();
const op = ref();
const selectedItems = ref([]);

// --- CONFIGURATION DES COLONNES ---
const allColumns = [
    { field: 'title', header: 'Titre', sortable: true },
    { field: 'status', header: 'Statut', sortable: true },
    { field: 'customer_code', header: 'Code Client', sortable: false },
    { field: 'client_name', header: 'Nom Client', sortable: false },
    { field: 'region_name', header: 'Région', sortable: false },
    { field: 'priority', header: 'Priorité', sortable: true },
    { field: 'scheduled_date', header: 'Date Prévue', sortable: true },
    { field: 'assigned_to_user_name', header: 'Technicien', sortable: false },
    { field: 'is_validated', header: 'Validée', sortable: true },
];

const selectedColumnFields = ref(['title', 'status', 'customer_code', 'client_name', 'priority', 'scheduled_date']);

const displayedColumns = computed(() => {
    return allColumns.filter(col => selectedColumnFields.value.includes(col.field));
});

// --- GESTION DES FILTRES ---
const search = ref(props.filters?.search || '');
const filterForm = ref({
    status: props.filters?.status || null,
    region_id: props.filters?.region_id || null,
    priority: props.filters?.priority || null,
});

const activeFiltersCount = computed(() => {
    return Object.values(filterForm.value).filter(v => v !== null && v !== '').length;
});

const applyFilters = () => {
    router.get(route('interventions.index'), {
        search: search.value,
        ...filterForm.value
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filterForm.value = { status: null, region_id: null, priority: null };
    search.value = '';
    applyFilters();
};

const toggleColumnSelection = (event) => {
    op.value.toggle(event);
};

// --- ACTIONS DE SUPPRESSION ---
const confirmDeleteSelected = () => {
    router.post(route('interventions.bulk-destroy'), {
        ids: selectedItems.value.map(i => i.id)
    }, {
        onSuccess: () => {
            deleteDialog.value = false;
            selectedItems.value = [];
            toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Éléments supprimés', life: 3000 });
        }
    });
};

// --- FORMULAIRE MODAL ---
const requester_type = ref('client');

const connectionsList = computed(() => {
    return props.connections.map(c => ({
        ...c,
        search_label: `${c.customer_code} - ${c.first_name} ${c.last_name}`
    }));
});

const form = useForm({
    id: null, title: '', description: '', status: 'pending',
    requested_by_user_id: null, requested_by_connection_id: null,
    assigned_to_user_id: null, region_id: null, zone_id: null,
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
    requester_type.value = data.requested_by_connection_id ? 'client' : 'agent';
    isModalOpen.value = true;
};

const submit = () => {
    const url = form.id ? route('interventions.update', form.id) : route('interventions.store');
    form[form.id ? 'put' : 'post'](url, {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: 'Succès', life: 3000 });
        }
    });
};

const formattedList = computed(() => (props.interventionRequests?.data || []).map(ir => ({
    ...ir,
    client_name: ir.requested_by_connection ? `${ir.requested_by_connection.first_name} ${ir.requested_by_connection.last_name}` : '-',
    customer_code: ir.requested_by_connection ? ir.requested_by_connection.customer_code : '-',
    assigned_to_user_name: ir.assigned_to_user ? ir.assigned_to_user.name : '-',
    region_name: ir.region?.designation || '-',
})));
</script>

<template>
    <AppLayout title="Interventions">
        <Toast />
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex gap-2">
                        <Button label="Nouvelle" icon="pi pi-plus" class="p-button-primary" @click="openCreate" />
                        <Button label="Supprimer" icon="pi pi-trash" class="p-button-danger"
                                @click="deleteDialog = true" :disabled="!selectedItems.length" />
                    </div>
                </template>
                <template #end>
                    <div class="flex items-center gap-2">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" placeholder="Recherche..." @input="applyFilters" />
                        </IconField>

                        <Button type="button" icon="pi pi-filter" label="Filtres"
                                :badge="activeFiltersCount > 0 ? activeFiltersCount.toString() : null"
                                badgeClass="p-badge-danger"
                                class="p-button-outlined p-button-secondary" @click="opFilters.toggle($event)" />

                        <Button icon="pi pi-ellipsis-v" class="p-button-secondary p-button-text"
                                @click="toggleColumnSelection" />
                    </div>
                </template>
            </Toolbar>

            <OverlayPanel ref="op">
                <div class="flex flex-col gap-2">
                    <span class="font-bold">Colonnes à afficher</span>
                    <MultiSelect v-model="selectedColumnFields" :options="allColumns" optionLabel="header" optionValue="field"
                                 placeholder="Sélectionner" class="w-64" display="chip" />
                </div>
            </OverlayPanel>

            <OverlayPanel ref="opFilters" style="width: 320px">
                <div class="flex flex-col gap-4 p-2">
                    <div class="flex justify-between items-center border-b pb-2">
                        <span class="font-bold">Filtres Avancés</span>
                        <Button icon="pi pi-refresh" class="p-button-text p-button-sm" @click="resetFilters" />
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500">Statut</label>
                            <Dropdown v-model="filterForm.status" :options="props.statuses" placeholder="Tous" class="w-full" showClear @change="applyFilters" />
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500">Région</label>
                            <Dropdown v-model="filterForm.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter placeholder="Toutes" class="w-full" showClear @change="applyFilters" />
                        </div>
                    </div>
                </div>
            </OverlayPanel>

            <DataTable ref="dt" :value="formattedList" v-model:selection="selectedItems" dataKey="id" responsiveLayout="scroll">
                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                    <template #body="slotProps">
                        <span v-if="col.field === 'status'" :class="'status-badge ' + slotProps.data.status">{{ slotProps.data.status }}</span>
                        <span v-else-if="col.field === 'scheduled_date'">{{ slotProps.data.scheduled_date ? new Date(slotProps.data.scheduled_date).toLocaleDateString() : '-' }}</span>
                        <i v-else-if="col.field === 'is_validated'" class="pi" :class="slotProps.data.is_validated ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'"></i>
                        <span v-else>{{ slotProps.data[col.field] }}</span>
                    </template>
                </Column>
                <Column header="Actions">
                    <template #body="{ data }">
                        <Button icon="pi pi-pencil" class="p-button-text" @click="openEdit(data)" />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="isModalOpen" :header="form.id ? 'Modifier' : 'Nouvelle Demande'" modal class="w-full max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="md:col-span-2 space-y-1">
                    <InputLabel value="Titre / Référence" />
                    <TextInput v-model="form.title" class="w-full bg-gray-50" readonly />
                </div>

                <div class="md:col-span-2 space-y-1">
                    <InputLabel value="Description" />
                    <textarea v-model="form.description" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary" rows="3"></textarea>
                </div>

                <div class="md:col-span-2 p-4 bg-gray-50 border rounded-lg">
                    <label class="font-bold block mb-3">Type de Demandeur</label>
                    <div class="flex gap-6 mb-4">
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="requester_type" value="client" inputId="c1" />
                            <label for="c1">Client</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="requester_type" value="agent" inputId="c2" />
                            <label for="c2">Agent Interne</label>
                        </div>
                    </div>

                    <div v-if="requester_type === 'client'">
                        <InputLabel value="Code ou Nom Client" />
                        <Dropdown v-model="form.requested_by_connection_id"
                                  :options="connectionsList"
                                  optionLabel="search_label"
                                  optionValue="id"
                                  filter
                                  placeholder="Rechercher un client..."
                                  class="w-full mt-1">
                            <template #option="slotProps">
                                <div class="flex flex-col">
                                    <span class="font-bold">{{ slotProps.option.customer_code }}</span>
                                    <small class="text-gray-500">{{ slotProps.option.first_name }} {{ slotProps.option.last_name }}</small>
                                </div>
                            </template>
                        </Dropdown>
                    </div>
                    <div v-else>
                        <InputLabel value="Sélectionner l'Agent" />
                        <Dropdown v-model="form.requested_by_user_id" :options="props.users" optionLabel="name" optionValue="id" filter class="w-full mt-1" />
                    </div>
                </div>

                <div class="space-y-1">
                    <InputLabel value="Raison" />
                    <Dropdown v-model="form.intervention_reason" :options="REASONS" class="w-full" filter />
                </div>
                <div class="space-y-1"><InputLabel value="Statut" /><Dropdown v-model="form.status" :options="props.statuses" class="w-full" /></div>
                <div class="space-y-1"><InputLabel value="Assigné à" /><Dropdown v-model="form.assigned_to_user_id" :options="props.users" optionLabel="name" optionValue="id" class="w-full" filter /></div>
                <div class="space-y-1"><InputLabel value="Région" /><Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" class="w-full" /></div>
                <div class="space-y-1"><InputLabel value="Zone" /><Dropdown v-model="form.zone_id" :options="props.zones" optionLabel="title" optionValue="id" class="w-full" /></div>
                <div class="space-y-1"><InputLabel value="Priorité" /><Dropdown v-model="form.priority" :options="props.priorities" class="w-full" /></div>
                <div class="space-y-1"><InputLabel value="Date prévue" /><Calendar v-model="form.scheduled_date" class="w-full" showIcon dateFormat="dd/mm/yy" /></div>

                <div class="md:col-span-2 grid grid-cols-2 gap-4">
                    <div class="space-y-1"><InputLabel value="Latitude" /><TextInput v-model="form.gps_latitude" class="w-full" type="number" step="any" /></div>
                    <div class="space-y-1"><InputLabel value="Longitude" /><TextInput v-model="form.gps_longitude" class="w-full" type="number" step="any" /></div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="isModalOpen = false">Annuler</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">Enregistrer</PrimaryButton>
                </div>
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteDialog" header="Confirmation" modal class="w-96">
            <div class="flex items-center gap-3">
                <i class="pi pi-exclamation-triangle text-red-500 text-2xl" />
                <span>Supprimer {{ selectedItems.length }} éléments ?</span>
            </div>
            <template #footer>
                <Button label="Annuler" class="p-button-text" @click="deleteDialog = false" />
                <Button label="Confirmer" class="p-button-danger" @click="confirmDeleteSelected" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}
/* Ajoutez ici vos styles personnalisés pour les statuts */
</style>
