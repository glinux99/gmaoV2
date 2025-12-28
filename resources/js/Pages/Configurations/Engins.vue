<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { v4 as uuidv4 } from 'uuid';
import { useI18n } from 'vue-i18n';

// --- API V11 FILTERS ---
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- COMPOSANTS ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import MultiSelect from 'primevue/multiselect';

const { t } = useI18n();
const props = defineProps({
    engins: Array,
    filters: Object,
    enginTypes: Array,
    regions: Array,
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();

// --- ÉTATS ---
const labelDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const loading = ref(false);

// --- CONFIGURATION DES FILTRES V11 ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    type: { value: null, matchMode: FilterMatchMode.IN }, // Utilise 'IN' pour MultiSelect
    region_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    date_mise_en_service: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        type: { value: null, matchMode: FilterMatchMode.IN },
        region_id: { value: null, matchMode: FilterMatchMode.EQUALS },
        date_mise_en_service: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
    };
};
// --- MAPPING DES ICONES PAR TYPE ---
const typeMetadata = {
    'camion': { icon: 'pi-truck', color: '#3B82F6', bg: '#EFF6FF' },
    'moto': { icon: 'pi-th-large', color: '#10B981', bg: '#ECFDF5' },
    'fuso': { icon: 'pi-box', color: '#F59E0B', bg: '#FFFBEB' },
    'vehicule': { icon: 'pi-car', color: '#8B5CF6', bg: '#F5F3FF' },
    'default': { icon: 'pi-cog', color: '#64748B', bg: '#F8FAFC' }
};

const getMeta = (typeId) => typeMetadata[typeId] || typeMetadata.default;

// --- GESTION DES TYPES ---
const enginTypesOptions = ref([]);
const loadEnginTypes = () => {
    const defaultTypes = [
        { id: 'camion', name: 'Camion', prefix: 'CAM' },
        { id: 'moto', name: 'Moto', prefix: 'MOT' },
        { id: 'fuso', name: 'Fuso', prefix: 'FUS' },
        { id: 'vehicule', name: 'Véhicule', prefix: 'VEH' },
    ];
    enginTypesOptions.value = props.enginTypes?.length ? props.enginTypes : defaultTypes;
};

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    designation: '',
    immatriculation: '',
    date_mise_en_service: null,
    type: null,
    description: null,
    region_id: null,
});

const handleTypeChange = (event) => {
    const selected = enginTypesOptions.value.find(t => t.id === event.value);
    if (selected && selected.prefix && !editing.value) {
        form.designation = `${selected.prefix}-${uuidv4().substring(0, 6).toUpperCase()}`;
    }
};

const openNew = () => {
    form.reset();
    editing.value = false;
    labelDialog.value = true;
};

const editEngin = (engin) => {
    form.clearErrors();
    Object.assign(form, {
        ...engin,
        date_mise_en_service: engin.date_mise_en_service ? new Date(engin.date_mise_en_service) : null
    });
    editing.value = true;
    labelDialog.value = true;
};

const saveEngin = () => {
    submitted.value = true;
    if (!form.designation || !form.type || !form.region_id) return;

    const url = editing.value ? route('engins.update', form.id) : route('engins.store');
    form.transform(data => ({
        ...data,
        date_mise_en_service: data.date_mise_en_service ? data.date_mise_en_service.toISOString().split('T')[0] : null,
    })).submit(editing.value ? 'put' : 'post', url, { // Correction: 'put' pour la mise à jour
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: editing.value ? t('engins.toast.updateSuccess') : t('engins.toast.createSuccess'), life: 3000 });
        }
    });
};

const deleteEngin = (engin) => {
    confirm.require({
        message: t('engins.confirm.deleteMessage', { name: engin.designation }),
        header: t('engins.confirm.header'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('engins.destroy', engin.id));
        }
    });
};

onMounted(() => loadEnginTypes());
</script>

