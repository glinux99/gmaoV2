<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useI18n } from 'vue-i18n';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
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

const props = defineProps({
    sparePartMovements: Object,
    filters: Object,
    regions: Array,
    unities: Array,
    spareParts: Array,
    users: Array,
});

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const sparePartMovementDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const dt = ref();

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'type': { value: null, matchMode: FilterMatchMode.EQUALS },
    'user.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const initFilters = () => {
    filters.value.global.value = null;
    filters.value.type.value = null;
    filters.value['user.name'].constraints[0].value = null;
};

const form = useForm({
    id: null,
    spare_part_id: null,
    user_id: null,
    type: null,
    quantity: 0,
    location: null,
    region_id: null,
    unity_id: null,
    notes: null,
});

const op = ref();

const allColumns = ref([
    { field: 'type', header: 'Type' },
    { field: 'quantity', header: 'Quantité' },
    { field: 'user.name', header: 'Utilisateur' },
    { field: 'location', header: 'Emplacement' },
]);
const visibleColumns = ref(allColumns.value.map(col => col.field)); // Affiche toutes les colonnes par défaut

// Actions
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    sparePartMovementDialog.value = true;
};

const hideDialog = () => {
    sparePartMovementDialog.value = false;
};

const editSparePartMovement = (movement) => {
    form.id = movement.id;
    form.spare_part_id = movement.spare_part_id;
    form.user_id = movement.user_id;
    form.type = movement.type;
    form.quantity = movement.quantity;
    form.location = movement.location;
    form.region_id = movement.spare_part?.region_id || null; // Auto-select region
    form.unity_id = movement.unity_id;
    form.notes = movement.notes;
    editing.value = true;
    sparePartMovementDialog.value = true;
};

const saveSparePartMovement = () => {
    submitted.value = true;

    const url = editing.value ? route('spare-part-movements.update', form.id) : route('spare-part-movements.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            sparePartMovementDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: editing.value ? t('sparePartMovements.toast.updateSuccess') : t('sparePartMovements.toast.createSuccess'), life: 4000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du mouvement", errors);
            const errorDetail = Object.values(errors).flat().join(' ; ');
            toast.add({ severity: 'error', summary: t('common.error'), detail: errorDetail || t('sparePartMovements.toast.saveError'), life: 5000 });
        }
    });
};

const deleteMovement = (data) => {
    confirm.require({
        message: t('sparePartMovements.confirm.deleteMessage', { reference: data.spare_part.reference }),
        header: t('confirm.deleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('spare-part-movements.destroy', data.id), { // Assurez-vous que cette route existe
                onSuccess: () => toast.add({ severity: 'success', summary: t('common.success'), detail: t('sparePartMovements.toast.deleteSuccess'), life: 3000 })
            });
        }
    });
};

// Computed
const movementTypesOptions = computed(() => [
    { label: t('sparePartMovements.movementTypes.entry'), value: 'entree' },
    { label: t('sparePartMovements.movementTypes.exit'), value: 'sortie' },
]);
const formattedSpareParts = computed(() => {
    if (!form.region_id) return [];
    return props.spareParts
        .filter(sp => sp.region_id === form.region_id)
        .map(sp => ({
            ...sp,
            label: `${sp.reference} | ${t('sparePartMovements.stockLabel')}: ${sp.quantity}`
        }));
});

const movementStats = computed(() => {
    const total = props.sparePartMovements.data.length;
    const entries = props.sparePartMovements.data.filter(m => m.type === 'entree').length;
    const exits = props.sparePartMovements.data.filter(m => m.type === 'sortie').length;
    const uniqueParts = new Set(props.sparePartMovements.data.map(m => m.spare_part_id)).size;
    return { total, entries, exits, uniqueParts };
});

const dialogTitle = computed(() => editing.value ? t('sparePartMovements.dialog.editTitle') : t('sparePartMovements.dialog.createTitle'));

const exportCSV = () => {
    dt.value.exportCSV();
};
</script>

