<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- COMPOSANTS PRIME VUE ---
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
import Textarea from 'primevue/textarea';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import Checkbox from 'primevue/checkbox';
import Timeline from 'primevue/timeline';
import AutoComplete from 'primevue/autocomplete';
import Chart from 'primevue/chart';


const { t } = useI18n();
const props = defineProps({
    meters: Object, // Changed from engins to meters
    filters: Object,
    connections: Array, // For linking a meter to a connection
    regions: Array,
    zones: Array,
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();

// --- ÉTATS ---
const meterDialog = ref(false);
const deleteMetersDialog = ref(false);
const editing = ref(false);
const transferDialog = ref(false);
const selectedMeters = ref([]);
const expandedRows = ref([]);
const selectedRegionForStock = ref(null);

const transferSearchQuery = ref('');
const metersToTransfer = ref([]);

const op = ref();

// --- GESTION DES COLONNES ---
const allColumns = ref([
    { field: 'serial_number', header: t('meters.table.serial_number') },
    { field: 'type', header: t('meters.table.type') },
    { field: 'status', header: t('meters.table.status') },
    { field: 'connection_full_name', header: t('meters.table.connection_full_name') },
    { field: 'installation_date', header: t('meters.table.installation_date') },
    { field: 'region_name', header: t('meters.table.region_name') },
    { field: 'zone_name', header: t('meters.table.zone_name') },
    { field: 'is_additional', header: t('meters.table.is_additional') },
    { field: 'notes', header: t('meters.table.notes') },
]);
const visibleColumns = ref(['serial_number', 'type', 'status', 'connection_full_name', 'region_name']);

// --- CONFIGURATION DES FILTRES V11 ---
const filters = ref();

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CUSTOM },
        serial_number: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        type: { value: null, matchMode: FilterMatchMode.EQUALS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
        'connection.full_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
};
initFilters();

const customGlobalFilter = (value, data, filterMeta) => {
    if (!value) return true;
    const searchTerms = value.toLowerCase().split(',').map(term => term.trim()).filter(term => term);
    if (searchTerms.length === 0) return true;

    const serialNumber = data.serial_number?.toLowerCase() || '';
    const connectionName = data.connection_full_name?.toLowerCase() || '';

    // Si la recherche contient des virgules, on cherche uniquement dans les numéros de série
    if (value.includes(',')) {
        return searchTerms.some(term => serialNumber.includes(term));
    }

    // Sinon, recherche globale classique
    return serialNumber.includes(value.toLowerCase()) || connectionName.includes(value.toLowerCase());
};

const meterStats = computed(() => {
    const stats = {
        total: props.meters.data.length,
        active: 0,
        in_stock: 0,
        faulty: 0,
    };
    props.meters.data.forEach(meter => {
        if (stats.hasOwnProperty(meter.status)) {
            stats[meter.status]++;
        }
    });
    return stats;
});

const stockByRegion = computed(() => {
    const stock = {};
    props.meters.data.forEach(meter => { // Correction: on ne compte que les compteurs qui ont une région
        if (meter.status === 'in_stock' && meter.region) {
            const regionName = meter.region?.designation || 'Non spécifiée';
            if (!stock[regionName]) {
                stock[regionName] = 0;
            }
            stock[regionName]++;
        }
    });
    return Object.entries(stock).map(([name, count]) => ({ name, count }));
});

const stockByZone = computed(() => {
    if (!selectedRegionForStock.value) return [];

    const stock = {};
    props.meters.data.forEach(meter => {
        const regionName = meter.region?.designation || 'Non spécifiée';
        if (meter.status === 'in_stock' && regionName === selectedRegionForStock.value) {
            const zoneName = meter.zone?.nomenclature || 'Hors-zone';
            if (!stock[zoneName]) stock[zoneName] = 0;
            stock[zoneName]++;
        }
    });

    return Object.entries(stock).map(([name, count]) => ({ name, count })).sort((a, b) => a.name.localeCompare(b.name));
});

const chartData = computed(() => {
    // ÉTAPE 1 : Récupérer le style ici, à l'intérieur du computed
    const documentStyle = getComputedStyle(document.documentElement);

    // ÉTAPE 2 : Définir getColor de manière sécurisée (avec fallback)
    const getColor = (variable, fallback) => {
        const val = documentStyle.getPropertyValue(variable).trim();
        // Si la variable CSS n'existe pas, on utilise la couleur de secours
        return val || fallback;
    };

    return {
        labels: [t('meters.stats.active'), t('meters.stats.in_stock'), t('meters.stats.faulty')],
        datasets: [
            {
                data: [
                    meterStats.value?.active || 0,
                    meterStats.value?.in_stock || 0,
                    meterStats.value?.faulty || 0
                ],
                backgroundColor: [
                    getColor('--indigo-500', '#6366f1'),
                    getColor('--orange-500', '#f59e0b'),
                    getColor('--rose-500', '#f43f5e')
                ],
                hoverBackgroundColor: [
                    getColor('--indigo-400', '#818cf8'),
                    getColor('--orange-400', '#fbbf24'),
                    getColor('--rose-400', '#fb7185')
                ]
            }
        ]
    };
});

const chartOptions = ref({
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, font: { weight: 'bold' } } }
    }
});

