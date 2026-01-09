<script setup>
import { ref, computed, watch , onMounted} from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import FileUpload from 'primevue/fileupload';
import OverlayPanel from 'primevue/overlaypanel';
import Textarea from 'primevue/textarea';
import AutoComplete from 'primevue/autocomplete';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ToggleButton from 'primevue/togglebutton';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import { useSpareParts } from '@/composables/useSpareParts';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

const props = defineProps({
    connections: Object,
    regions: Array,
    zones: Array,
    connectionStatuses: Array,
    queryParams: Object,
    meters: Array,
    keypads: Array,
    spareParts: Array,
});

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const isImportModalOpen = ref(false);
const loading = ref(false);
const isModalOpen = ref(false);

// --- INITIALISATION DU FORMULAIRE (TOUS LES CHAMPS DU MODÈLE) ---
const form = useForm({
    id: null,
    customer_code: '',
    region_id: null,
    zone_id: null,
    status: 'pending',
    first_name: '',
    last_name: '',
    phone_number: '',
    secondary_phone_number: '',
    gps_latitude: null,
    gps_longitude: null,
    customer_type: 'individual',
    customer_type_details: '',
    commercial_agent_name: '',
    amount_paid: 0,
    payment_number: '',
    payment_voucher_number: '',
    payment_date: null,
    is_verified: false,
    connection_type: '',
    connection_date: null,
    meter_id: null,
    keypad_id: null,
    cable_section: '',
    meter_type_connected: '',
    cable_length: null,
    box_type: '',
    meter_seal_number: '',
    box_seal_number: '',
    phase_number: 1,
    amperage: '',
    voltage: 220,
    with_ready_box: false,
    tariff: '',
    tariff_index: null,
    pole_number: '',
    distance_to_pole: null,
    needs_small_pole: false,
    bt_poles_installed: 0,
    small_poles_installed: 0,
    additional_meter_1: '',
    additional_meter_2: '',
    additional_meter_3: '',
    rccm_number: '',
    client_search: null,
    spare_parts_used: []
});

// --- GESTION RÉACTIVE DU STOCK LOCAL (POUR MATÉRIAUX) ---
const localSpareParts = ref([]);

onMounted(() => {
    localSpareParts.value = JSON.parse(JSON.stringify(props.spareParts || []));
});

watch(() => props.spareParts, (newParts) => {
    localSpareParts.value = JSON.parse(JSON.stringify(newParts || []));
}, { deep: true });

// --- COMPOSABLE SPARE PARTS (POUR MATÉRIAUX) ---
const {
    sparePartDialogVisible,
    sparePartData,
    getSparePartReference,
    openSparePartDialog,
    saveSparePart,
    removeSparePart,
} = useSpareParts(form, ref(localSpareParts), 'spare_parts_used');

const sparePartOptions = computed(() => {
    const regionId = form.region_id;
    if (!regionId) return [];

    return (localSpareParts.value || []).map(part => {
        const stockInRegion = part.stocks_by_region?.[regionId] || 0;
        return {
            label: `${part.reference} (${t('sparePartMovements.stockLabel')}: ${stockInRegion})`,
            value: part.id,
        };
    }).filter(part => part.label.includes(`Stock actuel:`) && !part.label.includes(`Stock actuel: 0`));
});

// --- ZONES FILTRÉES ---
const filteredZones = computed(() => {
    if (!form.region_id) {
        return props.zones; // Retourne toutes les zones si aucune région n'est sélectionnée
    }
    return props.zones.filter(zone => zone.region_id === form.region_id);
});

// --- GESTION DATATABLE & FILTRES ---
const dt = ref();
const op = ref();
const selectedConnections = ref([]);

