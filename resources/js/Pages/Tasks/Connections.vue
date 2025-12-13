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
import ConfirmDialog from 'primevue/confirmdialog';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect'; // Utilisé pour les colonnes
import FileUpload from 'primevue/fileupload'; // Utilisé pour l'import

const props = defineProps({
    connections: Object, // pagination Laravel { data, links, meta }
    filters: Object,
    regions: Array,
    zones: Array,
    connectionStatuses: Array,
});

// Recherche serveur
const search = ref(props.filters?.search || '');
let debounceId = null;

const performSearch = () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => {
        router.get(route('connections.index'), { search: search.value }, { preserveState: true, replace: true, only: ['connections', 'filters'] });
    }, 300);
};

// Liste paginée
const connectionList = computed(() => props.connections?.data || []);
const totalRecords = computed(() => props.connections?.meta?.total || 0);
const currentPage = computed(() => props.connections?.meta?.current_page || 1);
const perPage = computed(() => props.connections?.meta?.per_page || 10);

const onPage = (event) => {
    router.get(route('connections.index'), { page: event.page + 1, per_page: event.rows, search: search.value }, { preserveState: true, replace: true, only: ['connections', 'filters'] });
};

// Sélection multiple
const selected = ref([]);
const bulkDisabled = computed(() => !selected.value || selected.value.length < 2);

// Modales
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isImportModalOpen = ref(false);
const toDelete = ref(null);

// Formulaire création/édition
const form = useForm({
    id: null,
    customer_code: '',
    region_id: null,
    zone_id: null,
    status: null,
    first_name: '',
    last_name: '',
    phone_number: '',
    // Ajoutez tous les champs requis ici
});

// Formulaire pour l'importation
const importForm = useForm({
    file: null,
});

// --- Gestion Dynamique des Colonnes ---
// Définition de TOUTES les colonnes possibles
const allColumns = [
    { field: 'customer_code', header: 'Code Client', sortable: true },
    { field: 'full_name', header: 'Nom Complet', sortable: false }, // Champ calculé ou accessoire
    { field: 'region.name', header: 'Région', sortable: true },
    { field: 'zone.name', header: 'Zone', sortable: true },
    { field: 'status', header: 'Statut', sortable: true },
    { field: 'connection_date', header: 'Date Conn.', sortable: true },
    { field: 'phone_number', header: 'Téléphone', sortable: false },
    // Ajoutez plus de colonnes ici
];

// Colonnes visibles par défaut (utiliser les objets complets)
const visibleColumns = ref(allColumns.filter(col => ['customer_code', 'full_name', 'region.name', 'zone.name', 'status'].includes(col.field)));

const formatConnectionList = computed(() => {
    return connectionList.value.map(connection => ({
        ...connection,
        full_name: `${connection.first_name || ''} ${connection.last_name || ''}`.trim(),
    }));
});
// ----------------------------------------

// Modale création
const openCreate = () => {
    isModalOpen.value = true;
    form.reset();
};

// Modale édition (ADAPTÉ AU MODÈLE)
const openEdit = (connection) => {
    isModalOpen.value = true;
    form.clearErrors();
    form.id = connection.id;
    form.customer_code = connection.customer_code;
    form.region_id = connection.region_id;
    form.zone_id = connection.zone_id;
    form.status = connection.status;
    form.first_name = connection.first_name;
    form.last_name = connection.last_name;
    form.phone_number = connection.phone_number;
    // Remplir d'autres champs si nécessaire
};

const closeModal = () => { isModalOpen.value = false; };

// Soumission création/édition
const submit = () => {
    const url = form.id ? route('connections.update', form.id) : route('connections.store');
    const method = form.id ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            closeModal();
            // Optionnel : Afficher un toast de succès
        },
    });
};

// Suppression simple
const confirmDelete = (connection) => { toDelete.value = connection; isDeleteModalOpen.value = true; };
const doDelete = () => {
    router.delete(route('connections.destroy', toDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        }
    });
};

// Suppression multiple
const bulkDelete = () => {
    if (!selected.value.length) return;
    if (!confirm(`Supprimer ${selected.value.length} raccordement(s) sélectionné(s) ?`)) return;
    router.post(route('connections.bulkdestroy'), { ids: selected.value.map(c => c.id) }, {
        onSuccess: () => { selected.value = []; }
    });
};

// Export CSV
const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

// Import
const openImport = () => {
    isImportModalOpen.value = true;
    importForm.reset();
};

const onFileSelect = (event) => {
    // PrimeVue FileUpload utilise event.files
    if (event.files && event.files.length > 0) {
        importForm.file = event.files[0];
    }
};

