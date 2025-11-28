<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    sparePartMovements: Object, // Changed from spareParts to sparePartMovements
    filters: Object,
    regions: Array,
    unities: Array,
    spareParts: Array, // To select a spare part for the movement
    users: Array, // To select a user for the movement
    sparePartCharacteristics: Array, // Existing characteristics for the spare part
});

const characteristicTypes = ref([
    { label: 'Texte', value: 'text' },
    { label: 'Nombre', value: 'number' },
    { label: 'Date', value: 'date' },
    { label: 'image', value: 'text' },
    { label: 'Boolean', value: 'boolean' },
    { label: 'Liste déroulante', value: 'select' },
]);

const toast = useToast();
const confirm = useConfirm();
const page = usePage();
const sparePartMovementDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    spare_part_id: null,
    user_id: null,
    type: null, // 'entree' or 'sortie'
    quantity: 0,
    location: null,
    region_id: null,
    unity_id: null,
    notes: null,
});

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    sparePartMovementDialog.value = true;
};

const hideDialog = () => {
    sparePartMovementDialog.value = false;
    submitted.value = false;
};

const editSparePartMovement = (movement) => {
    form.id = movement.id;
    form.spare_part_id = movement.spare_part_id;
    form.user_id = movement.user_id;
    form.type = movement.type;
    form.quantity = movement.quantity;
    form.location = movement.location;
    form.region_id = movement.region_id;
    form.unity_id = movement.unity_id;
    form.notes = movement.notes;
    editing.value = true;
    sparePartMovementDialog.value = true;
};

const saveSparePartMovement = () => {
    submitted.value = true;
    if (!form.spare_part_id || !form.user_id || !form.type || form.quantity === null || form.quantity <= 0) {
        return;
    }

    submitForm();
};

const submitForm = () => {
    const url = editing.value ? route('spare-part-movements.update', form.id) : route('spare-part-movements.store');
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data, // Send all form data
    })).submit(method, url, {
        onSuccess: () => {
            sparePartMovementDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Succès',
                detail: `Mouvement de pièce de rechange ${editing.value ? 'mis à jour' : 'créé'} avec succès`,
                life: 3000
            });
            form.reset();
        },
        onError: (errors) => {
            console.error('Erreur lors de la sauvegarde du mouvement de pièce de rechange', errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la sauvegarde.', life: 3000 });
        }
    });
};

