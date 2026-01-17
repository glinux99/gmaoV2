<script setup>
import { ref, computed, onMounted, watch } from 'vue';
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
    zones: Object, // Changed from regions to zones (paginated)
    regions: Array,
});

// --- ÉTATS RÉACTIFS ---
const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const zoneDialog = ref(false); // Renamed from labelDialog
const importDialog = ref(false);
const editing = ref(false);
const loading = ref(false);
const selectedZones = ref(null); // Renamed from selectedRegions
const op = ref(); // Ref for OverlayPanel

// --- FORMULAIRES ---
const form = useForm({
    id: null,
    // name: '',
    title: '',
    description: '',
    region_id: null,
    nomenclature: '',
    number: null,
});

// --- SYSTÈME DE FILTRES AVANCÉS (V11 Custom) ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    nomenclature: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    title: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const lazyParams = ref({
    first: props.zones.from - 1,
    rows: props.zones.per_page || 10,
    sortField: 'created_at',
    sortOrder: -1, // -1 pour desc
    filters: filters.value
});

const loadLazyData = () => {
    loading.value = true;

    const queryParams = {
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
        rows: lazyParams.value.rows,
        sortField: lazyParams.value.sortField,
        sortOrder: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
        // On envoie l'objet filtres tel quel, le contrôleur s'en occupera
        filters: lazyParams.value.filters
    };

    router.get(route('zones.index'), queryParams, {
        preserveState: true,
        preserveScroll: true, // Évite que la page remonte au changement de page
        onFinish: () => { loading.value = false; }
    });
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nomenclature: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        title: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
    lazyParams.value.filters = filters.value;
    loadLazyData();
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData();
};

const onFilter = (event) => {
    lazyParams.value.filters = filters.value;
    loadLazyData();
};

// --- CONFIGURATION DES COLONNES ---
const allColumns = ref([
 { field: 'nomenclature', header: 'Nomenclature', default: true },
 { field: 'region.designation', header: 'Région Parente', default: true },
 { field: 'title', header: 'Titre', default: true },
 { field: 'description', header: 'Description', default: false },
]);
const visibleColumns = ref(allColumns.value.filter(col => col.default).map(col => col.field));

const toggleColumnSelection = (event) => {
 op.value.toggle(event);
};

const rowsPerPageOptions = ref([10, 25, 50, 100]);

watch(() => lazyParams.value.rows, (newValue) => {
    // Recharger les données quand le nombre de lignes par page change
    loadLazyData();
});

// Watch for changes to auto-complete nomenclature
watch([() => form.region_id, () => form.number, () => form.title], ([newRegionId, newNumber, newTitle]) => {
    let nomenclatureParts = [];
    const region = props.regions.find(r => r.id === newRegionId);

    if (region && region.code) {
        nomenclatureParts.push(region.code);
    }
    if (newNumber !== null && newNumber !== '') {
        nomenclatureParts.push(`Zone ${newNumber}`);
    }
    if (newTitle) {
        nomenclatureParts.push(`/ ${newTitle}`);
    }
    form.nomenclature = nomenclatureParts.join(' - ').replace(' - /', ' /');
});


// --- MÉTHODES D'ACTION ---
const openNew = () => {
    form.reset();
    editing.value = false;
    zoneDialog.value = true;
};

const editZone = (zone) => {
    form.clearErrors();
    Object.assign(form, zone);
    editing.value = true;
    zoneDialog.value = true;
};

const saveZone = () => {
    loading.value = true;
    const url = editing.value ? route('zones.update', form.id) : route('zones.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            zoneDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Zone enregistrée', life: 3000 });
            loading.value = false;
        },
        onError: () => { loading.value = false; }
    });
};