// --- MAPPING DES STATUTS ---
const meterTypes = ref(['prepaid', 'postpaid']);
const meterStatuses = ref(['active', 'inactive', 'in_stock', 'faulty']);

const getStatusSeverity = (status) => {
    const severities = {
        'active': 'success',
        'in_stock': 'info',
        'faulty': 'danger',
        'inactive': 'warning',
    };
    return severities[status] || 'secondary';
};

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    serial_number: '',
    model: '',
    manufacturer: '',
    type: 'prepaid',
    status: 'in_stock',
    installation_date: null,
    connection_id: null,
    region_id: null,
    zone_id: null,
    // Add region_id and zone_id to the form

    is_additional: false,

    notes: '',
});

const transferForm = useForm({
    meter_ids: [],
    region_id: null,
});

const openNew = () => {
    form.reset();
    editing.value = false;
    meterDialog.value = true;
};

const editMeter = (meter) => {
    form.clearErrors();
    Object.assign(form, {
        ...meter,
        installation_date: meter.installation_date ? new Date(meter.installation_date) : null,
        region_id: meter.region_id,
        zone_id: meter.zone_id,
        is_additional: meter.is_additional,

    });
    editing.value = true;
    meterDialog.value = true;
};

const saveMeter = () => {
    const url = editing.value ? route('meters.update', form.id) : route('meters.store');
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data,
        installation_date: data.installation_date ? new Date(data.installation_date).toISOString().split('T')[0] : null,
    })).submit(method, url, {
        onSuccess: () => {
            meterDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Compteur ${editing.value ? 'mis à jour' : 'créé'} avec succès.`, life: 3000 });
        },
        onError: (errors) => {
            const errorDetails = Object.values(errors).join(', ');
            toast.add({ severity: 'error', summary: 'Erreur de sauvegarde', detail: errorDetails, life: 5000 });
        }
    });
};

const deleteMeter = (meter) => {
    confirm.require({
        message: t('meters.confirm.deleteMessage', { serial_number: meter.serial_number }),
        header: t('meters.confirm.deleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('meters.destroy', meter.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: t('common.success'), detail: t('meters.toast.deleteSuccess'), life: 3000 }),
            });
        }
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: t('meters.confirm.bulkDeleteMessage', { count: selectedMeters.value.length }),
        header: t('meters.confirm.bulkDeleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: t('common.delete'),
        rejectLabel: t('common.cancel'),
        accept: () => {
            deleteSelectedMeters();
        },
    });
};

const deleteSelectedMeters = () => {
    router.post(route('meters.bulkdestroy'), {
        ids: selectedMeters.value.map(m => m.id),
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('meters.toast.bulkDeleteSuccess'), life: 3000 });
            selectedMeters.value = [];
        },
        onError: () => {
            toast.add({ severity: 'error', summary: t('common.error'), detail: t('meters.toast.bulkDeleteError'), life: 3000 });
        }
    });
};

const triggerImportInput = () => {
    document.getElementById('import-file').click();
};

const importMeters = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    router.post(route('meters.import'), formData, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('meters.toast.importStarted'), life: 3000 });
        },
        onError: (errors) => {
            const errorDetails = Object.values(errors).join(', ');
            toast.add({ severity: 'error', summary: t('meters.toast.importError'), detail: errorDetails || t('meters.toast.fileInvalid'), life: 5000 });
        }
    });
};

const openTransferDialog = () => {
    // Initialise la liste des compteurs à transférer avec la sélection actuelle
    metersToTransfer.value = [...selectedMeters.value];
    // Met à jour les IDs dans le formulaire
    transferForm.meter_ids = metersToTransfer.value.map(m => m.id);
    transferSearchQuery.value = ''; // Réinitialise la recherche
    transferDialog.value = true;
};

const confirmTransfer = () => {
    // S'assurer que les IDs du formulaire sont à jour avant l'envoi
    transferForm.meter_ids = metersToTransfer.value.map(m => m.id);
    if (!transferForm.region_id) {
        toast.add({ severity: 'warn', summary: t('common.attention'), detail: t('meters.toast.selectDestinationRegion'), life: 3000 });
        return;
    }
    transferForm.post(route('meters.bulk-transfer'), {
        onSuccess: () => {
            transferDialog.value = false;
            selectedMeters.value = [];
            metersToTransfer.value = [];
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('meters.toast.transferSuccess', { count: transferForm.meter_ids.length }), life: 3000 });
        },
        onError: (errors) => {
            const errorDetails = Object.values(errors).join(', ');
            toast.add({ severity: 'error', summary: t('meters.toast.transferError'), detail: errorDetails, life: 5000 });
        }
    });
};

const formattedMeters = computed(() => {
    return props.meters.data.map(meter => ({
        ...meter,
        connection_full_name: meter.connection ? `${meter.connection.first_name} ${meter.connection.last_name}`.trim() : t('meters.table.unassigned'),
        region_name: meter.region?.designation || t('common.notApplicable'),
        zone_name: meter.zone?.nomenclature || t('common.notApplicable'),
    }));
});

const filteredZones = computed(() => {
    if (!form.region_id) {
        return [];
    }
    return props.zones.filter(zone => zone.region_id === form.region_id);
});

watch(() => form.region_id, (newRegionId) => {
    form.zone_id = null;
});

const selectRegionForStock = (regionName) => {
    if (selectedRegionForStock.value === regionName) {
        selectedRegionForStock.value = null; // Toggle off
    } else {
        selectedRegionForStock.value = regionName;
    }
};

const searchMetersForTransfer = (event) => {
    transferSearchQuery.value = event.query;
};

const availableMetersForTransfer = computed(() => {
    if (!transferSearchQuery.value) return [];
    const query = transferSearchQuery.value.toLowerCase();
    return props.meters.data.filter(meter => {
        const isAlreadySelected = metersToTransfer.value.some(tm => tm.id === meter.id);
        return meter.serial_number.toLowerCase().includes(query) && !isAlreadySelected && meter.status === 'in_stock';
    });
});

const addMeterToTransfer = (event) => {
    const meterToAdd = event.value;
    if (meterToAdd && !metersToTransfer.value.some(m => m.id === meterToAdd.id)) {
        metersToTransfer.value.push(meterToAdd);
    }
    transferSearchQuery.value = ''; // Réinitialiser le champ de recherche
    // Mettre à jour les IDs dans le formulaire
    transferForm.meter_ids = metersToTransfer.value.map(m => m.id);
};

const removeMeterFromTransfer = (meterId) => {
    metersToTransfer.value = metersToTransfer.value.filter(m => m.id !== meterId);
    // Mettre à jour les IDs dans le formulaire
    transferForm.meter_ids = metersToTransfer.value.map(m => m.id);
};
watch(() => form.connection_id, (newConnectionId) => {
      form.region_id = null;
            form.zone_id = null;
            form.meter_id = null;
    if (newConnectionId && !form.meter_id) { // N'agit que si aucun compteur n'est déjà sélectionné
        const selectedConnection = props.connections.find(c => c.id === newConnectionId);
        if (selectedConnection) {
            form.region_id = selectedConnection.region_id;
            form.zone_id = selectedConnection.zone_id;
            form.meter_id = selectedConnection.meter_id;
        }
    }
});

</script>



<template>
    <AppLayout>
        <Head :title="t('meters.title')" />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center shadow-xl shadow-primary-100 rotate-3">
                        <i class="pi pi-bolt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase" v-html="t('meters.titleHtml')">
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('meters.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <div v-if="selectedMeters.length > 0" class="flex gap-2">
                        <Button :label="t('meters.actions.transfer') + ` (${selectedMeters.length})`" icon="pi pi-arrows-h" severity="info" @click="openTransferDialog" class="rounded-xl px-4" />
                        <Button :label="t('meters.actions.delete') + ` (${selectedMeters.length})`" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected" class="rounded-xl px-4" />
                    </div>
                    <div class="flex gap-2">
                        <input type="file" id="import-file" class="hidden" accept=".csv,.xlsx" @change="importMeters" />
                        <Button :label="t('meters.actions.import')" icon="pi pi-upload" severity="secondary" @click="triggerImportInput" class="rounded-xl px-4" />
                        <Button :label="t('meters.actions.add')" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
                    </div>
                </div>
            </div>

            <!-- Section des statistiques globales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-bolt text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ meterStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('meters.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-check-circle text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ meterStats.active }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('meters.stats.active') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center"><i class="pi pi-exclamation-triangle text-2xl text-red-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ meterStats.faulty }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('meters.stats.faulty') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-inbox text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ meterStats.in_stock }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('meters.stats.in_stock') }}</div>
                    </div>
                </div>
            </div>

            <!-- Section des stocks par région -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2">
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">{{ t('meters.stats.stock_by_region') }}</h3>
                    <div v-if="stockByRegion.length > 0" class="flex flex-wrap gap-4">
                        <div v-for="stock in stockByRegion" :key="stock.name"
                             @click="selectRegionForStock(stock.name)"
                             :class="['p-4 bg-white rounded-xl border-2 shadow-sm flex items-center gap-4 cursor-pointer transition-all duration-300',
                                      selectedRegionForStock === stock.name ? 'border-primary-500 ring-4 ring-primary-100' : 'border-slate-200 hover:border-primary-300']">
                            <div class="w-10 h-10 rounded-lg bg-sky-100 flex items-center justify-center"><i class="pi pi-map-marker text-lg text-sky-600"></i></div>
                            <div>
                                <div class="text-xl font-black text-slate-800">{{ stock.count }}</div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ stock.name }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 bg-white rounded-xl border border-slate-200 text-slate-400 italic text-sm">
                        {{ t('meters.messages.no_stock') }}
                    </div>

                    <!-- NOUVEAU : Section des stocks par zone (conditionnelle) -->
                    <div v-if="selectedRegionForStock && stockByZone.length > 0" class="mt-6 p-6 bg-primary-50 border-2 border-dashed border-primary-200 rounded-2xl animate-fade-in">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-black text-primary-800 uppercase tracking-widest">
                                {{ t('meters.stats.stock_detail_for') }} <span class="text-primary-600">{{ selectedRegionForStock }}</span>
                            </h3>
                            <Button icon="pi pi-times" text rounded severity="secondary" @click="selectedRegionForStock = null" v-tooltip.bottom="t('meters.actions.close_details')" />
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="zoneStock in stockByZone" :key="zoneStock.name" class="p-3 bg-white rounded-lg border border-primary-100 shadow-sm flex items-center gap-3">
                                <div class="w-8 h-8 rounded-md bg-primary-100 flex items-center justify-center">
                                    <i class="pi pi-compass text-sm text-primary-700"></i>
                                </div>
                                <div>
                                    <div class="text-lg font-black text-slate-800">{{ zoneStock.count }}</div>
                                    <div class="text-[9px] font-bold text-primary-600/80 uppercase tracking-wider">{{ zoneStock.name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">{{ t('meters.stats.distribution_by_status') }}</h3>
                    <div class="flex justify-center items-center h-48">
                        <Chart type="doughnut" :data="chartData" :options="chartOptions" class="w-full md:w-48" />
                    </div>
                </div>
            </div>


            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable
                    v-model:expandedRows="expandedRows"
                    ref="dt" :value="formattedMeters" v-model:filters="filters" v-model:selection="selectedMeters"
                    dataKey="id" :paginator="true" :rows="15" filterDisplay="menu" :custom-global-filter="customGlobalFilter"
                    :globalFilterFields="['serial_number', 'model', 'manufacturer', 'type', 'status', 'connection_full_name']"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="t('meters.table.report')"
                    class="ultimate-table"
                >
                    <template #header>
                        <div class="flex justify-between items-center p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-primary-500" />
                                <InputText v-model="filters['global'].value" :placeholder="t('meters.table.search_placeholder')" class="w-full md:w-96 rounded-2xl border-none bg-slate-100" />
                            </IconField>
                             <div class="flex items-center gap-2">
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" v-tooltip.bottom="t('common.exportCSV')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="t('common.selectColumns')" />
                            </div>
                        </div>
                    </template>

                    <Column :expander="true" headerStyle="width: 3rem" frozen />
                    <Column selectionMode="multiple" headerStyle="width: 3rem" frozen></Column>

                    <Column v-if="visibleColumns.includes('serial_number')" field="serial_number" :header="t('meters.table.serial_number')" sortable filterField="serial_number" frozen class="min-w-[250px]">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-slate-100">
                                    <i class="pi pi-bolt text-slate-500"></i>
                                </div>
                                <div class="flex flex-col">
                                    <a href="#" @click.prevent="editMeter(data)" class="font-black text-slate-800 leading-none mb-1 hover:text-primary-600 hover:underline">
                                        {{ data.serial_number }}
                                    </a>
                                    <span class="text-[10px] font-mono text-slate-400 tracking-tighter">{{ data.manufacturer }} - {{ data.model }}</span>
                                </div>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('meters.filter.by_serial')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('type')" field="type" :header="t('meters.table.type')" sortable filterField="type">
                        <template #body="{ data }">
                            <Tag :value="data.type" class="rounded-lg px-3 font-black text-[10px] uppercase border-none" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="meterTypes" :placeholder="t('meters.filter.by_type')" class="w-full" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('status')" field="status" :header="t('meters.table.status')" sortable filterField="status">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 font-black text-[10px] uppercase" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="meterStatuses" :placeholder="t('meters.filter.by_status')" class="w-full" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('connection_full_name')" field="connection_full_name" :header="t('meters.table.connection_full_name')" sortable filterField="connection.full_name">
                        <template #body="{ data }">
                            <a v-if="data.connection" :href="route('connections.show', data.connection.id)" class="flex items-center gap-2 group">
                                <i class="pi pi-user text-primary-400 text-xs group-hover:text-primary-600"></i>
                                <span class="text-sm font-bold text-slate-600 group-hover:text-primary-600 group-hover:underline">
                                    {{ data.connection_full_name }}
                                </span>
                            </a>
                             <span v-else class="text-slate-400 text-xs italic">{{ t('meters.table.unassigned') }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('meters.filter.by_client')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('installation_date')" field="installation_date" :header="t('meters.table.installation_date')" sortable filterField="installation_date" dataType="date">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">
                                {{ data.installation_date ? new Date(data.installation_date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : t('common.notApplicableShort') }}
                            </span>
                        </template>
                        <template #filter="{ filterModel }">
                            <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" :placeholder="t('common.date_format_placeholder')" mask="99/99/9999" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('region_name')" field="region_name" :header="t('meters.table.region_name')" sortable>
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.region_name }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('zone_name')" field="zone_name" :header="t('meters.table.zone_name')" sortable>
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.zone_name }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('is_additional')" field="is_additional" :header="t('meters.table.is_additional')" sortable>
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_additional ? t('common.yes') : t('common.no')"
                                :severity="data.is_additional ? 'success' : 'secondary'"
                                class="rounded-lg px-3 font-black text-[10px] uppercase"
                            />
                        </template>
                    </Column>


                    <Column v-if="visibleColumns.includes('notes')" field="notes" :header="t('meters.table.notes')" class="min-w-[250px]">
                        <template #body="{ data }">
                            <p class="text-xs text-slate-500 truncate" :title="data.notes">
                                {{ data.notes || t('common.notApplicableShort') }}
                            </p>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-2 justify-end">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editMeter(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteMeter(data)" />
                            </div>
                        </template>
                    </Column>

                    <template #expansion="{ data }">
                        <div class="bg-slate-50 p-6 border-t border-slate-200">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-600 mb-6 flex items-center gap-3">
                                <i class="pi pi-history text-primary-500"></i>
                                {{ t('meters.history.title') }}
                            </h4>
                            <div v-if="data.transfers && data.transfers.length > 0">
                                <Timeline :value="data.transfers" align="alternate" class="custom-timeline">
                                    <template #marker="slotProps">
                                        <span class="flex w-8 h-8 items-center justify-center text-white rounded-full z-10 shadow-lg" :class="{'bg-primary-500': slotProps.index === 0, 'bg-slate-400': slotProps.index > 0}">
                                            <i class="pi pi-arrow-right-arrow-left text-sm"></i>
                                        </span>
                                    </template>
                                    <template #content="slotProps">
                                        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-sm mb-4">
                                            <div class="text-xs font-bold text-slate-500 mb-2">
                                                {{ new Date(slotProps.item.transfer_date).toLocaleString('fr-FR') }}
                                            </div>
                                            <div class="flex items-center gap-3 text-sm">
                                                <Tag :value="slotProps.item.old_region.designation" class="bg-slate-200 text-slate-700 font-bold" />
                                                <i class="pi pi-angle-double-right text-slate-400"></i>
                                                <Tag :value="slotProps.item.new_region.designation" class="bg-primary-100 text-primary-800 font-bold" />
                                            </div>
                                            <div class="text-right text-[10px] italic text-slate-400 mt-3">{{ t('meters.history.by_user', { user: slotProps.item.user.name }) }}</div>
                                        </div>
                                    </template>
                                </Timeline>
                            </div>
                            <div v-else class="text-center py-4 text-slate-400 italic text-sm">{{ t('meters.history.none') }}</div>
                        </div>
                    </template>
                </DataTable>
            </div>

            <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                <h4 class="text-sm font-black text-slate-800 mb-4">{{ t('common.customize_columns') }}</h4>
                <MultiSelect
                    v-model="visibleColumns"
                    :options="allColumns"
                    optionLabel="header" optionValue="field"
                    placeholder="Sélectionnez les colonnes"
                     display="chip"
                    class="w-full max-w-xs"
                />
            </OverlayPanel>
        </div>

    <Dialog
        v-model:visible="meterDialog"
        modal :header="false" :closable="false" :style="{ width: '95vw', maxWidth: '650px' }"
        :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
    >
        <div>
            <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50 rounded-xl">
                <div class="flex items-center gap-4">
                    <div class="p-2.5 bg-blue-500/20 rounded-xl border border-blue-500/30">
                        <i class="pi pi-bolt text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">
                        {{ editing ? t('meters.dialog.editTitle') : t('meters.dialog.createTitle') }}
                        </h2>
                        <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">
                        {{ t('meters.dialog.subtitle') }}
                        </span>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="meterDialog = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 py-4">
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.serial_number') }}</label>
                        <InputText v-model="form.serial_number" class="w-full" :placeholder="t('meters.form.serial_number_placeholder')" :invalid="form.errors.serial_number" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.manufacturer') }}</label>
                        <InputText v-model="form.manufacturer" class="w-full" :placeholder="t('meters.form.manufacturer_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.model') }}</label>
                        <InputText v-model="form.model" class="w-full" :placeholder="t('meters.form.model_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.type') }}</label>
                        <Dropdown v-model="form.type" :options="meterTypes" class="w-full" :placeholder="t('meters.form.type_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.status') }}</label>
                        <Dropdown v-model="form.status" :options="meterStatuses" class="w-full" :placeholder="t('meters.form.status_placeholder')" />
                    </div>
                     <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.connection') }}</label>
                      <Dropdown
    v-model="form.connection_id"
    :options="connections"
    optionValue="id"
    filter
    class="w-full shadow-sm border-slate-200 p-inputtext-sm"
    :placeholder="t('keypads.form.connection_placeholder')"
    showClear
    :filter-fields="['full_name', 'first_name', 'last_name', 'customer_code']"
>
    <template #value="slotProps">
        <div v-if="slotProps.value" class="flex items-center gap-2 overflow-hidden">
            <div class="flex flex-row items-center gap-2 truncate">
                <span class="text-xs font-semibold text-slate-900 leading-none">
                    {{ connections.find(c => c.id === slotProps.value)?.full_name }}
                </span>
                <span class="text-[10px] text-indigo-600 font-bold uppercase leading-none">
                    {{ connections.find(c => c.id === slotProps.value)?.customer_code }}
                </span>
                <i v-if="connections.find(c => c.id === slotProps.value)?.keypad_id"
                   class="pi pi-exclamation-triangle text-red-500 text-[10px]"></i>
            </div>
        </div>
        <span v-else class="text-xs text-slate-400 font-normal">
            {{ slotProps.placeholder }}
        </span>
    </template>

    <template #option="slotProps">
        <div class="flex items-center justify-between w-full py-0">
            <div class="flex flex-col py-1">
                <span class="text-xs font-bold text-slate-800 leading-tight">
                    {{ slotProps.option.full_name }}
                </span>
                <div class="flex items-center gap-1">
                    <span class="text-[9px] text-slate-500 uppercase tracking-tighter">
                        {{ slotProps.option.customer_code }}
                    </span>
                </div>
            </div>

            <div class="flex gap-1">
                <Tag v-if="slotProps.option.zone" :value="slotProps.option.zone"
                     severity="secondary" class="text-[8px] px-1 h-3.5" />

                <Tag v-if="slotProps.option.keypad_id" :value="t('keypads.form.has_keypad')"
                     severity="warning" class="text-[8px] px-1 h-3.5" />
            </div>
        </div>
    </template>

    <template #footer>
        <div class="py-1 px-3 border-t bg-slate-50 text-[9px] text-slate-400 italic">
            {{ connections.length }} {{ t('connections.headTitle').toLowerCase() }}
        </div>
    </template>
</Dropdown>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.installation_date') }}</label>
                        <Calendar v-model="form.installation_date" dateFormat="dd/mm/yy" showIcon iconDisplay="input" class="w-full" :placeholder="t('common.date_format_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.region') }}</label>
                        <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" filter class="w-full" :placeholder="t('meters.form.region_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.zone') }}</label>
                        <Dropdown v-model="form.zone_id" :options="filteredZones" optionLabel="nomenclature" optionValue="id" filter class="w-full" :placeholder="t('meters.form.zone_placeholder')" :disabled="!form.region_id || filteredZones.length === 0" />
                    </div>
                    <div class="flex items-center gap-2 md:col-span-2">
                        <Checkbox v-model="form.is_additional" :binary="true" inputId="is_additional" />
                        <label for="is_additional" class="text-sm font-bold text-slate-700">{{ t('meters.form.is_additional') }}</label>
                    </div>
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.form.notes') }}</label>
                        <Textarea v-model="form.notes" rows="3" class="w-full" :placeholder="t('meters.form.notes_placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="meterDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="editing ? t('common.update') : t('common.save')" icon="pi pi-check-circle" severity="indigo" @click="saveMeter" :loading="form.processing" class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs" />
            </div>
        </template>
    </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>

    <!-- DIALOGUE DE TRANSFERT -->
    <Dialog v-model:visible="transferDialog" modal :header="false" :closable="false" :style="{ width: '95vw', maxWidth: '600px' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }">
        <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50 rounded-xl">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-blue-500/20 rounded-xl border border-blue-500/30">
                    <i class="pi pi-arrows-h text-blue-400 text-xl"></i>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">{{ t('meters.dialog.transfer_title') }}</h2>
                    <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">Mouvement inter-régional</span>
                </div>
            </div>
            <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="transferDialog = false" class="text-white hover:bg-white/10" />
        </div>

        <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth space-y-6">
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.dialog.add_by_serial') }}</label>
                <AutoComplete v-model="transferSearchQuery" :suggestions="availableMetersForTransfer" @complete="searchMetersForTransfer"
                              @item-select="addMeterToTransfer" field="serial_number" placeholder="Rechercher et ajouter un compteur en stock..."
                              class="w-full" inputClass="p-inputtext-lg">
                    <template #option="slotProps">
                        <div class="flex items-center justify-between">
                            <span>{{ slotProps.option.serial_number }}</span>
                            <Tag :value="t('meters.status.in_stock')" severity="info" />
                        </div>
                    </template>
                </AutoComplete>
            </div>

            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">
                    {{ t('meters.dialog.to_transfer_count', { count: metersToTransfer.length }) }}
                </h4>
                <div class="max-h-48 overflow-y-auto space-y-2 pr-2">
                    <div v-for="meter in metersToTransfer" :key="meter.id" class="flex items-center justify-between bg-white p-2 rounded-lg border border-slate-200">
                        <span class="text-sm font-bold text-slate-700">{{ meter.serial_number }}</span>
                        <Button icon="pi pi-times" text rounded severity="danger" @click="removeMeterFromTransfer(meter.id)" class="w-6 h-6" />
                    </div>
                    <div v-if="metersToTransfer.length === 0" class="text-center py-4 text-slate-400 italic text-xs">
                        {{ t('meters.messages.no_meter_selected') }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('meters.dialog.destination_region') }}</label>
                <Dropdown v-model="transferForm.region_id" :options="regions" optionLabel="designation" optionValue="id" filter
                          class="w-full" :placeholder="t('meters.form.region_placeholder')" :invalid="transferForm.errors.region_id" />
                <small class="p-error" v-if="transferForm.errors.region_id">{{ transferForm.errors.region_id }}</small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="transferDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="t('meters.actions.confirm_transfer')" icon="pi pi-check-circle"  @click="confirmTransfer" :loading="transferForm.processing" class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs" :disabled="metersToTransfer.length === 0" />
            </div>
        </template>
    </Dialog>
</template>

<style lang="scss">
/* --- DESIGN SYSTEM ULTIMATE V11 --- */
.p-multiselect-panel .p-multiselect-items .p-multiselect-item { padding: 0.5rem 1rem; border-radius: 8px; }

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

.p-inputtext, .p-dropdown, .p-calendar .p-inputtext, .p-textarea {
    @apply rounded-xl border-slate-200 bg-slate-50 p-3 text-sm font-bold w-full;
    &:focus-within, &:focus { @apply bg-white ring-4 ring-primary-50 border-primary-500 outline-none; }
}

.p-dropdown .p-dropdown-label, .p-calendar .p-inputtext {
    @apply p-0;
}

.p-paginator {
    @apply border-t border-slate-100 py-4 bg-white rounded-b-[2.5rem];
    .p-paginator-page { @apply rounded-lg font-bold; &.p-highlight { @apply bg-primary-50 text-primary-600; } }
}

/* Styles pour la timeline d'historique */
.custom-timeline .p-timeline-event-opposite {
    display: none;
}
.custom-timeline .p-timeline-event-content,
.custom-timeline .p-timeline-event-separator {
    padding: 0 1rem;
}
.custom-timeline .p-timeline-event-marker {
    border: 2px solid white;
}

</style>
