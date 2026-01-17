<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- IMPORTS COMPOSANTS ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Checkbox from 'primevue/checkbox';
import ColorPicker from 'primevue/colorpicker';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';

const { t } = useI18n();
const props = defineProps({
    labels: Object, // Inertia pagination object
    filters: Object,
});

// --- ÉTATS & RÉFÉRENCES ---
const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const labelDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedLabels = ref([]); // Gardé pour la suppression en masse

// --- TYPES DE CARACTÉRISTIQUES ---
const characteristicTypes = ref([
    { label: computed(() => t('labels.types.text')), value: 'text' },
    { label: computed(() => t('labels.types.number')), value: 'number' },
    { label: computed(() => t('labels.types.date')), value: 'date' },
    { label: computed(() => t('labels.types.file')), value: 'file' },
    { label: computed(() => t('labels.types.boolean')), value: 'boolean' },
    { label: computed(() => t('labels.types.select')), value: 'select' },
]);

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    designation: '',
    description: '',
    color: '3B82F6', // Bleu par défaut
    characteristics: [],
});

// --- STATISTIQUES (Calculées sur les labels actuels) ---
const stats = computed(() => {
    const data = props.labels.data || [];
    return {
        total: data.length,
        withChars: data.filter(l => l.label_characteristics?.length > 0).length,
    };
});

// --- GESTION DU DATATABLE LAZY ---
const loading = ref(false);

const lazyParams = ref({
    first: props.labels.from - 1,
    rows: props.labels.per_page,
    sortField: 'created_at',
    sortOrder: -1, // Descendant par défaut
    filters: {
        global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
        designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    }
});

const initFilters = () => {
    lazyParams.value.filters = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
    loadLazyData();
};

const loadLazyData = () => {
    loading.value = true;
    router.get(route('labels.index'), {
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
        per_page: lazyParams.value.rows,
        sortField: lazyParams.value.sortField,
        sortOrder: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
        search: lazyParams.value.filters.global.value,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => { loading.value = false; }
    });
};

const onPage = (event) => {
    lazyParams.value.first = event.first;
    lazyParams.value.rows = event.rows;
    loadLazyData();
};

const onSort = (event) => {
    lazyParams.value.sortField = event.sortField;
    lazyParams.value.sortOrder = event.sortOrder;
    loadLazyData();
};

const onFilter = (event) => {
    lazyParams.value.filters = event.filters;
    loadLazyData();
};

// --- LOGIQUE MÉTIER ---
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true;
};

const editLabel = (label) => {
    form.id = label.id;
    form.designation = label.designation;
    form.description = label.description;
    form.color = label.color.replace('#', '');
    form.characteristics = label.label_characteristics ? JSON.parse(JSON.stringify(label.label_characteristics)) : [];
    editing.value = true;
    labelDialog.value = true;
};

const saveLabel = () => {
    submitted.value = true;
    if (!form.designation) return;

    const hasChangedChars = editing.value && form.isDirty;

    if (hasChangedChars) {
        confirm.require({
            message: t('labels.messages.confirmChange'),
            header: t('common.confirmation'),
            icon: 'pi pi-exclamation-triangle',
            acceptClass: 'p-button-warning',
            accept: () => submitForm(),
        });
    } else {
        submitForm();
    }
};

const submitForm = () => {
    const url = editing.value ? route('labels.update', form.id) : route('labels.store');
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data,
        color: '#' + data.color,
        characteristics: data.characteristics.filter(c => c.name.trim() !== '')
    })).submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: t(`labels.messages.${editing.value ? 'updateSuccess' : 'createSuccess'}`), life: 3000 });
            form.reset();
        },
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: t('common.errorOccurred'), life: 3000 })
    });
};

