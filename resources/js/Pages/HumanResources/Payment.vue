<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

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
        fluxTotal: total.toLocaleString('fr-FR') + ' XOF',
        transactions: data.length,
        enAttente: data.filter(p => p.status === 'pending').length,
        moyenne: data.length > 0 ? (total / data.length).toFixed(0) : 0
    };
});

// --- SYSTÈME DE FILTRES AVANCÉS (V11) ---
const filters = ref({
    global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    category: { value: null, matchMode: FilterMatchMode.IN },
    payment_method: { value: null, matchMode: FilterMatchMode.IN }
});

// --- LOGIQUE POLYMORPHIQUE PAYABLE ---
const payableTypes = [
    { label: 'Employé', value: 'App\\Models\\Employee', icon: 'pi pi-user' },
    { label: 'Fournisseur', value: 'App\\Models\\Supplier', icon: 'pi pi-truck' },
    { label: 'Utilisateur', value: 'App\\Models\\User', icon: 'pi pi-id-card' },
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

watch(() => form.payable_type, () => { form.payable_id = null; });

// --- ACTIONS ---
const openNew = () => {
    form.reset();
    form.payment_date = new Date();
    form.paid_by = user?.id;
    editing.value = false;
    submitted.value = false;
    paymentDialog.value = true;
};

const editPayment = (payment) => {
    form.clearErrors();
    Object.assign(form, {
        ...payment,
        payment_date: payment.payment_date ? new Date(payment.payment_date) : null
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
            toast.add({ severity: 'success', summary: 'Confirmé', detail: 'Paiement enregistré', life: 3000 });
            loading.value = false;
        },
        onError: () => { loading.value = false; }
    });
};

const deletePayment = (payment) => {
    confirm.require({
        message: `Supprimer le paiement de ${payment.amount} XOF ?`,
        header: 'Attention',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('payments.destroy', payment.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Info', detail: 'Supprimé' })
            });
        }
    });
};

// --- CONFIGURATIONS UI ---
const getStatusSeverity = (s) => ({ pending: 'warning', completed: 'success', failed: 'danger' }[s] || 'secondary');

const getPayableName = (type, id) => {
    const p = props.payables.find(x => x.type === type && x.id === id);
    return p ? p.name : 'N/A';
};
</script>

<template>
    <AppLayout>
        <Head title="Finance V11" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight text-uppercase">Flux Financiers</h1>
                    <p class="text-slate-500 font-medium text-sm">Gestion des décaissements et de la paie</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Nouvelle Transaction" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ key.replace(/([A-Z])/g, ' $1') }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-black text-slate-800">{{ val }}</span>
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
                                <InputText v-model="filters['global'].value" placeholder="Recherche globale..." class="w-full md:w-80 rounded-2xl border-slate-200" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column field="amount" header="Montant" sortable>
                        <template #body="{ data }">
                            <span class="font-black text-slate-800">{{ data.amount.toLocaleString() }} <small>XOF</small></span>
                        </template>
                    </Column>

                    <Column field="payable_name" header="Bénéficiaire">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="getPayableName(data.payable_type, data.payable_id)?.charAt(0)" shape="circle" class="bg-slate-100 text-slate-600 font-bold" />
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 text-sm">{{ getPayableName(data.payable_type, data.payable_id) }}</span>
                                    <small class="text-slate-400 text-[10px] uppercase font-bold">{{ data.payable_type.split('\\').pop() }}</small>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="Statut" sortable>
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 text-[10px] uppercase" />
                        </template>
                    </Column>

                    <Column field="payment_date" header="Date" sortable>
                        <template #body="{ data }">
                            <span class="text-slate-500 font-medium text-sm">{{ new Date(data.payment_date).toLocaleDateString('fr-FR') }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen>
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
            :header="editing ? 'Révision Transaction' : 'Nouveau Décaissement'"
            :style="{ width: '90vw', maxWidth: '700px' }"
            class="ultimate-modal"
        >
            <div class="p-2 space-y-8">
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-dollar text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 m-0">Paramètres financiers</h4>
                        <p class="text-xs text-slate-500 m-0">Veuillez renseigner les détails du mouvement de fonds</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">Montant (XOF)</label>
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

                <div class="flex flex-col sm:flex-row gap-3 pt-8 border-t border-slate-100">
                    <button @click="paymentDialog = false" class="flex-1 px-6 py-4 rounded-2xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all uppercase text-xs tracking-widest">Abandonner</button>
                    <button @click="savePayment" class="flex-1 px-8 py-4 rounded-2xl font-black text-white bg-primary-600 hover:bg-primary-700 shadow-xl shadow-primary-200 hover:-translate-y-1 transition-all uppercase text-xs tracking-widest flex items-center justify-center gap-3">
                        <i v-if="loading" class="pi pi-spin pi-spinner"></i>
                        <span>{{ editing ? 'Mettre à jour' : 'Confirmer le paiement' }}</span>
                    </button>
                </div>
            </div>
        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
// Importation des styles Version 11 définis précédemment
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
        &:hover { background: #f1f5f9 !important; }
    }
}

.ultimate-modal {
    .p-dialog-header {
        background: white;
        padding: 2rem 2rem 1rem 2rem;
        .p-dialog-title { font-weight: 900; font-size: 1.25rem; color: #0f172a; }
    }
    .p-dialog-content { padding: 0 2rem 2rem 2rem; }
}
</style>
