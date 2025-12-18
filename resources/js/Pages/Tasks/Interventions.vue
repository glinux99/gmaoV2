<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import MultiSelect from 'primevue/multiselect';
import FileUpload from 'primevue/fileupload';
import Checkbox from 'primevue/checkbox';
import OverlayPanel from 'primevue/overlaypanel';
import { useToast } from "primevue/usetoast";

const props = defineProps({
    interventionRequests: Object,
    filters: Object,
    users: Array,
    connections: Array,
    regions: Array,
    zones: Array,
    interventionReasons: Array,
    statuses: Array,
    technicalComplexities: Array,
    categories: Array,
    priorities: Array,
});

const toast = useToast();
let debounceId = null;
// Ajoutez cette fonction dans votre <script setup>
const sortByProximity = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            router.get(route('connections.index'), {
                user_lat: position.coords.latitude,
                user_lng: position.coords.longitude,
                search: search.value
            }, { preserveState: true });
        }, () => {
            toast.add({severity:'error', summary: 'Erreur', detail: 'Géolocalisation refusée', life: 3000});
        });
    }
};

const op = ref();
const dt = ref();
const search = ref(props.filters?.search || '');

// --- CONFIGURATION DES COLONNES ---
const allColumns = [
    { field: 'title', header: 'Titre', sortable: true },
    { field: 'description', header: 'Description', sortable: true },
    { field: 'status', header: 'Statut', sortable: true },
    { field: 'requested_by_user_name', header: 'Demandé par (Utilisateur)', sortable: false },
    { field: 'requested_by_connection_name', header: 'Demandé par (Client)', sortable: false },
    { field: 'assigned_to_user_name', header: 'Assigné à', sortable: false },
    { field: 'region_name', header: 'Région', sortable: false },
    { field: 'zone_name', header: 'Zone', sortable: false },
    { field: 'intervention_reason', header: 'Raison', sortable: true },
    { field: 'category', header: 'Catégorie', sortable: true },
    { field: 'technical_complexity', header: 'Complexité', sortable: true },
    { field: 'priority', header: 'Priorité', sortable: true },
    { field: 'scheduled_date', header: 'Date Prévue', sortable: true },
    { field: 'completed_date', header: 'Date Complétée', sortable: true },
    { field: 'gps_latitude', header: 'Lat.', sortable: true },
    { field: 'gps_longitude', header: 'Long.', sortable: true },
];

// Initialiser avec les colonnes par défaut
const selectedColumnFields = ref(allColumns.map(col => col.field));

// Filtrage dynamique pour le tableau
const displayedColumns = computed(() => {
    return allColumns.filter(col => selectedColumnFields.value.includes(col.field));
});

