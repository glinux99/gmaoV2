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
import MultiSelect from 'primevue/multiselect';
import FileUpload from 'primevue/fileupload';
import OverlayPanel from 'primevue/overlaypanel';
import Textarea from 'primevue/textarea';
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
        <Head title="Raccordements" />
        <Toast />

        <div class="min-h-screen bg-slate-50 p-4 md:p-10 font-sans">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-blue-600 shadow-xl shadow-blue-200">
                        <i class="pi pi-link text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tighter text-slate-900 md:text-4xl">Gestion des Raccordements</h1>
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Clients & Points de connexion</p>
                    </div>
                </div>

                <div class="flex w-full flex-col sm:flex-row items-center gap-3 lg:w-auto">
                    <Button label="Importer" icon="pi pi-upload" class="p-button-success flex-1 sm:flex-none" @click="isImportModalOpen = true" />
                    <Button label="Nouveau Raccordement" icon="pi pi-plus-circle" class="flex-[2] sm:flex-none !font-black" @click="openCreate" />
                </div>
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-[2.5rem] border border-white bg-white/50 p-4 shadow-sm backdrop-blur-md">
                <div class="flex items-center gap-2">
                    <Button label="Supprimer" icon="pi pi-trash" severity="danger" @click="bulkDelete" :disabled="!selected.length" />
                    <Button label="Plus proches" icon="pi pi-map-marker" severity="info" outlined @click="sortByProximity" />
                </div>
                <div class="flex items-center gap-2">
                     <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" class="w-full sm:w-auto" />
                    </IconField>
                    <Button icon="pi pi-file-excel" severity="success" text @click="exportCSV" v-tooltip.bottom="'Exporter en CSV'" />
                    <Button icon="pi pi-columns" text @click="toggleColumnSelection" class="!text-slate-400 hover:!text-indigo-500" v-tooltip.bottom="'Choisir les colonnes'" />
                </div>
            </div>

            <div class="overflow-hidden rounded-[3rem] border border-white bg-white shadow-2xl shadow-slate-200/60">
                <DataTable ref="dt" :value="formatConnectionList" v-model:selection="selected" dataKey="id"
                    :paginator="true" :rows="perPage" :totalRecords="totalRecords" :first="(currentPage - 1) * perPage"
                    @page="onPage" :rowsPerPageOptions="[10, 20, 50]" lazy
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    responsiveLayout="scroll" class="v11-table">

                    <Column selectionMode="multiple" headerStyle="width: 3rem" class="pl-8"></Column>

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                        <template #body="slotProps">
                             <span v-if="col.field === 'full_name'" class="font-bold">
                                {{ slotProps.data.full_name }}
                            </span>
                            <span v-else-if="col.field === 'is_verified'">
                                <i class="pi" :class="slotProps.data.is_verified ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'"></i>
                            </span>
                             <span v-else>
                                {{ slotProps.data[col.field] }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" class="p-button-text" @click="openEdit(data)" />
                        </template>
                    </Column>
                </DataTable>

                <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                    <div class="font-semibold mb-3">Sélectionner les colonnes :</div>
                    <MultiSelect
                        v-model="selectedColumnFields"
                        :options="allColumns"
                        optionLabel="header"
                        optionValue="field"
                        display="chip"
                        placeholder="Choisir les colonnes"
                        class="w-full max-w-xs"
                    />
                </OverlayPanel>
            </div>
        </div>

        <Dialog v-model:visible="isImportModalOpen" header="Importer depuis Excel/CSV" modal :style="{width: '450px'}">
            <FileUpload mode="basic" accept=".xlsx,.xls,.csv" :maxFileSize="1000000" @select="onFileSelect" :auto="false" chooseLabel="Choisir un fichier" class="w-full" />
            <div class="flex justify-end mt-4">
                <Button @click="doImport" :disabled="importForm.processing || !importForm.file" label="Lancer l'import" icon="pi pi-upload" />
            </div>
        </Dialog>

        <Dialog v-model:visible="isModalOpen" :header="form.id ? $t('connections.dialog.editTitle') : $t('connections.dialog.createTitle')" modal class="quantum-dialog w-full max-w-4xl" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">
             <div class="p-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Code Client</label>
                        <InputText v-model="form.customer_code" class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.customer_code">{{ form.errors.customer_code }}</small>
                    </div>
                    <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Prénom</label>
                        <InputText v-model="form.first_name" class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.first_name">{{ form.errors.first_name }}</small>
                    </div>
                    <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Nom</label>
                        <InputText v-model="form.last_name" class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.last_name">{{ form.errors.last_name }}</small>
                    </div>
                     <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Téléphone</label>
                        <InputText v-model="form.phone_number" class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.phone_number">{{ form.errors.phone_number }}</small>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Région</label>
                        <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.region_id">{{ form.errors.region_id }}</small>
                    </div>
                    <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Zone</label>
                        <Dropdown v-model="form.zone_id" :options="props.zones" optionLabel="title" optionValue="id" filter class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.zone_id">{{ form.errors.zone_id }}</small>
                    </div>
                    <div class="field">
                        <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Statut</label>
                        <Dropdown v-model="form.status" :options="props.connectionStatuses" optionLabel="label" optionValue="value" class="w-full quantum-input" />
                        <small class="p-error" v-if="form.errors.status">{{ form.errors.status }}</small>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="field">
                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Latitude GPS</label>
                            <InputText v-model="form.gps_latitude" class="w-full quantum-input" />
                            <small class="p-error" v-if="form.errors.gps_latitude">{{ form.errors.gps_latitude }}</small>
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Longitude GPS</label>
                            <InputText v-model="form.gps_longitude" class="w-full quantum-input" />
                            <small class="p-error" v-if="form.errors.gps_longitude">{{ form.errors.gps_longitude }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="isModalOpen = false" />
                <Button label="Enregistrer" icon="pi pi-check" @click="submit" :loading="form.processing" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style>
/* Style spécifique pour la table afin d'affiner le rendu Tailwind avec PrimeVue */
.v11-table .p-datatable-thead > tr > th {
    background: #f8fafc !important;
    color: #94a3b8 !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15em !important;
    padding: 1.5rem 1rem !important;
    border: none !important;
}

.v11-table .p-datatable-tbody > tr {
    transition: all 0.2s ease;
}

.v11-table .p-datatable-tbody > tr:hover {
    background: #f1f5f9/50 !important;
}

.p-dialog-mask {
    backdrop-filter: blur(4px);
}
</style>
