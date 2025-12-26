<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import InputNumber from 'primevue/inputnumber'; // Import nécessaire pour InputNumber

const props = defineProps({
    spareParts: Object, // Changed from labels to spareParts
    filters: Object,
    regions: Array, // To select a region
    labels: Array, // To select a label for the spare part
    users: Array, // To select a responsible user
    sparePartCharacteristics: Array, // Existing characteristics for the spare part
});

const characteristicTypes = ref([
    { label: computed(() => t('characteristics.types.text')), value: 'text' },
    { label: computed(() => t('characteristics.types.number')), value: 'number' },
    { label: computed(() => t('characteristics.types.date')), value: 'date' },
    { label: computed(() => t('characteristics.types.image')), value: 'text' },
    { label: computed(() => t('characteristics.types.boolean')), value: 'boolean' },
    { label: computed(() => t('characteristics.types.select')), value: 'select' },
]);
const { t } = useI18n();

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
    // --- NOUVEAU: Ajout du champ prix ---
    price: 0.00,
    // ------------------------------------
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
    // --- MISE À JOUR: Chargement du prix ---
    form.price = sparePart.price;
    // ----------------------------------------
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
    // Ajout de la validation pour 'price'
    if (!form.reference || form.quantity === null || form.min_quantity === null || form.price === null || !form.user_id) {
        return;
    }

    // Validate required characteristics
    const selectedLabel = props.labels.find(l => l.id === form.label_id);
    if (selectedLabel && selectedLabel.label_characteristics) {
        for (const char of selectedLabel.label_characteristics) {
            if (char.is_required && !form.characteristic_values[char.id]) {
                toast.add({ severity: 'error', summary: t('toast.error'), detail: t('spareParts.validation.characteristicRequired', { characteristic: char.name }), life: 3000 });
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
            toast.add({ severity: 'success', summary: t('toast.success'), detail: t(`spareParts.toast.${editing.value ? 'updateSuccess' : 'createSuccess'}`), life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la pièce de rechange", errors);
            toast.add({ severity: 'error', summary: t('toast.error'), detail: t('toast.genericError'), life: 3000 });
        }
    });
};

const deleteSparePart = (sparePart) => { // Changed from deleteLabel to deleteSparePart
    confirm.require({
        message: t('spareParts.confirm.deleteMessage', { reference: sparePart.reference }),
        header: t('confirm.deleteHeader'),
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: t('confirm.cancel'),
        acceptLabel: t('confirm.delete'),
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('spare-parts.destroy', sparePart.id), { // Changed route
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('toast.success'), detail: t('spareParts.toast.deleteSuccess'), life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: t('toast.error'), detail: t('spareParts.toast.deleteError'), life: 3000 });
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

onMounted(() => {
    // Logic for spare parts loading
});

const dialogTitle = computed(() => editing.value ? t('spareParts.dialog.editTitle') : t('spareParts.dialog.createTitle'));

</script>

<template>
    <AppLayout :title="t('spareParts.title')">
        <Head :title="t('spareParts.headTitle')" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">

                                <span class="block mt-2 md:mt-0 p-input-icon-left flex align-items-center gap-2">
                                    <Button :label="t('spareParts.toolbar.add')" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                     <IconField><InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="search" :placeholder="t('spareParts.toolbar.searchPlaceholder')" @input="performSearch" />
            </IconField>
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button :label="t('spareParts.toolbar.export')" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="spareParts.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        :currentPageReportTemplate="t('dataTable.currentPageReport', { first: '{first}', last: '{last}', totalRecords: '{totalRecords}' })"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="reference" header="Référence" :sortable="true" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.reference }}
                            </template>
                        </Column>
                        <Column field="quantity" :header="t('spareParts.table.quantity')" :sortable="true" headerStyle="width:10%; min-width:6rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.quantity }}
                            </template>
                        </Column>
                        <Column field="min_quantity" :header="t('spareParts.table.min_quantity')" :sortable="true" headerStyle="width:10%; min-width:6rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.min_quantity }}
                            </template>
                        </Column>

                        <Column field="price" :header="t('spareParts.table.price')" :sortable="true" headerStyle="width:10%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(slotProps.data.price) }}
                            </template>
                        </Column>
                        <Column field="location" :header="t('spareParts.table.location')" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.location }} / {{ slotProps.data.region?.designation }}
                            </template>
                        </Column>
                        <Column field="label.designation" :header="t('spareParts.table.partType')" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.label?.designation" :style="{ backgroundColor: slotProps.data.label?.color }" />
                            </template>
                        </Column>
                        <Column field="user.name" :header="t('spareParts.table.responsible')" :sortable="true" headerStyle="width:15%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.user?.name }}
                            </template>
                        </Column>
                        <Column :header="t('spareParts.table.characteristics')" headerStyle="width:20%; min-width:12rem;">
                            <template #body="slotProps">
                                <div class="flex flex-wrap gap-1">
                                    <Tag v-for="char_val in slotProps.data.spare_part_characteristics" :key="char_val.id"
                                        :value="`${char_val.label_characteristic.name}: ${char_val.value}`" severity="info"></Tag>
                                </div>
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" :header="t('spareParts.table.actions')">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editSparePart(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteSparePart(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="sparePartDialog" modal :header="dialogTitle" :style="{ width: '50rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">{{ t('spareParts.dialog.editSubtitle') }}</span>
                        <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">{{ t('spareParts.dialog.createSubtitle') }}</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="label_id" class="font-semibold w-24">{{ t('spareParts.form.partType') }}</label>
                            <Dropdown id="label_id" v-model="form.label_id" :options="labels" optionLabel="designation" optionValue="id" :placeholder="t('spareParts.form.partTypePlaceholder')" class="flex-auto"
                                />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.label_id">{{ t('validation.required', { field: t('spareParts.form.partType') }) }}</small>
                        <small class="p-error" v-if="form.errors.label_id">{{ form.errors.label_id }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="reference" class="font-semibold w-24">{{ t('spareParts.form.reference') }}</label>
                            <InputText id="reference" v-model.trim="form.reference" required="true"
                                :class="{ 'p-invalid': submitted && !form.reference }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.reference">{{ t('validation.required', { field: t('spareParts.form.reference') }) }}</small>
                        <small class="p-error" v-if="form.errors.reference">{{ form.errors.reference }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="quantity" class="font-semibold w-24">{{ t('spareParts.form.quantity') }}</label>
                            <InputNumber id="quantity" v-model="form.quantity" required="true" :min="0"
                                :class="{ 'p-invalid': submitted && form.quantity === null }" class="flex-auto" />
                        </div>
                        <small class="p-invalid" v-if="submitted && form.quantity === null">{{ t('validation.required', { field: t('spareParts.form.quantity') }) }}</small>
                        <small class="p-error" v-if="form.errors.quantity">{{ form.errors.quantity }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="min_quantity" class="font-semibold w-24">{{ t('spareParts.form.min_quantity') }}</label>
                            <InputNumber id="min_quantity" v-model="form.min_quantity" required="true" :min="0"
                                :class="{ 'p-invalid': submitted && form.min_quantity === null }" class="flex-auto" />
                        </div>
                        <small class="p-invalid" v-if="submitted && form.min_quantity === null">{{ t('validation.required', { field: t('spareParts.form.min_quantity') }) }}</small>
                        <small class="p-error" v-if="form.errors.min_quantity">{{ form.errors.min_quantity }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="price" class="font-semibold w-24">{{ t('spareParts.form.price') }}</label>
                            <InputNumber id="price" v-model="form.price" required="true" :min="0" :maxFractionDigits="2"
                                :class="{ 'p-invalid': submitted && form.price === null }"
                                class="flex-auto" mode="currency" currency="EUR" locale="fr-FR" />
                        </div>
                        <small class="p-invalid" v-if="submitted && form.price === null">{{ t('validation.required', { field: t('spareParts.form.price') }) }}</small>
                        <small class="p-error" v-if="form.errors.price">{{ form.errors.price }}</small>
                        <div class="flex items-center gap-4 mb-4">
                            <label for="location" class="font-semibold w-24">{{ t('spareParts.form.location') }}</label>
                            <InputText id="location" v-model.trim="form.location" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-error" v-if="form.errors.location">{{ form.errors.location }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="region_id" class="font-semibold w-24">{{ t('spareParts.form.region') }}</label>
                            <Dropdown id="region_id" v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('spareParts.form.regionPlaceholder')" class="flex-auto"
                                />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.region_id">{{ t('validation.required', { field: t('spareParts.form.region') }) }}</small>
                        <small class="p-error" v-if="form.errors.region_id">{{ form.errors.region_id }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="user_id" class="font-semibold w-24">{{ t('spareParts.form.responsible') }}</label>
                            <Dropdown id="user_id" v-model="form.user_id" :options="users" optionLabel="name" optionValue="id" :placeholder="t('spareParts.form.responsiblePlaceholder')" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.user_id }" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.user_id">{{ t('validation.required', { field: t('spareParts.form.responsible') }) }}</small>
                        <small class="p-error" v-if="form.errors.user_id">{{ form.errors.user_id }}</small>

                        <Divider />

                        <div v-if="selectedLabelCharacteristics.length > 0" class="field">
                            <label class="font-semibold">{{ t('spareParts.form.characteristicValues') }}</label>
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
                                    <small class="p-invalid" v-if="submitted && characteristic.is_required && !form.characteristic_values[characteristic.id] || form.characteristic_values[characteristic.id] === null">
                                        {{ t('validation.required', { field: characteristic.name }) }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <div class="flex justify-end gap-2">
                            <Button type="button" :label="t('dialog.cancel')" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" :label="t('dialog.save')" @click="saveSparePart" :loading="form.processing"></Button>
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
