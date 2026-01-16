<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';

// Core V11 API
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import FileUpload from 'primevue/fileupload';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';
import SelectButton from 'primevue/selectbutton';
import MultiSelect from 'primevue/multiselect';
import Slider from 'primevue/slider';
import OverlayPanel from 'primevue/overlaypanel';

const { t } = useI18n();
const props = defineProps({
    regions: Object, // Modifié de Array à Object pour la pagination
    filters: Object,
});

// --- ÉTATS RÉACTIFS ---
const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const labelDialog = ref(false);
const importDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const loading = ref(false);
const selectedRegions = ref(null);
const op = ref(); // Ref for OverlayPanel

// --- FORMULAIRES ---
const form = useForm({
    id: null,
    designation: '',
    type_centrale: 'solaire',
    puissance_centrale: 0,
    description: ''
    ,
    code: '', // Ajout du champ code
    status: 'active' // Ajout du statut
});

// --- SYSTÈME DE FILTRES AVANCÉS (V11 Custom) ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    type_centrale: { value: null, matchMode: FilterMatchMode.IN },
    puissance_centrale: { value: [0, 1000], matchMode: FilterMatchMode.BETWEEN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const lazyParams = ref({
    first: props.regions.from - 1,
    rows: props.regions.per_page,
    sortField: 'created_at',
    sortOrder: -1, // -1 pour desc
    filters: filters.value
});

const loadLazyData = () => {
    loading.value = true;
    const queryParams = {
        ...lazyParams.value,
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
    };

    router.get(route('regions.index'), queryParams, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        type_centrale: { value: null, matchMode: FilterMatchMode.IN },
        puissance_centrale: { value: [0, 1000], matchMode: FilterMatchMode.BETWEEN },
        status: { value: null, matchMode: FilterMatchMode.EQUALS }
    };
    lazyParams.value.filters = filters.value;
    loadLazyData();
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData();
};

// --- CONFIGURATION DES COLONNES ---
const allColumns = ref([
 { field: 'designation', header: 'Désignation', default: true },
 { field: 'type_centrale', header: 'Source', default: true },
 { field: 'puissance_centrale', header: 'Capacité', default: true },
 { field: 'description', header: 'Description', default: false },
 { field: 'code', header: 'Code', default: false }, // Ajout de la colonne code
 { field: 'status', header: 'Statut', default: false },
]);
const visibleColumns = ref(allColumns.value.filter(col => col.default).map(col => col.field));

const toggleColumnSelection = (event) => {
 op.value.toggle(event);
};


// --- LOGIQUE DE CALCUL DES STATS ---
const stats = computed(() => {
    const data = props.regions.data || []; // Utiliser props.regions.data
    const totalMWValue = data.reduce((acc, curr) => acc + (parseFloat(curr.puissance_centrale) || 0), 0);
    return {
        total: props.regions.total || 0, // Utiliser le total de la pagination
        totalMW: totalMWValue.toLocaleString('fr-FR', { minimumFractionDigits: 2 }), // Le calcul reste sur les données visibles
        avgPower: data.length > 0 ? (totalMWValue / data.length).toFixed(1) : 0,
        highPerf: data.filter(r => r.puissance_centrale > 500).length
    };
});

// --- MÉTHODES D'ACTION ---
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true;
};

const editRegion = (region) => {
    form.clearErrors();
    Object.assign(form, region);
    form.code = region.code; // Assigner la valeur du code
    editing.value = true;
    labelDialog.value = true;
};

const saveRegion = () => {
    submitted.value = true;
    if (!form.designation) return;

    loading.value = true;
    const url = editing.value ? route('regions.update', form.id) : route('regions.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Région enregistrée', life: 3000 });
            loading.value = false;
        },
        onError: () => { loading.value = false; }
    });
};

const deleteRegion = (region) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer ${region.designation} ?`,
        header: 'Attention',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('regions.destroy', region.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Info', detail: 'Supprimé' })
            });
        }
    });
};

const deleteSelectedRegions = () => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer les ${selectedRegions.value.length} régions sélectionnées ?`,
        header: 'Confirmation de suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = selectedRegions.value.map(r => r.id);
            router.post(route('regions.bulkDestroy'), { ids: ids }, {
                onSuccess: () => { toast.add({ severity: 'info', summary: 'Info', detail: 'Régions supprimées', life: 3000 }); selectedRegions.value = null; },
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la suppression', life: 3000 })
            });
        }
    });
};

