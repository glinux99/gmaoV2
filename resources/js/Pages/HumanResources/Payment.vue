<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
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
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';
import MultiSelect from 'primevue/multiselect';
import Textarea from 'primevue/textarea';


const props = defineProps({
    payments: Object,
    filters: Object,
    users: Array,
    employees: Array,
    suppliers: Array,
    payables: Array,
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const paymentDialog = ref(false);
const { t } = useI18n();
const submitted = ref(false);
const editing = ref(false);
const loading = ref(false);
const selectedPayments = ref(null);
const { user } = usePage().props.auth;

// --- ÉTAT DU FORMULAIRE ---
const form = useForm({
    id: null,
    amount: null,
    payment_date: new Date(),
    payment_method: 'bank_transfer',
    reference: '',
    notes: '',
    status: 'completed',
    paid_by: user?.id,
    payable_type: null,
    payable_id: null,
    category: 'salary',
});

// --- STATISTIQUES (Version 11) ---
const stats = computed(() => {
    const data = props.payments.data || [];
    const total = data.reduce((acc, curr) => acc + (parseFloat(curr.amount) || 0), 0);
    return {
        totalFlux: total.toLocaleString('fr-FR') + ' USD',
        transactions: data.length,
        pending: data.filter(p => p.status === 'pending').length,
        average: data.length > 0 ? (total / data.length).toFixed(0) : 0
    };
});

// --- SYSTÈME DE FILTRES AVANCÉS (V11) ---
const filters = ref({
    global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.IN },
    category: { value: null, matchMode: FilterMatchMode.IN },
    payment_method: { value: null, matchMode: FilterMatchMode.IN },
    payment_date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] }
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        status: { value: null, matchMode: FilterMatchMode.IN },
        category: { value: null, matchMode: FilterMatchMode.IN },
        payment_method: { value: null, matchMode: FilterMatchMode.IN },
        payment_date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] }
    };
};
// --- LOGIQUE POLYMORPHIQUE PAYABLE ---
const payableTypes = [
    { label: t('payments.payableTypes.employee'), value: 'App\\Models\\Employee', icon: 'pi pi-user' },
    { label: t('payments.payableTypes.supplier'), value: 'App\\Models\\Supplier', icon: 'pi pi-truck' },
    { label: t('payments.payableTypes.user'), value: 'App\\Models\\User', icon: 'pi pi-id-card' },
];

const filteredPayables = computed(() => {
    if (!form.payable_type) return [];
    switch (form.payable_type) {
        case 'App\\Models\\Employee': return props.employees;
        case 'App\\Models\\Supplier': return props.suppliers;
        case 'App\\Models\\User': return props.users;
        default: return [];
    }
});

// watch(() => form.payable_type, () => { form.payable_id = null; });

// --- ACTIONS ---
const openNew = () => {
    form.reset();
    form.payment_date = new Date();
    form.paid_by = user?.id;
    editing.value = false;
    form.payable_type = null; // Reset payable type
    submitted.value = false;
    paymentDialog.value = true;
};

const editPayment = (payment) => {
    form.clearErrors();
    Object.assign(form, {
        ...payment,
        payment_date: payment.payment_date ? new Date(payment.payment_date) : null,
        payable_type: payment.payable_type,
        paid_by: payment.paid_by.id, // Ensure payable_type is set for editing
    });
    editing.value = true;
    paymentDialog.value = true;
};

const savePayment = () => {
    submitted.value = true;
    if (!form.amount || !form.payable_id) return;

    loading.value = true;
    const url = editing.value ? route('payroll.update', form.id) : route('payroll.store');
    form.transform(data => ({
        ...data,
        payment_date: data.payment_date ? data.payment_date.toISOString().split('T')[0] : null,
    })).submit(editing.value ? 'put' : 'post', url, {
        onSuccess: () => {
            paymentDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('payments.toast.saved'), life: 3000 });
            loading.value = false;
        },
        onError: () => { loading.value = false; }
    });
};

const deletePayment = (payment) => {
    confirm.require({
        message: t('payments.toast.deleteConfirmation', { amount: payment.amount.toLocaleString(), beneficiary: getPayableName(payment.payable_type, payment.payable_id) }),
        header: t('common.attention'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => { // Changed from maintenance to task
            router.delete(route('payments.destroy', payment.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: t('common.info'), detail: t('payments.toast.deleted'), life: 3000 })
            });
        }
    });
};

// --- CONFIGURATIONS UI ---
const getStatusSeverity = (s) => ({ pending: 'warning', completed: 'success', failed: 'danger' }[s] || 'info');
const statusOptions = computed(() => [
    { label: t('payments.statusTypes.pending'), value: 'pending' },
    { label: t('payments.statusTypes.completed'), value: 'completed' },
    { label: t('payments.statusTypes.failed'), value: 'failed' },
]);

const getPayableName = (type, id) => {
    const p = props.payables.find(x => x.type === type && x.id === id);
    return p ? p.name : 'N/A';
};

