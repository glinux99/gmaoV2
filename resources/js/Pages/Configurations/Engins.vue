<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
   import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { v4 as uuidv4 } from 'uuid';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
 engins: Array,
    filters: Object,
    enginTypes: Array, // Add enginTypes to props
    regions: Array,

});

const toast = useToast();
const confirm = useConfirm();

const labelDialog = ref(false);
const submitted = ref(false); // Used for form validation
const editing = ref(false); // To determine if we are editing or creating
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    designation: '',
 immatriculation: '',
    date_mise_en_service: null, // Add date_mise_en_service
    type: null, // Add type
 description: null,
    region_id: null,
});

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

const editEngin = (engin) => {
    form.id = engin.id;
    form.designation = engin.designation;
    form.immatriculation = engin.immatriculation;
    form.date_mise_en_service = engin.date_mise_en_service; // Populate date_mise_en_service
    form.type = engin.type; // Populate type
    form.description = engin.description;
    form.region_id = engin.region_id;
    editing.value = true;
    labelDialog.value = true;
};

const saveEngin = () => { // Changed from saveLabel to saveEngin
    submitted.value = true;
    if (!form.designation) {
        return;
    }

    const url = editing.value ? route('engins.update', form.id) : route('engins.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Engin ${editing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du label", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deleteEngin = (engin) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer l'engin "${engin.designation}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('engins.destroy', engin.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Label supprimé avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('engins.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const enginTypesOptions = ref([]);

const loadEnginTypes = () => {
    const defaultTypes = [
        { id: 'camion', name: 'Camion', prefix: 'C' },
        { id: 'moto', name: 'Moto', prefix: 'MT' },
        { id: 'fuso', name: 'Fuso', prefix: 'F' },
        { id: 'vehicule', name: 'Véhicule', prefix: 'V' },
    ];
    const localTypes = JSON.parse(localStorage.getItem('customEnginTypes') || '[]');

    // Merge default types with existing types from props, ensuring uniqueness by name
    const mergedTypes = [...defaultTypes];
    if (props.enginTypes) {
        props.enginTypes.forEach(propType => {
            if (!mergedTypes.some(mt => mt.name === propType.name)) {
                mergedTypes.push({ id: propType.id, name: propType.name, prefix: propType.prefix || '' });
            }
        });
    }

    // Add local types, ensuring uniqueness
    localTypes.forEach(localType => {
        if (!mergedTypes.some(mt => mt.name === localType.name)) {
            mergedTypes.push(localType);
        }
    });

    enginTypesOptions.value = mergedTypes;
};

const handleTypeChange = (event) => {
    const selectedType = enginTypesOptions.value.find(type => type.id === event.value);
    if (selectedType && selectedType.prefix) {
        // Generate a unique code based on the prefix and a UUID
        form.designation = `${selectedType.prefix}-${uuidv4().substring(0, 8).toUpperCase()}`;
    } else {
        form.designation = ''; // Clear if no prefix or type selected
    }
};

onMounted(() => {
    loadEnginTypes();
});

const dialogTitle = computed(() => editing.value ? 'Modifier l\'Engin' : 'Créer un nouvel Engin');

</script>

<template>
    <AppLayout title="Gestion des Engins">
        <Head title="Labels" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">

                                <span class="block mt-2 md:mt-0 p-input-icon-left"> <Button label="Ajouter un engin" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />  <i class="pi pi-search" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="engins" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} engins"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="designation" header="Nom" :sortable="true" headerStyle="width:30%; min-width:10rem;">
                            <template #body="slotProps">

                                {{ slotProps.data.designation }}
                            </template>
                        </Column>
                        <Column field="immatriculation" header="Immatriculation" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.immatriculation }}
                            </template>
                        </Column>
                        <Column field="type" header="Type d'Engin" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.type ? slotProps.data.type : 'N/A' }}
                            </template>
                        </Column>
                        <Column field="region.nom" header="Région" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.region ? slotProps.data.region.nom : 'N/A' }}
                            </template>
                        </Column>
                        <Column field="date_mise_en_service" header="Date d'Acquisition" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ new Date(slotProps.data.date_mise_en_service).toLocaleString('fr-FR', { month: 'long', year: 'numeric' }) }}
                            </template>
                        </Column>
                        <Column field="compteur_horaire" header="Compteur Horaire" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.description }}
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editEngin(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteEngin(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations de l'engin.</span>
  <div class="flex items-center gap-4 mb-4">
                            <label for="type" class="font-semibold w-24">Type d'Engin</label>
                            <Dropdown id="type" v-model="form.type" :options="enginTypesOptions" optionLabel="name" optionValue="id" placeholder="Sélectionner un type" class="flex-auto" @change="handleTypeChange" />
                        </div>
                        <small class="p-error" v-if="form.errors.type">{{ form.errors.type }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="designation" class="font-semibold w-24">Désignation</label>
                            <InputText id="designation" v-model.trim="form.designation" required="true" autofocus
                                :class="{ 'p-invalid': submitted && !form.designation }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.designation">Le nom est requis.</small>
                        <small class="p-error" v-if="form.errors.designation">{{ form.errors.designation }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="immatriculation" class="font-semibold w-24">Immatriculation</label>
                            <InputText id="immatriculation" v-model="form.immatriculation" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-error" v-if="form.errors.immatriculation">{{ form.errors.immatriculation }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="region" class="font-semibold w-24">Région</label>
                            <Dropdown id="region" v-model="form.region_id" :options="regions" optionLabel="nom" optionValue="id" placeholder="Sélectionner une région" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.region_id">{{ form.errors.region_id }}</small>


                        <div class="flex items-center gap-4 mb-4">
                            <label for="date_mise_en_service" class="font-semibold w-24">Date d'Acquisition</label>
                            <Calendar id="date_mise_en_service" v-model="form.date_mise_en_service"  view="month" dateFormat="mm/yy"  showIcon class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.date_mise_en_service">{{ form.errors.date_mise_en_service }}</small>


                        <div class="flex items-center gap-4 mb-4">
                            <label for="compteur_horaire" class="font-semibold w-24">Compteur Horaire</label>
                            <InputText id="compteur_horaire" v-model="form.description"  class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.description">{{ form.errors.description }}</small>

                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveEngin" :loading="form.processing"></Button>
                        </div>
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