// --- OPTIONS DE CONFIGURATION (I18N) ---
const typeOptions = computed(() => [
    { label: t('regions.types.thermique'), value: 'thermique', icon: 'pi pi-bolt', color: 'red' },
    { label: t('regions.types.solaire'), value: 'solaire', icon: 'pi pi-sun', color: 'orange' },
    { label: t('regions.types.hydraulique'), value: 'hydraulique', icon: 'pi pi-cloud', color: 'blue' },
    { label: t('regions.types.eolienne'), value: 'eolienne', icon: 'pi pi-directions', color: 'green' },
    { label: t('regions.types.reseauElectrique'), value: 'reseau Electrique', icon: 'pi pi-share-alt', color: 'blue' },
    { label: t('regions.types.autreSource'), value: 'autreSource', icon: 'pi pi-ellipsis-h', color: 'gray' }
]);

const statusOptions = computed(() => [
    { label: t('regions.status.active'), value: 'active' },
    { label: t('regions.status.inactive'), value: 'inactive' },
]);

const statLabels = computed(() => ({
    total: t('regions.stats.total'),
    totalMW: t('regions.stats.totalMW'),
    avgPower: t('regions.stats.avgPower'),
    highPerf: t('regions.stats.highPerf')
}));

const getSeverity = (type) => {
    const map = { solaire: 'yellow', thermique: 'danger', hydraulique: 'info', eolienne: 'success', 'reseau Electrique': 'primary' };
    return map[type] || 'secondary';
};
const onFilter = (event) => {
    lazyParams.value.filters = filters.value;
    loadLazyData();
};
</script>

