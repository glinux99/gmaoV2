<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import debounce from 'lodash/debounce';
import { useI18n } from 'vue-i18n';

// Importations PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import MultiSelect from 'primevue/multiselect';
import OverlayPanel from 'primevue/overlaypanel';

const props = defineProps({
    expenses: { type: Array, default: () => [] },
    filters: Object,
    users: Array,
    categories: Array, // Ajouté
    projects: Array,   // Ajouté
    vehicles: Array,   // Ajouté
    expensables: {
        type: Array,
        default: () => []
    }
});

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

const expenseDialog = ref(false);
const editing = ref(false);
const dt = ref();
const selectedExpenses = ref([]);
const filters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    category_id: props.filters?.category_id || null,
    date: props.filters?.date || null,
});
const op = ref();
const filtersOp = ref();

// --- Configuration des Colonnes ---
const allColumns = ref([
    { field: 'description', header: computed(() => t('expenses.table.description')), default: true },
    { field: 'amount', header: computed(() => t('expenses.table.amount')), default: true },
    { field: 'expense_date', header: computed(() => t('expenses.table.date')), default: true },
    { field: 'category', header: computed(() => t('expenses.table.category')), default: true },
    { field: 'status', header: computed(() => t('expenses.table.status')), default: true },
    { field: 'user.name', header: computed(() => t('expenses.table.createdBy')), default: true },
    { field: 'expensable_title', header: computed(() => t('expenses.table.linkedTo')), default: true },
    { field: 'provider', header: "Fournisseur", default: false },
    { field: 'invoice_number', header: "N° Facture", default: false },
]);

const selectedColumns = ref(allColumns.value.filter(col => col.default).map(c => c.field));

// --- Formulaire Pro ---
const form = useForm({
    id: null,
    label: '', // Libellé
    description: '',
    amount: null,
    tax_rate: 20,
    expense_date: new Date(),
    category_id: null,
    provider: '',
    invoice_number: '',
    payment_method: 'Carte Bancaire',
    project_id: null,
    vehicle_id: null,
    notes: '',
    status: 'pending',
});

// --- Logique de données ---
const flatExpenses = computed(() => {
    if (!props.expenses) return [];
    return props.expenses.flatMap(group =>
        group.details.map(detail => ({
            ...detail,
            expensable_title: group.expensable_title
        }))
    );
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(value);
};

// --- Actions ---
const openNew = () => {
    form.reset();
    editing.value = false;
    expenseDialog.value = true;
};

const hideDialog = () => {
    expenseDialog.value = false;
};

const editExpense = (expense) => {
    form.id = expense.id;
    form.label = expense.description; // Mapping
    form.description = expense.description;
    form.amount = parseFloat(expense.amount);
    form.expense_date = new Date(expense.expense_date);
    form.category_id = expense.category_id;
    form.provider = expense.provider;
    form.invoice_number = expense.invoice_number;
    form.status = expense.status;

    editing.value = true;
    expenseDialog.value = true;
};

const saveExpense = () => {
    const action = editing.value
        ? route('expenses.update', form.id)
        : route('expenses.store');

    const method = editing.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Opération réussie', life: 3000 });
            hideDialog();
        }
    });
};

const updateExpenseStatus = (expense, newStatus) => {
    confirm.require({
        message: `Voulez-vous vraiment passer cette dépense en statut : ${newStatus} ?`,
        header: 'Confirmation de statut',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: newStatus === 'approved' ? 'p-button-success' : 'p-button-danger',
        accept: () => {
            router.put(route('expenses.updateStatus', expense.id), { status: newStatus }, {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Mis à jour', life: 3000 })
            });
        }
    });
};

const updateMultipleExpenseStatus = (expenses, newStatus) => {
    const ids = expenses.map(e => e.id);
    confirm.require({
        message: `Confirmer le changement de statut pour ${ids.length} éléments ?`,
        accept: () => {
            router.put(route('expenses.updateGroupStatus'), { ids, status: newStatus }, {
                onSuccess: () => {
                    selectedExpenses.value = [];
                    toast.add({ severity: 'success', summary: 'Traitement groupé terminé' });
                }
            });
        }
    });
};

