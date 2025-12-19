<script setup>
import { ref, computed } from 'vue';
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
import MultiSelect from 'primevue/multiselect';
import FileUpload from 'primevue/fileupload';
import OverlayPanel from 'primevue/overlaypanel';
import { useToast } from "primevue/usetoast";

const props = defineProps({
    connections: Object,
    filters: Object,
    regions: Array,
    zones: Array,
    connectionStatuses: Array,
});

const toast = useToast();
const op = ref();
const dt = ref();
const search = ref(props.filters?.search || '');
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
// --- CONFIGURATION DES COLONNES ---
const allColumns = [
    { field: 'customer_code', header: 'Code Client', sortable: true },
    { field: 'full_name', header: 'Nom Complet', sortable: false },
    { field: 'region_name', header: 'Région', sortable: false },
    { field: 'zone_name', header: 'Zone', sortable: false },
    { field: 'status', header: 'Statut', sortable: true },
    { field: 'phone_number', header: 'Téléphone', sortable: true },
    { field: 'gps_latitude', header: 'Lat.', sortable: true },
    { field: 'gps_longitude', header: 'Long.', sortable: true },
    { field: 'is_verified', header: 'Vérifié', sortable: true },
    { field: 'amount_paid', header: 'Montant Payé', sortable: true },
    { field: 'connection_date', header: 'Date Raccordement', sortable: true },
    { field: 'meter.serial_number', header: 'Compteur N°', sortable: true },
    { field: 'keypad.serial_number', header: 'Clavier N°', sortable: true },

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
        router.get(route('connections.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 300);
};

// --- PAGINATION & DONNÉES ---
const connectionList = computed(() => props.connections?.data || []);
const totalRecords = computed(() => props.connections?.total || 0);
const perPage = computed(() => props.connections?.per_page || 10);
const currentPage = computed(() => props.connections?.current_page || 1);

const onPage = (event) => {
    router.get(route('connections.index'), {
        page: event.page + 1,
        per_page: event.rows,
        search: search.value
    }, { preserveState: true });
};

const formatConnectionList = computed(() => {
    return connectionList.value.map(c => ({
        ...c,
        full_name: `${c.first_name || ''} ${c.last_name || ''}`.trim(),
        region_name: c.region?.designation || '-',
        zone_name: c.zone?.title || '-',
    }));
});

// --- FORMULAIRES ---
const isModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selected = ref([]);
const importForm = useForm({ file: null });

const form = useForm({
    id: null,
    customer_code: '',
    region_id: null,
    zone_id: null,
    status: null,
    first_name: '',
    last_name: '',
    phone_number: '',
    gps_latitude: null,
    gps_longitude: null,
    is_verified: false,
});

const openCreate = () => { form.reset(); isModalOpen.value = true; };
const openEdit = (data) => {
    form.clearErrors();
    Object.assign(form, data);
    isModalOpen.value = true;
};

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
    importForm.post(route('connections.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            toast.add({severity:'success', summary: 'Succès', detail: 'Données importées', life: 3000});
        }
    });
};

const bulkDelete = () => {
    if (confirm(`Supprimer les ${selected.value.length} raccordements ?`)) {
        router.post(route('connections.bulkdestroy'), { ids: selected.value.map(c => c.id) }, {
            onSuccess: () => { selected.value = []; }
        });
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
        <Button label="Nouveau" icon="pi pi-plus" @click="openCreate" />
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

            <DataTable ref="dt" :value="formatConnectionList" v-model:selection="selected" dataKey="id" responsiveLayout="scroll">
                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                    <template #body="slotProps" v-if="col.field === 'is_verified'">
                        <i class="pi" :class="slotProps.data.is_verified ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'"></i>
                    </template>
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

        <Dialog v-model:visible="isImportModalOpen" header="Importer Excel/CSV" modal :style="{width: '450px'}">
            <FileUpload mode="basic" accept=".xlsx,.xls,.csv" :maxFileSize="1000000" @select="onFileSelect" :auto="false" chooseLabel="Choisir un fichier" class="w-full" />
            <div class="flex justify-end mt-4">
                <PrimaryButton @click="doImport" :disabled="importForm.processing || !importForm.file">Lancer l'import</PrimaryButton>
            </div>
        </Dialog>

        <Dialog v-model:visible="isModalOpen" :header="form.id ? 'Modifier' : 'Nouveau'" modal class="p-fluid" :style="{width: '50vw'}">
            <div class="grid grid-cols-2 gap-4 mt-2">
                <div class="field">
                    <InputLabel value="Code Client" />
                    <TextInput v-model="form.customer_code" />
                </div>
                <div class="field">
                    <InputLabel value="Statut" />
                    <Dropdown v-model="form.status" :options="props.connectionStatuses" optionLabel="label" optionValue="value" />
                </div>
                </div>
            <template #footer>
                <SecondaryButton @click="isModalOpen = false">Annuler</SecondaryButton>
                <PrimaryButton @click="submit" :disabled="form.processing">Enregistrer</PrimaryButton>
            </template>
        </Dialog>
    </AppLayout>
</template>