<template>
    <AppLayout>
        <Head :title="t('engins.title')" />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center shadow-xl shadow-primary-100 rotate-3">
                        <i class="pi pi-truck text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">{{ t('engins.title') }}</h1>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">{{ t('engins.subtitle', { count: engins?.length || 0 }) }}</p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button :label="t('engins.toolbar.resetFilters')" icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" />
                    <Button icon="pi pi-download" text severity="secondary" @click="dt.exportCSV()" :aria-label="t('engins.toolbar.export')" />
                    <Button :label="t('engins.toolbar.addEngin')" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable
                    ref="dt" :value="engins" v-model:filters="filters"
                    dataKey="id" :paginator="true" :rows="15" filterDisplay="menu"
                    :globalFilterFields="['designation', 'immatriculation', 'type']"
                    :paginatorTemplate="t('dataTable.paginatorTemplate', 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown')"
                    :currentPageReportTemplate="t('dataTable.currentPageReportEngins')"
                    class="ultimate-table" scrollable scrollHeight="700px"
                >
                    <template #header>
                        <div class="flex justify-between items-center p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-primary-500" />
                                <InputText v-model="filters['global'].value" :placeholder="t('engins.toolbar.searchPlaceholder')" class="w-full md:w-96 rounded-2xl border-none bg-slate-100" />
                            </IconField>
                        </div>
                    </template>

                    <Column field="designation" :header="t('engins.table.designation')" sortable filterField="designation" frozen class="min-w-[280px]">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div :style="{ backgroundColor: getMeta(data.type).bg }" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all group-hover:scale-110">
                                    <i :class="['pi', getMeta(data.type).icon]" :style="{ color: getMeta(data.type).color }"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 leading-none mb-1">{{ data.designation }}</span>
                                    <span class="text-[10px] font-mono text-slate-400 tracking-tighter">{{ data.immatriculation || t('engins.common.noPlate') }}</span>
                                </div>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('engins.toolbar.searchByName')" />
                        </template>
                    </Column>

                    <Column field="type" :header="t('engins.table.category')" sortable filterField="type" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            <Tag :value="data.type" :style="{ backgroundColor: getMeta(data.type).bg, color: getMeta(data.type).color }" class="rounded-lg px-3 font-black text-[10px] uppercase border-none" />
                        </template>
                        <template #filter="{ filterModel }">
                            <MultiSelect v-model="filterModel.value" :options="enginTypesOptions" optionLabel="name" optionValue="id" :placeholder="t('engins.form.typePlaceholder')" class="w-full" />
                        </template>
                    </Column>

                    <Column field="region_id" :header="t('engins.table.assignment')" sortable filterField="region_id">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-primary-400 text-xs"></i>
                                <span class="text-sm font-bold text-slate-600">{{ data.region?.designation || '--' }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('engins.form.regionPlaceholder')" class="w-full" />
                        </template>
                    </Column>

                    <Column field="date_mise_en_service" :header="t('engins.form.dateAcquisition')" sortable filterField="date_mise_en_service" dataType="date">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">
                                {{ data.date_mise_en_service ? new Date(data.date_mise_en_service).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) : '--' }}
                            </span>
                        </template>
                        <template #filter="{ filterModel }">
                            <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" :placeholder="t('engins.form.datePlaceholder')" mask="99/99/9999" />
                        </template>
                    </Column>

                    <Column :header="t('engins.table.actions')" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-2 justify-end">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editEngin(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteEngin(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

    <Dialog
    v-model:visible="labelDialog"
    modal
    :header="false" :closable="false"
    :style="{ width: '90vw', maxWidth: '600px' }"
    :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
>
    <div>
        <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-blue-500/20 rounded-xl border border-blue-500/30">
                    <i class="pi pi-truck text-blue-400 text-xl"></i>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">
                        {{ editing ? t('engins.dialog.editTitle') : t('engins.dialog.createTitle') }}
                    </h2>
                    <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">
                        Gestion de la flotte
                    </span>
                </div>
            </div>
            <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="labelDialog = false" class="text-white hover:bg-white/10" />
        </div>

        <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 py-4">
                <div class="flex flex-col gap-2">
                    <label for="type" class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">
                        {{ t('engins.form.type') }}
                    </label>
                    <Dropdown
                        id="type"
                        v-model="form.type"
                        :options="enginTypesOptions"
                        optionLabel="name"
                        optionValue="id"
                        @change="handleTypeChange"
                        class="v11-input w-full"
                        :placeholder="t('engins.form.typePlaceholder')"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="designation" class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">
                        {{ t('engins.form.designation') }}
                    </label>
                    <InputText
                        id="designation"
                        v-model="form.designation"
                        class="v11-input w-full"
                        placeholder="Ex: Camion-benne"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="immat" class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">
                        {{ t('engins.form.immatriculation') }}
                    </label>
                    <InputText
                        id="immat"
                        v-model="form.immatriculation"
                        class="v11-input w-full"
                        placeholder="Ex: 1234AB/01"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="region" class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">
                        {{ t('engins.form.region') }}
                    </label>
                    <Dropdown
                        id="region"
                        v-model="form.region_id"
                        :options="regions"
                        optionLabel="designation"
                        optionValue="id"
                        class="v11-input w-full"
                        :placeholder="t('engins.form.regionPlaceholder')"
                    />
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="date_service" class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">
                        {{ t('engins.form.dateAcquisition') }}
                    </label>
                    <Calendar
                        id="date_service"
                        v-model="form.date_mise_en_service"
                        dateFormat="dd/mm/yy"
                        showIcon
                        iconDisplay="input"
                        class="v11-calendar w-full"
                        :placeholder="t('engins.form.datePlaceholder')"
                    />
                </div>
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
            <Button
                :label="t('engins.common.cancel')"
                icon="pi pi-times"
                text
                severity="secondary"
                @click="labelDialog = false"
                class="font-bold uppercase text-[10px] tracking-widest"
            />
            <Button
                :label="editing ? t('engins.common.update') : t('engins.common.save')"
                icon="pi pi-check-circle"
                severity="indigo"
                @click="saveEngin"
                :loading="form.processing"
                class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
            />
        </div>
    </template>
</Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
/* --- DESIGN SYSTEM ULTIMATE V11 --- */
.ultimate-table {
    .p-datatable-thead > tr > th {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-6 px-4 border-b border-slate-100;
        &.p-highlight { @apply text-primary-600 bg-primary-50/30; }
    }
    .p-datatable-tbody > tr {
        @apply border-b border-slate-50 transition-all duration-300;
        &:hover { @apply bg-primary-50/5 !important; }
        td { @apply py-4 px-4; }
    }
}

.v11-input, .v11-calendar {
    .p-inputtext, .p-dropdown {
        @apply rounded-xl border-slate-200 bg-slate-50 p-3 text-sm font-bold;
        &:focus { @apply bg-white ring-4 ring-primary-50 border-primary-500 outline-none; }
    }
}

.v11-dialog {
    .p-dialog-header { @apply p-8 border-none; .p-dialog-title { @apply font-black text-slate-900; } }
    .p-dialog-content { @apply px-8 pb-8; }
}

.p-paginator {
    @apply border-t border-slate-100 py-4 bg-white rounded-b-[2.5rem];
    .p-paginator-page { @apply rounded-lg font-bold; &.p-highlight { @apply bg-primary-50 text-primary-600; } }
}
</style>