const deleteSparePart = (sparePart) => { // Changed from deleteLabel to deleteSparePart
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la pièce de rechange "${sparePart.reference}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger', // Changed from deleteLabel to deleteSparePartMovement
        accept: () => {
            router.delete(route('spare-part-movements.destroy', sparePart.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Pièce de rechange supprimée avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression de la pièce de rechange.', life: 3000 });
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
    timeoutId = setTimeout(() => { // Changed route
        router.get(route('spare-part-movements.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const movementTypes = ref([
    { label: 'Entrée', value: 'entree' },
    { label: 'Sortie', value: 'sortie' },
]);

const formattedSpareParts = computed(() => {
    if (!form.region_id) {
        return [];
    }
    return props.spareParts
        .filter(sp => sp.region_id === form.region_id)
        .map(sp => {
            const characteristicsValues = sp.characteristics
                ?.map(char => char.value)
                .filter(value => value) // Garder uniquement les valeurs non vides
                .join(' / ');

            const label = [sp.reference, characteristicsValues, `Stock: ${sp.quantity}`].filter(Boolean).join(' / ');
            return { ...sp, formattedLabel: label };
        });
});

const getSparePartQuantity = (sparePartId) => {
    const sparePart = props.spareParts.find(sp => sp.id === sparePartId);
    return sparePart ? sparePart.quantity : 'N/A';
};

const dialogTitle = computed(() => editing.value ? 'Modifier la Pièce de Rechange' : 'Créer une nouvelle Pièce de Rechange');

</script>

<template>
    <AppLayout title="Gestion des Pièces de Rechange">
        <Head title="Labels" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">

                                <span class="block mt-2 md:mt-0 p-input-icon-left flex align-items-center gap-2">
                                    <Button label="Ajouter un mouvement" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />  <i class="pi pi-search" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="sparePartMovements.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} labels"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="spare_part.reference" header="Pièce de Rechange" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.spare_part?.reference }}
                            </template>
                        </Column>
                        <Column field="user.name" header="Utilisateur" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.user?.name }}
                            </template>
                        </Column>
                        <Column field="type" header="Type" :sortable="true" headerStyle="width:10%; min-width:8rem;">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.type === 'entree' ? 'Entrée' : 'Sortie'"
                                    :severity="slotProps.data.type === 'entree' ? 'success' : 'danger'" />
                            </template>
                        </Column>
                        <Column field="quantity" header="Quantité" :sortable="true" headerStyle="width:10%; min-width:6rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.quantity }}
                            </template>
                        </Column>
                        <Column field="location" header="Emplacement" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.location }} <span v-if="slotProps.data.spare_part?.region?.designation">/ {{ slotProps.data.spare_part?.region?.designation }}</span>
                            </template>
                        </Column>
                        <Column field="notes" header="Notes" :sortable="true" headerStyle="width:20%; min-width:12rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.notes }}
                            </template>
                        </Column>

                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editSparePartMovement(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteSparePart(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="sparePartMovementDialog" modal :header="dialogTitle" :style="{ width: '50rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations du mouvement de pièce de rechange.</span>
                        <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">Créez un nouveau mouvement de pièce de rechange.</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="region_id" class="font-semibold w-24">Région</label>
                            <Dropdown id="region_id" v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" placeholder="Sélectionner une région" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.region_id }" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.region_id">La région est requise.</small>
                        <small class="p-error" v-if="form.errors.region_id">{{ form.errors.region_id }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="spare_part_id" class="font-semibold w-24">Pièce de Rechange</label>

                            <Dropdown id="spare_part_id" v-model="form.spare_part_id" :options="formattedSpareParts" optionLabel="formattedLabel" optionValue="id" placeholder="Sélectionner une pièce" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.spare_part_id }" :disabled="!form.region_id" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.spare_part_id">La pièce de rechange est requise.</small>


                        <div class="flex items-center gap-4 mb-4">
                            <label for="user_id" class="font-semibold w-24">Utilisateur</label>
                            <Dropdown id="user_id" v-model="form.user_id" :options="users" optionLabel="name" optionValue="id" placeholder="Sélectionner un utilisateur" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.user_id }" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.user_id">L'utilisateur est requis.</small>
                        <small class="p-error" v-if="form.errors.user_id">{{ form.errors.user_id }}</small>
F
                        <div class="flex items-center gap-4 mb-4">
                            <label for="type" class="font-semibold w-24">Type de Mouvement</label>
                            <Dropdown id="type" v-model="form.type" :options="movementTypes" optionLabel="label" optionValue="value" placeholder="Sélectionner un type" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.type }" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.type">Le type de mouvement est requis.</small>
                        <small class="p-error" v-if="form.errors.type">{{ form.errors.type }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="quantity" class="font-semibold w-24">Quantité</label>
                            <InputNumber id="quantity" v-model="form.quantity" required="true" :min="1"
                                :class="{ 'p-invalid': submitted && (form.quantity === null || form.quantity <= 0) }" class="flex-auto" />
                        </div>
                        <small class="p-invalid" v-if="submitted && (form.quantity === null || form.quantity <= 0)">La quantité est requise et doit être supérieure à 0.</small>
                        <small class="p-error" v-if="form.errors.quantity">{{ form.errors.quantity }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="location" class="font-semibold w-24">Emplacement</label>
                            <InputText id="location" v-model.trim="form.location" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-error" v-if="form.errors.location">{{ form.errors.location }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="unity_id" class="font-semibold w-24">Unité</label>
                            <Dropdown id="unity_id" v-model="form.unity_id" :options="unities" optionLabel="designation" optionValue="id" placeholder="Sélectionner une unité" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.unity_id }" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.unity_id">L'unité est requise.</small>
                        <small class="p-error" v-if="form.errors.unity_id">{{ form.errors.unity_id }}</small>


                        <div class="flex items-center gap-4 mb-4">
                            <label for="notes" class="font-semibold w-24">Notes</label>
                            <Textarea id="notes" v-model.trim="form.notes" rows="3" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.notes">{{ form.errors.notes }}</small>

                        <Divider />

                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveSparePartMovement" :loading="form.processing"></Button>
                        </div>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Styles spécifiques si nécessaire */
.p-datatable .p-column-header-content {
    justify-content: space-between;
}

.p-colorpicker-preview {
    border-radius: 4px;
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
