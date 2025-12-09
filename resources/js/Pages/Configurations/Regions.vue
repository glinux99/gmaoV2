<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// Importez les composants PrimeVue nécessaires pour les nouvelles fonctionnalités
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import FileUpload from 'primevue/fileupload'; // Ajouté pour l'import

const props = defineProps({
    regions: Array,
    filters: Object,
});

const toast = useToast();
const confirm = useConfirm();

const labelDialog = ref(false); // Dialogue Création/Modification
const importDialog = ref(false); // Dialogue Importation (NOUVEAU)
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedRegions = ref(null); // Pour la suppression multiple (NOUVEAU)

// Pour le File Upload (NOUVEAU)
const fileUploadRef = ref(null);
const fileImportForm = useForm({
    file: null,
});


const form = useForm({
    id: null,
    designation: '',
    type_centrale: null,
    puissance_centrale: null,
});

const dt = ref(); // Référence à la DataTable pour l'export

// --- CRUD de base (Création/Modification/Suppression unitaire) ---

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true;
};

const hideDialog = () => {
    labelDialog.value = false;
    submitted.value = false;
};

const editRegion = (region) => {
    form.id = region.id;
    form.designation = region.designation;
    form.type_centrale = region.type_centrale;
    form.puissance_centrale = region.puissance_centrale;
    editing.value = true;
    labelDialog.value = true;
};

const saveRegion = () => {
    submitted.value = true;
    if (!form.designation) {
        return;
    }

    const url = editing.value ? route('regions.update', form.id) : route('regions.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Région ${editing.value ? 'mise à jour' : 'créée'} avec succès`, life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la région", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deleteRegion = (region) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la région "${region.designation}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('regions.destroy', region.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Région supprimée avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

// --- Exportation CSV ---

const exportCSV = () => {
    // PrimeVue DataTable gère l'exportation CSV du tableau affiché
    dt.value.exportCSV();
};

// --- Suppression Multiple (NOUVEAU) ---

const confirmDeleteSelected = () => {
    if (!selectedRegions.value || selectedRegions.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez sélectionner au moins une région.', life: 3000 });
        return;
    }

    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedRegions.value.length} régions sélectionnées ?`,
        header: 'Confirmation de suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteSelectedRegions();
        },
    });
};

const deleteSelectedRegions = () => {
    const ids = selectedRegions.value.map(region => region.id);

    // Inertia utilise POST avec une méthode _method DELETE pour la suppression
    router.post(route('regions.bulkDestroy'), { ids: ids }, { // Assurez-vous d'avoir une route 'regions.bulkDestroy'
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} régions supprimées avec succès.`, life: 3000 });
            selectedRegions.value = null; // Désélectionner les régions
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression multiple.', life: 3000 });
        },
        preserveState: false, // Recharger la page pour mettre à jour le tableau
    });
};

// --- Importation de Fichier (NOUVEAU) ---

const openImportDialog = () => {
    fileImportForm.reset();
    importDialog.value = true;
};

const uploadFile = () => {
    if (!fileImportForm.file) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez sélectionner un fichier à importer.', life: 3000 });
        return;
    }

    // Le fichier est déjà dans fileImportForm.file grâce à l'événement @select (voir template)
    fileImportForm.post(route('regions.import'), { // Assurez-vous d'avoir une route 'regions.import'
        onSuccess: () => {
            importDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Importation lancée. Le tableau se mettra à jour.', life: 3000 });
            fileImportForm.reset();
            // Optionnel : Recharger la page si l'import est synchrone, sinon, attendre le résultat (queue)
            router.reload({ only: ['regions'] });
        },
        onError: (errors) => {
            console.error("Erreur lors de l'importation", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: fileImportForm.errors.file || 'Erreur lors du traitement du fichier.', life: 5000 });
        },
    });
};

// Gestion de la recherche existante
let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('regions.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const typeCentraleOptions = ref([
    { label: 'Thermique', value: 'thermique' },
    { label: 'Solaire', value: 'solaire' },
    { label: 'Hydraulique', value: 'hydraulique' },
    { label: 'Eolienne', value: 'eolienne' },
]);

const dialogTitle = computed(() => editing.value ? 'Modifier la Région' : 'Créer une nouvelle Région');
const hasSelectedRegions = computed(() => selectedRegions.value && selectedRegions.value.length > 0);

</script>