const lazyParams = ref({
    first: props.connections.from - 1,
    rows: props.connections.per_page || 10,
    sortField: props.queryParams?.sortField || 'created_at',
    sortOrder: props.queryParams?.sortOrder === 'desc' ? -1 : 1,
    filters: {
        'global': { value: props.queryParams?.filters?.global?.value || null, matchMode: FilterMatchMode.CONTAINS },
        'customer_code': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'full_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'status': { value: null, matchMode: FilterMatchMode.EQUALS },
        'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    }
});

const loadLazyData = () => {
    loading.value = true;
    router.get(route('connections.index'), {
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
        per_page: lazyParams.value.rows,
        sortField: lazyParams.value.sortField,
        sortOrder: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
        filters: lazyParams.value.filters,
    }, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const onPage = (event) => {
    lazyParams.value = { ...lazyParams.value, ...event };
    loadLazyData();
};

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'customer_code': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'full_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'status': { value: null, matchMode: FilterMatchMode.EQUALS },
    'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const initFilters = () => {
    lazyParams.value.filters = {
        'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'customer_code': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'full_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'status': { value: null, matchMode: FilterMatchMode.EQUALS },
        'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
    loadLazyData();
};

const openCreate = () => { form.reset(); isModalOpen.value = true; };

const openEdit = (data) => {
    form.clearErrors();
    Object.keys(form.data()).forEach(key => {
        if (['connection_date', 'payment_date'].includes(key)) {
            form[key] = data[key] ? new Date(data[key]) : null;
        } else {
            form[key] = data[key];
        }
    });
    form.client_search = { full_name: `${data.first_name} ${data.last_name}` };
    isModalOpen.value = true;
};

const submit = () => {
    // Étape 1: Préparation des données finales
    // form.service_order_cost = serviceOrderCost.value; // Not applicable here

    // Convertir les objets Date en ISO string formaté pour MySQL
    const dataToSend = {
        ...form.data(),
        connection_date: form.connection_date ? new Date(form.connection_date).toISOString().slice(0, 19).replace('T', ' ') : null,
        payment_date: form.payment_date ? new Date(form.payment_date).toISOString().slice(0, 19).replace('T', ' ') : null,
    };
    console.log(dataToSend);
    // Déterminer la route et la méthode
    const method = form.id ? 'put' : 'post';
    const routeName = !form.id ? 'connections.store' : 'connections.update';
    const routeParams = form.id;
    const successMessage = form.id ? t('connections.toast.updateSuccess') : t('connections.toast.saveSuccess');

    const handler = {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: successMessage, life: 3000 });
        },
        onError: (errors) => {
            console.error(errors);
            toast.add({ severity: 'error', summary: t('common.error'), detail: t('connections.toast.saveError'), life: 3000 });
        }
    };
    if (method === 'post') {
        form.transform(() => dataToSend).post(route(routeName), handler);
    } else {
        form.transform(() => dataToSend).put(route(routeName, routeParams), handler);
    }
};
const allColumns = computed(() => [
    { field: 'customer_code', header: t('connections.table.customerCode'), sortable: true },
    { field: 'full_name', header: t('connections.table.fullName'), sortable: true },
    { field: 'status', header: t('connections.table.status'), sortable: true },
    { field: 'region.designation', header: t('connections.table.region'), sortable: true },
    { field: 'zone.title', header: t('connections.table.zone'), sortable: false },
    { field: 'connection_date', header: t('connections.table.connectionDate'), sortable: true },
    { field: 'amount_paid', header: t('connections.table.amountPaid'), sortable: true },
    { field: 'phone_number', header: t('connections.table.phone') },
    { field: 'meter.serial_number', header: t('connections.table.meterNumber') },
    { field: 'keypad.serial_number', header: t('connections.table.keypadNumber') },
]);

const selectedColumnFields = ref(['customer_code', 'full_name', 'status', 'region.designation', 'connection_date', 'amount_paid']);
const displayedColumns = computed(() => allColumns.value.filter(col => selectedColumnFields.value.includes(col.field)));

const stats = computed(() => {
    const data = props.connections.data || [];
    return {
        total: props.connections.total || 0,
        active: data.filter(c => c.status === 'active').length,
        verified: data.filter(c => c.is_verified).length,
        pending: data.filter(c => c.status === 'pending').length,
    };
});

const getStatusSeverity = (status) => {
    const statusMap = {
        active: 'success',
        pending: 'warning',
        suspended: 'danger',
        fraud: 'danger',
        inactive: 'info'
    };
    return statusMap[status] || 'secondary';
};

const exportCSV = () => dt.value.exportCSV();

const confirmDeleteSelected = () => {
    confirm.require({
        message: t('connections.confirm.bulkDeleteMessage', { count: selectedConnections.value.length }),
        header: t('connections.confirm.deleteHeader'),
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            // Implement bulk delete logic here
        },
    });
};

