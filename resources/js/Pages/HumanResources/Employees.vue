<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';

// --- COMPOSANTS PRIME V4 ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';

const { t } = useI18n();
const props = defineProps({
    employees: Object,
    regions: Array,
    filters: Object,
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();

// --- ÉTATS ---
const employeeDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedEmployees = ref(null);

const { auth } = usePage().props;

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    date_of_birth: null,
    hire_date: null,
    job_title: '',
    department: '',
    salary: 0,
    employment_status: 'active',
    notes: '',
});

// --- CONFIGURATION DES COLONNES ---
const allColumns = computed(() => [
    { field: 'first_name', header: t('employees.fields.first_name'), default: true },
    { field: 'last_name', header: t('employees.fields.last_name'), default: true },
    { field: 'email', header: t('employees.fields.email'), default: true },
    { field: 'job_title', header: t('employees.fields.job_title'), default: true },
    { field: 'employment_status', header: t('employees.fields.status'), default: true },
    { field: 'hire_date', header: t('employees.fields.hire_date'), default: false },
    { field: 'salary', header: t('employees.fields.salary'), default: false },
]);

const selectedColumns = ref(allColumns.value.filter(col => col.default));

// --- OPTIONS DE STATUT ---
const employmentStatuses = computed(() => [
    { label: t('employees.status.active'), value: 'active', severity: 'success' },
    { label: t('employees.status.on_leave'), value: 'on_leave', severity: 'warning' },
    { label: t('employees.status.terminated'), value: 'terminated', severity: 'danger' },
]);

const getStatusSeverity = (status) => {
    return employmentStatuses.value.find(s => s.value === status)?.severity || 'secondary';
};

// --- ACTIONS ---
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    employeeDialog.value = true;
};

const editEmployee = (emp) => {
    form.clearErrors();
    Object.assign(form, {
        ...emp,
        date_of_birth: emp.date_of_birth ? new Date(emp.date_of_birth) : null,
        hire_date: emp.hire_date ? new Date(emp.hire_date) : null,
    });
    editing.value = true;
    employeeDialog.value = true;
};

const saveEmployee = () => {
    submitted.value = true;
    const url = editing.value ? route('employees.update', form.id) : route('employees.store');

    form.submit(editing.value ? 'put' : 'post', url, {
        onSuccess: () => {
            employeeDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('employees.messages.saved'), life: 3000 });
        }
    });
};

const deleteEmployee = (emp) => {
    confirm.require({
        message: t('employees.messages.confirmDelete', { name: emp.first_name }),
        header: t('common.confirmation'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('employees.destroy', emp.id));
        }
    });
};

// --- RECHERCHE ---
let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('employees.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 400);
};
</script>

<template>
    <AppLayout>
        <Head :title="t('employees.title')" />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100 rotate-3">
                        <i class="pi pi-users text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ t('employees.title') }}</h1>
                        <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest">{{ props.employees.total }} {{ t('employees.count_label') }}</p>
                    </div>
                </div>
                <div class="flex gap-2 bg-white p-1.5 rounded-xl shadow-sm border border-slate-200">
                    <Button icon="pi pi-upload" text severity="secondary" @click="dt.exportCSV()" />
                    <Button :label="t('employees.actions.add')" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-lg font-bold" />
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                <DataTable
                    ref="dt" :value="props.employees.data" dataKey="id"
                    v-model:selection="selectedEmployees"
                    :paginator="true" :rows="10"
                    class="v11-table" responsiveLayout="scroll"
                >
                    <template #header>
                        <div class="flex flex-wrap justify-between items-center gap-4 p-2">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="search" :placeholder="t('common.search')" @input="performSearch" class="w-full md:w-80 border-none bg-slate-50 rounded-xl" />
                            </IconField>
                            <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header"
                                :placeholder="t('common.columns')" display="chip" class="w-full md:w-64 border-none bg-slate-50 rounded-xl" />
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-for="col in selectedColumns" :key="col.field" :field="col.field" :header="col.header" sortable>
                        <template #body="{ data, field }">
                            <template v-if="field === 'employment_status'">
                                <Tag :value="t(`employees.status.${data[field]}`)" :severity="getStatusSeverity(data[field])" class="rounded-md font-bold text-[10px]" />
                            </template>
                            <template v-else-if="field === 'salary'">
                                <span class="font-mono font-bold text-slate-700">
                                    {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(data[field]) }}
                                </span>
                            </template>
                            <template v-else-if="field.includes('date')">
                                <span class="text-slate-500 text-sm">{{ data[field] ? new Date(data[field]).toLocaleDateString() : '--' }}</span>
                            </template>
                            <template v-else>
                                <span class="font-semibold text-slate-700">{{ data[field] }}</span>
                            </template>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="text-right">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded severity="info" @click="editEmployee(data)" />
                            <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteEmployee(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="employeeDialog" modal :header="editing ? t('employees.dialog.edit') : t('employees.dialog.new')" :style="{ width: '600px' }" class="v11-dialog">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.first_name') }}</label>
                    <InputText v-model="form.first_name" :class="{'p-invalid': form.errors.first_name}" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.last_name') }}</label>
                    <InputText v-model="form.last_name" :class="{'p-invalid': form.errors.last_name}" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.email') }}</label>
                    <InputText v-model="form.email" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.status') }}</label>
                    <Dropdown v-model="form.employment_status" :options="employmentStatuses" optionLabel="label" optionValue="value" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.salary') }}</label>
                    <InputNumber v-model="form.salary" mode="currency" currency="XOF" locale="fr-FR" class="v11-input" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.hire_date') }}</label>
                    <Calendar v-model="form.hire_date" dateFormat="dd/mm/yy" showIcon class="v11-calendar" />
                </div>
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('employees.fields.notes') }}</label>
                    <Textarea v-model="form.notes" rows="3" class="v11-input" />
                </div>
            </div>

            <template #footer>
                <div class="flex gap-3 w-full pt-6 border-t border-slate-100 mt-4">
                    <Button :label="t('common.cancel')" text severity="secondary" @click="employeeDialog = false" class="flex-1 rounded-xl" />
                    <Button :label="t('common.save')" severity="primary" raised @click="saveEmployee" :loading="form.processing" class="flex-1 rounded-xl font-bold" />
                </div>
            </template>
        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
.v11-table {
    .p-datatable-thead > tr > th {
        @apply bg-slate-50/50 text-slate-400 font-bold text-[10px] uppercase tracking-widest py-4 px-4 border-b border-slate-100;
    }
    .p-datatable-tbody > tr {
        @apply hover:bg-indigo-50/30 transition-colors;
        td { @apply py-4 px-4 border-b border-slate-50; }
    }
}

.v11-input, .v11-calendar {
    .p-inputtext, .p-dropdown, .p-inputnumber-input {
        @apply rounded-xl border-slate-200 bg-slate-50/50 p-3 text-sm font-semibold;
        &:focus { @apply bg-white ring-4 ring-indigo-50 border-indigo-500; }
    }
}

.v11-dialog {
    .p-dialog-header { @apply p-6 border-b border-slate-50; .p-dialog-title { @apply font-black text-slate-900; } }
    .p-dialog-content { @apply p-6; }
}
</style>