<template>
    <AppLayout title="Gestion des Régions">
        <Head title="Régions" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">
                                <span class="block mt-2 md:mt-0 p-input-icon-left">
                                    <Button label="Ajouter une région" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />
                                    <Button label="Supprimer la sélection" icon="pi pi-trash" class="p-button-sm p-button-danger mr-2"
                                        :disabled="!hasSelectedRegions" @click="confirmDeleteSelected" /> <Button label="Importer" icon="pi pi-download" class="p-button-sm p-button-warning"
                                        @click="openImportDialog" /> </span>
                            </div>
                        </template>

                        <template #end>
                            <IconField class="mr-2">
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                            </IconField>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="regions" dataKey="id" :paginator="true" :rows="10"
                        :selection="selectedRegions" @update:selection="selectedRegions = $event" :rowsPerPageOptions="[5, 10, 25]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} régions"
                        responsiveLayout="scroll">

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <Column field="designation" header="Désignation" :sortable="true" headerStyle="width:30%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.designation }}
                            </template>
                        </Column>
                        <Column field="type_centrale" header="Type de Centrale" headerStyle="width:35%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.type_centrale }}
                            </template>
                        </Column>
                        <Column field="puissance_centrale" header="Puissance de la Centrale (MW)" headerStyle="width:20%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.puissance_centrale }}
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editRegion(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteRegion(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <div class="flex items-center gap-4 mb-4">
                            <label for="designation" class="font-semibold w-24">Désignation</label>
                            <InputText id="designation" v-model.trim="form.designation" required="true" autofocus
                                :class="{ 'p-invalid': submitted && !form.designation }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.designation">La désignation est requise.</small>
                        <small class="p-error" v-if="form.errors.designation">{{ form.errors.designation }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="type_centrale" class="font-semibold w-24">Type de Centrale</label>
                            <Dropdown id="type_centrale" v-model="form.type_centrale" :options="typeCentraleOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner un type" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.type_centrale">{{ form.errors.type_centrale }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="puissance_centrale" class="font-semibold w-24">Puissance Centrale (MW)</label>
                            <InputNumber id="puissance_centrale" v-model="form.puissance_centrale" mode="decimal" :min="0" :maxFractionDigits="2" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.puissance_centrale">{{ form.errors.puissance_centrale }}</small>

                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveRegion" :loading="form.processing"></Button>
                        </div>
                    </Dialog>

                    <Dialog v-model:visible="importDialog" modal header="Importer des Régions" :style="{ width: '30rem' }">
                        <p class="text-surface-500 dark:text-surface-400 mb-4">
                            Téléchargez un fichier CSV ou Excel pour importer de nouvelles régions.
                        </p>
                        <form @submit.prevent="uploadFile">
                            <FileUpload
                                ref="fileUploadRef"
                                mode="basic"
                                name="file"
                                chooseLabel="Choisir un fichier"
                                accept=".csv, .xlsx"
                                :maxFileSize="1000000"
                                @select="event => fileImportForm.file = event.files[0]"
                                customUpload
                                :auto="false"
                                :class="{ 'p-invalid': fileImportForm.errors.file }"
                            />
                            <small class="p-error block mt-2" v-if="fileImportForm.errors.file">{{ fileImportForm.errors.file }}</small>

                            <div class="flex justify-end gap-2 mt-4">
                                <Button type="button" label="Annuler" severity="secondary" @click="importDialog = false"></Button>
                                <Button type="submit" label="Importer" :loading="fileImportForm.processing"></Button>
                            </div>
                        </form>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<style scoped>
.p-datatable .p-datatable-header {
    border-bottom: 1px solid var(--surface-d);
}

.p-datatable .p-column-header-content {
    justify-content: space-between;
}

.p-button.p-button-warning, .p-button.p-button-warning:hover {
    background: var(--orange-500);
    border-color: var(--orange-500);
}

.p-button.p-button-warning:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--orange-700), 0 1px 2px 0 black;
}

.p-button.p-button-success, .p-button.p-button-success:hover {
    background: var(--green-500);
    border-color: var(--green-500);
}

.p-button.p-button-success:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--green-700), 0 1px 2px 0 black;
}

.p-button.p-button-danger, .p-button.p-button-danger:hover {
    background: var(--red-500);
    border-color: var(--red-500);
}

.p-button.p-button-danger:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--red-700), 0 1px 2px 0 black;
}
</style>