const deleteZone = (zone) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer la zone "${zone.title}" ?`,
        header: 'Attention',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('zones.destroy', zone.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Info', detail: 'Supprimé' })
            });
        }
    });
};

const deleteSelectedZones = () => {
     confirm.require({
         message: `Voulez-vous vraiment supprimer les ${selectedZones.value.length} zones sélectionnées ?`,
         header: 'Confirmation de suppression multiple',
         icon: 'pi pi-exclamation-triangle',
         acceptClass: 'p-button-danger',
         accept: () => {
             const ids = selectedZones.value.map(z => z.id);
             router.post(route('zones.bulkDestroy'), { ids: ids }, {
                 onSuccess: () => { toast.add({ severity: 'info', summary: 'Info', detail: 'Zones supprimées', life: 3000 }); selectedZones.value = null; },
                 onError: (errors) => toast.add({ severity: 'error', summary: 'Erreur', detail: errors.error || 'Échec de la suppression', life: 3000 })
             });
         }
     });
};

// --- OPTIONS DE CONFIGURATION (I18N) ---

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Zones" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
 <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
 <i class="pi pi-map-marker text-2xl text-white"></i>
                    </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Gestion des
                         <span class="text-primary-600">Zones</span>
                    </h1>
                    <p class="text-slate-500 font-medium">Organisation des localisations géographiques</p>
                </div>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('regions.actions.resetFilters')" icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" />
                    <Button :label="t('regions.actions.add')" icon="pi pi-plus"  raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="zones.data"
                    v-model:selection="selectedZones"
                    v-model:filters="filters"
                    dataKey="id"
                    :loading="loading"
                    :paginator="true"
                    :rows="lazyParams.rows"
                    :totalRecords="zones.total"
                    :lazy="true"
                    @page="onPage($event)"
                    @sort="onPage($event)"
                    @filter="onFilter($event)"
                    v-model:first="lazyParams.first"
                    :sortField="lazyParams.sortField"
                    :sortOrder="lazyParams.sortOrder"
                    filterDisplay="menu"
                    :globalFilterFields="['nomenclature', 'title', 'region.designation']"
                    class="p-datatable-custom"
                    removableSort

                >
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher une zone..." class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <label for="rowsPerPage" class="text-sm font-medium text-slate-600">Lignes par page:</label>
                                <Dropdown v-model="lazyParams.rows" :options="rowsPerPageOptions"
                                          class="p-dropdown-sm rounded-xl border-slate-200"
                                          id="rowsPerPage" />
                            </div>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="toggleColumnSelection" />
                                <Button v-if="selectedZones && selectedZones.length > 0"
                                        :label="t('common.deleteSelection', { count: selectedZones.length })"
                                        icon="pi pi-trash" severity="danger" raised
                                        @click="deleteSelectedZones" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column field="nomenclature" header="Nomenclature" sortable filterField="nomenclature" :showFilterMatchModes="false" v-if="visibleColumns.includes('nomenclature')">
                        <template #body="{ data }" v-if="visibleColumns.includes('nomenclature')">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.nomenclature ? data.nomenclature[0] : 'Z'" shape="circle" class="bg-primary-50 text-primary-600 font-bold" />
                                <span class="font-bold text-slate-700">{{ data.nomenclature }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" placeholder="Chercher par nomenclature" />
                        </template>
                    </Column>
                    <Column field="title" header="Titre" sortable filterField="title" :showFilterMatchModes="false" v-if="visibleColumns.includes('title')">
                        <template #body="{ data }">
                            <span class="font-medium text-slate-600">{{ data.title }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" placeholder="Chercher par titre" />
                        </template>
                    </Column>

                    <Column field="region.designation" header="Région Parente" sortable filterField="region.designation" :showFilterMatchModes="false" v-if="visibleColumns.includes('region.designation')">
                        <template #body="{ data }">
                            <Tag :value="data.region?.designation || 'N/A'" severity="info" class="rounded-lg px-3 uppercase text-[10px]" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="props.regions" optionLabel="designation" optionValue="designation" placeholder="Toutes les régions" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column field="description" header="Description" v-if="visibleColumns.includes('description')">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-600">{{ data.description || 'N/A' }}</span>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen class="min-w-[120px]">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editZone(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteZone(data)" />
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
            v-model:visible="zoneDialog"
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
                        <h4 class="font-black text-slate-100 m-0">Configuration de la Zone</h4>
                        <p class="text-xs text-slate-500 m-0">Renseignez les informations de la zone</p>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="zoneDialog = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
              <div class="space-y-6 p-2">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="flex flex-col gap-2">
            <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Région Parente</label>
            <Dropdown
                v-model="form.region_id"
                :options="props.regions"
                optionLabel="designation"
                optionValue="id"
                filter
                class="w-full rounded-xl border-slate-200 shadow-sm"
                placeholder="Sélectionner une région"
            />
            <small v-if="form.errors.region_id" class="text-red-500 font-medium italic">{{ form.errors.region_id }}</small>
        </div>
 <div class="flex flex-col gap-2">

            <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Numéro de la zone</label>
            <InputNumber
                v-model="form.number"
                inputId="zoneNumber"
                :useGrouping="false"
                class="w-full rounded-xl border-slate-200"
                inputClass="py-3 rounded-xl border-none w-full"
                placeholder="Ex: 1"
            />
            <small v-if="form.errors.number" class="text-red-500 font-medium italic">{{ form.errors.number }}</small>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <div class="flex flex-col gap-2">
            <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Titre de la zone</label>
            <IconField iconPosition="left">
                <InputIcon class="pi pi-tag" />
                <InputText
                    v-model="form.title"
                    class="w-full py-3 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50"
                    placeholder="Ex: Zone d'activité économique"
                />
            </IconField>
            <small v-if="form.errors.title" class="text-red-500 font-medium italic">{{ form.errors.title }}</small>
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Nomenclature</label>
            <InputText
                v-model="form.nomenclature"
                class="w-full py-3 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50"
                placeholder="Ex: ZAE-001"
            />
            <small v-if="form.errors.nomenclature" class="text-red-500 font-medium italic">{{ form.errors.nomenclature }}</small>
        </div>
    </div>

    <div class="flex flex-col gap-2">
        <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Description</label>
        <textarea
            v-model="form.description"
            rows="4"
            class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-primary-50 transition-all text-sm outline-none shadow-sm"
            placeholder="Notes additionnelles sur cette zone..."
        ></textarea>
        <small v-if="form.errors.description" class="text-red-500 font-medium italic">{{ form.errors.description }}</small>
    </div>
</div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="zoneDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="editing ? t('common.update') : t('common.save')"
                            icon="pi pi-check-circle" severity="indigo"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                            @click="saveZone" :loading="loading" />
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