// --- FONCTIONS TOOLBAR ---
const toggleColumnSelection = (event) => {
    op.value.toggle(event);
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const performSearch = () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => {
        router.get(route('interventions.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 300);
};

// --- PAGINATION & DONNÉES ---
const interventionRequestList = computed(() => props.interventionRequests?.data || []);
const totalRecords = computed(() => props.interventionRequests?.total || 0);
const perPage = computed(() => props.interventionRequests?.per_page || 10);
const currentPage = computed(() => props.interventionRequests?.current_page || 1);

const onPage = (event) => {
    router.get(route('interventions.index'), {
        page: event.page + 1,
        per_page: event.rows,
        search: search.value
    }, { preserveState: true });
};

const formatInterventionRequestList = computed(() => {
    return interventionRequestList.value.map(ir => ({
        ...ir,
        requested_by_user_name: ir.requested_by_user?.name || '-',
        requested_by_connection_name: ir.requested_by_connection ? `${ir.requested_by_connection.first_name} ${ir.requested_by_connection.last_name}`.trim() : '-',
        assigned_to_user_name: ir.assigned_to_user?.name || '-',
        region_name: ir.region?.designation || '-',
        zone_name: ir.zone?.title || '-',
        scheduled_date: ir.scheduled_date ? new Date(ir.scheduled_date).toLocaleDateString() : '-',
        completed_date: ir.completed_date ? new Date(ir.completed_date).toLocaleDateString() : '-',
    }));
});

// --- FORMULAIRES ---
const isModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selected = ref([]);
const importForm = useForm({ file: null });
const requester_type = ref('client'); // 'client' or 'agent'

const form = useForm({
    id: null, // For editing
    title: '',
    description: '',
    status: 'pending',
    requested_by_user_id: null,
    requested_by_connection_id: null,
    assigned_to_user_id: null,
    region_id: null,
    zone_id: null,
    intervention_reason: '',
    category: '',
    technical_complexity: '',
    min_time_hours: null,
    max_time_hours: null,
    comments: '',
    priority: '',
    scheduled_date: null,
    completed_date: null,
    resolution_notes: '',
    gps_latitude: null,
    gps_longitude: null,
    is_validated: true, // Par défaut, la demande est validée
});

const openCreate = () => {
    form.reset();
    requester_type.value = 'client'; // Default to client on create
    form.requested_by_user_id = null;
    form.requested_by_connection_id = null;
    isModalOpen.value = true;
};
const openEdit = (data) => {
    form.clearErrors();
    form.defaults(data).reset(); // Use defaults and reset to populate form
    isModalOpen.value = true;
};

// Watcher pour auto-remplir les champs quand un client est sélectionné
watch(() => form.requested_by_connection_id, (newConnectionId) => {
    if (newConnectionId) {
        const connection = props.connections.find(c => c.id === newConnectionId);
        if (connection) {
            form.region_id = connection.region_id;
            form.zone_id = connection.zone_id;
            form.gps_latitude = connection.gps_latitude;
            form.gps_longitude = connection.gps_longitude;
        }
    } else {
        // Optionnel: réinitialiser si aucun client n'est sélectionné
        if (!form.id) { // seulement pour la création
            form.reset('region_id', 'zone_id', 'gps_latitude', 'gps_longitude');
        }
    }
});

// Watcher to clear the other requester type when one is selected
watch(requester_type, (newType) => {
    if (newType === 'client') {
        form.requested_by_user_id = null;
    } else {
        form.requested_by_connection_id = null;
    }
});

const connectionsWithFullName = computed(() => {
    return props.connections.map(c => ({ ...c, full_name_with_code: `${c.first_name} ${c.last_name} (${c.customer_code})` }));
});

const submit = () => {
    const method = form.id ? 'put' : 'post';
    const url = form.id ? route('connections.update', form.id) : route('connections.store');
    form[method](url, {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({severity:'success', summary: 'Succès', detail: 'Opération réussie', life: 3000});
        }
    });
};
const onFileSelect = (event) => { importForm.file = event.files[0]; };

const doImport = () => {
    // Assuming there's an import route for intervention requests
    importForm.post(route('interventions.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            toast.add({severity:'success', summary: 'Succès', detail: 'Données importées', life: 3000});
        },
    });
};

const bulkDelete = () => {
    if (confirm(`Supprimer les ${selected.value.length} raccordements ?`)) {
        router.post(route('connections.bulkdestroy'), { ids: selected.value.map(c => c.id) }, {
            onSuccess: () => { selected.value = []; }
        }); // This route should be for interventions, not connections
    }
};
</script>

<template>
    <AppLayout title="Raccordements">
        <Toast />
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-2">
                        <Button label="Nouvelle Demande" icon="pi pi-plus" @click="openCreate" />
                        <Button label="Importer" icon="pi pi-upload" class="p-button-success" @click="isImportModalOpen = true" />

                        <Button
                            label="Plus proches"
                            icon="pi pi-map-marker"
                            class="p-button-info p-button-outlined"
                            @click="sortByProximity"
        />

        <Button label="Supprimer" icon="pi pi-trash" class="p-button-danger" @click="bulkDelete" :disabled="!selected.length" />
    </div>

                </template>

                <template #end>
                    <div class="flex items-center gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                        </IconField>

                        <Button label="Exporter" icon="pi pi-download" class="p-button-help" @click="exportCSV" />

                        <Button
                            icon="pi pi-ellipsis-v"
                            class="p-button-secondary p-button-text"
                            @click="toggleColumnSelection"
                        />

                        <OverlayPanel ref="op" appendTo="body" style="width: 300px">
                            <div class="font-semibold mb-3">Sélectionner les colonnes :</div>
                            <MultiSelect
                                v-model="selectedColumnFields"
                                :options="allColumns"
                                optionLabel="header"
                                optionValue="field"
                                display="chip"
                                placeholder="Choisir les colonnes"
                                class="w-full"
                                :filter="true"
                            />
                        </OverlayPanel>
                    </div>
                </template>
            </Toolbar>

            <DataTable ref="dt" :value="formatInterventionRequestList" v-model:selection="selected" dataKey="id" responsiveLayout="scroll">
                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                    <!-- Custom body templates can be added here if needed for specific fields -->
                </Column>

                <Column header="Actions">
                    <template #body="{ data }">
                        <Button icon="pi pi-pencil" class="p-button-text" @click="openEdit(data)" />
                    </template>
                </Column>
            </DataTable>

            <Paginator
                :rows="perPage"
                :totalRecords="totalRecords"
                :first="(currentPage - 1) * perPage"
                @page="onPage"
                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rowsPerPageOptions="[10, 20, 50]"
            />
        </div>

      <Dialog
    v-model:visible="isImportModalOpen"
    header="Importer Excel/CSV"
    modal
    class="w-full max-w-md"
>
    <div class="mt-2 space-y-4">
        <p class="text-sm text-gray-600">Sélectionnez votre fichier .xlsx ou .csv pour mettre à jour la base de données.</p>

        <FileUpload
            mode="basic"
            accept=".xlsx,.xls,.csv"
            :maxFileSize="1000000"
            @select="onFileSelect"
            :auto="false"
            chooseLabel="Choisir un fichier"
            class="w-full"
        />
    </div>

    <template #footer>
        <div class="flex justify-end gap-3 mt-4">
            <SecondaryButton @click="isImportModalOpen = false">Annuler</SecondaryButton>
            <PrimaryButton
                @click="doImport"
                :disabled="importForm.processing || !importForm.file"
            >
                Lancer l'import
            </PrimaryButton>
        </div>
    </template>
</Dialog>
       <Dialog
    v-model:visible="isModalOpen"
    :header="form.id ? 'Modifier l\'intervention' : 'Nouvelle demande d\'intervention'"
    modal
    class="w-full max-w-4xl mx-4"
>
    <div class="max-h-[70vh] overflow-y-auto px-1 pt-4 pb-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

            <div class="md:col-span-2 space-y-1">
                <InputLabel for="title" value="Titre de l'intervention" />
                <TextInput id="title" v-model="form.title" class="w-full" placeholder="Ex: Panne compteur..." />
                <InputError :message="form.errors.title" />
            </div>

            <div class="md:col-span-2 space-y-1">
                <InputLabel value="Description détaillée" />
                <textarea
                    v-model="form.description"
                    rows="3"
                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    placeholder="Détails techniques..."
                ></textarea>
                <InputError :message="form.errors.description" />
            </div>

            <div class="space-y-1">
                <InputLabel value="Statut" />
                <Dropdown v-model="form.status" :options="props.statuses" class="w-full border-gray-300" placeholder="Sélectionner un statut" />
                <InputError :message="form.errors.status" />
            </div>

            <div class="space-y-1">
                <InputLabel value="Assigné à (Technicien)" />
                <Dropdown v-model="form.assigned_to_user_id" :options="props.users" optionLabel="name" optionValue="id" class="w-full border-gray-300" placeholder="Choisir un technicien" filter />
                <InputError :message="form.errors.assigned_to_user_id" />
            </div>

            <div class="md:col-span-2 space-y-3 p-3 bg-gray-50 rounded-lg border">
                <InputLabel value="Type de demandeur" class="font-semibold"/>
                <div class="flex items-center gap-6">
                    <div class="flex items-center">
                        <RadioButton v-model="requester_type" inputId="requesterTypeClient" name="requesterType" value="client" />
                        <label for="requesterTypeClient" class="ml-2">Par un Client</label>
                    </div>
                    <div class="flex items-center">
                        <RadioButton v-model="requester_type" inputId="requesterTypeAgent" name="requesterType" value="agent" />
                        <label for="requesterTypeAgent" class="ml-2">Par un Agent</label>
                    </div>
                </div>

                <div v-if="requester_type === 'client'" class="space-y-1 pt-2">
                    <InputLabel value="Client" />
                    <Dropdown v-model="form.requested_by_connection_id" :options="connectionsWithFullName" optionLabel="full_name_with_code" optionValue="id" class="w-full border-gray-300" placeholder="Rechercher par nom ou code client..." filter>
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="truncate">
                            {{ connectionsWithFullName.find(c => c.id === slotProps.value)?.full_name_with_code }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                </Dropdown>
                <InputError :message="form.errors.requested_by_connection_id" />
            </div>
                <div v-if="requester_type === 'agent'" class="space-y-1 pt-2">
                    <InputLabel value="Agent interne" />
                    <Dropdown v-model="form.requested_by_user_id" :options="props.users" optionLabel="name" optionValue="id" class="w-full border-gray-300" placeholder="Choisir un utilisateur" filter />
                    <InputError :message="form.errors.requested_by_user_id" />
                </div>
            </div>

            <div class="space-y-1">
                <InputLabel value="Région / Zone" />
                <div class="flex gap-2">
                    <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" class="w-1/2 border-gray-300" placeholder="Région" filter />
                    <Dropdown v-model="form.zone_id" :options="props.zones" optionLabel="title" optionValue="id" class="w-1/2 border-gray-300" placeholder="Zone" filter />
                </div>
            </div>

            <div class="space-y-1">
                <InputLabel value="Raison de l'intervention" />
                <Dropdown v-model="form.intervention_reason" :options="props.interventionReasons" class="w-full border-gray-300" filter />
            </div>

            <div class="space-y-1">
                <InputLabel value="Priorité" />
                <Dropdown v-model="form.priority" :options="props.priorities" class="w-full border-gray-300" />
            </div>

            <div class="space-y-1">
                <InputLabel value="Date prévue" />
                <TextInput v-model="form.scheduled_date" type="date" class="w-full" />
            </div>

            <div class="space-y-1">
                <InputLabel value="Estimation (Heures max)" />
                <TextInput v-model="form.max_time_hours" type="number" class="w-full" />
            </div>

            <div class="md:col-span-2 grid grid-cols-2 gap-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                <div class="space-y-1">
                    <InputLabel value="Latitude GPS" />
                    <TextInput v-model="form.gps_latitude" type="number" step="0.0000001" class="w-full bg-white" />
                </div>
                <div class="space-y-1">
                    <InputLabel value="Longitude GPS" />
                    <TextInput v-model="form.gps_longitude" type="number" step="0.0000001" class="w-full bg-white" />
                </div>
            </div>

            <div class="md:col-span-2 flex items-center space-x-3 p-3">
                <Checkbox v-model="form.is_validated" :binary="true" inputId="is_validated" />
                <InputLabel for="is_validated" value="Demande Validée" />
                <InputError :message="form.errors.is_validated" />
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <SecondaryButton @click="isModalOpen = false">Annuler</SecondaryButton>
            <PrimaryButton @click="submit" :disabled="form.processing">
                <i v-if="form.processing" class="pi pi-spin pi-spinner mr-2"></i>
                Enregistrer
            </PrimaryButton>
        </div>
    </template>
</Dialog>
    </AppLayout>
</template>
