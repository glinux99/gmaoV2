<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- Importations des composants PrimeVue ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import SelectButton from 'primevue/selectbutton';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import AutoComplete from 'primevue/autocomplete';
import OverlayPanel from 'primevue/overlaypanel';
import Calendar from 'primevue/calendar';
import MultiSelect from 'primevue/multiselect';
import debounce from 'lodash/debounce';

import axios from 'axios';
const props = defineProps({
    stockMovements: Object,
    filters: Object,
    regions: Array,
    users: Array,
    // On passe tous les types d'items "déplaçables"
    movableItems: Object, // Ex: { spare_parts: [...], equipments: [...], meters: [...], keypads: [...] }
    masterMovableItems: Object, // NOUVEAU: Liste complète pour les entrées
    queryParams: Object,
});

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const movementDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const dt = ref();
const op = ref();
const selectedMovements = ref([]);
const loading = ref(false);

// --- GESTION DES FILTRES ---
const filters = ref();
const initFilters = () => {
    filters.value = {
        'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'type': { operator: FilterOperator.OR, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
        'movable_type': { operator: FilterOperator.OR, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
        'region': { value: null, matchMode: FilterMatchMode.EQUALS },
        'date': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
        'user.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
};
initFilters();
const lazyParams = ref({
    first: props.stockMovements.from - 1,
    rows: props.stockMovements.per_page,
    sortField: props.queryParams.sortField || 'date',
    sortOrder: props.queryParams.sortOrder === 'desc' ? -1 : 1,
    filters: filters.value
});

const onPage = (event) => {
    loading.value = true;
    lazyParams.value = event;
    const queryParams = {
        ...event,
        page: event.page + 1,
        'filters[region][value]': filters.value.region.value,
        per_page: event.rows,
    };

    router.get(route('stock-movements.index'), queryParams, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};


const form = useForm({
    id: null,
    items: [], // Le "panier" d'articles
    type: 'sortie',
    date: new Date(),
    notes: null,
    responsible_user_id: null,
    intended_for_user_id: null,
    source_region_id: null,
    destination_region_id: null,
});

// --- GESTION DES COLONNES ---
const allColumns = ref([
    { field: 'movable_type', header: t('stockMovements.table.itemType') },
    { field: 'movable', header: t('stockMovements.table.item') },
    { field: 'type', header: t('stockMovements.table.type') },
    { field: 'quantity', header: t('stockMovements.table.quantity') },
    { field: 'source_region', header: t('stockMovements.table.source') },
    { field: 'destination_region', header: t('stockMovements.table.destination') },
    { field: 'user', header: t('stockMovements.table.user') },
    { field: 'stock_at_movement', header: 'Stock au Mvt.' }, // NOUVELLE COLONNE
    { field: 'date', header: t('stockMovements.table.date') },
]);
const visibleColumns = ref(['movable_type', 'movable', 'type', 'quantity', 'user', 'date']);


// --- Logique du formulaire ---

const movableTypeOptions = ref([
    { label: t('stockMovements.movableTypes.spare_part'), value: 'App\\Models\\SparePart' },
    { label: t('stockMovements.movableTypes.equipment'), value: 'App\\Models\\Equipment' },
    { label: t('stockMovements.movableTypes.meter'), value: 'App\\Models\\Meter' },
    { label: t('stockMovements.movableTypes.keypad'), value: 'App\\Models\\Keypad' },
    { label: t('stockMovements.movableTypes.engin'), value: 'App\\Models\\Engin' },
]);

const movementTypeOptions = ref([
    { label: t('stockMovements.movementTypes.entry'), value: 'entry' },
    { label: t('stockMovements.movementTypes.exit'), value: 'exit' },
    { label: t('stockMovements.movementTypes.transfer'), value: 'transfer' },
]);


const availableItems = computed(() => {
    if (!newItem.value.movable_type) return [];

    // NOUVELLE LOGIQUE :
    // Si le mouvement est une 'entrée', on utilise la liste "maîtresse" complète.
    // Sinon (sortie/transfert), on utilise la liste filtrée par région.
    const sourceList = form.type === 'entry' ? props.masterMovableItems : props.movableItems;

    switch (newItem.value.movable_type) {
        case 'App\\Models\\SparePart': return sourceList.spare_parts || [];
        case 'App\\Models\\Equipment': return sourceList.equipments || [];
        case 'App\\Models\\Meter': return sourceList.meters || [];
        case 'App\\Models\\Keypad': return sourceList.keypads || [];
        case 'App\\Models\\Engin': return sourceList.engins || [];
        default: return [];
    }
});

const isItemSelectionDisabled = computed(() => {
    if (form.type === 'entry') {
        return !form.destination_region_id;
    }
    if (form.type === 'exit') {
        return !form.source_region_id;
    }
    if (form.type === 'transfer') {
        return !form.source_region_id || !form.destination_region_id;
    }
    return true; // Désactivé par défaut si aucun type n'est sélectionné
});

// --- Logique du panier ---
const newItem = ref({
    movable_type: null,
    movable_id: null,
    quantity: 1,
});

const addItemToCart = () => {
    if (!newItem.value.movable_type || !newItem.value.movable_id) return;

    // 1. Déterminer la liste source (complète pour les entrées, filtrée pour le reste)
    let sourceList;
    if (form.type === 'entry') sourceList = props.masterMovableItems;
    else sourceList = props.movableItems;

    // 2. Trouver la clé correspondante de manière fiable (ex: 'spare_parts')
    // On extrait le nom du modèle (ex: 'SparePart') de la chaîne 'App\Models\SparePart'
    const modelName = newItem.value.movable_type.split('\\').pop().toLowerCase();
    // On cherche une clé dans sourceList qui contient ce nom de modèle (ex: 'spare_parts' contient 'sparepart')
    const itemTypeKey = Object.keys(sourceList).find(key => key.replace('_', '').includes(modelName));

    if (!itemTypeKey) return; // Sécurité: si aucune clé ne correspond

    // 3. Trouver l'article dans la bonne liste
    const itemToAdd = sourceList[itemTypeKey]?.find(i => i.id === newItem.value.movable_id);
    if (!itemToAdd) return;

    // 4. Ajouter au panier et réinitialiser
    form.items.push({ ...newItem.value, item_details: itemToAdd });
    newItem.value = { movable_type: null, movable_id: null, quantity: 1 }; // Reset
};

const removeItemFromCart = (index) => {
    form.items.splice(index, 1);
};

const itemLabel = (item) => {
    // Retourne un label formaté selon le type d'objet
    if (!item) return '';

    // Affiche le stock calculé pour la région si disponible
    const stockInfo = item.stock_in_region !== null ? ` - Stock: ${item.stock_in_region}` : '';

    if (item.serial_number) return `${item.serial_number} (${item.model || 'N/A'})`; // Pour Meters, Keypads
    // Pour les pièces détachées, on affiche le stock calculé
    if (item.reference) return `${item.reference} (${item.label?.name || 'N/A'})${stockInfo}`;
    if (item.tag) return `${item.tag} - ${item.designation}`; // Pour Equipments
    return `ID: ${item.id}`;
};

watch(() => newItem.value.movable_type, () => {
    newItem.value.movable_id = null; // Réinitialiser l'item sélectionné quand le type change
});

watch(() => form.type, () => {
    // Réinitialiser les régions lors du changement de type
    form.source_region_id = null;
    form.destination_region_id = null;
    form.items = []; // Vider le panier
});

watch(() => form.source_region_id, (newRegionId, oldRegionId) => {
    form.items = [];
    newItem.value.movable_id = null;

    console.log('Source region changed. New region ID:', newRegionId, 'Old region ID:', oldRegionId);
    // Pour les sorties/transferts, on recharge les articles filtrés par la région source.
    if ((form.type === 'exit' || form.type === 'transfer') && newRegionId !== oldRegionId) {
        // On recharge la page avec le filtre de région pour obtenir les bons articles
        router.get(route('stock-movements.index'), {
            ...lazyParams.value,
            'filters[region_id][value]': newRegionId,
            'filters[type][value]': form.type, // Ajouter le type de mouvement

        }, {
            preserveState: true,
            preserveScroll: true,
        });
 console.log('Props loaded after region change:', props.movableItems);
    }
});

// --- NOUVEAU : Watcher pour la mise à jour du stock en temps réel ---
watch(() => form.items, (newItems, oldItems) => {
    if (form.type === 'entry') return; // Pas de gestion de stock pour les entrées

    const getListAndId = (item) => {
        const typeKey = Object.keys(localMovableItems.value).find(key => item.movable_type.toLowerCase().includes(key.slice(0, -1)));
        if (!typeKey) return { list: null, id: null };
        return { list: localMovableItems.value[typeKey], id: item.movable_id };
    };

    // Rétablir le stock des anciens items
    oldItems.forEach(oldItem => {
        const { list, id } = getListAndId(oldItem);
        if (list) {
            const itemInStock = list.find(i => i.id === id);
            if (itemInStock && typeof itemInStock.stock_in_region !== 'undefined') {
                itemInStock.stock_in_region += oldItem.quantity;
            }
        }
    });

    // Déduire le stock des nouveaux items
    newItems.forEach(newItem => {
        const { list, id } = getListAndId(newItem);
        if (list) {
            const itemInStock = list.find(i => i.id === id);
            if (itemInStock && typeof itemInStock.stock_in_region !== 'undefined') {
                itemInStock.stock_in_region -= newItem.quantity;
            }
        }
    });

}, { deep: true });


const isQuantityDisabled = computed(() => {
    if (!newItem.value.movable_type) return true;
    // La quantité est désactivée (forcée à 1) pour les items sérialisés
    return (newItem.value.movable_type !== 'App\\Models\\SparePart') && newItem.value.movable_type !== 'App\\Models\\Equipment';
});

watch(isQuantityDisabled, (disabled) => {
    if (disabled) {
        newItem.value.quantity = 1;
    }
});


// --- Actions (CRUD) ---

const openNew = () => {
    form.reset();
    form.items = []; // S'assurer que le panier est vide
    form.date = new Date();
    newItem.value = { movable_type: null, movable_id: null, quantity: 1 };
    editing.value = false;
    submitted.value = false;
    movementDialog.value = true;
};

const hideDialog = () => {
    form.reset();
    movementDialog.value = false;
};

const editMovement = (movement) => {
     editing.value = true;
    form.id = movement.id;
    form.type = movement.type;
    form.notes = movement.notes || '';
    form.date = new Date(movement.date);
    form.responsible_user_id = movement.responsible_user_id;
    form.items = [{
        movable_type: movement.movable_type,
        movable_id: movement.movable_id,
        quantity: movement.quantity,
        item_details: movement.movable
    }];
    movementDialog.value = true;
    // L'édition d'un mouvement de "panier" n'est pas standard.
    // On pourrait ouvrir un dialogue simplifié pour modifier les notes ou la date, mais pas les items.
};

const saveMovement = () => {
    submitted.value = true;

    const url = editing.value ? route('stock-movements.update', form.id) : route('stock-movements.store');
    const method = editing.value ? 'put' : 'post';

    if (form.items.length === 0) {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('stockMovements.toast.noItems'), life: 4000 });
        return;
    }
    console.log(form);

    // On transforme les données pour correspondre à la validation backend
    const dataToSend = {
        ...form.data(),
        items: form.items.map(item => ({
            movable_type: item.movable_type,
            movable_id: item.movable_id,
            quantity: item.quantity,
        })),
        date: form.date.toISOString().slice(0, 10), // Format YYYY-MM-DD
    };

    router.visit(url, {
        method: method,
        data: dataToSend,
        onSuccess: () => {
            movementDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: editing.value ? t('stockMovements.toast.updateSuccess') : t('stockMovements.toast.createSuccess'), life: 4000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du mouvement", errors);
            const errorDetail = Object.values(errors).flat().join(' ; ');
            toast.add({ severity: 'error', summary: t('common.error'), detail: errorDetail || t('stockMovements.toast.saveError'), life: 5000 });
        }
    });
};

const deleteMovement = (movement) => {
    confirm.require({
        message: t('stockMovements.confirm.deleteMessage', { id: movement.id }),
        header: t('confirm.deleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('stock-movements.destroy', movement.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: t('common.success'), detail: t('stockMovements.toast.deleteSuccess'), life: 3000 })
            });
        }
    });
};

