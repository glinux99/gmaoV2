<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import { useTransfer } from '@/composables/useTransfer.js';
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
import AutoComplete from 'primevue/autocomplete';
import Chart from 'primevue/chart';


const { t } = useI18n();
const props = defineProps({
    keypads: Object,
    filters: Object,
    connections: Array,
    regions: Array,
    zones: Array,
    meters: Array, // Pour lier un clavier à un compteur
    queryParams: Object,
    installed: Number,
    available: Number,
    faulty: Number,
    total : Number
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();

// --- ÉTATS ---
const keypadDialog = ref(false);
const editing = ref(false);
const selectedKeypads = ref([]);

const op = ref();

// --- GESTION DES COLONNES ---
const allColumns = ref([
    { field: 'serial_number', header: t('keypads.table.serial_number') },
    { field: 'model', header: t('keypads.table.model') },
    { field: 'manufacturer', header: t('keypads.table.manufacturer') },
    { field: 'type', header: t('keypads.table.type') },
    { field: 'status', header: t('keypads.table.status') },
    { field: 'connection_full_name', header: t('keypads.table.connection_full_name') },
    { field: 'meter_serial_number', header: t('keypads.table.meter_serial_number') },
    { field: 'installation_date', header: t('keypads.table.installation_date') },
    { field: 'region_name', header: t('keypads.table.region_name') },
    { field: 'zone_name', header: t('keypads.table.zone_name') },
    { field: 'notes', header: t('keypads.table.notes') },
]);
const visibleColumns = ref(['serial_number', 'status', 'connection_full_name', 'meter_serial_number', 'region_name']);

// --- CONFIGURATION DES FILTRES ---
const filters = ref();

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        serial_number: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        type: { value: null, matchMode: FilterMatchMode.EQUALS },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
        'connection.full_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'meter.serial_number': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
};
initFilters();

const loading = ref(false);

const lazyParams = ref({
    first: props.keypads.from - 1,
    rows: props.keypads.per_page,
    sortField: props.queryParams?.sortField || 'created_at',
    sortOrder: props.queryParams?.sortOrder === 'desc' ? -1 : 1,
    filters: filters.value
});

const loadLazyData = (event) => {
    loading.value = true;
    lazyParams.value = { ...lazyParams.value, ...event };
    const queryParams = {
        ...lazyParams.value,
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
        sortOrder: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
    };

    router.get(route('keypads.index'), queryParams, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const onPage = (event) => loadLazyData(event);
const onSort = (event) => loadLazyData(event);
const onFilter = (event) => {
    lazyParams.value.filters = filters.value;
    loadLazyData(event);
};
const keypadStats = computed(() => {
    const stats = {
        total: props.total,
        available: props.available ?? 0,
        installed: props.installed,
        faulty: props.faulty ?? 0,
    };
    // props.keypads.data.forEach(keypad => {
    //     if (stats.hasOwnProperty(keypad.status)) {
    //         stats[keypad.status]++;
    //     }
    // });
    return stats;
});

const stockByRegion = computed(() => {
    const stock = {};
    props.keypads.data.forEach(keypad => {
        if (keypad.status === 'available' && keypad.region) {
            const regionName = keypad.region?.designation || 'Non spécifiée';
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
    props.keypads.data.forEach(keypad => {
        const regionName = keypad.region?.designation || 'Non spécifiée';
        if (keypad.status === 'available' && regionName === selectedRegionForStock.value) {
            const zoneName = keypad.zone?.nomenclature || 'Hors-zone';
            if (!stock[zoneName]) stock[zoneName] = 0;
            stock[zoneName]++;
        }
    });

    return Object.entries(stock).map(([name, count]) => ({ name, count })).sort((a, b) => a.name.localeCompare(b.name));
});

const chartData = computed(() => {
    const documentStyle = getComputedStyle(document.documentElement);
    const getColor = (variable, fallback) => {
        const val = documentStyle.getPropertyValue(variable).trim();
        return val || fallback;
    };

    return {
        labels: [t('keypads.stats.installed'), t('keypads.stats.available'), t('keypads.stats.faulty')],
        datasets: [
            {
                data: [
                    keypadStats.value?.installed || 0,
                    keypadStats.value?.available || 0,
                    keypadStats.value?.faulty || 0
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
const keypadTypes = ref(['standard', 'avancé']); // Exemple, à adapter
const keypadStatuses = ref(['available', 'installed', 'faulty']);

const getStatusSeverity = (status) => {
    const severities = {
        'installed': 'success',
        'available': 'info',
        'faulty': 'danger',
    };
    return severities[status] || 'secondary';
};

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    serial_number: '',
    model: '',
    manufacturer: '',
    type: 'standard',
    status: 'available',
    installation_date: null,
    connection_id: null,
    region_id: null,
    zone_id: null,
    meter_id: null,
    notes: '',
});
const openNew = () => {
    form.reset();
    editing.value = false;
    form.installation_date = new Date(); // Remplir automatiquement la date du jour
    keypadDialog.value = true;
};

const editKeypad = (keypad) => {
    // form.clearErrors();
    form.id = keypad.id;
    form.serial_number = keypad.serial_number;
    form.model = keypad.model;
    form.manufacturer = keypad.manufacturer;
    form.type = keypad.type;
    form.status = keypad.status;
    form.installation_date = keypad.installation_date;
    form.connection_id = keypad.connection_id;
    form.region_id = keypad.region_id;
    // form.zone_id = keypad.zone_id;
    form.meter_id = keypad.meter_id;
    form.notes = keypad.notes;

    editing.value = true;
    keypadDialog.value = true;
};

const saveKeypad = () => {
    const url = editing.value ? route('keypads.update', form.id) : route('keypads.store');
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data,
        installation_date: data.installation_date ? new Date(data.installation_date).toISOString().split('T')[0] : null,
    })).submit(method, url, {
        onSuccess: () => {
            keypadDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: `Clavier ${editing.value ? 'mis à jour' : 'créé'} avec succès.`, life: 3000 });
        },
        onError: (errors) => {
            const errorDetails = Object.values(errors).join(', ');
            toast.add({ severity: 'error', summary: 'Erreur de sauvegarde', detail: errorDetails, life: 5000 });
        }
    });
};

const deleteKeypad = (keypad) => {
    confirm.require({
        message: t('keypads.confirm.deleteMessage', { serial_number: keypad.serial_number }),
        header: t('keypads.confirm.deleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('keypads.destroy', keypad.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: t('common.success'), detail: t('keypads.toast.deleteSuccess'), life: 3000 }),
            });
        }
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: t('keypads.confirm.bulkDeleteMessage', { count: selectedKeypads.value.length }),
        header: t('keypads.confirm.bulkDeleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: t('common.delete'),
        rejectLabel: t('common.cancel'),
        accept: () => {
            deleteSelectedKeypads();
        },
    });
};

const deleteSelectedKeypads = () => {
    router.post(route('keypads.bulkdestroy'), {
        ids: selectedKeypads.value.map(k => k.id),
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('keypads.toast.bulkDeleteSuccess'), life: 3000 });
            selectedKeypads.value = [];
        },
        onError: () => {
            toast.add({ severity: 'error', summary: t('common.error'), detail: t('keypads.toast.bulkDeleteError'), life: 3000 });
        }
    });
};

// --- LOGIQUE DE TRANSFERT (Refactorisée) ---
const allKeypads = computed(() => props.keypads.data);
const {
    transferDialog,
    transferForm,
    itemsToTransfer: keypadsToTransfer,
    transferSearchQuery,
    availableItemsForTransfer: availableKeypadsForTransfer,
    openTransferDialog,
    confirmTransfer,
    searchItemsForTransfer: searchKeypadsForTransfer,
    addItemToTransfer: addKeypadToTransfer,
    removeItemFromTransfer: removeKeypadFromTransfer,
} = useTransfer({
    allItems: allKeypads,
    selectedItems: selectedKeypads,
    transferRouteName: route('keypads.bulk-transfer'),
    idKey: 'keypad_ids',
    stockStatus: 'available',
    translations: {
        attention: t('common.attention'),
        selectDestinationRegion: t('keypads.toast.selectDestinationRegion'),
        success: t('common.success'),
        transferSuccess: (count) => t('keypads.toast.transferSuccess', { count }),
        transferError: t('keypads.toast.transferError'),
    }
});

const formattedKeypads = computed(() => {
    return props.keypads.data.map(keypad => ({
        ...keypad,
        connection_full_name: keypad.connection ? `${keypad.connection.first_name} ${keypad.connection.last_name}`.trim() : t('keypads.table.unassigned'),
        meter_serial_number: keypad.meter?.serial_number || t('keypads.table.unassigned'),
        region_name: keypad.region?.designation || t('common.notApplicable'),
        zone_name: keypad.zone?.nomenclature || t('common.notApplicable'),
    }));
});

const filteredZones = computed(() => {
    if (!form.region_id) {
        return [];
    }
    return props.zones.filter(zone => zone.region_id === form.region_id);
});

watch(() => form.region_id, (newRegionId) => {
    if(editing.value) return ;

    if (!filteredZones.value.some(z => z.id === form.zone_id)) {
        form.zone_id = null;
    }
});

watch(() => form.meter_id, (newMeterId) => {
    if(editing.value) return ;
     if(!form.connection_id){
        form.region_id = null;
        form.zone_id = null;
     }
    // Si un compteur est sélectionné
    if (newMeterId) {
        const selectedMeter = props.meters.find(m => m.id === newMeterId);
        if (selectedMeter) {
            // On met à jour la région et la zone du formulaire du clavier
              if(!form.connection_id){
            form.region_id = selectedMeter.region_id;
            form.zone_id = selectedMeter.zone_id;
     }

        }
            console.log(form.region_id, form.zone_id, selectedMeter);
    } else {
        // Optionnel : si aucun compteur n'est sélectionné, on peut réinitialiser les champs
          if(!form.connection_id){
        form.region_id = null;
        form.zone_id = null;
     }
    }

});

watch(() => form.connection_id, (newConnectionId) => {

    if(!editing.value){
        form.region_id = null;
    form.zone_id = null;
    form.meter_id = null;
    if (newConnectionId) {
        const selectedConnection = props.connections.find(c => c.id === newConnectionId);
        if (selectedConnection) {
            // Priorité à la région/zone de la connexion
            form.region_id = selectedConnection.region_id;
            form.zone_id = selectedConnection.zone_id;
            form.meter_id = selectedConnection.meter_id;
        }
    }
    }
});


const availableMeters = computed(() => {
    // 1. Créer un ensemble (Set) des IDs de compteurs déjà associés à un clavier.
    const associatedMeterIds = new Set(
        props.keypads.data
            .map(keypad => keypad.meter_id)
            .filter(id => id !== null && id !== undefined)
    );

    // 2. Filtrer la liste des compteurs.
    return props.meters.filter(meter => {
        // Inclure le compteur s'il n'est PAS dans la liste des compteurs déjà associés.
        const isNotAssociated = !associatedMeterIds.has(meter.id);

        // OU s'il est le compteur actuellement lié au clavier en cours de modification.
        const isCurrentlyEdited = editing.value && form.meter_id === meter.id;

        return isNotAssociated || isCurrentlyEdited;
    });
});

const selectedConnection = computed(() => {
    if (form.connection_id) {
        return props.connections.find(c => c.id === form.connection_id);
    }
    return null;
});

const selectedRegionForStock = ref(null);

const selectRegionForStock = (regionName) => {
    if (selectedRegionForStock.value === regionName) {
        selectedRegionForStock.value = null; // Toggle off
    } else {
        selectedRegionForStock.value = regionName;
    }
};

</script>



<template>
    <AppLayout>
        <Head :title="t('keypads.title')" />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center shadow-xl shadow-primary-100 rotate-3">
                        <i class="pi pi-key text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase" v-html="t('keypads.titleHtml')">
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('keypads.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <div v-if="selectedKeypads.length > 0" class="flex gap-2">
                        <Button :label="t('keypads.actions.transfer') + ` (${selectedKeypads.length})`" icon="pi pi-arrows-h" severity="info" @click="openTransferDialog" class="rounded-xl px-4" />
                        <Button :label="t('keypads.actions.delete') + ` (${selectedKeypads.length})`" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected" class="rounded-xl px-4" />
                    </div>
                    <div class="flex gap-2">
                        <Button :label="t('keypads.actions.add')" icon="pi pi-plus"  raised @click="openNew" class="rounded-xl px-6" />
                    </div>
                </div>
            </div>

            <!-- Section des statistiques globales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-key text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ keypadStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('keypads.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-check-circle text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ keypadStats.installed }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('keypads.stats.installed') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center"><i class="pi pi-exclamation-triangle text-2xl text-red-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ keypadStats.faulty }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('keypads.stats.faulty') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-inbox text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ keypadStats.available }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('keypads.stats.available') }}</div>
                    </div>
                </div>
            </div>

            <!-- Section des stocks par région -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2">
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">{{ t('keypads.stats.stock_by_region') }}</h3>
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
                        {{ t('keypads.messages.no_stock') }}
                    </div>

                    <!-- Section des stocks par zone (conditionnelle) -->
                    <div v-if="selectedRegionForStock && stockByZone.length > 0" class="mt-6 p-6 bg-primary-50 border-2 border-dashed border-primary-200 rounded-2xl animate-fade-in">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-black text-primary-800 uppercase tracking-widest">
                                {{ t('keypads.stats.stock_detail_for') }} <span class="text-primary-600">{{ selectedRegionForStock }}</span>
                            </h3>
                            <Button icon="pi pi-times" text rounded severity="secondary" @click="selectedRegionForStock = null" v-tooltip.bottom="t('keypads.actions.close_details')" />
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
                    <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest mb-4">{{ t('keypads.stats.distribution_by_status') }}</h3>
                    <div class="flex justify-center items-center h-48">
                        <Chart type="doughnut" :data="chartData" :options="chartOptions" class="w-full md:w-48" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="formattedKeypads"
                    v-model:selection="selectedKeypads"
                    v-model:filters="filters"
                    dataKey="id"
                    :loading="loading"
                    :paginator="true"
                    :rows="keypads.per_page"
                    :totalRecords="keypads.total"
                    :lazy="true"
                    @page="onPage($event)"
                    @filter="onFilter($event)"
                    @sort="onSort($event)"
                    v-model:first="lazyParams.first"
                    :sortField="lazyParams.sortField"
                    :sortOrder="lazyParams.sortOrder"
                    filterDisplay="menu"
                    :globalFilterFields="['serial_number', 'model', 'manufacturer', 'type', 'status', 'connection_full_name', 'meter_serial_number']"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="t('keypads.table.report')"
                    class="ultimate-table"
                >
                    <template #header>
                        <div class="flex justify-between items-center p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-primary-500" />
                                <InputText v-model="filters['global'].value" :placeholder="t('keypads.table.search_placeholder')" class="w-full md:w-96 rounded-2xl border-none bg-slate-100" />
                            </IconField>
                             <div class="flex items-center gap-2">
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" v-tooltip.bottom="t('common.exportCSV')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="t('common.selectColumns')" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" frozen></Column>

                    <Column v-if="visibleColumns.includes('serial_number')" field="serial_number" :header="t('keypads.table.serial_number')" sortable filterField="serial_number" frozen class="min-w-[250px]">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-slate-100">
                                    <i class="pi pi-key text-slate-500"></i>
                                </div>
                                <div class="flex flex-col">
                                    <a href="#" @click.prevent="editKeypad(data)" class="font-black text-slate-800 leading-none mb-1 hover:text-primary-600 hover:underline">
                                        {{ data.serial_number }}
                                    </a>
                                    <span class="text-[10px] font-mono text-slate-400 tracking-tighter">{{ data.manufacturer }} - {{ data.model }}</span>
                                </div>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('keypads.filter.by_serial')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('status')" field="status" :header="t('keypads.table.status')" sortable filterField="status">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 font-black text-[10px] uppercase" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="keypadStatuses" :placeholder="t('keypads.filter.by_status')" class="w-full" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('type')" field="type" :header="t('keypads.table.type')" sortable filterField="type">
                        <template #body="{ data }">
                            <Tag :value="data.type" class="rounded-lg px-3 font-black text-[10px] uppercase border-none" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="keypadTypes" :placeholder="t('keypads.filter.by_type')" class="w-full" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('connection_full_name')" field="connection_full_name" :header="t('keypads.table.connection_full_name')" sortable filterField="connection.full_name">
                        <template #body="{ data }">
                            <a v-if="data.connection" :href="route('connections.show', data.connection.id)" class="flex items-center gap-2 group">
                                <i class="pi pi-user text-primary-400 text-xs group-hover:text-primary-600"></i>
                                <span class="text-sm font-bold text-slate-600 group-hover:text-primary-600 group-hover:underline">
                                    {{ data.connection_full_name }}
                                </span>
                            </a>
                             <span v-else class="text-slate-400 text-xs italic">{{ t('keypads.table.unassigned') }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('keypads.filter.by_client')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('meter_serial_number')" field="meter_serial_number" :header="t('keypads.table.meter_serial_number')" sortable filterField="meter.serial_number">
                        <template #body="{ data }">
                            <a v-if="data.meter" :href="route('meters.index', { global: data.meter.serial_number })" class="flex items-center gap-2 group">
                                <i class="pi pi-bolt text-primary-400 text-xs group-hover:text-primary-600"></i>
                                <span class="text-sm font-bold text-slate-600 group-hover:text-primary-600 group-hover:underline">
                                    {{ data.meter_serial_number }}
                                </span>
                            </a>
                             <span v-else class="text-slate-400 text-xs italic">{{ t('keypads.table.unassigned') }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('keypads.filter.by_meter')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('installation_date')" field="installation_date" :header="t('keypads.table.installation_date')" sortable filterField="installation_date" dataType="date">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">
                                {{ data.installation_date ? new Date(data.installation_date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : t('common.notApplicableShort') }}
                            </span>
                        </template>
                        <template #filter="{ filterModel }">
                            <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" :placeholder="t('common.date_format_placeholder')" mask="99/99/9999" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('region_name')" field="region_name" :header="t('keypads.table.region_name')" sortable>
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.region_name }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('zone_name')" field="zone_name" :header="t('keypads.table.zone_name')" sortable>
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.zone_name }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('model')" field="model" :header="t('keypads.table.model')">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.model || 'N/A' }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('manufacturer')" field="manufacturer" :header="t('keypads.table.manufacturer')">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.manufacturer || 'N/A' }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('notes')" field="notes" :header="t('keypads.table.notes')" class="min-w-[250px]">
                        <template #body="{ data }">
                            <p class="text-xs text-slate-500 truncate" :title="data.notes">
                                {{ data.notes || t('common.notApplicableShort') }}
                            </p>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-2 justify-end">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editKeypad(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteKeypad(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                <h4 class="text-sm font-black text-slate-800 mb-4">{{ t('common.customize_columns') }}</h4>
                <MultiSelect
                    v-model="visibleColumns"
                    :options="allColumns"
                    optionLabel="header" optionValue="field"
                    :placeholder="t('common.selectColumns')"
                     display="chip"
                    class="w-full max-w-xs"
                />
            </OverlayPanel>
        </div>

    <Dialog
        v-model:visible="keypadDialog"
        modal :header="false" :closable="false" :style="{ width: '95vw', maxWidth: '650px' }"
        :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
    >
        <div>
            <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50 rounded-xl">
                <div class="flex items-center gap-4">
                    <div class="p-2.5 bg-blue-500/20 rounded-xl border border-blue-500/30">
                        <i class="pi pi-key text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">
                        {{ editing ? t('keypads.dialog.editTitle') : t('keypads.dialog.createTitle') }}
                        </h2>
                        <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">
                        {{ t('keypads.dialog.subtitle') }}
                        </span>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="keypadDialog = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 py-4">
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.serial_number') }}</label>
                        <InputText v-model="form.serial_number" class="w-full" :placeholder="t('keypads.form.serial_number_placeholder')" :invalid="form.errors.serial_number" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.manufacturer') }}</label>
                        <InputText v-model="form.manufacturer" class="w-full" :placeholder="t('keypads.form.manufacturer_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.model') }}</label>
                        <InputText v-model="form.model" class="w-full" :placeholder="t('keypads.form.model_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.type') }}</label>
                        <Dropdown v-model="form.type" :options="keypadTypes" class="w-full" :placeholder="t('keypads.form.type_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.status') }}</label>
                        <Dropdown v-model="form.status" :options="keypadStatuses" class="w-full" :placeholder="t('keypads.form.status_placeholder')" />
                    </div>
                     <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.connection') }}</label>
               <Dropdown
    v-model="form.connection_id"
    :options="connections"
    optionValue="id"
    filter
    class="w-full shadow-sm border-slate-200"
    :placeholder="t('keypads.form.connection_placeholder')"
    showClear
    :filter-fields="['full_name', 'first_name', 'last_name', 'customer_code']"
>
    <template #value="slotProps">
        <div v-if="slotProps.value" class="flex items-center gap-2 overflow-hidden">
            <div class="flex flex-row items-center gap-2 truncate">
                <span class="text-xs font-semibold text-slate-900 leading-none ">
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

                    <!-- Bloc d'information du client sélectionné -->

                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.meter') }}</label>
                        <Dropdown v-model="form.meter_id" :options="availableMeters" optionValue="id" filter class="w-full" :placeholder="t('keypads.form.meter_placeholder')" showClear>
                            <template #value="slotProps">
                                <div v-if="slotProps.value">
                                    {{ availableMeters.find(m => m.id === slotProps.value)?.serial_number }}
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex justify-between items-center w-full">
                                    <span class="font-bold">{{ slotProps.option.serial_number }}</span>
                                    <div v-if="slotProps.option.connection" class="text-xs text-slate-500 ml-4 flex items-center gap-1">
                                        <i class="pi pi-user text-[9px]"></i><span>{{ slotProps.option.connection.full_name }}</span>
                                    </div>
                                </div>
                            </template>
                        </Dropdown>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.installation_date') }}</label>
                        <Calendar v-model="form.installation_date" dateFormat="dd/mm/yy" showIcon iconDisplay="input" class="w-full" :placeholder="t('common.date_format_placeholder')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.region') }}</label>
                        <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" filter class="w-full" :placeholder="t('keypads.form.region_placeholder')" :disabled="!!form.meter_id" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.zone') }}</label>
                        <Dropdown v-model="form.zone_id" :options="filteredZones" optionLabel="nomenclature" optionValue="id" filter class="w-full" :placeholder="t('keypads.form.zone_placeholder')" :disabled="!!form.meter_id || !form.region_id || filteredZones.length === 0" />
                    </div>
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.form.notes') }}</label>
                        <Textarea v-model="form.notes" rows="3" class="w-full" :placeholder="t('keypads.form.notes_placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="keypadDialog = false" class="font-bold uppercase text-[10px] tracking-widest" >
                  <template #loadingicon="slotProps"></template>
                </Button>
                <Button :label="editing ? t('common.update') : t('common.save')" icon="pi pi-check-circle" severity="indigo" @click="saveKeypad" :loading="form.processing" class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs" />
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
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">{{ t('keypads.dialog.transfer_title') }}</h2>
                    <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">Mouvement inter-régional</span>
                </div>
            </div>
            <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="transferDialog = false" class="text-white hover:bg-white/10" />
        </div>

        <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth space-y-6">
                    <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.dialog.destination_region') }}</label>
                <Dropdown v-model="transferForm.region_id" :options="regions" optionLabel="designation" optionValue="id" filter
                          class="w-full" :placeholder="t('keypads.form.region_placeholder')" :invalid="transferForm.errors.region_id" />
                <small class="p-error" v-if="transferForm.errors.region_id">{{ transferForm.errors.region_id }}</small>
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ t('keypads.dialog.add_by_serial') }}</label>
                <AutoComplete v-model="transferSearchQuery" :suggestions="availableKeypadsForTransfer" @complete="searchKeypadsForTransfer($event)"
                              @item-select="addKeypadToTransfer" field="serial_number" placeholder="Rechercher et ajouter un clavier disponible..."
                              class="w-full" inputClass="p-inputtext-lg">
                    <template #option="slotProps">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex flex-col">
                                <span class="font-bold text-sm">{{ slotProps.option.serial_number }}</span>
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <i class="pi pi-map-marker text-[10px]"></i>
                                    <span class="font-semibold text-slate-600">{{ slotProps.option.region?.designation || t('common.notApplicable') }}</span>
                                    <i class="pi pi-angle-right text-[8px] text-primary-500"></i>
                                    <span class="font-black text-primary-600">
                                        {{ transferForm.region_id ? regions.find(r => r.id === transferForm.region_id)?.designation : '...' }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <Tag :value="t(`keypads.status.${slotProps.option.status}`)" :severity="getStatusSeverity(slotProps.option.status)" />
                            </div>
                        </div>
                    </template>
                </AutoComplete>
            </div>

            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">
                    {{ t('keypads.dialog.to_transfer_count', { count: keypadsToTransfer.length }) }}
                </h4>
                <div class="max-h-48 overflow-y-auto space-y-2 pr-2">
                    <div v-for="keypad in keypadsToTransfer" :key="keypad.id" class="flex items-center justify-between bg-white p-2 rounded-lg border border-slate-200">
                        <span class="text-sm font-bold text-slate-700">{{ keypad.serial_number }}</span>
                        <Button icon="pi pi-times" text rounded severity="danger" @click="removeKeypadFromTransfer(keypad.id)" class="w-6 h-6" />
                    </div>
                    <div v-if="keypadsToTransfer.length === 0" class="text-center py-4 text-slate-400 italic text-xs">
                        {{ t('keypads.messages.no_keypad_selected') }}
                    </div>
                </div>
            </div>


        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="transferDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="t('keypads.actions.confirm_transfer')" icon="pi pi-check-circle"  @click="confirmTransfer" :loading="transferForm.processing" class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs" :disabled="keypadsToTransfer.length === 0" />
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

</style>
