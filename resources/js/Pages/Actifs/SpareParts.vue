<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    spareParts: Object, // Changed from labels to spareParts
    filters: Object,
    regions: Array, // To select a region
    labels: Array, // To select a label for the spare part
    users: Array, // To select a responsible user
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

const sparePartDialog = ref(false); // Changed from labelDialog to sparePartDialog
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    reference: '',
    quantity: 0,
    min_quantity: 0,
    location: '',
    region_id: null, // Added based on fillable
    unity_id: null, // Added based on fillable
    label_id: null, // To link to a label
    characteristic_values: {}, // To store values for label characteristics
    user_id: null, // Default to current user
});

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    sparePartDialog.value = true;
};

const hideDialog = () => {
    sparePartDialog.value = false;
    submitted.value = false;
};

const editSparePart = (sparePart) => { // Changed from editLabel to editSparePart
    form.id = sparePart.id;
    form.reference = sparePart.reference;
    form.quantity = sparePart.quantity;
    form.min_quantity = sparePart.min_quantity;
    form.location = sparePart.location;
    form.region_id = sparePart.region_id; // Added based on fillable
    form.unity_id = sparePart.unity_id; // Added based on fillable
    form.user_id = sparePart.user_id; // Added based on fillable
    form.label_id = sparePart.label_id;
    form.characteristic_values = sparePart.spare_part_characteristics ?
        sparePart.spare_part_characteristics.reduce((acc, char) => {
            acc[char.label_characteristic_id] = char.value;
            return acc;
        }, {}) : {};
    editing.value = true;
    sparePartDialog.value = true;
};

const saveSparePart = () => { // Changed from saveLabel to saveSparePart
    submitted.value = true;
    if (!form.reference || form.quantity === null || form.min_quantity === null ||  !form.user_id) {
        return;
    }

    // Validate required characteristics
    const selectedLabel = props.labels.find(l => l.id === form.label_id);
    if (selectedLabel && selectedLabel.label_characteristics) {
        for (const char of selectedLabel.label_characteristics) {
            if (char.is_required && !form.characteristic_values[char.id]) {
                toast.add({ severity: 'error', summary: 'Erreur', detail: `La caractéristique "${char.name}" est requise.`, life: 3000 });
                return;
            }
        }
    }

    submitForm();
};