const deleteLabel = (label) => {
    confirm.require({
        message: t('labels.messages.confirmDelete', { name: label.designation }),
        header: t('common.deleteConfirmation'),
        icon: 'pi pi-exclamation-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('labels.destroy', label.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: t('labels.messages.deleteSuccess'), life: 3000 })
            });
        }
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: t('labels.messages.confirmBulkDelete'),
        header: t('common.deleteConfirmation'),
        icon: 'pi pi-exclamation-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const form = useForm({
                ids: selectedLabels.value.map(l => l.id)
            });
            form.post(route('labels.bulkDestroy'), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Supprimé', detail: t('labels.messages.bulkDeleteSuccess'), life: 3000 });
                    selectedLabels.value = [];
                },
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: t('common.errorOccurred'), life: 3000 })
            });
        }
    });
};

// --- GESTION CARACTÉRISTIQUES ---
const addCharacteristic = () => {
    form.characteristics.push({ id: null, name: '', type: 'text', is_required: false });
};

const removeCharacteristic = (index) => {
    form.characteristics.splice(index, 1);
};

const dialogTitle = computed(() => editing.value ? t('labels.dialog.editTitle') : t('labels.dialog.createTitle'));
</script>

<template>
    <AppLayout title="Gestion des Labels">
        <Head :title="t('labels.title')" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-tags text-2xl text-white"></i>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Configuration des <span class="text-primary-600">Labels</span></h1>
                </div>
                <Button :label="t('labels.actions.add')" icon="pi pi-plus" raised @click="openNew" class="rounded-xl px-6" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t(`labels.stats.${key}`) }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i :class="key === 'total' ? 'pi pi-tags' : 'pi pi-sitemap'" class="text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <Toast />
                <ConfirmDialog />

                <DataTable ref="dt"
                    :value="labels.data"
                    v-model:selection="selectedLabels"
                    dataKey="id"
                    :lazy="true"
                    :paginator="true"
                    :rowsPerPageOptions="[15, 30, 50, 100, 500]"
                    :totalRecords="labels.total"
                    :rows="labels.per_page"
                    :loading="loading"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    @filter="onFilter($event)"
                    v-model:filters="lazyParams.filters"
                    filterDisplay="menu"
                    :globalFilterFields="['designation', 'description']"
                    class="p-datatable-custom"
                    removableSort
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    responsiveLayout="scroll"
                    :currentPageReportTemplate="t('myActivities.table.report')">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="lazyParams.filters['global'].value" :placeholder="t('labels.toolbar.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex gap-2">
                                <Button v-if="selectedLabels.length" :label="`Supprimer (${selectedLabels.length})`" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected" class="rounded-xl" />
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="t('labels.toolbar.resetFilters')" />
                                <Button :label="t('common.export')" icon="pi pi-upload" severity="secondary" text @click="dt.exportCSV()" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column field="designation" :header="t('labels.fields.name')" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.designation[0]" shape="circle" :style="{ backgroundColor: data.color + '20', color: data.color }" class="font-bold" />
                                <span class="font-black text-slate-700">{{ data.designation }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('common.searchByName')" />
                        </template>
                    </Column>

                    <Column :header="t('labels.fields.style')">
                        <template #body="{ data }">
                            <Tag :value="data.color" :style="{ backgroundColor: data.color, color: '#fff' }" class="rounded-lg px-3 border-none" />
                        </template>
                    </Column>

                    <Column field="description" :header="t('labels.fields.description')" class="text-slate-500 text-sm" />

                    <Column :header="t('labels.fields.characteristics')">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="char in data.label_characteristics" :key="char.id" :value="char.name" severity="secondary" class="bg-slate-100 text-slate-600 border-none px-2"></Tag>
                                <span v-if="!data.label_characteristics?.length" class="text-xs italic text-slate-400">Aucune</span>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editLabel(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteLabel(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="labelDialog" modal :header="false" :closable="false"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :contentStyle="{ maxHeight: '85vh', overflow: 'auto' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }">

            <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-tag text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">
                            {{ editing ? t('labels.dialog.editTitle') : t('labels.dialog.createTitle') }}
                        </h2>
                        <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">
                            {{ editing ? t('labels.dialog.editSubtitle') : t('labels.dialog.createSubtitle') }}
                        </span>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="labelDialog = false" class="text-white hover:bg-white/10" v-tooltip.bottom="t('common.cancel')" />
            </div>

            <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                    <div class="md:col-span-12 space-y-8">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('labels.fields.name') }}</label>
                                <InputText v-model="form.designation" class="w-full py-3.5 rounded-xl border-slate-200 shadow-inner" :class="{ 'p-invalid': submitted && !form.designation }" />
                                <small class="text-red-500 font-bold" v-if="submitted && !form.designation">{{ t('labels.validation.nameRequired') }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('labels.fields.color') }}</label>
                                <div class="flex items-center gap-3 p-2 bg-white rounded-xl border border-slate-200">
                                    <ColorPicker v-model="form.color" />
                                    <span class="font-mono text-xs font-bold text-slate-400">#{{ form.color }}</span>
                                </div>
                            </div>

                            <div class="md:col-span-3 space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('labels.fields.description') }}</label>
                                <Textarea v-model="form.description" rows="2" class="w-full rounded-xl border-slate-200" />
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <div>
                                    <h4 class="font-black text-slate-800 m-0">{{ t('labels.fields.technicalAttributes') }}</h4>
                                    <p class="text-xs text-slate-500 m-0">{{ t('labels.fields.technicalAttributesSubtitle') }}</p>
                                </div>
                            </div>

                            <div v-if="form.characteristics.length" class="space-y-4">
                                <div v-for="(char, index) in form.characteristics" :key="index"
                                    class="grid grid-cols-12 items-center gap-3 p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">

                                    <div class="col-span-5">
                                        <InputText v-model="char.name" :placeholder="t('labels.fields.charName')" class="w-full rounded-lg border-slate-200 text-sm" />
                                    </div>

                                    <div class="col-span-4">
                                        <Dropdown v-model="char.type" :options="characteristicTypes" optionLabel="label" optionValue="value" class="w-full rounded-lg border-slate-200 text-sm" :disabled="char.id !== null" />
                                    </div>

                                    <div class="col-span-2 flex items-center justify-center gap-2">
                                        <Checkbox v-model="char.is_required" :binary="true" :inputId="`req-${index}`" />
                                        <label :for="`req-${index}`" class="text-xs font-bold text-slate-500">{{ t('labels.fields.isRequiredShort') }}</label>
                                    </div>

                                    <div class="col-span-1 flex justify-end">
                                        <Button icon="pi pi-trash" severity="danger" text rounded @click="removeCharacteristic(index)" />
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                                <p class="text-slate-400 text-sm italic">Aucune caractéristique définie pour ce label.</p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                             <Button :label="t('labels.actions.addChar')" icon="pi pi-plus" severity="secondary" outlined @click="addCharacteristic" class="rounded-xl" />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="labelDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="editing ? t('common.update') : t('common.save')" icon="pi pi-check-circle" severity="indigo"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                            @click="saveLabel" :loading="form.processing" />
                </div>
                </template>

        </Dialog>
    </AppLayout>
</template>

<style lang="scss">
/* Integration des styles V11 Custom */
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.7rem;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }
    .p-datatable-tbody > tr {
        transition: all 0.2s;
        &:hover { background: #f8fafc !important; }
    }
}

.ultimate-modal {
    .p-dialog-header {
        padding: 2rem 2rem 1rem 2rem;
        .p-dialog-title { font-weight: 900; font-size: 1.25rem; }
    }
    .p-dialog-content { padding: 0 2rem 2rem 2rem; }
}

/* Style spécifique pour le bouton primaire de validation */
.p-button.p-button-primary {
    background: #2563eb !important;
    border: none;
    &:hover { background: #1d4ed8 !important; transform: translateY(-1px); }
}
</style>
