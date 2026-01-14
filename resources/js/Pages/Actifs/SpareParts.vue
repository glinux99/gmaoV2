<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import InputNumber from 'primevue/inputnumber'; // Import nécessaire pour InputNumber
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';

// --- NOUVEAU: Import pour la fonctionnalité d'import ---
import FileUpload from 'primevue/fileupload';

const props = defineProps({
    spareParts: Object, // Changed from labels to spareParts
    filters: Object,
    regions: Array, // To select a region
    labels: Array, // To select a label for the spare part
    users: Array, // To select a responsible user
    sparePartCharacteristics: Array, // Existing characteristics for the spare part
    queryParams: Object,
    // --- NOUVEAU: Propriété pour les erreurs d'importation ---
    import_errors: Array,
    sparePartStats: Object, // NOUVEAU: Propriété pour les statistiques
    import_errors: Array,
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

const sparePartDialog = ref(false); // Changed from labelDialog to sparePartDialog
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
// --- NOUVEAU: Référence pour la boîte de dialogue d'importation ---
const importDialog = ref(false);

const selectedRegion = ref(props.filters?.region_id || null);
const selectedSpareParts = ref([]);
const loading = ref(false);

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

// --- NOUVEAU: Formulaire pour l'importation ---
const importForm = useForm({
    file: null,
    region_id: null, // Pour assigner une région par défaut lors de l'import
});

const filters = ref();

const initFilters = () => {
    filters.value = {
        'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'reference': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'label.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'location': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
};
initFilters();

const lazyParams = ref({
    first: props.spareParts.from - 1,
    rows: props.spareParts.per_page,
    sortField: props.queryParams?.sortField || 'reference',
    sortOrder: props.queryParams?.sortOrder === 'desc' ? -1 : 1,
    filters: filters.value
});

const onPage = (event) => {
    loading.value = true;
    lazyParams.value = event;
    const queryParams = {
        ...event,
        page: event.page + 1,
        per_page: event.rows,
        search: search.value,
        region_id: selectedRegion.value,
    };

    router.get(route('spare-parts.index'), queryParams, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const op = ref();

const allColumns = ref([
    { field: 'reference', header: 'REF' },
    { field: 'label.designation', header: 'Composant' },
    { field: 'quantity', header: 'Stock / Alerte' },
    { field: 'price', header: 'Prix' },
    { field: 'location', header: 'Emplacement' },
    { field: 'region.designation', header: 'Région' },
    { field: 'user.name', header: 'Responsable' },
]);
const visibleColumns = ref(allColumns.value.slice(0, 5).map(col => col.field));

// --- NOUVEAU: Logique pour l'importation ---
const openImportDialog = () => {
    importForm.reset();
    importDialog.value = true;
};

const importSpareParts = (event) => {
    const file = event.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    if (importForm.region_id) {
        formData.append('region_id', importForm.region_id);
    }

    router.post(route('spare-parts.import'), formData, {
        onSuccess: () => {
            importDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('spareParts.toast.importStarted'), life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l\'envoi du fichier.', life: 3000 });
        }
    });
};

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

const deleteSelectedSpareParts = () => {
    confirm.require({
        message: t('spareParts.confirm.bulkDeleteMessage'),
        header: t('confirm.deleteHeader'),
        accept: () => {
            router.post(route('spare-parts.bulkDestroy'), {
                ids: selectedSpareParts.value.map(p => p.id)
            }, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('toast.success'), detail: t('spareParts.toast.bulkDeleteSuccess'), life: 3000 });
                    selectedSpareParts.value = [];
                }
            });
        }
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
        router.get(route('spare-parts.index'), { search: search.value, region_id: selectedRegion.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

onMounted(() => {
    // Logic for spare parts loading
    // --- NOUVEAU: Afficher les erreurs d'importation au chargement de la page ---
    if (props.import_errors && props.import_errors.length > 0) {
        toast.add({ severity: 'error', summary: 'Erreurs d\'importation', detail: 'Certaines lignes du fichier n\'ont pas pu être traitées.', life: 10000 });
    }
});

const formatCurrency = (value) => {
 return value.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
};

const dialogTitle = computed(() => editing.value ? t('spareParts.dialog.editTitle') : t('spareParts.dialog.createTitle'));
const bulkDeleteButtonIsDisabled = computed(() => !selectedSpareParts.value || selectedSpareParts.value.length < 2);

</script>
<template>
    <AppLayout :title="t('spareParts.title')">
        <Head :title="t('spareParts.headTitle')" />

        <div class="p-6 max-w-[1600px] mx-auto">
            <Toast />
            <ConfirmDialog />
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                 <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-primary-600 shadow-xl shadow-primary-200 text-white text-2xl">
                         <i class="pi pi-box"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                            {{ t('spareParts.title') }} <span class="text-primary-600">GMAO</span>
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('spareParts.headTitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <!-- NOUVEAU: Bouton d'importation -->
                    <Button :label="t('common.import')" icon="pi pi-upload" severity="secondary" outlined @click="openImportDialog" />
                    <Button :label="t('spareParts.toolbar.add')" icon="pi pi-plus"
                            class=" shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-box text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ sparePartStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pièces Uniques</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center"><i class="pi pi-exclamation-triangle text-2xl text-red-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ sparePartStats.lowStock }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stock Faible</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-wallet text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ formatCurrency(sparePartStats.totalValue) }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Valeur Totale Stock</div>
                    </div>
                </div>
            </div>

            <!-- NOUVEAU: Affichage des erreurs d'importation -->
            <div v-if="import_errors && import_errors.length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <h3 class="font-bold">Erreurs lors de la dernière importation :</h3>
                <ul class="list-disc list-inside mt-2 text-sm">
                    <li v-for="(error, index) in import_errors" :key="index">{{ error }}</li>
                </ul>
            </div>






            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="spareParts.data" ref="dt" dataKey="id" v-model:selection="selectedSpareParts" :paginator="true"
                           :rows="spareParts.per_page"
                           :lazy="true" @page="onPage($event)" @sort="onPage($event)"
                           :totalRecords="spareParts.total" :loading="loading"
                           v-model:first="lazyParams.first"
                           :sortField="lazyParams.sortField" :sortOrder="lazyParams.sortOrder"
                           :rowsPerPageOptions="[10, 25, 50, 100]"
                           v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['reference', 'label.designation', 'location', 'region.designation']"
                           paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
                           :currentPageReportTemplate="t('spareParts.table.report')"
                           class="p-datatable-sm quantum-table" >

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                 <template #header>
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">

        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">

            <Dropdown
                v-model="selectedRegion"
                :options="props.regions"
                optionLabel="designation"
                optionValue="id"
                :placeholder="t('spareParts.toolbar.filterByRegion')"
                showClear
                @change="performSearch"
                class="w-full md:w-64 h-11 !rounded-2xl !border-slate-200 !bg-slate-50/50 focus:!ring-2 focus:!ring-primary-500/20 focus:!bg-white transition-all duration-200"
            />

            <IconField iconPosition="left" class="w-full md:w-80">
                <InputIcon class="pi pi-search text-slate-400" />
                <InputText
                    v-model="search"
                    @input="performSearch"
                    :placeholder="t('spareParts.toolbar.searchPlaceholder')"
                    class="w-full h-11 rounded-2xl border-slate-200 bg-slate-50/50 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-all duration-200"
                />
            </IconField>
        </div>

        <div class="flex items-center gap-2">
            <Button
                v-if="!bulkDeleteButtonIsDisabled"
                :label="`Supprimer (${selectedSpareParts.length})`"
                icon="pi pi-trash"
                severity="danger"
                @click="deleteSelectedSpareParts"
                class="rounded-xl h-11"
            />

            <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl h-11" v-tooltip.bottom="t('common.resetFilters')" />
            <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" class="h-11 w-11" v-tooltip.bottom="t('common.export')" />
            <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" class="h-11 w-11" v-tooltip.bottom="'Colonnes'" />
        </div>

    </div>
</template>

                    <Column v-if="visibleColumns.includes('reference')" field="reference" header="REF" :sortable="true" style="min-width: 8rem;">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-md">{{ data.reference }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('label.designation')" field="label.designation" header="Composant" :sortable="true" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-8 rounded-full" :style="{ background: data.label?.color }"></div>
                                <div>
                                    <div class="text-sm font-black text-slate-700 uppercase">{{ data.label?.designation }}</div>
                                    <div class="text-[10px] text-slate-400 font-bold italic">{{ data.location }}</div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('quantity')" field="quantity" header="Stock / Alerte" :sortable="true" style="min-width: 10rem;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <span :class="['text-base font-black', data.quantity <= data.min_quantity ? 'text-rose-500' : 'text-primary-600']">
                                    {{ data.quantity }}
                                </span>
                                <div class="h-1 w-12 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-slate-300" :style="{ width: (data.min_quantity/data.quantity*100) + '%' }"></div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('price')" field="price" header="Prix" :sortable="true" style="min-width: 8rem;">
                        <template #body="{ data }">
                            <span class="font-bold text-slate-500">{{ formatCurrency(data.price) }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('location')" field="location" header="Emplacement" :sortable="true" style="min-width: 10rem;"></Column>

                    <Column v-if="visibleColumns.includes('region.designation')" field="region.designation" header="Région" :sortable="true" style="min-width: 10rem;">
                        <template #body="{ data }">
                            {{ data.region?.designation }}                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('user.name')" field="user.name" header="Responsable" :sortable="true" style="min-width: 10rem;">
                        <template #body="{ data }">{{ data.user?.name }}</template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editSparePart(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteSparePart(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" v-tooltip.top="'Supprimer'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
            <OverlayPanel ref="op" class="quantum-overlay">
                <div class="p-2 space-y-3">
                    <span class="text-[10px] font-black uppercase text-slate-400 block border-b pb-2">Colonnes actives</span>
                    <MultiSelect v-model="visibleColumns" :options="allColumns" optionLabel="header" optionValue="field"
                                 display="chip" class="w-64 quantum-multiselect" />
                </div>
            </OverlayPanel>

        <Dialog
    v-model:visible="sparePartDialog"
    modal
    :header="false"
    :closable="false"
    :style="{ width: '65rem' }"
    class="p-0 overflow-hidden shadow-2xl"
    :pt="{
        root: { class: 'border-none rounded-3xl bg-slate-50' },
        mask: { style: 'backdrop-filter: blur(8px)' }
    }"
>
    <div class="bg-slate-900 p-6 flex justify-between items-center text-white rounded-xl">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary-500 rounded-2xl shadow-lg shadow-primary-500/20">
                <i class="pi pi-box text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-black uppercase tracking-tight m-0">{{ dialogTitle }}</h2>
                <p class="text-xs text-slate-400 m-0">{{ editing ? t('spareParts.dialog.editSubtitle') : t('spareParts.dialog.createSubtitle') }}</p>
            </div>
        </div>
        <Button icon="pi pi-times" @click="hideDialog" text rounded class="text-white hover:bg-white/10" />
    </div>

    <div class="p-6">
        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 lg:col-span-7 space-y-6">

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200/60">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                        Informations Générales
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600 ml-1">{{ t('spareParts.form.partType') }}</label>
                            <Dropdown v-model="form.label_id" :options="labels" optionLabel="designation" optionValue="id"
                                class="w-full !rounded-xl !border-slate-200 !bg-slate-50/50" />
                            <small class="p-error" v-if="form.errors.label_id">{{ form.errors.label_id }}</small>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600 ml-1">{{ t('spareParts.form.reference') }}</label>
                            <InputText v-model.trim="form.reference" class="!rounded-xl !border-slate-200 font-mono font-bold" />
                            <small class="p-error" v-if="form.errors.reference">{{ form.errors.reference }}</small>
                        </div>

                        <div class="flex flex-col gap-2 col-span-2 mt-2">
                            <label class="font-bold text-sm text-slate-600 ml-1">{{ t('spareParts.form.responsible') }}</label>
                            <Dropdown v-model="form.user_id" :options="users" optionLabel="name" optionValue="id"
                                class="w-full !rounded-xl !border-slate-200" />
                        </div>
                    </div>
                </div>

                <div v-if="selectedLabelCharacteristics.length > 0" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200/60">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                        <i class="pi pi-sliders-h text-primary-500"></i>
                        Spécifications Techniques
                    </h3>

                    <div class="grid grid-cols-2 gap-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="char in selectedLabelCharacteristics" :key="char.id" class="flex flex-col gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <label class="text-[11px] font-black text-slate-500 uppercase">{{ char.name }} <span v-if="char.is_required" class="text-red-500">*</span></label>

                            <InputText v-if="['text', 'image'].includes(char.type)" v-model="form.characteristic_values[char.id]" class="!p-inputtext-sm !border-none !bg-white !rounded-lg shadow-sm" />
                            <InputNumber v-else-if="char.type === 'number'" v-model="form.characteristic_values[char.id]" class="!border-none !bg-white !rounded-lg shadow-sm" />
                            <Calendar v-else-if="char.type === 'date'" v-model="form.characteristic_values[char.id]" dateFormat="dd/mm/yy" class="!border-none !bg-white !rounded-lg shadow-sm" />
                            <InputSwitch v-else-if="char.type === 'boolean'" v-model="form.characteristic_values[char.id]" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-5 space-y-6">

                <div class="bg-primary-600 p-8 rounded-xl shadow-xl shadow-primary-500/20 text-white relative overflow-hidden">
                    <i class="pi pi-chart-line absolute -right-4 -bottom-4 text-8xl opacity-10 rotate-12"></i>

                   <div class="relative z-10 p-4 bg-white/5 rounded-xl backdrop-blur-sm border border-white/10">
    <div class="mb-6">
        <label class="text-[10px] font-black uppercase opacity-60 mb-1 block tracking-tighter">
            {{ t('spareParts.form.price') }} (EUR)
        </label>
        <InputNumber
            v-model="form.price"
            mode="currency"
            currency="EUR"
            locale="fr-FR"
            class="w-full"
            :pt="{
                input: { class: 'bg-transparent border-none text-white text-4xl font-black p-0 focus:ring-0 shadow-none selection:bg-blue-500/30' }
            }"
        />
    </div>

    <div class="grid grid-cols-2 gap-0 mt-4 pt-4 border-t border-white/10">
        <div class="flex flex-col gap-1 text-center border-r border-white/10 px-2">
            <span class="text-[9px] font-bold opacity-50 uppercase tracking-widest">Actuel</span>
            <InputNumber
                v-model="form.quantity"
                :min="0"
                class="w-full"
                :pt="{
                    input: { class: 'bg-transparent border-none text-white text-xl font-bold p-0 text-center focus:ring-0 shadow-none' }
                }"
            />
        </div>

        <div class="flex flex-col gap-1 text-center px-2">
            <span class="text-[9px] font-bold opacity-50 uppercase tracking-widest">Alerte Min</span>
            <InputNumber
                v-model="form.min_quantity"
                :min="0"
                class="w-full"
                :pt="{
                    input: { class: 'bg-transparent border-none text-orange-400 text-xl font-bold p-0 text-center focus:ring-0 shadow-none' }
                }"
            />
        </div>
    </div>
</div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200/60">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Emplacement</h3>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600">{{ t('spareParts.form.region') }}</label>
                            <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" class="!rounded-xl !bg-slate-50/50" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600">{{ t('spareParts.form.location') }}</label>
                            <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-200 shadow-inner">
                                <i class="pi pi-map-marker text-primary-500 ml-2"></i>
                                <InputText v-model.trim="form.location" class="!border-none !bg-transparent w-full font-bold text-sm" placeholder="Rayon / Étagère" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-end items-center gap-3 p-4 bg-white border-t border-slate-100">
            <Button :label="t('dialog.cancel')" icon="pi pi-times" @click="hideDialog" text severity="secondary" class="!rounded-xl font-bold" />
            <Button :label="t('dialog.save')" icon="pi pi-check-circle" @click="saveSparePart" :loading="form.processing"
                class="!rounded-xl !bg-slate-900 !border-none !px-8 shadow-lg shadow-slate-200 font-bold" />
        </div>
    </template>
</Dialog>

        <!-- NOUVEAU: Boîte de dialogue pour l'importation -->
        <Dialog v-model:visible="importDialog" modal header="Importer des Pièces de Rechange" :style="{ width: '40rem' }">
            <div class="p-fluid">
                <p class="mb-4 text-sm text-slate-600">
                    Importez des pièces de rechange via un fichier CSV ou Excel. Assurez-vous que votre fichier respecte la structure suivante : <br>
                    <code class="bg-slate-100 p-1 rounded text-xs">Référence, Quantité, Quantité_Min, Prix, Emplacement, Région_ID, Label_ID, User_ID</code>
                </p>
                <div class="field">
                    <label for="import-region">Région par défaut (Optionnel)</label>
                    <Dropdown
                        id="import-region"
                        v-model="importForm.region_id"
                        :options="props.regions"
                        optionLabel="designation"
                        optionValue="id"
                        placeholder="Assigner à une région"
                        class="w-full"
                    />
                    <small class="text-slate-500">Toutes les pièces importées seront assignées à cette région si elle est sélectionnée.</small>
                </div>
                <div class="field mt-4">
                    <label for="file-upload">Fichier CSV</label>
                    <FileUpload
                        name="file"
                        @select="onFileSelect"
                        :multiple="false"
                        accept=".csv,.txt,.xls,.xlsx"
                        :maxFileSize="1000000"
                        chooseLabel="Choisir un fichier"
                        :auto="true"
                        customUpload
                        @uploader="importSpareParts"
                    >
                        <template #empty>
                            <p>Glissez-déposez un fichier ici pour le téléverser.</p>
                        </template>
                    </FileUpload>
                </div>
            </div>
        </Dialog>
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