<template>
    <AppLayout>
        <Head :title="t('regions.title')" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
 <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
 <i class="pi pi-bolt text-2xl text-white"></i>
 <i class="pi pi-sun text-2xl text-white -ml-2"></i>
                    </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ t('regions.headTitle') }}
                         <span class="text-primary-600">{{ t('regions.headTitle2') }}</span>
                    </h1>
                    <p class="text-slate-500 font-medium">{{ t('regions.subtitle') }}</p>
                </div>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('regions.actions.resetFilters')" icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" />
                    <Button :label="t('regions.actions.add')" icon="pi pi-plus"  raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ statLabels[key] }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i :class="key === 'totalMW' ? 'pi pi-bolt' : 'pi pi-database'" class="text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="regions.data"
                    v-model:selection="selectedRegions"
                    v-model:filters="filters"
                    dataKey="id"
                    :loading="loading"
                    :paginator="true"
                    :rows="regions.per_page"
                    :totalRecords="regions.total"
                    :lazy="true"
                    @page="onPage($event)"
                     @filter="onFilter($event)"
                    @sort="onPage($event)"
                    v-model:first="lazyParams.first"
                    :sortField="lazyParams.sortField"
                    :sortOrder="lazyParams.sortOrder"
                    filterDisplay="menu"
                    :globalFilterFields="['designation', 'type_centrale', 'code', 'status']"
                    class="p-datatable-custom" removableSort
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('regions.toolbar.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="toggleColumnSelection" />
                                <Button v-if="selectedRegions && selectedRegions.length > 0"
                                        :label="t('common.deleteSelection', { count: selectedRegions.length })"
                                        icon="pi pi-trash" severity="danger" raised
                                        @click="deleteSelectedRegions" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column field="designation" header="Désignation" sortable filterField="designation">
                        <template #body="{ data }" v-if="visibleColumns.includes('designation')">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.designation[0]" shape="circle" class="bg-primary-50 text-primary-600 font-bold" />
                                <span class="font-bold text-slate-700">{{ data.designation }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" placeholder="Chercher par nom" />
                        </template>
                    </Column>

                    <Column field="type_centrale" header="Source / Region" sortable :showFilterMatchModes="false" v-if="visibleColumns.includes('type_centrale')">
                        <template #body="{ data }">
                            <Tag :value="data.type_centrale" :severity="getSeverity(data.type_centrale)" class="rounded-lg px-3 uppercase text-[10px]" />
                        </template>
                        <template #filter="{ filterModel }">
                            <MultiSelect v-model="filterModel.value" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Tous les types" class="w-full" />
                        </template>
                    </Column>

                    <Column field="puissance_centrale" header="Capacité" sortable v-if="visibleColumns.includes('puissance_centrale')" class="w-40">
                        <template #body="{ data }">
                            <div v-if="data.type_centrale !== 'reseau Electrique'" class="flex flex-col">
                                <div class="flex justify-between items-center mb-1 w-full">
                                    <span class="text-sm font-black text-slate-700">{{ data.puissance_centrale }} <small>MW</small></span>
                                    <span class="text-[10px] text-slate-400 font-bold">{{ ((data.puissance_centrale / 1000) * 100).toFixed(0) }}%</span>
                                </div>
                                <ProgressBar :value="(data.puissance_centrale / 1000) * 100" :showValue="false" style="height: 7px" class="rounded-full overflow-hidden" />
                            </div>
                            <div v-else class="flex items-center justify-center h-full">
                                <span class="text-red-500 font-bold text-lg">-</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <div class="px-3 pt-2 pb-4 w-full">
                                <Slider v-model="filterModel.value" range :min="0" :max="1000" class="w-full" />
                                <div class="flex justify-between mt-3 text-xs font-bold text-slate-500">
                                    <span>{{ filterModel.value[0] }}MW</span>
                                    <span>{{ filterModel.value[1] }}MW</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="description" header="Description" v-if="visibleColumns.includes('description')">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-600">{{ data.description || 'N/A' }}</span>
                        </template>
                    </Column>

                    <Column field="status" header="Statut" v-if="visibleColumns.includes('status')">
                        <template #body="{ data }">
                            <Tag :value="data.status"
                                 :severity="data.status === 'active' ? 'success' : 'danger'"
                                 class="rounded-lg px-3 uppercase text-[10px]" />
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen class="min-w-[120px]">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editRegion(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteRegion(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                <div class="font-semibold mb-3">{{ t('common.columnSelector.title') }}</div>
                <MultiSelect
                    v-model="visibleColumns"
                    :options="allColumns"
                    optionLabel="header"
                    optionValue="field"
                    display="chip"
                    :placeholder="t('common.columnSelector.placeholder')" class="w-full max-w-xs" />
            </OverlayPanel>
        </div>

        <Dialog
            v-model:visible="labelDialog"
            modal
            :header="false" :closable="false"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :contentStyle="{ maxHeight: '80vh', overflow: 'auto' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
        >
            <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                     <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-box text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ t('regions.dialog.regionSettings') }}</h4>
                        <p class="text-xs text-slate-500 m-0">{{ t('regions.dialog.productionInfo') }}</p>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="labelDialog = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                    <div class="md:col-span-12 space-y-8">


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('regions.fields.designation') }}</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-map-marker" />
                            <InputText v-model="form.designation" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" :placeholder="t('regions.placeholders.designation')" />
                        </IconField>
                        <small v-if="form.errors.designation" class="text-red-500 font-bold italic">{{ form.errors.designation }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('regions.fields.status') }}</label>
                        <Dropdown v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="rounded-xl border-slate-200 py-1" />
                        <small v-if="form.errors.status" class="text-red-500 font-bold italic">{{ form.errors.status }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('regions.fields.type') }}</label>
                        <Dropdown v-model="form.type_centrale" :options="typeOptions" optionLabel="label" optionValue="value" class="rounded-xl border-slate-200 py-1">
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="slotProps.option.icon"></i>
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex flex-col gap-2" v-if="form.type_centrale !== 'reseau Electrique'">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('regions.fields.power') }}</label>
                        <InputNumber v-model="form.puissance_centrale" showButtons :min="0" inputId="minmaxfraction" :minFractionDigits="2" :maxFractionDigits="5" suffix=" MW" class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('regions.fields.code') }}</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-qrcode" />
                            <InputText v-model="form.code" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" :placeholder="t('regions.placeholders.code')" />
                        </IconField>
                        <small v-if="form.errors.code" class="text-red-500 font-bold italic">{{ form.errors.code }}</small>
                    </div>



                    <div class="md:col-span-2 flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('regions.fields.notes') }}</label>
                        <textarea v-model="form.description" rows="3" class="w-full p-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-primary-50 transition-all text-sm outline-none" :placeholder="t('regions.placeholders.notes')"></textarea>
                    </div>
                </div>

                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="labelDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="editing ? t('common.update') : t('common.save')"
                            icon="pi pi-check-circle" severity="indigo"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                            @click="saveRegion" :loading="loading" />
                </div>
            </template>
        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
/* Personnalisation PrimeVue V11 pour matching Tailwind */
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        background: #f8fafc;
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .p-datatable-tbody > tr {
        background: white;
        transition: all 0.2s;
        &:hover {
            background: #f1f5f9 !important;
        }
    }
}

.ultimate-modal {
    .p-dialog-header {
        background: white;
        padding: 2rem 2rem 1rem 2rem;
        border: none;
        .p-dialog-title { font-weight: 900; font-size: 1.25rem; color: #0f172a; }
    }
    .p-dialog-content {
        padding: 0 2rem 2rem 2rem;
    }
}

/* Fix for Primary Style Toggle */
.p-button.p-button-primary {
    background: #2563eb;
    border-color: #2563eb;
    &:hover { background: #1d4ed8; border-color: #1d4ed8; }
}

/* Custom MultiSelect and Sliders */
.p-multiselect, .p-dropdown {
    border-radius: 12px;
}
</style>