const resetFilters = () => {
    Object.keys(filters.value).forEach(key => filters.value[key] = null);
};


// --- Helpers UI ---
const getStatusSeverity = (status) => {
    const map = { pending: 'warning', approved: 'success', rejected: 'danger', paid: 'info' };
    return map[status] || 'secondary';
};

const performSearch = debounce(() => {
    const params = {
        search: filters.value.search,
        status: filters.value.status,
        category_id: filters.value.category_id,
    };
    if (filters.value.date && filters.value.date.length === 2) {
        params.start_date = filters.value.date[0].toISOString().split('T')[0];
        params.end_date = filters.value.date[1].toISOString().split('T')[0];
    }

    router.get(route('expenses.index'), params, { preserveState: true, replace: true });
}, 300);

const toggleColumnSelection = (event) => op.value.toggle(event);
const exportCSV = () => dt.value.exportCSV();
</script>

<template>
    <AppLayout :title="t('expenses.headTitle')">
        <Head :title="t('expenses.headTitle')" />
        <Toast />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50 p-4 md:p-10">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-amber-500 shadow-xl shadow-amber-200">
                        <i class="pi pi-wallet text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tighter text-slate-900 md:text-4xl">{{ t('expenses.title') }}</h1>
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">{{ t('expenses.subtitle') }}</p>
                    </div>
                </div>
                <Button :label="t('expenses.addNew')" icon="pi pi-plus-circle" class="!rounded-2xl !font-black" @click="openNew" />
            </div>

            <!-- Toolbar -->
            <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-check" severity="success" :disabled="!selectedExpenses.length" @click="updateMultipleExpenseStatus(selectedExpenses, 'approved')" v-tooltip.top="'Approuver la sélection'"/>
                    <Button icon="pi pi-times" severity="danger" :disabled="!selectedExpenses.length" @click="updateMultipleExpenseStatus(selectedExpenses, 'rejected')" v-tooltip.top="'Rejeter la sélection'"/>
                </div>
                <div class="flex items-center gap-2">
                     <Button icon="pi pi-file-excel" severity="success" text @click="exportCSV" v-tooltip.top="'Exporter en CSV'" />
                     <Button icon="pi pi-columns" text @click="toggleColumnSelection" v-tooltip.top="'Choisir les colonnes'" />
                </div>
            </div>

            <div class="overflow-hidden rounded-[2.5rem] border border-white bg-white shadow-xl">
                <DataTable :value="flatExpenses" ref="dt" dataKey="id" :paginator="true" :rows="10"
                    v-model:selection="selectedExpenses" class="v11-table" responsiveLayout="scroll">

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <template v-for="col in allColumns" :key="col.field">
                        <Column v-if="selectedColumns.includes(col.field)" :field="col.field" :header="col.header" sortable>
                            <template #body="{ data, field }">
                                <template v-if="field === 'amount'">
                                    <span class="font-bold text-slate-900">{{ formatCurrency(data.amount) }}</span>
                                </template>
                                <template v-else-if="field === 'status'">
                                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="!rounded-full !px-3" />
                                </template>
                                <template v-else-if="field === 'expense_date'">
                                    {{ new Date(data.expense_date).toLocaleDateString() }}
                                </template>
                                <template v-else>
                                    {{ data[field] }}
                                </template>
                            </template>
                        </Column>
                    </template>

                    <Column header="Actions" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editExpense(data)" />
                                <template v-if="data.status === 'pending'">
                                    <Button icon="pi pi-check" text rounded severity="success" @click="updateExpenseStatus(data, 'approved')" />
                                    <Button icon="pi pi-times" text rounded severity="danger" @click="updateExpenseStatus(data, 'rejected')" />
                                </template>
                            </div>
                        </template>
                    </Column>

                    <template #footer>
                        <div class="text-right p-2 text-slate-500 font-bold">
                            Total sélection : {{ formatCurrency(selectedExpenses.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0)) }}
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="expenseDialog" modal :header="false" :closable="false" class="quantum-dialog" :style="{ width: '55rem' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }">

            <div class="bg-slate-900 p-8 flex justify-between items-center text-white">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center">
                        <i class="pi pi-receipt text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black m-0 uppercase tracking-tighter">{{ editing ? 'Modifier Dépense' : 'Nouveau Justificatif' }}</h2>
                        <span class="text-[9px] opacity-50 uppercase tracking-widest">Enregistrement comptable</span>
                    </div>
                </div>
                <Button icon="pi pi-times" @click="hideDialog" text rounded class="text-white/50" />
            </div>

            <div class="p-8 bg-slate-50">
                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 bg-white p-6 rounded-[2rem] shadow-sm flex flex-wrap gap-8 items-center border border-slate-200">
                        <div class="flex-1 min-w-[200px]">
                            <label class="text-[10px] font-black uppercase text-slate-400 block mb-2">Montant HT (XOF)</label>
                            <InputNumber v-model="form.amount" mode="decimal" class="w-full" :pt="{ input: { class: 'text-3xl font-black border-none bg-slate-50 rounded-xl p-3 w-full' } }" />
                        </div>
                        <div class="w-px h-12 bg-slate-100 hidden md:block"></div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="text-[10px] font-black uppercase text-slate-400 block mb-2">TVA (%)</label>
                            <Dropdown v-model="form.tax_rate" :options="[0, 5, 10, 18, 20]" class="w-full !rounded-xl !bg-slate-50 !border-none font-bold" />
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="text-[10px] font-black uppercase text-slate-400 block mb-2">Date d'opération</label>
                            <Calendar v-model="form.expense_date" dateFormat="dd/mm/yy" class="w-full" :pt="{ input: { class: 'font-bold border-none bg-slate-50 rounded-xl p-3 w-full' } }" />
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-7 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500">Fournisseur</label>
                                <InputText v-model="form.provider" class="w-full !rounded-xl !border-slate-100" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500">N° Facture</label>
                                <InputText v-model="form.invoice_number" class="w-full !rounded-xl !border-slate-100" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-500">Libellé court</label>
                            <InputText v-model="form.label" class="w-full !rounded-xl !border-slate-100" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500">Catégorie</label>
                                <Dropdown v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" class="w-full !rounded-xl" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500">Paiement</label>
                                <Dropdown v-model="form.payment_method" :options="['Carte', 'Espèces', 'Virement']" class="w-full !rounded-xl" />
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">
                        <div class="bg-slate-800 p-6 rounded-[2rem] text-white">
                            <h4 class="text-[10px] font-black uppercase text-amber-400 mb-4">Affectation</h4>
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] opacity-60 uppercase">Projet</label>
                                    <Dropdown v-model="form.project_id" :options="projects" optionLabel="name" optionValue="id" class="w-full !bg-white/10 !border-none !text-white" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] opacity-60 uppercase">Véhicule</label>
                                    <Dropdown v-model="form.vehicle_id" :options="vehicles" optionLabel="license_plate" optionValue="id" class="w-full !bg-white/10 !border-none !text-white" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white border-t flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest italic items-center flex gap-2">
                   <i class="pi pi-lock text-[8px]"></i> Session Sécurisée
                </span>
                <div class="flex gap-3">
                    <Button label="Annuler" text @click="hideDialog" class="!rounded-xl" />
                    <Button label="Enregistrer" icon="pi pi-save" @click="saveExpense" :loading="form.processing" class="!rounded-xl !bg-slate-900 !px-8" />
                </div>
            </div>
        </Dialog>

        <OverlayPanel ref="op" class="p-3">
            <h4 class="text-xs font-black uppercase mb-3">Colonnes visibles</h4>
            <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header" optionValue="field" display="chip" class="w-64" />
        </OverlayPanel>
    </AppLayout>
</template>

<style scoped>
.v11-table :deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.v11-table :deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

.v11-table :deep(.p-datatable-tbody > tr:hover) {
    background: #f8fafc;
}
</style>