<template>
    <AppLayout :title="t('sparePartMovements.title')">
        <Head :title="t('sparePartMovements.headTitle')" />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">
            <Toast />
            <ConfirmDialog />

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200 text-white text-2xl">
                        <i class="pi pi-arrows-h"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                            {{ t('sparePartMovements.title') }} <span class="text-primary-600">GMAO</span>
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('sparePartMovements.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button :label="t('sparePartMovements.toolbar.addMovement')" icon="pi pi-plus"
                            class="shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-sync text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('sparePartMovements.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-arrow-down text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.entries }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('sparePartMovements.stats.entries') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center"><i class="pi pi-arrow-up text-2xl text-red-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.exits }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('sparePartMovements.stats.exits') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-box text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ movementStats.uniqueParts }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('sparePartMovements.stats.uniqueParts') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="sparePartMovements.data" ref="dt" dataKey="id" :rows="10" paginator responsiveLayout="scroll"
                        class="p-datatable-sm quantum-table"
                        v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['spare_part.reference', 'spare_part.name', 'user.name', 'location']"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                        :currentPageReportTemplate="t('common.paginationReport')"
                        rowGroupMode="subheader" groupRowsBy="spare_part.reference"
                        sortMode="single" sortField="spare_part.reference" :sortOrder="1">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('sparePartMovements.toolbar.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" v-tooltip.bottom="t('common.export')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="t('common.columns')" />
                            </div>
                        </div>
                    </template>

                    <template #groupheader="slotProps">
                        <div class="flex items-center gap-3 px-4 py-3 bg-slate-50">
                            <i class="pi pi-box text-primary-500"></i>
                            <span class="font-bold text-primary-700">{{ slotProps.data['spare_part']['reference'] }}</span>
                            <span class="text-xs text-slate-500">({{ slotProps.data.spare_part.name }})</span>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column field="spare_part.reference" :header="t('spareParts.table.reference')" sortable>
                         <template #body="{ data }">
                            <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-md">{{ data.spare_part.reference }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('type')" field="type" :header="t('sparePartMovements.table.type')" style="min-width: 10rem;">
                        <template #body="{ data }">
                            <Tag :value="data.type === 'entree' ? t('sparePartMovements.movementTypes.entry') : t('sparePartMovements.movementTypes.exit')"
                                 :severity="data.type === 'entree' ? 'success' : 'danger'"
                                 :icon="data.type === 'entree' ? 'pi pi-arrow-down' : 'pi pi-arrow-up'"
                                 class="uppercase text-[9px] px-2" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="movementTypesOptions" optionLabel="label" optionValue="value" :placeholder="t('sparePartMovements.filter.byType')" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('quantity')" field="quantity" :header="t('sparePartMovements.table.quantity')" sortable style="min-width: 8rem;">
                        <template #body="{ data }">
                            <span class="font-mono font-black text-lg">{{ data.quantity }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('user.name')" field="user.name" :header="t('sparePartMovements.table.user')" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <div v-if="data.user" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100">
                                <Avatar :label="data.user.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ data.user.name }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.constraints[0].value" type="text" class="p-column-filter" :placeholder="t('sparePartMovements.filter.byUser')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('location')" field="location" :header="t('sparePartMovements.table.location')" style="min-width: 10rem;">
                        <template #body="{ data }">
                            <span class="text-xs font-medium text-slate-500">{{ data.location }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editSparePartMovement(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteMovement(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" v-tooltip.top="'Supprimer'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <OverlayPanel ref="op" class="quantum-overlay">
                <div class="p-2 space-y-3">
                    <span class="text-[10px] font-black uppercase text-slate-400 block border-b pb-2">Colonnes actives</span>
                    <MultiSelect v-model="visibleColumns" :options="allColumns" optionLabel="header"
                                 display="chip" class="w-64 quantum-multiselect" />
                </div>
            </OverlayPanel>

            <Dialog v-model:visible="sparePartMovementDialog" modal :header="false" :closable="false" :style="{ width: '55rem' }"
                class="quantum-dialog" :pt="{ mask: { style: 'backdrop-filter: blur(6px)' }, content: { class: 'p-0 rounded-3xl border-none shadow-2xl' } }">

                <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-primary-500/20 rounded-xl border border-primary-500/30">
                            <i class="pi pi-sync text-blue-400 text-xl"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">{{ dialogTitle }}</h2>
                            <span class="text-[9px] text-primary-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">{{ editing ? t('sparePartMovements.dialog.editSubtitle') : t('sparePartMovements.dialog.createSubtitle') }}</span>
                        </div>
                    </div>
                    <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
                </div>

                <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h4 class="text-xs font-black uppercase text-primary-600 tracking-widest flex items-center gap-2"><i class="pi pi-sitemap"></i> {{ t('sparePartMovements.dialog.fluxIdentification') }}</h4>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.region') }}</label>
                            <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('sparePartMovements.form.regionPlaceholder')" class="w-full quantum-input-v16" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.sparePart') }}</label>
                            <Dropdown v-model="form.spare_part_id" :options="formattedSpareParts" optionLabel="label" optionValue="id" :placeholder="t('sparePartMovements.form.sparePartPlaceholder')" :disabled="!form.region_id" filter class="w-full quantum-input-v16" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.movementType') }}</label>
                            <SelectButton v-model="form.type" :options="movementTypesOptions" optionLabel="label" optionValue="value" class="v16-select-button" />
                        </div>
                    </div>

                    <div class="space-y-6 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <h4 class="text-xs font-black uppercase text-primary-600 tracking-widest flex items-center gap-2"><i class="pi pi-list-check"></i> {{ t('sparePartMovements.dialog.detailsValidation') }}</h4>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.quantity') }}</label>
                            <InputNumber v-model="form.quantity" showButtons buttonLayout="horizontal" :min="1" inputClass="text-2xl font-black text-center" class="w-full" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.user') }}</label>
                            <Dropdown v-model="form.user_id" :options="users" optionLabel="name" optionValue="id" :placeholder="t('sparePartMovements.form.userPlaceholder')" class="w-full quantum-input-v16" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.notes') }}</label>
                            <Textarea v-model="form.notes" rows="2" class="w-full text-sm quantum-input-v16" />
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-between items-center w-full px-8 py-4 bg-slate-50 border-t border-slate-100">
                        <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="font-bold uppercase text-[10px] tracking-widest" />
                        <Button :label="t('common.save')" icon="pi pi-check-circle"

                                class="px-10 h-14 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                                @click="saveSparePartMovement" :loading="form.processing" />
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
