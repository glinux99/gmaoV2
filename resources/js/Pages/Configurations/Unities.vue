<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';

// --- IMPORTS COMPOSANTS ---
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

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    designation: '',
    abreviation: '',
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
const dt = ref();
let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('unities.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
};

const dialogTitle = computed(() => editing.value ? t('unities.dialog.editTitle') : t('unities.dialog.createTitle'));
</script>

<template>
    <AppLayout :title="t('unities.title')">
        <Head :title="t('unities.title')" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ t('unities.title') }}</h1>
                    <p class="text-slate-500 font-medium">{{ t('unities.subtitle') }}</p>
                </div>
                <Button :label="t('unities.actions.add')" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6 h-12" />
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 overflow-hidden">
                <Toast />
                <ConfirmDialog />

                <DataTable ref="dt" :value="unities" dataKey="id" :paginator="true" :rows="10"
                    class="p-datatable-minimal" responsiveLayout="scroll"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="t('common.paginationReport', { first: '{first}', last: '{last}', total: '{totalRecords}' })">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4 bg-slate-50/30">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="search" :placeholder="t('common.search')" @input="performSearch" class="w-full md:w-80 rounded-2xl border-slate-200 bg-white" />
                            </IconField>
                            <Button :label="t('common.export')" icon="pi pi-upload" severity="secondary" text size="small" @click="dt.exportCSV()" />
                        </div>
                    </template>

                    <Column field="designation" :header="t('unities.fields.name')" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.designation[0].toUpperCase()" shape="circle" class="bg-primary-50 text-primary-600 font-bold" />
                                <span class="font-bold text-slate-700">{{ data.designation }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="abreviation" :header="t('unities.fields.symbol')">
                        <template #body="{ data }">
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg font-mono font-bold text-sm">
                                {{ data.abreviation || '--' }}
                            </span>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" class="w-32">
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

        <Dialog v-model:visible="labelDialog" modal :header="dialogTitle"
            :style="{ width: '90vw', maxWidth: '500px' }" class="ultimate-modal">

            <div class="flex flex-col gap-6 py-4">
                <span class="text-slate-500 text-sm">
                    {{ editing ? t('unities.dialog.editSubtitle') : t('unities.dialog.createSubtitle') }}
                </span>

                <div class="flex flex-col gap-2">
                    <label for="designation" class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t('unities.fields.name') }}</label>
                    <InputText id="designation" v-model.trim="form.designation" autofocus
                        :class="{ 'p-invalid': submitted && !form.designation }" class="w-full py-3 rounded-xl border-slate-200" />
                    <small class="text-red-500 font-bold" v-if="submitted && !form.designation">{{ t('unities.validation.nameRequired') }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="abreviation" class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t('unities.fields.symbol') }}</label>
                    <InputText id="abreviation" v-model="form.abreviation" class="w-full py-3 rounded-xl border-slate-200" placeholder="ex: kg, m, pcs" />
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-slate-100 mt-4">
                    <Button :label="t('common.cancel')" severity="secondary" text @click="hideDialog" class="flex-1 rounded-xl" />
                    <Button :label="t('common.save')" severity="primary" raised @click="saveUnit" :loading="form.processing"
                        class="flex-1 py-4 rounded-xl font-black shadow-lg shadow-primary-200 uppercase text-xs tracking-widest" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style lang="scss">
/* --- STYLE MINIMALISTE V11 --- */
.p-datatable-minimal {
    border: none !important;

    .p-datatable-thead > tr > th {
        background: transparent;
        color: #94a3b8;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 0.05em;
        padding: 1.25rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .p-datatable-tbody > tr {
        background: transparent;
        > td {
            border-bottom: 1px solid #f8fafc;
            padding: 1rem;
        }
        &:hover { background: #f8fafc !important; }
        &:last-child > td { border-bottom: none; }
    }

    .p-paginator {
        border-top: 1px solid #f1f5f9 !important;
        background: #fdfdfd;
        padding: 0.75rem;
    }
}

/* --- STYLE MODAL ULTIMATE --- */
.ultimate-modal {
    .p-dialog-header {
        padding: 2rem 2rem 0 2rem;
        .p-dialog-title { font-weight: 900; color: #1e293b; font-size: 1.25rem; }
    }
    .p-dialog-content { padding: 0 2rem 2rem 2rem; }
}

/* --- BOUTON PRIMARY --- */
.p-button.p-button-primary {
    background: #2563eb !important;
    border: none;
    &:hover { background: #1d4ed8 !important; transform: translateY(-1px); }
}
</style>