const deleteSelectedMovements = () => {
    confirm.require({
        message: t('stockMovements.dialog.deleteSelectedMessage', { count: selectedMovements.value.length }),
        header: t('confirm.deleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = selectedMovements.value.map(m => m.id);
            // Assurez-vous d'avoir une route (ex: POST 'stock-movements/bulk-destroy') qui accepte un tableau d'IDs
            router.post(route('stock-movements.bulk-destroy'), { ids }, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('common.success'), detail: t('stockMovements.toast.deleteSuccess'), life: 3000 });
                    selectedMovements.value = [];
                },
                onError: () => toast.add({ severity: 'error', summary: t('common.error'), detail: t('stockMovements.toast.deleteError'), life: 5000 })
            });
        }
    });
};

const dialogTitle = computed(() => editing.value ? t('stockMovements.dialog.editTitle') : t('stockMovements.dialog.createTitle'));

const getMovableTypeLabel = (type) => {
    const option = movableTypeOptions.value.find(opt => opt.value === type);
    return option ? option.label : type.split('\\').pop();
};

const getMovableItemDisplay = (movable) => {
    if (!movable) return 'N/A';
    return movable.serial_number || movable.reference || movable.tag || movable.designation || `ID: ${movable.id}`;
};

const getMovementTypeSeverity = (type) => {
    switch (type) {
        case 'entry': return 'success';
        case 'exit': return 'danger';
        case 'transfer': return 'info';
        default: return 'secondary';
    }
};

