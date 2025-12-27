<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useI18n } from 'vue-i18n';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
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

// State
const sparePartMovementDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const dt = ref();

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

const op = ref(); // Référence à l'OverlayPanel pour la sélection de colonnes

// Colonnes pour la sélection
const allColumns = ref([
    { field: 'spare_part.reference', header: 'Composant' },
    { field: 'type', header: 'Flux' },
    { field: 'quantity', header: 'Qté' },
    { field: 'user.name', header: 'Responsable' },
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
            toast.add({ severity: 'success', summary: t('common.success'), detail: editing.value ? t('sparePartMovements.toast.updateSuccess') : t('sparePartMovements.toast.createSuccess'), life: 3000 });
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
        message: t('sparePartMovements.confirm.deleteMessage'),
        header: t('confirm.deleteHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('spare-part-movements.destroy', data.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: t('common.success'), detail: t('sparePartMovements.toast.deleteSuccess'), life: 3000 })
            });
        }
    });
};

// Computed
const movementTypes = computed(() => [
    { label: t('sparePartMovements.movementTypes.entry'), value: 'entree', icon: 'pi pi-arrow-down-left', color: 'text-primary-500' },
    { label: t('sparePartMovements.movementTypes.exit'), value: 'sortie', icon: 'pi pi-arrow-up-right', color: 'text-rose-500' },
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

const dialogTitle = computed(() => editing.value ? t('sparePartMovements.dialog.editTitle') : t('sparePartMovements.dialog.createTitle'));

const exportCSV = () => {
    dt.value.exportCSV();
};

// Search Debounce
let timeoutId = null;
watch(search, (val) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('spare-part-movements.index'), { search: val }, { preserveState: true, replace: true });
    }, 400);
});
</script>
<template>
    <AppLayout :title="t('sparePartMovements.title')">
        <Head :title="t('sparePartMovements.headTitle')" />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">
            <Toast />
            <ConfirmDialog />

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                        {{ t('sparePartMovements.title') }} <span class="text-primary-600">GMAO</span>
                    </h1>
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('sparePartMovements.subtitle') }}</p>
                </div>
                <div class="flex gap-2">
                    <Button :label="t('sparePartMovements.toolbar.addMovement')" icon="pi pi-plus"
                            class="p-button-primary shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <div class="bg-white/70 backdrop-blur-md border border-white shadow-sm rounded-2xl p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex gap-2 items-center">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" :placeholder="t('sparePartMovements.toolbar.searchPlaceholder')"
                                       class="p-inputtext-sm border-none bg-slate-100 rounded-xl w-64" />
                        </IconField>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button icon="pi pi-download" :label="t('common.export')"
                                class="p-button-text p-button-secondary p-button-sm font-bold" @click="exportCSV" />
                        <Button icon="pi pi-columns" class="p-button-text p-button-secondary" @click="op.toggle($event)" />
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="sparePartMovements.data" ref="dt" dataKey="id" :rows="10" paginator responsiveLayout="scroll"
                        class="p-datatable-sm quantum-table"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                        :currentPageReportTemplate="t('common.paginationReport')">

                    <Column v-if="visibleColumns.includes('spare_part.reference')" field="spare_part.reference" :header="t('sparePartMovements.table.sparePart')" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-col cursor-pointer" @click="editSparePartMovement(data)">
                                <span class="font-black text-slate-700 tracking-tight">{{ data.spare_part?.reference }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase">{{ data.spare_part?.label?.designation }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('type')" :header="t('sparePartMovements.table.type')" sortable field="type">
                        <template #body="{ data }">
                            <Tag :value="data.type === 'entree' ? t('sparePartMovements.movementTypes.entry') : t('sparePartMovements.movementTypes.exit')"
                                 :severity="data.type === 'entree' ? 'success' : 'danger'"
                                 :icon="data.type === 'entree' ? 'pi pi-arrow-down' : 'pi pi-arrow-up'"
                                 class="uppercase text-[9px] px-2" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('quantity')" field="quantity" :header="t('sparePartMovements.table.quantity')" sortable>
                        <template #body="{ data }">
                            <span class="font-mono font-black text-lg">{{ data.quantity }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('user.name')" field="user.name" :header="t('sparePartMovements.table.user')">
                        <template #body="{ data }">
                            <div v-if="data.user" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100">
                                <Avatar :label="data.user.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ data.user.name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('location')" field="location" :header="t('sparePartMovements.table.location')">
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
                    <MultiSelect v-model="visibleColumns" :options="allColumns" optionLabel="header" optionValue="field"
                                 display="chip" class="w-64 quantum-multiselect" />
                </div>
            </OverlayPanel>

            <Dialog v-model:visible="sparePartMovementDialog" modal :header="false" :style="{ width: '55rem' }"
                class="p-0 overflow-hidden" :pt="{ root: { class: 'border-none rounded-[2rem] shadow-2xl bg-white' }, mask: { style: 'backdrop-filter: blur(4px)' } }">

                <div class="p-6 bg-slate-950 text-white flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="bg-primary-500 p-3 rounded-2xl shadow-lg shadow-primary-500/20">
                            <i class="pi pi-sync text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-black uppercase tracking-tight">{{ dialogTitle }}</h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ editing ? t('sparePartMovements.dialog.editSubtitle') : t('sparePartMovements.dialog.createSubtitle') }}</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" text rounded severity="secondary" @click="hideDialog" class="text-white opacity-50 hover:opacity-100" />
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h4 class="text-[11px] font-black text-slate-500 uppercase tracking-widest italic">Identification du Flux</h4>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.region') }}</label>
                            <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('sparePartMovements.form.regionPlaceholder')" class="quantum-input-v16 w-full" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.sparePart') }}</label>
                            <Dropdown v-model="form.spare_part_id" :options="formattedSpareParts" optionLabel="label" optionValue="id" :placeholder="t('sparePartMovements.form.sparePartPlaceholder')" :disabled="!form.region_id" filter class="quantum-input-v16 w-full" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.movementType') }}</label>
                            <SelectButton v-model="form.type" :options="movementTypes" optionLabel="label" optionValue="value" class="v16-select-button" />
                        </div>
                    </div>

                    <div class="space-y-6 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <h4 class="text-[11px] font-black text-slate-500 uppercase tracking-widest italic">Détails & Validation</h4>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.quantity') }}</label>
                            <InputNumber v-model="form.quantity" showButtons buttonLayout="horizontal" :min="1" inputClass="text-2xl font-black text-center" class="w-full" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.user') }}</label>
                            <Dropdown v-model="form.user_id" :options="users" optionLabel="name" optionValue="id" :placeholder="t('sparePartMovements.form.userPlaceholder')" class="quantum-input-v16 w-full" />
                        </div>
                        <div class="field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">{{ t('sparePartMovements.form.notes') }}</label>
                            <Textarea v-model="form.notes" rows="2" class="quantum-input-v16 w-full text-sm" />
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end items-center w-full rounded-b-[2rem]">
                        <Button :label="t('common.cancel')" icon="pi pi-times" class="p-button-text p-button-secondary font-bold text-xs" @click="hideDialog" />
                        <Button :label="t('common.save')" icon="pi pi-check-circle"
                                class="p-button-primary px-8 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase text-xs tracking-widest"
                                @click="saveSparePartMovement" :loading="form.processing" />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-button-primary {
    background: #10b981;
    border-color: #10b981;
    color: white;
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
.v16-select-button :deep(.p-button) {
    background: white;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: 0.1em;
    border-radius: 12px !important;
    margin: 0 4px;
    flex: 1;
}
.v16-select-button :deep(.p-highlight) {
    background: #1e293b !important;
    color: white !important;
}
</style>
