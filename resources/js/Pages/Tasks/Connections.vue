<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import FileUpload from 'primevue/fileupload';
import OverlayPanel from 'primevue/overlaypanel';
import Textarea from 'primevue/textarea';
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    connections: Object,
    filters: Object,
    regions: Array,
    zones: Array,
    connectionStatuses: Array,
});

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const op = ref();
const dt = ref();
let debounceId = null;

const filters = ref({
    'global': { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
    'customer_code': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'full_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'region_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'zone_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'status': { value: null, matchMode: FilterMatchMode.EQUALS },
});

const stats = computed(() => {
    const data = props.connections.data || [];
    const total = props.connections.total || 0;
    const verified = data.filter(c => c.is_verified).length;
    const active = data.filter(c => c.status === 'active').length;
    const pending = data.filter(c => c.status === 'pending').length;

    return {
        total,
        verified,
        active,
        pending,
    };
});

const initFilters = () => {
    for (const key in filters.value) {
        if (key === 'global') filters.value[key].value = null;
        else if (filters.value[key].constraints) filters.value[key].constraints[0].value = null;
        else filters.value[key].value = null;
    }
};
// Ajoutez cette fonction dans votre <script setup>
const sortByProximity = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            router.get(route('connections.index'), {
                user_lat: position.coords.latitude,
                user_lng: position.coords.longitude,
                search: filters.value.global.value
            }, { preserveState: true });
        }, () => {
            toast.add({severity:'error', summary: 'Erreur', detail: 'Géolocalisation refusée', life: 3000});
        });
    }
};
// --- CONFIGURATION DES COLONNES ---
const allColumns = [
    { field: 'customer_code', header: 'Code Client', sortable: true },
    { field: 'full_name', header: 'Nom Complet', sortable: false },
    { field: 'region_name', header: 'Région', sortable: false },
    { field: 'zone_name', header: 'Zone', sortable: false },
    { field: 'status', header: 'Statut', sortable: true },
    { field: 'phone_number', header: 'Téléphone', sortable: true },
    { field: 'gps_latitude', header: 'Lat.', sortable: true },
    { field: 'gps_longitude', header: 'Long.', sortable: true },
    { field: 'is_verified', header: 'Vérifié', sortable: true },
    { field: 'amount_paid', header: 'Montant Payé', sortable: true },
    { field: 'connection_date', header: 'Date Raccordement', sortable: true },
    { field: 'meter.serial_number', header: 'Compteur N°', sortable: true },
    { field: 'keypad.serial_number', header: 'Clavier N°', sortable: true },

];

// Initialiser avec les colonnes par défaut
const selectedColumnFields = ref(['customer_code', 'full_name', 'region_name', 'zone_name', 'status', 'phone_number']);

// Filtrage dynamique pour le tableau
const displayedColumns = computed(() => {
    return allColumns.filter(col => selectedColumnFields.value.includes(col.field));
});

// --- FONCTIONS TOOLBAR ---
const toggleColumnSelection = (event) => {
    op.value.toggle(event);
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const getStatusSeverity = (status) => {
    if (status === 'active') return 'success';
    if (status === 'inactive') return 'danger';
    if (status === 'pending') return 'warning';
    return 'info';
}

// --- PAGINATION & DONNÉES ---
const connectionList = computed(() => props.connections?.data || []);
const totalRecords = computed(() => props.connections?.total || 0);
const perPage = computed(() => props.connections?.per_page || 10);
const currentPage = computed(() => props.connections?.current_page || 1);

const onPage = (event) => {
    router.get(route('connections.index'), {
        page: event.page + 1,
        per_page: event.rows,
        search: filters.value.global.value
    }, { preserveState: true });
};

const formatConnectionList = computed(() => {
    return connectionList.value.map(c => ({
        ...c,
        full_name: `${c.first_name || ''} ${c.last_name || ''}`.trim(),
        region_name: c.region?.designation || '-',
        zone_name: c.zone?.title || '-',
    }));
});

// --- FORMULAIRES ---
const isModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selected = ref([]);
const importForm = useForm({ file: null });

const form = useForm({
    id: null,
    customer_code: '',
    region_id: null,
    zone_id: null,
    status: null,
    first_name: '',
    last_name: '',
    phone_number: '',
    gps_latitude: null,
    gps_longitude: null,
    is_verified: false,
    amount_paid: 0,
    connection_date: null,
    meter_number: '',
    keypad_number: '',
    notes: '',

});

const openCreate = () => { form.reset(); isModalOpen.value = true; };
const openEdit = (data) => {
    form.clearErrors();
    Object.assign(form, data);
    isModalOpen.value = true;
};

const submit = () => {
    const method = form.id ? 'put' : 'post';
    const url = form.id ? route('connections.update', form.id) : route('connections.store');
    form[method](url, {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({severity:'success', summary: 'Succès', detail: 'Opération réussie', life: 3000});
        }
    });
};

const onFileSelect = (event) => { importForm.file = event.files[0]; };