const getMovementTypeIcon = (type) => {
    switch (type) {
        case 'entry': return 'pi pi-arrow-down';
        case 'exit': return 'pi pi-arrow-up';
        case 'transfer': return 'pi pi-arrows-h';
        default: return 'pi pi-question-circle';
    }
};

// --- STATISTIQUES ---
const movementStats = computed(() => {
    const stats = {
        total: props.stockMovements.data.length,
        entry: 0,
        exit: 0,
        transfer: 0,
    };
    props.stockMovements.data.forEach(movement => {
        stats[movement.type.toLowerCase()]++;
    });
    return stats;
});
</script>

<template>
    <AppLayout :title="t('stockMovements.title')">
        <Head :title="t('stockMovements.headTitle')" />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">
            <Toast />
            <ConfirmDialog />

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200 text-white text-2xl">
                        <i class="pi pi-sync"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                            {{ t('stockMovements.title') }} <span class="text-primary-600">GMAO</span>
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('stockMovements.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button :label="t('stockMovements.toolbar.addMovement')" icon="pi pi-plus"
                            class="shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-sync text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('stockMovements.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-arrow-down text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.entry }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('stockMovements.stats.entries') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center"><i class="pi pi-arrow-up text-2xl text-red-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.exit }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('stockMovements.stats.exits') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-arrows-h text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.transfer }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('stockMovements.stats.transfers') }}</div>
                    </div>
                </div>
            </div>


            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="stockMovements.data" ref="dt" dataKey="id" :rows="stockMovements.per_page" paginator
                        v-model:selection="selectedMovements"
                        :lazy="true" @page="onPage($event)" @sort="onPage($event)"
                        :totalRecords="stockMovements.total" :loading="loading"
                        v-model:first="lazyParams.first"
                        :sortField="lazyParams.sortField" :sortOrder="lazyParams.sortOrder"
                        :rowsPerPageOptions="[10, 25, 50, 100]"
                        class="p-datatable-sm quantum-table"
                        v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['movable.serial_number', 'movable.reference', 'movable.tag', 'user.name', 'notes']"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
                        :currentPageReportTemplate="t('common.paginationReport')">
<template #header>
    <div v-if="!selectedMovements.length" class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">

        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
            <Dropdown
                v-model="filters['region'].value"
                :options="regions"
                optionLabel="designation"
                optionValue="id"
                :placeholder="t('stockMovements.filter.byRegion')"
                showClear
                @change="onPage(lazyParams)"
                class="w-full md:w-64 h-11 !rounded-2xl !border-slate-200 !bg-slate-50/50 focus:!ring-2 focus:!ring-primary-500/20 focus:!bg-white transition-all duration-200"
            />

            <IconField iconPosition="left" class="w-full md:w-96">
                <InputIcon class="pi pi-search text-slate-400" />
                <InputText
                    v-model="filters['global'].value"
                    @input="debounce(() => onPage(lazyParams), 500)()"
                    :placeholder="t('stockMovements.toolbar.searchPlaceholder')"
                    class="w-full h-11 rounded-2xl border-slate-200 bg-slate-50/50 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-all duration-200"
                />
            </IconField>
        </div>

        <div class="flex items-center gap-2">
            <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl h-11" v-tooltip.bottom="t('common.resetFilters')" />
            <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" class="h-11 w-11" v-tooltip.bottom="t('common.exportCSV')" />
            <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" class="h-11 w-11" v-tooltip.bottom="t('common.selectColumns')" />
        </div>
    </div>

    <div v-else class="flex justify-between items-center p-4 bg-primary-50 rounded-xl">
        <span class="font-bold text-sm text-primary-700">
            {{ t('common.selectedCount', { count: selectedMovements.length }) }}
        </span>
        <Button
            :label="t('common.deleteSelection')"
            icon="pi pi-trash"
            severity="danger"
            @click="deleteSelectedMovements"
            class="rounded-xl h-11"
        />
    </div>
</template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" frozen></Column>

                    <Column v-if="visibleColumns.includes('movable_type')" field="movable_type" :header="t('stockMovements.table.itemType')" sortable filterField="movable_type" :showFilterMatchModes="false" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <Tag :value="getMovableTypeLabel(data.movable_type)" severity="secondary" class="font-bold" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="movableTypeOptions" optionLabel="label" optionValue="value" :placeholder="t('stockMovements.filter.byItemType')" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('movable')" field="movable" :header="t('stockMovements.table.item')" sortable style="min-width: 15rem;">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-md">{{ getMovableItemDisplay(data.movable) }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('type')" field="type" :header="t('stockMovements.table.type')" sortable filterField="type" :showFilterMatchModes="false" style="min-width: 10rem;">
                        <template #body="{ data }">
                            <Tag :value="t(`stockMovements.movementTypes.${data.type.toLowerCase()}`)"
                                 :severity="getMovementTypeSeverity(data.type)"
                                 :icon="getMovementTypeIcon(data.type)"
                                 class="uppercase text-[9px] px-2" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="movementTypeOptions" optionLabel="label" optionValue="value" :placeholder="t('stockMovements.filter.byType')" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('quantity')" field="quantity" :header="t('stockMovements.table.quantity')" sortable style="min-width: 8rem;" class="text-center">
                        <template #body="{ data }">
                            <span class="font-mono font-black text-lg">{{ data.quantity }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('stock_at_movement')" field="stock_at_movement" header="Stock au Mvt." sortable style="min-width: 8rem;" class="text-center">
                        <template #body="{ data }">
                            <span class="font-mono font-bold text-sm" :class="data.stock_at_movement > 0 ? 'text-slate-700' : 'text-red-500'">{{ data.stock_at_movement ?? 'N/A' }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('date')" field="date" :header="t('stockMovements.table.date')" sortable dataType="date" style="min-width: 10rem;" class="text-center">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">
                                {{ new Date(data.date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                            </span>
                        </template>
                        <template #filter="{ filterModel }">
                            <Calendar v-model="filterModel.value" dateFormat="yy-mm-dd" :placeholder="t('stockMovements.filter.byDate')"
                                      showIcon iconDisplay="input"
                                      selectionMode="range" :manualInput="false"
                                      class="p-column-filter" />
                        </template>
                    </Column>


                    <Column v-if="visibleColumns.includes('source_region')" field="source_region.designation" :header="t('stockMovements.table.source')" sortable style="min-width: 10rem;">
                        <template #body="{ data }">
                            <div v-if="data.source_region" class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-slate-400"></i>
                                <span class="text-xs font-medium text-slate-500">{{ data.source_region.designation }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('destination_region')" field="destination_region.designation" :header="t('stockMovements.table.destination')" sortable style="min-width: 10rem;">
                        <template #body="{ data }">
                             <div v-if="data.destination_region" class="flex items-center gap-2">
                                <i class="pi pi-flag-fill text-slate-400"></i>
                                <span class="text-xs font-medium text-slate-500">{{ data.destination_region.designation }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('user')" field="user.name" :header="t('stockMovements.table.user')" sortable filterField="user.name" :showFilterMatchModes="false" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <div v-if="data.user" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100">
                                <Avatar :label="data.user.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ data.user.name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2" v-if="!editing">
                                <Button icon="pi pi-pencil" text rounded @click="editMovement(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteMovement(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" v-tooltip.top="'Supprimer'" />
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

            <Dialog v-model:visible="movementDialog" modal :header="false" :closable="false" :style="{ width: '55rem' }"
                class="quantum-dialog" :pt="{ mask: { style: 'backdrop-filter: blur(6px)' }, content: { class: 'p-0 rounded-3xl border-none shadow-2xl' } }">

                <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-primary-500/20 rounded-xl border border-primary-500/30">
                            <i class="pi pi-sync text-blue-400 text-xl"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">{{ dialogTitle }}</h2>
                            <span class="text-[9px] text-primary-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">{{ editing ? t('stockMovements.dialog.editSubtitle') : t('stockMovements.dialog.createSubtitle') }}</span>
                        </div>
                    </div>
                    <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
                </div>

                <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth">
                    <!-- Colonne de gauche : Identification de l'item et du flux -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <!-- Colonne de droite : Détails et validation -->
                        <div class="md:col-span-5 space-y-6 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <h4 class="text-xs font-black uppercase text-primary-600 tracking-widest flex items-center gap-2"><i class="pi pi-list-check"></i> {{ t('stockMovements.dialog.detailsValidation') }}</h4>

                            <div class="field">
                                <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.movementType') }}</label>
                                <SelectButton v-model="form.type" :options="movementTypeOptions" optionLabel="label" optionValue="value" class="v16-select-button" />
                            </div>

                            <div class="space-y-6">
                                <div class="field" v-if="form.type === 'exit' || form.type === 'transfer'">
                                    <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.sourceRegion') }}</label>
                                    <Dropdown v-model="form.source_region_id" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('stockMovements.form.sourceRegionPlaceholder')" class="w-full quantum-input-v16" />
                                </div>
                                <div class="field" v-if="form.type === 'entry' || form.type === 'transfer'">
                                    <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.destinationRegion') }}</label>
                                    <Dropdown v-model="form.destination_region_id" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('stockMovements.form.destinationRegionPlaceholder')" class="w-full quantum-input-v16" />
                                </div>
                            </div>

                            <div class="field">
                                <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.responsibleUser') }}</label>
                                <Dropdown v-model="form.responsible_user_id" :options="users" optionLabel="name" optionValue="id" :placeholder="t('stockMovements.form.userPlaceholder')" class="w-full quantum-input-v16" filter />
                            </div>
                            <div class="field">
                                <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.intendedForUser') }}</label>
                                <Dropdown v-model="form.intended_for_user_id" :options="users" optionLabel="name" optionValue="id" :placeholder="t('stockMovements.form.userPlaceholder')" class="w-full quantum-input-v16" filter />
                            </div>
                            <div class="field">
                                <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.notes') }}</label>
                                <Textarea v-model="form.notes" rows="2" class="w-full text-sm quantum-input-v16" />
                            </div>
                        </div>
                        <div class="md:col-span-7 space-y-6">
                            <h4 class="text-xs font-black uppercase text-primary-600 tracking-widest flex items-center gap-2"><i class="pi pi-box"></i> {{ t('stockMovements.dialog.itemSelection') }}</h4>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div class="field">
                                    <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.itemType') }}</label>
                                    <Dropdown v-model="newItem.movable_type" :options="movableTypeOptions" optionLabel="label" optionValue="value" :placeholder="t('stockMovements.form.itemTypePlaceholder')" class="w-full quantum-input-v16" :disabled="isItemSelectionDisabled"/>
                                </div>
                                <div class="field">

                                    <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.item') }}</label>
                                    <Dropdown v-model="newItem.movable_id" :options="availableItems" :optionLabel="itemLabel" optionValue="id" :placeholder="t('stockMovements.form.itemPlaceholder')" :disabled="!newItem.movable_type || isItemSelectionDisabled" filter class="w-full quantum-input-v16" />
                                </div>

                                <div class="field">
                                    <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('stockMovements.form.quantity') }}</label>
                                    <InputNumber v-model="newItem.quantity" showButtons :min="1" inputClass="font-black text-center" class="w-full" :disabled="isQuantityDisabled || isItemSelectionDisabled" />
                                </div>
                                <Button icon="pi pi-plus" :label="t('stockMovements.form.addItem')" @click="addItemToCart" :disabled="!newItem.movable_id || isItemSelectionDisabled" class="p-button-sm" />
                            </div>

                            <div class="mt-4 border-t border-slate-200 pt-4">
                                <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest mb-3">{{ t('stockMovements.dialog.itemsInMovement') }} ({{ form.items.length }})</h4>
                                <div class="max-h-60 overflow-y-auto space-y-2 pr-2">
                                    <div v-for="(item, index) in form.items" :key="index" class="flex items-center justify-between p-3 bg-slate-100 rounded-lg">
                                        <div>
                                            <p class="font-bold text-sm text-slate-800">{{ itemLabel(item.item_details) }}</p>
                                            <p class="text-xs text-slate-500">{{ getMovableTypeLabel(item.movable_type) }}</p>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="font-mono font-black text-lg">x{{ item.quantity }}</span>
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="removeItemFromCart(index)" />
                                        </div>
                                    </div>
                                    <p v-if="form.items.length === 0" class="text-center text-sm text-slate-400 italic py-4">{{ t('stockMovements.dialog.noItems') }}</p>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-between items-center w-full px-8 py-4 bg-slate-50 border-t border-slate-100">
                        <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="font-bold uppercase text-[10px] tracking-widest" />
                        <Button :label="t('common.save')" icon="pi pi-check-circle"
                                severity="primary"
                                class="px-10 h-14 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                                @click="saveMovement" :loading="form.processing" />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.card-v11 :deep(.p-datatable-thead > tr > th) {
    background: #fdfdfd;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
}

.card-v11 :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s;
}

.card-v11 :deep(.p-datatable-tbody > tr:not(.p-datatable-emptymessage):hover) {
    background: #f8faff !important;
}

.v16-select-button :deep(.p-button) {
    background: white;
    border: 1px solid #e2e8f0;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.1em;
    border-radius: 12px !important;
    margin: 0 4px;
    flex: 1;
    transition: all 0.3s;
}

.v16-select-button :deep(.p-highlight) {
    background: #1e293b !important;
    color: white !important;
    border-color: #1e293b !important;
}
</style>
