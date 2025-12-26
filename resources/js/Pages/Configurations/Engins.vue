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
    type: { value: null, matchMode: FilterMatchMode.IN },
    region_id: { value: null, matchMode: FilterMatchMode.EQUALS },
});

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
    })).submit(editing.value ? 'put' : 'post', url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: t('engins.messages.updateSuccess'), life: 3000 });
        }
    });
};

const deleteEngin = (engin) => {
    confirm.require({
        message: t('engins.messages.confirmDelete', { name: engin.designation }),
        header: 'Confirmation',
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
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">{{ engins?.length || 0 }} Unités en ligne</p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button icon="pi pi-download" text severity="secondary" @click="dt.exportCSV()" />
                    <Button :label="t('engins.actions.add')" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable
                    ref="dt" :value="engins" v-model:filters="filters"
                    dataKey="id" :paginator="true" :rows="15" filterDisplay="menu"
                    :globalFilterFields="['designation', 'immatriculation', 'type']"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="{first} à {last} sur {totalRecords}"
                    class="ultimate-table" scrollable scrollHeight="700px"
                >
                    <template #header>
                        <div class="flex justify-between items-center p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-primary-500" />
                                <InputText v-model="filters['global'].value" placeholder="Recherche rapide..." class="w-full md:w-96 rounded-2xl border-none bg-slate-100" />
                            </IconField>
                        </div>
                    </template>

                    <Column field="designation" header="Engin & ID" sortable frozen class="min-w-[280px]">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div :style="{ backgroundColor: getMeta(data.type).bg }" class="w-12 h-12 rounded-xl flex items-center justify-center transition-all group-hover:scale-110">
                                    <i :class="['pi', getMeta(data.type).icon]" :style="{ color: getMeta(data.type).color }"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 leading-none mb-1">{{ data.designation }}</span>
                                    <span class="text-[10px] font-mono text-slate-400 tracking-tighter">{{ data.immatriculation || 'SANS PLAQUE' }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="type" header="Catégorie" sortable :showFilterMatchModes="false">
                        <template #body="{ data }">
                            <Tag :value="data.type" :style="{ backgroundColor: getMeta(data.type).bg, color: getMeta(data.type).color }" class="rounded-lg px-3 font-black text-[10px] uppercase border-none" />
                        </template>
                        <template #filter="{ filterModel }">
                            <MultiSelect v-model="filterModel.value" :options="enginTypesOptions" optionLabel="name" optionValue="id" placeholder="Tous les types" class="w-full" />
                        </template>
                    </Column>

                    <Column field="region_id" header="Affectation" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-primary-400 text-xs"></i>
                                <span class="text-sm font-bold text-slate-600">{{ data.region?.designation || '--' }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="regions" optionLabel="designation" optionValue="id" placeholder="Toutes les régions" class="w-full" />
                        </template>
                    </Column>

                    <Column field="date_mise_en_service" header="Mise en Service" sortable>
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">
                                {{ data.date_mise_en_service ? new Date(data.date_mise_en_service).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) : '--' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Contrôle" alignFrozen="right" frozen>
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

        <Dialog v-model:visible="labelDialog" modal :header="editing ? 'Mise à jour Engin' : 'Nouvel Engin'" :style="{ width: '600px' }" class="v11-dialog">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type d'engin</label>
                    <Dropdown v-model="form.type" :options="enginTypesOptions" optionLabel="name" optionValue="id" @change="handleTypeChange" class="v11-input" placeholder="Sélectionner..." />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Désignation</label>
                    <InputText v-model="form.designation" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Immatriculation</label>
                    <InputText v-model="form.immatriculation" class="v11-input" placeholder="Ex: 1234AB/01" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Région d'affectation</label>
                    <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date de mise en service</label>
                    <Calendar v-model="form.date_mise_en_service" dateFormat="dd/mm/yy" showIcon class="v11-calendar" />
                </div>
            </div>

            <template #footer>
                <div class="flex gap-3 w-full pt-4">
                    <Button label="Annuler" text severity="secondary" @click="labelDialog = false" class="flex-1 rounded-xl" />
                    <Button label="Enregistrer" severity="primary" raised @click="saveEngin" :loading="form.processing" class="flex-1 rounded-xl font-black py-4 shadow-lg shadow-primary-200" />
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
