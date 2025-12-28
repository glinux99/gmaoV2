<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';

// --- IMPORTS COMPOSANTS ---
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';

const { t } = useI18n();
const props = defineProps({
    unities: Array,
    filters: Object,
});

const toast = useToast();
const confirm = useConfirm();

// --- ÉTATS ---
const labelDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const dt = ref();

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    designation: '',
    abreviation: '',
});

// --- FILTRES ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

// --- STATS ---
const stats = computed(() => {
    return { total: props.unities.length };
});

// --- ACTIONS ---
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true;
};

const hideDialog = () => {
    labelDialog.value = false;
    submitted.value = false;
};

const editUnit = (unit) => {
    form.id = unit.id;
    form.designation = unit.designation;
    form.abreviation = unit.abreviation;
    editing.value = true;
    labelDialog.value = true;
};

const saveUnit = () => {
    submitted.value = true;
    if (!form.designation) return;

    const url = editing.value ? route('unities.update', form.id) : route('unities.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({
                severity: 'success',
                summary: t('common.success'),
                detail: editing.value ? t('unities.messages.updateSuccess') : t('unities.messages.createSuccess'),
                life: 3000
            });
            form.reset();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: t('common.error'), detail: t('common.errorOccurred'), life: 3000 });
        }
    });
};

const deleteUnit = (unit) => {
    confirm.require({
        message: t('unities.messages.confirmDelete', { name: unit.designation }),
        header: t('common.deleteConfirmation'),
        icon: 'pi pi-exclamation-circle',
        rejectLabel: t('common.cancel'),
        acceptLabel: t('common.delete'),
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('unities.destroy', unit.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('common.success'), detail: t('unities.messages.deleteSuccess'), life: 3000 });
                }
            });
        },
    });
};

// --- RECHERCHE & EXPORT ---
const performSearch = () => {
        router.get(route('unities.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
};

const dialogTitle = computed(() => editing.value ? t('unities.dialog.editTitle') : t('unities.dialog.createTitle'));
</script>

<template>
    <AppLayout :title="t('unities.title')">
        <Head :title="t('unities.title')" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8 font-sans">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                 <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-box text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ t('unities.title') }}</h1>
                    <p class="text-slate-500 font-medium">{{ t('unities.subtitle') }}</p>
                    </div>
                </div>
                <Button :label="t('unities.actions.add')" icon="pi pi-plus"  raised @click="openNew" class="rounded-xl px-6 h-14 font-black" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t('common.total', { item: 'Unités' }) }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ stats.total }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i class="pi pi-database text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <Toast />
                <ConfirmDialog />

                <DataTable ref="dt" :value="unities" dataKey="id"
                    v-model:filters="filters"
                    :globalFilterFields="['designation', 'abreviation']"
                    :paginator="true" :rows="10"
                    class="p-datatable-custom" responsiveLayout="scroll"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="t('common.paginationReport', { first: '{first}', last: '{last}', total: '{totalRecords}', item: 'unités' })">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('common.search')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                        </div>
                    </template>

                    <Column field="designation" :header="t('unities.fields.name')" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.designation[0].toUpperCase()" shape="circle" class="bg-primary-50 text-primary-600 font-bold" />
                                <span class="font-bold text-slate-700">{{ data.designation }}</span>
                            </div>
                        </template>
                         <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('common.searchByName')" />
                        </template>
                    </Column>

                    <Column field="abreviation" :header="t('unities.fields.symbol')">
                        <template #body="{ data }">
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg font-mono font-bold text-sm">
                                {{ data.abreviation || '--' }}
                            </span>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen class="min-w-[120px]">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editUnit(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteUnit(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="labelDialog" modal :header="false" :closable="false"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }">

            <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                     <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-box text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ dialogTitle }}</h4>
                        <p class="text-xs text-slate-500 m-0">{{ editing ? t('unities.dialog.editSubtitle') : t('unities.dialog.createSubtitle') }}</p>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 bg-white space-y-6">
                 <div class="flex flex-col gap-2">
                    <label for="designation" class="text-xs font-black text-slate-500 uppercase">{{ t('unities.fields.name') }}</label>
                    <InputText id="designation" v-model.trim="form.designation" autofocus :class="{ 'p-invalid': form.errors.designation }" class="w-full py-3.5 rounded-xl border-slate-200" />
                    <small class="text-red-500 font-bold italic" v-if="form.errors.designation">{{ form.errors.designation }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="abreviation" class="text-xs font-black text-slate-500 uppercase">{{ t('unities.fields.symbol') }}</label>
                    <InputText id="abreviation" v-model="form.abreviation" class="w-full py-3.5 rounded-xl border-slate-200" placeholder="ex: kg, m, pcs" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="editing ? t('common.update') : t('common.save')" icon="pi pi-check-circle" severity="indigo" class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs" @click="saveUnit" :loading="form.processing" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style lang="scss">
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
</style>