const doImport = () => {
    importForm.post(route('connections.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            toast.add({severity:'success', summary: 'Succès', detail: 'Données importées', life: 3000});
        }
    });
};

const bulkDelete = () => {
    confirm.require({
        message: t('connections.confirm.bulkDeleteMessage', { count: selected.value.length }),
        header: t('connections.confirm.deleteHeader'),
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        acceptLabel: t('common.delete'),
        rejectLabel: t('common.cancel'),
        accept: () => {
            router.post(route('connections.bulkdestroy'), { ids: selected.value.map(c => c.id) }, {
                onSuccess: () => {
                    selected.value = [];
                    toast.add({ severity: 'success', summary: t('common.success'), detail: t('connections.toast.bulkDeleteSuccess'), life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: t('common.error'), detail: t('connections.toast.bulkDeleteError'), life: 3000 });
                }
            });
        }
    });
};
</script>

<template>
    <AppLayout :title="t('connections.title')">
        <Head :title="t('connections.title')" />
        <Toast />
        <ConfirmDialog >
          <template #rejecticon></template>
        </ConfirmDialog>

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-blue-600 shadow-xl shadow-blue-200">
                        <i class="pi pi-link text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tighter text-slate-900 md:text-4xl" v-html="t('connections.headTitle')"></h1>
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">{{ t('connections.subtitle') }}</p>
                    </div>
                </div>

                <div class="flex w-full flex-col sm:flex-row items-center gap-3 lg:w-auto">
                    <Button :label="t('connections.import')" icon="pi pi-upload" severity="success" outlined class="flex-1 sm:flex-none" @click="isImportModalOpen = true" />
                    <Button :label="t('connections.addNew')" icon="pi pi-plus-circle" class="flex-[2] sm:flex-none !font-black shadow-lg shadow-primary-200" @click="openCreate" />
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-users text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('connections.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-power-off text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.active }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('connections.stats.active') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center">
                        <i class="pi pi-verified text-2xl text-sky-500"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.verified }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('connections.stats.verified') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 flex items-center justify-center"><i class="pi pi-clock text-2xl text-amber-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.pending }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('connections.stats.pending') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable ref="dt" :value="formatConnectionList" v-model:selection="selected" dataKey="id"
                    v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['customer_code', 'full_name', 'region_name', 'zone_name', 'status']"
                    :paginator="true" :rows="perPage" :totalRecords="totalRecords" :first="(currentPage - 1) * perPage"
                    @page="onPage" :rowsPerPageOptions="[10, 20, 50]" lazy
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="t('common.paginationReport', { item: 'raccordements' })"
                    responsiveLayout="scroll" class="p-datatable-sm quantum-table">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('connections.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button v-if="selected.length" :label="t('connections.delete') + ` (${selected.length})`" icon="pi pi-trash" severity="danger" @click="bulkDelete" text class="p-button-sm animate-fadein" />
                                <Button icon="pi pi-map-marker" outlined severity="secondary" @click="sortByProximity" class="rounded-xl" v-tooltip.bottom="t('connections.closest')" />
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" v-tooltip.bottom="t('connections.exportCSV')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="toggleColumnSelection($event)" v-tooltip.bottom="t('connections.selectColumns')" />
                            </div>
                        </div>
                    </template>
                    <Column selectionMode="multiple" headerStyle="width: 3rem" class="pl-8"></Column>

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable" :filterField="col.field">
                        <template #body="slotProps">
                             <span v-if="col.field === 'full_name'" class="font-bold">
                                {{ slotProps.data.full_name }}
                            </span>
                            <span v-else-if="col.field === 'is_verified'">
                                <i class="pi" :class="slotProps.data.is_verified ? 'pi-check-circle text-green-500' : 'pi-times-circle text-red-500'"></i>
                            </span>
                            <span v-else-if="col.field === 'status'">
                                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="uppercase text-[9px] px-2" />
                            </span>
                             <span v-else>
                                {{ slotProps.data[col.field] }}
                            </span>
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="['customer_code', 'full_name', 'region_name', 'zone_name'].includes(col.field)">
                            <InputText v-model="filterModel.constraints[0].value" type="text" @input="filterCallback()" class="p-column-filter" :placeholder="`Filtrer par ${col.header}`" />
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'status'">
                            <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="props.connectionStatuses" optionLabel="label" optionValue="value" placeholder="Statut" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column :header="t('connections.actions')" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" class="p-button-text" @click="openEdit(data)" />
                        </template>
                    </Column>
                </DataTable>

                <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                    <div class="font-semibold mb-3">{{ t('connections.selectColumns') }}</div>
                    <MultiSelect
                        v-model="selectedColumnFields"
                        :options="allColumns"
                        optionLabel="header"
                        optionValue="field"
                        display="chip"
                        placeholder="Choisir les colonnes"
                        class="w-full max-w-xs"
                    />
                </OverlayPanel>
            </div>
        </div>

        <Dialog v-model:visible="isImportModalOpen" :header="t('connections.importDialog.title')" modal :style="{width: '450px'}">
            <FileUpload mode="basic" accept=".xlsx,.xls,.csv" :maxFileSize="1000000" @select="onFileSelect" :auto="false" :chooseLabel="t('connections.importDialog.chooseFile')" class="w-full" />
            <div class="flex justify-end mt-4">
                <Button @click="doImport" :disabled="importForm.processing || !importForm.file" :label="t('connections.importDialog.startImport')" icon="pi pi-upload" />
            </div>
        </Dialog>

        <Dialog v-model:visible="isModalOpen" modal :header="false" :closable="false" class="quantum-dialog w-full max-w-5xl" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">
    <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center shadow-lg relative z-50">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-blue-500/20 rounded-lg border border-blue-500/30">
                <i class="pi pi-link text-blue-400 text-xl"></i>
            </div>
            <div class="flex flex-col">
                <h2 class="text-sm font-black uppercase tracking-widest text-white leading-none">
                    {{ form.id ? t('connections.dialog.editTitle') : t('connections.dialog.createTitle') }}
                </h2>
                <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1 italic">
                    Gestion des clients & points de connexion
                </span>
            </div>
        </div>
        <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isModalOpen = false" class="text-white hover:bg-white/10" />
    </div>

    <div class="p-8 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Colonne 1: Infos Client -->
            <div class="space-y-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b pb-2">Informations Client</h3>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.customerCode') }}</label><InputText v-model="form.customer_code" class="w-full quantum-input" /><small class="p-error">{{ form.errors.customer_code }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.firstName') }}</label><InputText v-model="form.first_name" class="w-full quantum-input" /><small class="p-error">{{ form.errors.first_name }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.lastName') }}</label><InputText v-model="form.last_name" class="w-full quantum-input" /><small class="p-error">{{ form.errors.last_name }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.phone') }}</label><InputText v-model="form.phone_number" class="w-full quantum-input" /><small class="p-error">{{ form.errors.phone_number }}</small></div>
            </div>

            <!-- Colonne 2: Infos Techniques -->
            <div class="space-y-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b pb-2">Données Techniques</h3>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.table.meterNumber') }}</label><InputText v-model="form.meter_number" class="w-full quantum-input" /><small class="p-error">{{ form.errors.meter_number }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.table.keypadNumber') }}</label><InputText v-model="form.keypad_number" class="w-full quantum-input" /><small class="p-error">{{ form.errors.keypad_number }}</small></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.gpsLatitude') }}</label><InputText v-model="form.gps_latitude" class="w-full quantum-input" /><small class="p-error">{{ form.errors.gps_latitude }}</small></div>
                    <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.gpsLongitude') }}</label><InputText v-model="form.gps_longitude" class="w-full quantum-input" /><small class="p-error">{{ form.errors.gps_longitude }}</small></div>
                </div>
                 <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">Notes</label><Textarea v-model="form.notes" rows="3" class="w-full quantum-input" /></div>
            </div>

            <!-- Colonne 3: Infos Administratives -->
            <div class="space-y-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 border-b pb-2">Administration</h3>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.region') }}</label><Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter class="w-full quantum-input" /><small class="p-error">{{ form.errors.region_id }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.zone') }}</label><Dropdown v-model="form.zone_id" :options="props.zones" optionLabel="title" optionValue="id" filter class="w-full quantum-input" /><small class="p-error">{{ form.errors.zone_id }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.dialog.status') }}</label><Dropdown v-model="form.status" :options="props.connectionStatuses" optionLabel="label" optionValue="value" class="w-full quantum-input" /><small class="p-error">{{ form.errors.status }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.table.connectionDate') }}</label><Calendar v-model="form.connection_date" class="w-full" dateFormat="yy-mm-dd" /><small class="p-error">{{ form.errors.connection_date }}</small></div>
                <div class="field"><label class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('connections.table.amountPaid') }}</label><InputNumber v-model="form.amount_paid" mode="currency" currency="XOF" locale="fr-FR" class="w-full" /><small class="p-error">{{ form.errors.amount_paid }}</small></div>
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-8 py-4 bg-slate-50 border-t border-slate-100">
            <Button :label="t('connections.dialog.cancel')" icon="pi pi-times" text @click="isModalOpen = false" />
            <Button :label="t('connections.dialog.save')" icon="pi pi-check" @click="submit" :loading="form.processing" class="px-6 shadow-lg shadow-primary-100" />
        </div>
    </template>
</Dialog>
    </AppLayout>
</template>

<style>
/* Style V11 CUSTOM TOKENS */
.card-v11 :deep(.p-datatable-thead > tr > th) {
    background: #f8fafc !important;
    color: #94a3b8 !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15em !important;
    padding: 1.5rem 1rem !important;
    border: none !important;
}

.card-v11 :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s ease;
}

.card-v11 :deep(.p-datatable-tbody > tr:hover) {
    background: #f1f5f9/50 !important;
}

.p-dialog-mask {
    backdrop-filter: blur(4px);
}
</style>