const submitForm = () => { // This function now handles spare part submission
    const url = editing.value ? route('spare-parts.update', form.id) : route('spare-parts.store'); // Changed routes
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data, // Send all form data
    })).submit(method, url, {
        onSuccess: () => {
            sparePartDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Pièce de rechange ${editing.value ? 'mise à jour' : 'créée'} avec succès`, life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la pièce de rechange", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
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
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('spare-parts.destroy', sparePart.id), { // Changed route
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

// Computed property to get characteristics of the selected label
const selectedLabelCharacteristics = computed(() => {
    if (form.label_id) {
        const label = props.labels.find(l => l.id === form.label_id);
        return label ? label.label_characteristics : [];
    }
    return [];
});

const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('spare-parts.index'), { search: search.value }, { // Changed route
            preserveState: true,
            replace: true,
        });
    }, 300);
};

onMounted(() => { // No specific onMounted logic needed for this refactor
    // The controller should return spare parts with their characteristic values
    // and also all available labels with their characteristics.
    // Example: SparePart::with('sparePartCharacteristics.labelCharacteristic'), Label::with('labelCharacteristics')
});

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
                                    <Button label="Ajouter un equipement detachee" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />  <i class="pi pi-search" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="spareParts.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} labels"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="reference" header="Référence" :sortable="true" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.reference }}
                            </template>
                        </Column>
                        <Column field="quantity" header="Quantité" :sortable="true" headerStyle="width:10%; min-width:6rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.quantity }}
                            </template>
                        </Column>
                        <Column field="min_quantity" header="Min. Quantité" :sortable="true" headerStyle="width:10%; min-width:6rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.min_quantity }}
                            </template>
                        </Column>
                        <Column field="location" header="Emplacement" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.location }} / {{ slotProps.data.region?.designation }}
                            </template>
                        </Column>
                        <Column field="label.designation" header="Type de Pièce" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.label?.designation" :style="{ backgroundColor: slotProps.data.label?.color }" />
                            </template>
                        </Column>
                        <Column field="user.name" header="Responsable" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.user?.name }}
                            </template>
                        </Column>
                        <Column header="Caractéristiques" headerStyle="width:20%; min-width:12rem;">
                            <template #body="slotProps">
                                <div class="flex flex-wrap gap-1">
                                    <Tag v-for="char_val in slotProps.data.spare_part_characteristics" :key="char_val.id"
                                        :value="`${char_val.label_characteristic.name}: ${char_val.value}`" severity="info"></Tag>
                                </div>
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editSparePart(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteSparePart(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="sparePartDialog" modal :header="dialogTitle" :style="{ width: '50rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations de la pièce de rechange.</span>
                        <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">Créez une nouvelle pièce de rechange.</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="reference" class="font-semibold w-24">Référence</label>
                            <InputText id="reference" v-model.trim="form.reference" required="true"
                                :class="{ 'p-invalid': submitted && !form.reference }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.reference">La référence est requise.</small>
                        <small class="p-error" v-if="form.errors.reference">{{ form.errors.reference }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="quantity" class="font-semibold w-24">Quantité</label>
                            <InputNumber id="quantity" v-model="form.quantity" required="true" :min="0"
                                :class="{ 'p-invalid': submitted && form.quantity === null }" class="flex-auto" />
                        </div>
                        <small class="p-invalid" v-if="submitted && form.quantity === null">La quantité est requise.</small>
                        <small class="p-error" v-if="form.errors.quantity">{{ form.errors.quantity }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="min_quantity" class="font-semibold w-24">Quantité Min.</label>
                            <InputNumber id="min_quantity" v-model="form.min_quantity" required="true" :min="0"
                                :class="{ 'p-invalid': submitted && form.min_quantity === null }" class="flex-auto" />
                        </div>
                        <small class="p-invalid" v-if="submitted && form.min_quantity === null">La quantité minimale est requise.</small>
                        <small class="p-error" v-if="form.errors.min_quantity">{{ form.errors.min_quantity }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="location" class="font-semibold w-24">Emplacement</label>
                            <InputText id="location" v-model.trim="form.location" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-error" v-if="form.errors.location">{{ form.errors.location }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="region_id" class="font-semibold w-24">Région</label>
                            <Dropdown id="region_id" v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" placeholder="Sélectionner une région" class="flex-auto"
                                />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.region_id">La région est requise.</small>
                        <small class="p-error" v-if="form.errors.region_id">{{ form.errors.region_id }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="label_id" class="font-semibold w-24">Type de Pièce</label>
                            <Dropdown id="label_id" v-model="form.label_id" :options="labels" optionLabel="designation" optionValue="id" placeholder="Sélectionner un type" class="flex-auto"
                                />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.label_id">Le type de pièce est requis.</small>
                        <small class="p-error" v-if="form.errors.label_id">{{ form.errors.label_id }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="user_id" class="font-semibold w-24">Responsable</label>
                            <Dropdown id="user_id" v-model="form.user_id" :options="users" optionLabel="name" optionValue="id" placeholder="Sélectionner un responsable" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.user_id }" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.user_id">Le responsable est requis.</small>
                        <small class="p-error" v-if="form.errors.user_id">{{ form.errors.user_id }}</small>

                        <Divider />

                        <!-- Section pour les valeurs des caractéristiques du label sélectionné -->
                        <div v-if="selectedLabelCharacteristics.length > 0" class="field">
                            <label class="font-semibold">Valeurs des Caractéristiques</label>
                            <div v-for="characteristic in selectedLabelCharacteristics" :key="characteristic.id" class="flex items-center gap-4 mb-2">
                                <label :for="`char-${characteristic.id}`" class="w-48">{{ characteristic.name }} <span v-if="characteristic.is_required" class="text-red-500">*</span></label>
                                <div class="flex-auto">
                                    <InputText v-if="characteristic.type === 'text' || characteristic.type === 'image'"
                                        :id="`char-${characteristic.id}`" v-model="form.characteristic_values[characteristic.id]"
                                        :class="{ 'p-invalid': submitted && characteristic.is_required && !form.characteristic_values[characteristic.id] }" />
                                    <InputNumber v-else-if="characteristic.type === 'number'"
                                        :id="`char-${characteristic.id}`" v-model="form.characteristic_values[characteristic.id]"
                                        :class="{ 'p-invalid': submitted && characteristic.is_required && form.characteristic_values[characteristic.id] === null }" />
                                    <Calendar v-else-if="characteristic.type === 'date'"
                                        :id="`char-${characteristic.id}`" v-model="form.characteristic_values[characteristic.id]" dateFormat="dd/mm/yy"
                                        :class="{ 'p-invalid': submitted && characteristic.is_required && !form.characteristic_values[characteristic.id] }" />
                                    <Checkbox v-else-if="characteristic.type === 'boolean'" :binary="true"
                                        :id="`char-${characteristic.id}`" v-model="form.characteristic_values[characteristic.id]" />
                                    <!-- Add Dropdown for 'select' type if options are available -->
                                    <small class="p-invalid" v-if="submitted && characteristic.is_required && !form.characteristic_values[characteristic.id]">
                                        {{ characteristic.name }} est requis.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveSparePart" :loading="form.processing"></Button>
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
