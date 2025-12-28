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

const stats = computed(() => {
    const allExpenses = flatExpenses.value;
    const totalAmount = allExpenses.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0);
    return {
        totalAmount: totalAmount,
        pending: allExpenses.filter(e => e.status === 'pending').length,
        average: allExpenses.length > 0 ? totalAmount / allExpenses.length : 0,
    };
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
    performSearch();
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
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-wallet text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Gestion des <span class="text-primary-600">Dépenses</span></h1>
                        <p class="text-slate-500 font-medium">{{ t('expenses.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('expenses.addNew')" icon="pi pi-plus" raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t('expenses.stats.totalAmount') }}</span>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-3xl font-black text-slate-800">{{ formatCurrency(stats.totalAmount) }}</span>
                        <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center"><i class="pi pi-dollar text-slate-400"></i></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t('expenses.stats.pending') }}</span>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-3xl font-black text-slate-800">{{ stats.pending }}</span>
                        <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center"><i class="pi pi-clock text-slate-400"></i></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t('expenses.stats.average') }}</span>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-3xl font-black text-slate-800">{{ formatCurrency(stats.average) }}</span>
                        <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center"><i class="pi pi-chart-line text-slate-400"></i></div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2.5rem] border border-white bg-white shadow-xl">
                <DataTable :value="flatExpenses" ref="dt" dataKey="id" :paginator="true" :rows="10"
                    v-model:selection="selectedExpenses" class="v11-table" responsiveLayout="scroll">
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters.search" @input="performSearch" :placeholder="t('expenses.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button v-if="selectedExpenses.length" icon="pi pi-check" severity="success" @click="updateMultipleExpenseStatus(selectedExpenses, 'approved')" v-tooltip.top="'Approuver la sélection'"/>
                                <Button v-if="selectedExpenses.length" icon="pi pi-times" severity="danger" @click="updateMultipleExpenseStatus(selectedExpenses, 'rejected')" v-tooltip.top="'Rejeter la sélection'"/>
                                <div v-if="selectedExpenses.length" class="w-px h-6 bg-slate-200"></div>
                                <Button icon="pi pi-filter" text rounded severity="secondary" @click="(event) => filtersOp.toggle(event)" />
                                <Button icon="pi pi-file-excel" text rounded severity="secondary" @click="exportCSV" v-tooltip.top="'Exporter en CSV'" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="toggleColumnSelection" v-tooltip.top="'Choisir les colonnes'" />
                            </div>
                        </div>
                    </template>
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

        <Dialog v-model:visible="expenseDialog" modal :header="false" :closable="false" :style="{ width: '55rem' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }">

            <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-receipt text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ editing ? t('expenses.dialog.editTitle') : t('expenses.dialog.createTitle') }}</h4>
                        <p class="text-xs text-slate-400 m-0">{{ t('expenses.subtitle') }}</p>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                    <div class="md:col-span-12 space-y-8">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Montant HT (XOF)</label>
                                <InputNumber v-model="form.amount" mode="decimal" class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">TVA (%)</label>
                                <Dropdown v-model="form.tax_rate" :options="[0, 5, 10, 18, 20]" class="w-full rounded-xl border-slate-200 py-1" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Date d'opération</label>
                                <Calendar v-model="form.expense_date" dateFormat="dd/mm/yy" class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Fournisseur</label>
                                <InputText v-model="form.provider" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" placeholder="Nom du fournisseur" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">N° Facture</label>
                                <InputText v-model="form.invoice_number" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" placeholder="Numéro de facture" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Libellé court</label>
                                <InputText v-model="form.label" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" placeholder="Libellé de la dépense" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Catégorie</label>
                                <Dropdown v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" class="w-full rounded-xl border-slate-200 py-1" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Paiement</label>
                                <Dropdown v-model="form.payment_method" :options="['Carte', 'Espèces', 'Virement']" class="w-full rounded-xl border-slate-200 py-1" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Projet</label>
                                <Dropdown v-model="form.project_id" :options="projects" optionLabel="name" optionValue="id" class="w-full rounded-xl border-slate-200 py-1" showClear />
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Véhicule</label>
                                <Dropdown v-model="form.vehicle_id" :options="vehicles" optionLabel="license_plate" optionValue="id" class="w-full rounded-xl border-slate-200 py-1" showClear />
                            </div>

                            <div class="md:col-span-2 flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Description</label>
                                <Textarea v-model="form.description" rows="3" class="w-full p-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-primary-50 transition-all text-sm outline-none" placeholder="Description détaillée de la dépense"></Textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="font-bold uppercase text-[10px] tracking-widest" />
                <div class="flex gap-3">
                    <Button :label="editing ? t('common.update') : t('common.save')" icon="pi pi-check-circle" severity="indigo"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                            @click="saveExpense" :loading="form.processing" />
                </div>
            </div>
            </template>
        </Dialog>

        <OverlayPanel ref="op" class="p-3">
            <h4 class="text-xs font-black uppercase mb-3">{{ t('common.columnSelector.title') }}</h4>
            <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header" optionValue="field" display="chip" class="w-64" />
        </OverlayPanel>

        <OverlayPanel ref="filtersOp" class="p-4">
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-semibold">{{ t('expenses.filter.status') }}</label>
                    <Dropdown v-model="filters.status" :options="['pending', 'approved', 'rejected', 'paid']" showClear class="w-full mt-1" />
                </div>
                <div>
                    <label class="text-xs font-semibold">{{ t('expenses.filter.category') }}</label>
                    <Dropdown v-model="filters.category_id" :options="categories" optionLabel="name" optionValue="id" showClear class="w-full mt-1" />
                </div>
                <Button :label="t('expenses.filter.apply')" icon="pi pi-check" @click="performSearch" class="w-full" />
                <Button :label="t('toolbar.resetFilters')" icon="pi pi-filter-slash" text @click="resetFilters" class="w-full" />
            </div>
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