const getPayableAvatarLabel = (type, id) => {
    const name = getPayableName(type, id);
    if (name && name !== 'N/A') return name.charAt(0).toUpperCase();
    return '?';
};
</script>

<template>
    <AppLayout>
        <Head :title="t('payments.title')" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                     <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-wallet text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ t('payments.title') }}</h1>
                        <p class="text-slate-500 font-medium">{{ t('payments.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('payments.newTransaction')" icon="pi pi-plus"  raised @click="openNew" class="rounded-xl px-6" />
                    <Button :label="t('common.resetFilters')" icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t(`payments.stats.${key}`) }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i class="pi pi-wallet text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="payments.data"
                    v-model:selection="selectedPayments"
                    v-model:filters="filters"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    filterDisplay="menu"
                    class="p-datatable-custom"
                    removableSort
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" :placeholder="t('common.search')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column field="amount" :header="t('payments.table.amount')" sortable>
                        <template #body="{ data }">
                            <span class="font-black text-slate-800">{{ data.amount.toLocaleString() }} <small>USD</small></span>
                        </template>
                    </Column>

                    <Column field="payable_name" :header="t('payments.table.beneficiary')">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="getPayableAvatarLabel(data.payable_type, data.payable_id)" shape="circle" class="bg-slate-100 text-slate-600 font-bold" />
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 text-sm">{{ getPayableName(data.payable_type, data.payable_id) }}</span>
                                    <small class="text-slate-400 text-[10px] uppercase font-bold">{{ t(`payments.payableTypes.${data.payable_type.split('\\').pop().toLowerCase()}`) }}</small>
                                </div>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <div class="flex flex-col gap-2 p-3">
                                <Dropdown v-model="filterModel.payable_type.value" :options="payableTypes" optionLabel="label" optionValue="value" placeholder="Type Bénéficiaire" class="w-full" />
                                <Dropdown
                                    v-model="filterModel.payable_id.value"
                                    :options="filteredPayables"
                                    optionLabel="name" optionValue="id" filter placeholder="Bénéficiaire" class="w-full" :disabled="!filterModel.payable_type.value" />
                                </div>
                        </template>
                    </Column>

                    <Column field="status" :header="t('payments.table.status')" sortable filterField="status" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            <Tag :value="t(`payments.statusTypes.${data.status}`)" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 text-[10px] uppercase" />
                        </template>
                        <template #filter="{ filterModel }">
                            <MultiSelect v-model="filterModel.value" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('common.total', {item: ''})" class="w-full" />
                        </template>
                    </Column>

                    <Column field="payment_date" :header="t('payments.table.date')" sortable filterField="payment_date" dataType="date">
                        <template #body="{ data }">
                            <span class="text-slate-500 font-medium text-sm">{{ new Date(data.payment_date).toLocaleDateString('fr-FR') }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <div class="px-3 pt-2 pb-4">
                                <Calendar v-model="filterModel.value" selectionMode="range" :manualInput="false" />
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('payments.table.actions')" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editPayment(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deletePayment(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog
            v-model:visible="paymentDialog"
            modal
            :header="false" :closable="false"
            :style="{ width: '90vw', maxWidth: '700px' }"
            :contentStyle="{ maxHeight: '80vh', overflow: 'auto' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
        >
            <div>
                <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                            <i class="pi pi-dollar text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-100 m-0">Paramètres financiers</h4>
                            <p class="text-xs text-slate-500 m-0">Veuillez renseigner les détails du mouvement de fonds</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="paymentDialog = false" class="text-white hover:bg-white/10" />
                </div>

                <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-black text-slate-500 uppercase">Montant (USD)</label>
                            <InputNumber v-model="form.amount" mode="decimal" class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                            <small v-if="submitted && !form.amount" class="text-red-500 font-bold text-[10px]">Requis</small>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-black text-slate-500 uppercase">Date de valeur</label>
                            <Calendar v-model="form.payment_date" dateFormat="dd/mm/yy" showIcon class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-black text-slate-500 uppercase">Type Bénéficiaire</label>
                            <Dropdown v-model="form.payable_type" :options="payableTypes" optionLabel="label" optionValue="value" class="rounded-xl border-slate-200 py-1" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-black text-slate-500 uppercase">Bénéficiaire Spécifique</label>
                            <Dropdown v-model="form.payable_id" :options="filteredPayables" optionLabel="name" optionValue="id" filter class="rounded-xl border-slate-200 py-1" :disabled="!form.payable_type" />
                        </div>

                        <div class="md:col-span-2 flex flex-col gap-2">
                            <label class="text-xs font-black text-slate-500 uppercase">Notes & Références</label>
                            <Textarea v-model="form.notes" rows="3" class="w-full p-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white transition-all text-sm outline-none" />
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="paymentDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="editing ? t('common.update') : t('common.save')"
                            icon="pi pi-check-circle" severity="indigo"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                            @click="savePayment" :loading="loading" />
                </div>
            </template>
        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss" scoped></style>