const doImport = () => {
    if (!importForm.file) return;

    importForm.post(route('connections.import'), {
        forceFormData: true,
        onSuccess: () => {
            isImportModalOpen.value = false;
            // Utiliser un toast ici pour le succès
        },
        onError: (errors) => {
             // Gérer les erreurs (ex: afficher dans le toast)
             console.error('Erreur d\'importation:', errors);
        }
    });
};
</script>
<template>
    <AppLayout title="Raccordements">
        <Head title="Raccordements" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestion des Raccordements</h2>
        </template>

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>

                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex items-center gap-2">
                                <Button label="Nouveau Raccordement" icon="pi pi-plus" @click="openCreate" class="p-button-primary" />
                                <Button label="Importer" icon="pi pi-upload" @click="openImport" class="p-button-success p-button-outlined" />
                                <Button :label="`Supprimer (${selected.length || 0})`" icon="pi pi-trash" @click="bulkDelete" :disabled="bulkDisabled" class="p-button-danger" />
                            </div>
                        </template>

                        <template #end>
                            <div class="flex items-center gap-2">
                                <IconField iconPosition="left">
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                                </IconField>

                                <MultiSelect
                                    v-model="visibleColumns"
                                    :options="allColumns"
                                    optionLabel="header"
                                    placeholder="Choisir les colonnes"
                                    class="w-full md:w-14rem"
                                    display="chip"
                                />

                                <Button label="Exporter" icon="pi pi-download" class="p-button-help" @click="exportCSV" />
                            </div>
                        </template>
                    </Toolbar>

                    <DataTable :value="formatConnectionList" ref="dt" dataKey="id" :rows="perPage" v-model:selection="selected" selectionMode="multiple" responsiveLayout="scroll" stripedRows>

                        <Column selectionMode="multiple" headerStyle="width: 3rem" :exportable="false"></Column>

                        <Column v-for="col in visibleColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable"></Column>

                        <Column header="Actions" :exportable="false" style="min-width: 9rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-sm mr-2" @click="openEdit(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger p-button-sm" @click="confirmDelete(data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Paginator v-if="totalRecords > perPage" :rows="perPage" :totalRecords="totalRecords" :rowsPerPageOptions="[5, 10, 20, 50]" @page="onPage" :first="(currentPage - 1) * perPage"></Paginator>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="isModalOpen" modal :header="form.id ? 'Modifier le Raccordement' : 'Créer un Raccordement'" :style="{ width: '50rem' }" class="p-fluid">
            <form @submit.prevent="submit" class="space-y-4">

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <InputLabel for="customer_code" value="Code Client" />
                        <TextInput id="customer_code" v-model="form.customer_code" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.customer_code" class="mt-2" />
                    </div>

                    <div class="field">
                        <InputLabel for="status" value="Statut" />
                        <Dropdown id="status" v-model="form.status" :options="props.connectionStatuses" optionLabel="label" optionValue="value" placeholder="Sélectionner un statut" class="mt-1 block w-full" />
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <InputLabel for="first_name" value="Prénom" />
                        <TextInput id="first_name" v-model="form.first_name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.first_name" class="mt-2" />
                    </div>

                    <div class="field">
                        <InputLabel for="last_name" value="Nom" />
                        <TextInput id="last_name" v-model="form.last_name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.last_name" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <InputLabel for="region" value="Région" />
                        <Dropdown id="region" v-model="form.region_id" :options="props.regions" optionLabel="name" optionValue="id" placeholder="Sélectionner une région" class="mt-1 block w-full" filter />
                        <InputError :message="form.errors.region_id" class="mt-2" />
                    </div>

                    <div class="field">
                        <InputLabel for="zone" value="Zone" />
                        <Dropdown id="zone" v-model="form.zone_id" :options="props.zones" optionLabel="name" optionValue="id" placeholder="Sélectionner une zone" class="mt-1 block w-full" filter />
                        <InputError :message="form.errors.zone_id" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <SecondaryButton type="button" @click="closeModal">Annuler</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">{{ form.id ? 'Mettre à jour' : 'Créer' }}</PrimaryButton>
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="isDeleteModalOpen" modal header="Confirmer la suppression" :style="{ width: '30rem' }">
            <div class="flex items-center">
                <i class="pi pi-exclamation-triangle text-red-500 mr-3" style="font-size: 2rem" />
                <p>Êtes-vous sûr de vouloir supprimer le raccordement <strong>{{ toDelete?.customer_code }} ({{ toDelete?.first_name }} {{ toDelete?.last_name }})</strong> ?</p>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <SecondaryButton @click="isDeleteModalOpen = false">Annuler</SecondaryButton>
                <DangerButton @click="doDelete" :disabled="form.processing">Supprimer</DangerButton>
            </div>
        </Dialog>

        <Dialog v-model:visible="isImportModalOpen" modal header="Importer des raccordements (CSV / Excel)" :style="{ width: '30rem' }">
            <form @submit.prevent="doImport" class="p-fluid">
                <p>Veuillez sélectionner un fichier Excel (.xlsx, .xls) ou CSV à importer.</p>

                <FileUpload
                    name="file"
                    @select="onFileSelect"
                    :multiple="false"
                    accept=".xlsx,.xls,.csv"
                    :maxFileSize="1000000"
                    :showUploadButton="false"
                    :showCancelButton="false"
                    customUpload
                    class="mt-3"
                >
                    <template #empty><p>Glissez-déposez ou cliquez ici pour sélectionner un fichier.</p></template>
                </FileUpload>

                <div class="flex justify-end gap-2 mt-4">
                    <SecondaryButton type="button" @click="isImportModalOpen = false">Annuler</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="!importForm.file || importForm.processing">Importer</PrimaryButton>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Styles spécifiques si nécessaire */
</style>