const handleFileUpload = (event) => {
    const file = event.files[0];
    router.post(route('connections.import'), { file }, { onSuccess: () => { isImportModalOpen.value = false; toast.add({ severity: 'success', summary: 'Succès', detail: 'Importation démarrée avec succès.' }); } });
};
const generateCustomerCode = () => {
    // Génère un code unique basé sur le temps et un aléa, commençant par 15.
    // Prend les 6 derniers chiffres du timestamp en millisecondes pour une haute unicité.
    const timePart = Date.now().toString().slice(-6);
    // Ajoute un nombre aléatoire pour éviter les collisions si plusieurs appels ont lieu dans la même milliseconde.
    const randomPart = Math.floor(Math.random() * 90); // 0-89
    const uniqueCode = `15${timePart}${randomPart}`;
    return `VE-CLI-${uniqueCode}`;
};

// Surveillance de l'ouverture pour le code auto-généré
watch(() => isModalOpen.value, (isOpen) => {
    if (isOpen && !form.id && !form.customer_code) {
        form.customer_code = generateCustomerCode();
    }
});


</script>

<template>
    <AppLayout>
        <Head :title="t('connections.title')" />
        <Toast />
        <ConfirmDialog />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-5">
                    <div class="h-16 w-16 bg-slate-900 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="pi pi-bolt text-2xl text-yellow-400"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase" v-html="t('connections.titleHtml')"></h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('connections.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button label="Importer" icon="pi pi-upload" severity="secondary" outlined @click="isImportModalOpen = true" />
                    <Button :label="t('connections.addNew')" icon="pi pi-plus" class="shadow-lg shadow-primary-200" @click="openCreate" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="stat in ['total', 'active', 'verified', 'pending']" :key="stat" class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center">
                        <i :class="`pi ${ {total: 'pi-box', active: 'pi-check-circle', verified: 'pi-shield', pending: 'pi-clock'}[stat] } text-2xl text-slate-500`"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats[stat] }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t(`connections.stats.${stat}`) }}</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable ref="dt" :value="connections.data" paginator :rows="connections.per_page" class="p-datatable-sm quantum-table" dataKey="id" filterDisplay="menu"
                           :lazy="true" @page="onPage" @sort="onPage" @filter="onPage"
                           :totalRecords="connections.total" :loading="loading"
                           v-model:first="lazyParams.first"
                           v-model:filters="lazyParams.filters" :globalFilterFields="['customer_code', 'full_name', 'status', 'region.designation']"
                           v-model:selection="selectedConnections" :rowsPerPageOptions="[10, 25, 50, 100]"
                           paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                           :currentPageReportTemplate="t('common.paginationReport', { item: 'raccordements' })">
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="lazyParams.filters['global'].value" :placeholder="t('connections.searchPlaceholder')"
                                           @keydown.enter="loadLazyData"
                                           class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button v-if="selectedConnections.length" icon="pi pi-trash" :label="t('common.deleteSelected', { count: selectedConnections.length })" severity="danger" text @click="confirmDeleteSelected" />
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" v-tooltip.bottom="t('common.exportCSV')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="t('common.selectColumns')" />
                            </div>
                        </div>
                    </template>
                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable" :filterField="col.field">
                        <template #body="{ data, field }">
                            <span v-if="field === 'full_name'" class="font-bold">{{ data.first_name }} {{ data.last_name }}</span>
                            <Tag v-else-if="field === 'status'" :value="t(`connections.status.${data.status}`, data.status)" :severity="getStatusSeverity(data.status)" class="uppercase text-[9px] px-2" />
                            <span v-else-if="field === 'amount_paid'">{{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(data.amount_paid) }}</span>
                            <span v-else-if="field.includes('date')">{{ data[field] ? new Date(data[field]).toLocaleDateString() : '-' }}</span>
                            <span v-else-if="field === 'region.designation'">{{ data.region?.designation }}</span>
                            <span v-else-if="field === 'zone.title'">{{ data.zone?.title }}</span>
                            <span v-else-if="field === 'meter.serial_number'">{{ data.meter?.serial_number }}</span>
                            <span v-else-if="field === 'keypad.serial_number'">{{ data.keypad?.serial_number }}</span>
                            <span v-else class="text-slate-600 text-sm truncate" :title="data[field]">{{ data[field] }}</span>
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="['customer_code', 'full_name', 'region.designation'].includes(col.field)">
                            <InputText v-model="filterModel.constraints[0].value" type="text" @keydown.enter="filterCallback()" class="p-column-filter" :placeholder="`Filtrer par ${col.header}`" />
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'status'">
                            <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="props.connectionStatuses" placeholder="Statut" class="p-column-filter" showClear/>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" v-tooltip.top="'Modifier'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <OverlayPanel ref="op">
            <div class="font-semibold mb-3">{{ t('common.columnSelector.title') }}</div>
            <MultiSelect v-model="selectedColumnFields" :options="allColumns" optionLabel="header" optionValue="field" display="chip" class="w-full max-w-xs" />
        </OverlayPanel>

  <Dialog v-model:visible="isModalOpen" modal :header="false" :closable="false"
    class="w-full max-w-7xl border-none shadow-2xl"
    :pt="{
        root: { class: 'rounded-2xl overflow-hidden' },
        mask: { style: 'backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.6)' }
    }">


        <div class="px-8 py-5 bg-slate-900 flex justify-between items-center shadow-xl relative z-10 rounded-xl">
            <div class="flex items-center gap-5">
                <div class="p-3 bg-blue-500/10 rounded-2xl border border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.3)]">
                    <i class="pi pi-bolt text-blue-400 text-2xl animate-pulse"></i>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-lg font-black uppercase tracking-widest text-white">
                        {{ form.id ? 'Modifier la Connexion' : 'Nouvelle Connexion' }}
                    </h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-300 text-[10px] font-bold rounded uppercase tracking-tighter border border-blue-500/30">
                            {{ form.customer_code }}
                        </span>
                    </div>
                </div>
            </div>
            <Button icon="pi pi-times" severity="secondary" rounded @click="isModalOpen = false"
                class="!text-white !bg-white/10 hover:!bg-white/20 !border-none transition-all" />
        </div>

        <div class="bg-white">
            <TabView class="custom-tabs">

                <TabPanel>
                    <template #header>
                        <div class="flex items-center gap-2 px-2"><i class="pi pi-user text-lg"></i><span class="font-bold">Informations Client</span></div>
                    </template>

                    <div class="p-8 space-y-8 animate-fadein">
                        <div class="flex flex-col md:flex-row gap-6 p-6 bg-blue-50/30 rounded-3xl border border-blue-100 items-end">
                            <div class="flex flex-col gap-2 w-full md:w-1/3">
                                <label class="text-[10px] font-black uppercase text-blue-600 tracking-[0.2em] ml-1 flex items-center gap-2">
                                    <i class="pi pi-lock text-[9px]"></i> Code Client Système
                                </label>
                                <div class="relative">
                                    <i class="pi pi-id-card absolute left-4 top-1/2 -translate-y-1/2 text-blue-400"></i>
                                    <InputText v-model="form.customer_code" readonly class="w-full h-11 !pl-12 !bg-blue-50/50 !border-blue-200 !text-blue-700 font-mono font-bold rounded-2xl cursor-not-allowed shadow-inner" />
                                </div>
                            </div>
                            <p class="text-[11px] text-blue-500/70 pb-2 italic font-medium"><i class="pi pi-info-circle mr-1"></i> Généré automatiquement.</p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-6 bg-white p-7 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>
                                <h3 class="text-xs font-black uppercase text-slate-800 tracking-widest flex items-center gap-2 mb-6">Identité & Contacts</h3>
                                <div class="grid grid-cols-2 gap-5">
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Prénom</label>
                                        <InputText v-model="form.first_name" class="h-11 rounded-2xl border-slate-200" /></div>
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Nom</label>
                                        <InputText v-model="form.last_name" class="h-11 rounded-2xl border-slate-200" /></div>
                                </div>
                                <div class="grid grid-cols-2 gap-5 pt-2">
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Téléphone 1</label>
                                        <InputText v-model="form.phone_number" class="h-11 rounded-2xl border-slate-200" /></div>
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Téléphone 2</label>
                                        <InputText v-model="form.secondary_phone_number" class="h-11 rounded-2xl border-slate-200" /></div>
                                </div>
                            </div>
                            <div class="space-y-6 bg-white p-7 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
                                <h3 class="text-xs font-black uppercase text-slate-800 tracking-widest flex items-center gap-2 mb-6">Secteur & Classification</h3>
                                <div class="grid grid-cols-2 gap-5">
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Type</label>
                                        <Dropdown v-model="form.customer_type" :options="['individual', 'business', 'government']" class="h-11 !rounded-2xl" /></div>
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Agent</label>
                                        <InputText v-model="form.commercial_agent_name" class="h-11 rounded-2xl" /></div>
                                </div>
                                <div class="grid grid-cols-2 gap-5 pt-2">
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Région</label>
                                        <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="name" optionValue="id" class="h-11 !rounded-2xl" /></div>
                                    <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-500 uppercase">Zone</label>
                                        <Dropdown v-model="form.zone_id" :options="filteredZones" optionLabel="name" optionValue="id" :disabled="!form.region_id" class="h-11 !rounded-2xl" /></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <TabPanel>
                    <template #header>
                        <div class="flex items-center gap-2 px-2"><i class="pi pi-cog text-lg"></i><span class="font-bold">Détails Techniques</span></div>
                    </template>
                    <div class="p-8 space-y-8 animate-fadein">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm space-y-4">
                                <label class="text-[10px] font-black uppercase text-blue-600 tracking-widest block mb-4 underline decoration-2 underline-offset-8">Matériel Central</label>
                                <div class="flex flex-col gap-1.5"><label class="text-[11px] font-bold text-slate-500">Compteur</label><Dropdown v-model="form.meter_id" filter class="h-11 !rounded-xl" /></div>
                                <div class="flex flex-col gap-1.5"><label class="text-[11px] font-bold text-slate-500">Clavier</label><Dropdown v-model="form.keypad_id" filter class="h-11 !rounded-xl" /></div>

                                <!-- Section Matériaux Utilisés -->
                                <div class="pt-4 border-t border-slate-100">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-[11px] font-bold text-slate-500">Matériaux Utilisés</label>
                                        <Button icon="pi pi-plus" rounded text severity="secondary" size="small" @click="openSparePartDialog('used')" />
                                    </div>

                                    <div class="space-y-1.5 max-h-24 overflow-y-auto custom-scrollbar pr-2">
                                        <div v-for="(part, idx) in form.spare_parts_used" :key="idx" class="flex items-center gap-2 p-2 bg-blue-50/50 rounded-lg text-[10px] border border-blue-100 group">
                                            <b class="text-blue-700">x{{ part.quantity }}</b>
                                            <span class="flex-grow truncate text-slate-600 font-medium">{{ getSparePartReference(part.id) }}</span>
                                            <i class="pi pi-trash text-red-400 cursor-pointer opacity-0 group-hover:opacity-100" @click="removeSparePart('used', idx)"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm space-y-4">
                                <label class="text-[10px] font-black uppercase text-blue-600 tracking-widest block mb-4 underline decoration-2 underline-offset-8">Spécifications</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5"><label class="text-[11px] font-bold text-slate-500 uppercase">Phasage</label><InputNumber v-model="form.phase_number" inputClass="h-11 rounded-xl w-full" /></div>
                                    <div class="flex flex-col gap-1.5"><label class="text-[11px] font-bold text-slate-500 uppercase">Voltage</label><InputNumber v-model="form.voltage" class="h-11 rounded-xl w-full" /></div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5"><label class="text-[11px] font-bold text-slate-500 uppercase">Ampérage</label><InputText v-model="form.amperage" class="h-11 rounded-xl" /></div>
                                    <div class="flex flex-col gap-1.5"><label class="text-[11px] font-bold text-slate-500 uppercase">Tarif</label><InputText v-model="form.tariff" class="h-11 rounded-xl" /></div>
                                </div>
                            </div>
                            <div class="p-6 bg-slate-900 rounded-3xl text-white space-y-4 shadow-xl relative overflow-hidden">
                                <label class="text-[10px] font-black uppercase text-blue-400 tracking-widest block mb-4">Scellés & Sécurité</label>
                                <div class="flex flex-col gap-1.5"><label class="text-[11px] text-slate-400 uppercase">Scellé Compteur</label><InputText v-model="form.meter_seal_number" class="h-10 !bg-white/10 !border-none !text-white !rounded-lg" /></div>
                                <div class="flex flex-col gap-1.5"><label class="text-[11px] text-slate-400 uppercase">Scellé Coffret</label><InputText v-model="form.box_seal_number" class="h-10 !bg-white/10 !border-none !text-white !rounded-lg" /></div>
                                <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl border border-white/10 mt-2">
                                    <label class="text-[10px] font-bold uppercase">Ready Box</label>
                                    <ToggleButton v-model="form.with_ready_box" onIcon="pi pi-check" offIcon="pi pi-times" class="h-8 w-14 !rounded-lg" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6 bg-slate-50 rounded-3xl border border-slate-100 shadow-inner">
                            <div class="space-y-4">
                                <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-4 flex items-center gap-2">
                                    <i class="pi pi-sitemap text-blue-500"></i> Réseau & Raccordement
                                </h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5 p-3 bg-white rounded-2xl border border-slate-100 shadow-sm font-bold text-slate-700">
                                        <label class="text-[10px] text-slate-500 uppercase">N° Poteau</label>
                                        <InputText v-model="form.pole_number" class="w-full !border-none !p-0 focus:!ring-0 text-sm bg-transparent" />
                                    </div>
                                    <div class="flex flex-col gap-1.5 p-3 bg-white rounded-2xl border border-slate-100 shadow-sm font-bold text-slate-700">
                                        <label class="text-[10px] text-slate-500 uppercase">Dist. (m)</label>
                                        <InputNumber v-model="form.distance_to_pole" class="w-full" inputClass="!border-none !p-0 focus:!ring-0 text-sm bg-transparent" />
                                    </div>
                                    <div class="flex flex-col gap-1.5 p-3 bg-white rounded-2xl border border-slate-100 shadow-sm font-bold text-slate-700">
                                        <label class="text-[10px] text-slate-500 uppercase">Câble (mm²)</label>
                                        <InputText v-model="form.cable_section" class="w-full !border-none !p-0 focus:!ring-0 text-sm bg-transparent" />
                                    </div>
                                    <div class="flex flex-col gap-1.5 p-3 bg-white rounded-2xl border border-slate-100 shadow-sm font-bold text-slate-700">
                                        <label class="text-[10px] text-slate-500 uppercase">Long. (m)</label>
                                        <InputNumber v-model="form.cable_length" class="w-full" inputClass="!border-none !p-0 focus:!ring-0 text-sm bg-transparent" />
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h3 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] mb-4 flex items-center gap-2">
                                    <i class="pi pi-compass text-red-500"></i> Coordonnées GPS
                                </h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500"><i class="pi pi-map-marker"></i></div>
                                        <div class="flex flex-col flex-grow">
                                            <label class="text-[9px] font-black text-slate-400 uppercase">Latitude</label>
                                            <InputText v-model="form.gps_latitude" class="w-full !border-none !p-0 focus:!ring-0 text-base font-mono font-bold text-slate-800 bg-transparent" placeholder="0.000000" />
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500"><i class="pi pi-map-marker rotate-90"></i></div>
                                        <div class="flex flex-col flex-grow">
                                            <label class="text-[9px] font-black text-slate-400 uppercase">Longitude</label>
                                            <InputText v-model="form.gps_longitude" class="w-full !border-none !p-0 focus:!ring-0 text-base font-mono font-bold text-slate-800 bg-transparent" placeholder="0.000000" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <TabPanel>
                    <template #header>
                        <div class="flex items-center gap-2 px-2"><i class="pi pi-wallet text-lg"></i><span class="font-bold">Administratif & Paiement</span></div>
                    </template>
                    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8 animate-fadein">
                        <div class="p-8 bg-gradient-to-br from-blue-700 to-indigo-900 rounded-[2rem] text-white shadow-2xl relative overflow-hidden border border-white/10">
                            <label class="text-[10px] font-black uppercase text-blue-200 tracking-widest block mb-8 opacity-70">Transactions & Ledger</label>
                            <div class="space-y-8 relative z-10">
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs font-bold text-blue-100 uppercase ml-1">Montant Versé (XOF)</label>
                                    <InputNumber v-model="form.amount_paid" mode="currency" currency="XOF" inputClass="!bg-white/10 !border-none !text-4xl !font-black !text-white w-full !p-3 !rounded-2xl" />
                                </div>
                                <div class="grid grid-cols-2 gap-6 pt-6 border-t border-white/10">
                                    <div class="flex flex-col gap-1"><label class="text-[10px] text-blue-200">N° Transaction</label>
                                        <InputText v-model="form.payment_number" class="!bg-white/5 !border-none !rounded-xl !p-3 !text-white" /></div>
                                    <div class="flex flex-col gap-1"><label class="text-[10px] text-blue-200">Date Paiement</label>
                                        <Calendar v-model="form.payment_date" class="!bg-white/5 w-full" inputClass="!bg-transparent !border-none !p-3 !text-white" /></div>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-5">
                            <h3 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">Dossier de Validation</h3>
                            <div class="flex flex-col gap-2"><label class="text-[11px] font-bold text-slate-600 uppercase">RCCM</label>
                                <InputText v-model="form.rccm_number" class="h-11 rounded-xl shadow-sm border-slate-200" /></div>
                            <div class="flex items-center justify-between p-4 bg-white rounded-2xl border-2 border-dashed border-slate-200 mt-4">
                                <div class="flex flex-col"><span class="text-sm font-black text-slate-700">Approbation Dossier</span><span class="text-[10px] text-slate-400 uppercase">Vérifié</span></div>
                                <ToggleButton v-model="form.is_verified" onIcon="pi pi-verified" offIcon="pi pi-times" class="h-10 px-6 !rounded-xl" />
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>
        </div>

        <template  #footer>
            <div class="flex justify-between items-center w-full px-4 py-2">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="isModalOpen = false" class="!rounded-xl px-6 h-11" />
                <div class="flex gap-4">
                    <Button :label="form.id ? t('common.update') : t('common.create')"
                        icon="pi pi-save"
                        class="!rounded-xl px-10 h-11 shadow-lg shadow-blue-500/30 font-black transition-all hover:scale-105 active:scale-95"
                        @click="submit"
                        :loading="form.processing" />
                </div>
            </div>
        </template>
    </Dialog>

        <Dialog v-model:visible="isImportModalOpen" modal header="Importer des Raccordements" class="w-full max-w-lg">
            <div class="p-fluid">
                <p class="mb-4">
                    Sélectionnez un fichier Excel (.xlsx) ou CSV (.csv) pour importer de nouveaux raccordements.
                    Assurez-vous que le fichier respecte le format attendu.
                </p>
                <FileUpload name="connections_import" @uploader="handleFileUpload" :multiple="false" accept=".xlsx,.csv" :maxFileSize="10000000" customUpload>
                    <template #empty>
                        <p>Glissez et déposez les fichiers ici pour les télécharger.</p>
                    </template>
                </FileUpload>
            </div>
        </Dialog>

        <!-- MODALE DE SÉLECTION DES MATÉRIAUX -->
        <Dialog v-model:visible="sparePartDialogVisible" modal :closable="false"
                class="quantum-dialog w-full max-w-lg shadow-2xl"
                :pt="{ mask: { style: 'backdrop-filter: blur(8px)' }, content: { class: 'p-0 rounded-2xl overflow-hidden' } }">

            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center">
                        <i class="pi pi-box text-blue-400 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">
                            Ajouter un matériau
                        </h2>
                        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">
                            {{ form.customer_code }}
                        </p>
                    </div>
                </div>
                <Button icon="pi pi-times" text rounded severity="secondary" @click="sparePartDialogVisible = false" />
            </div>

            <div class="p-6 space-y-6 bg-white">
                <div class="field">

                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">Références</label>
                    <MultiSelect v-model="sparePartData.ids" :options="sparePartOptions" optionLabel="label" optionValue="value" filter display="chip" placeholder="Rechercher une pièce..." class="w-full !rounded-xl" />
                </div>
                <div class="field">
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">Quantité</label>
                    <InputNumber v-model="sparePartData.quantity" showButtons :min="1" buttonLayout="horizontal" class="w-full" />
                </div>
            </div>
            <div class="p-4 bg-slate-100/60 flex justify-end gap-3 border-t border-slate-200"><Button label="Fermer" severity="secondary" @click="sparePartDialogVisible = false" class="font-bold text-xs" /><Button label="Valider" severity="info" @click="saveSparePart" class="font-bold text-xs rounded-lg shadow-lg shadow-blue-100" /></div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* STYLE V11 CUSTOM TOKENS */
.p-button-primary {
    background: #4f46e5;
    border: none;
    color: white;
    font-weight: 700;
    border-radius: 12px;
}

.card-v11 :deep(.p-datatable-thead > tr > th) {
    background: #fdfdfd;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.card-v11 :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s;
}

.card-v11 :deep(.p-datatable-tbody > tr:hover) {
    background: #f8faff !important;
}

.quantum-input :deep(.p-inputtext) {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

/* Transitions */
.animate-fadein {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

.quantum-table :deep(.p-datatable-thead > tr > th) {
    background: #fdfdfd;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.quantum-table :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s;
}
.quantum-table :deep(.p-datatable-tbody > tr:hover) {
    background: #f8faff !important;
}
.animate-fadein {
    animation: fadeIn 0.4s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
