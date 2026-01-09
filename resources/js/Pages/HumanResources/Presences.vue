<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// Import des composants PrimeVue manquants utilisés dans le template
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect'; // Nécessaire pour les membres
import OverlayPanel from 'primevue/overlaypanel';
import Calendar from 'primevue/calendar'; // Nécessaire pour la date
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const props = defineProps({
    payments: Object, // Les paiements paginés
    filters: Object,
    users: Array, // Liste des utilisateurs pour 'paid_by'
    payables: Array, // Liste des entités payables (employés, fournisseurs, etc.)
});

const toast = useToast();
const confirm = useConfirm();

const paymentDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const selectedPayments = ref(null); // Pour la suppression multiple
const dt = ref(); // Référence au DataTable
const op = ref(); // Référence à l'OverlayPanel

// --- SYSTÈME DE FILTRES AVANCÉS (basé sur Regions.vue) ---
const filters = ref();
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        'payable_name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        category: { value: null, matchMode: FilterMatchMode.IN },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
        amount: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
    };
};
initFilters(); // Initialiser les filtres

// Colonnes dynamiques pour le DataTable
const allColumns = ref([
    { field: 'amount', header: 'Montant', default: true },
    { field: 'payment_date', header: 'Date de Paiement', default: true },
    { field: 'payable_name', header: 'Payable à', default: true }, // Nom de l'entité payable
    { field: 'category', header: 'Catégorie', default: true },
    { field: 'status', header: 'Statut', default: true },
    { field: 'paid_by_user.name', header: 'Payé par', default: false },
    { field: 'payment_method', header: 'Méthode', default: false },
    { field: 'reference', header: 'Référence', default: false },
    { field: 'notes', header: 'Notes', default: false },
]);
const selectedColumns = ref(allColumns.value.filter(col => col.default));
const visibleColumns = computed(() => selectedColumns.value);
const { user } = usePage().props.auth;

const form = useForm({
    id: null,
    name: '',
    team_leader_id: null,
    creation_date: null,
    members: [], // Tableau d'IDs des techniciens
    amount: null,
    payment_date: null,
    payment_method: 'bank_transfer',
    reference: null,
    notes: null,
    status: 'completed',
    paid_by: null,
    payable_type: null,
    payable_id: null,
    category: 'salary',
});

// --- LOGIQUE DE SUPPRESSION MULTIPLE ---

const hasSelectedPayments = computed(() => selectedPayments.value && selectedPayments.value.length > 0);

const confirmDeleteSelected = () => {
    if (!hasSelectedPayments.value) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez sélectionner au moins un paiement.', life: 3000 });
        return;
    }

    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedPayments.value.length} paiements sélectionnés ?`,
        header: 'Confirmation de suppression multiple',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteSelectedTeams();
        },
    });
};

const deleteSelectedTeams = () => {
    const ids = selectedPayments.value.map(payment => payment.id);

    router.post(route('payments.bulkDestroy'), { ids: ids }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} paiements supprimés avec succès.`, life: 3000 });
            selectedPayments.value = null; // Désélectionner
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression multiple.', life: 3000 });
        },
        preserveState: false,
    });
};


// --- LOGIQUE DE FORMULAIRE DE PAIEMENT ---

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    form.payment_date = new Date();
    form.paid_by = user?.id; // Pré-remplir avec l'utilisateur actuel
    paymentDialog.value = true;
};

const hideDialog = () => {
    paymentDialog.value = false;
    submitted.value = false;
};

const editPayment = (payment) => {
    form.id = payment.id;
    form.amount = payment.amount;
    form.payment_date = payment.payment_date ? new Date(payment.payment_date) : null;
    form.payment_method = payment.payment_method;
    form.reference = payment.reference;
    form.notes = payment.notes;
    form.status = payment.status;
    form.paid_by = payment.paid_by;
    form.payable_type = payment.payable_type;
    form.payable_id = payment.payable_id;
    form.category = payment.category;
    editing.value = true;
    paymentDialog.value = true;
};

const savePayment = () => {
    submitted.value = true;

    if (!form.amount || !form.payment_date || !form.paid_by || !form.payable_type || !form.payable_id) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: "Veuillez remplir tous les champs obligatoires (Montant, Date, Payé par, Payable à).", life: 3000 });
        return;
    }

    const url = editing.value ? route('payments.update', form.id) : route('payments.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            paymentDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Paiement ${editing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
            form.reset();
            selectedPayments.value = null; // Réinitialiser la sélection
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du paiement", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deletePayment = (payment) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer le paiement de ${payment.amount} à ${payment.payable_name} ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('payments.destroy', payment.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Paiement supprimé avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

// Options pour les dropdowns
const paymentMethods = ref([
    { label: 'Virement bancaire', value: 'bank_transfer' },
    { label: 'Espèces', value: 'cash' },
    { label: 'Chèque', value: 'check' },
    { label: 'Autre', value: 'other' },
]);

const paymentCategories = ref([
    { label: 'Salaire', value: 'salary' },
    { label: 'Prime', value: 'bonus' },
    { label: 'Remboursement de dépenses', value: 'expense_reimbursement' },
    { label: 'Avance', value: 'advance' },
    { label: 'Autre', value: 'other' },
]);

const paymentStatuses = ref([
    { label: 'En attente', value: 'pending' },
    { label: 'Terminé', value: 'completed' },
    { label: 'Échoué', value: 'failed' },
    { label: 'Remboursé', value: 'refunded' },
]);

const getStatusSeverity = (status) => ({
    pending: 'warning',
    completed: 'success',
    failed: 'danger',
    refunded: 'info',
}[status] || 'secondary');

const getPayableName = (payableType, payableId) => {
    const payable = props.payables.find(p => p.type === payableType && p.id === payableId);
    return payable ? payable.name : 'N/A';
};


const exportCSV = () => {
    dt.value.exportCSV();
};

const dialogTitle = computed(() => editing.value ? 'Modifier le Paiement' : 'Créer un nouveau Paiement');

</script>

<template>
    <AppLayout title="Gestion des Équipes">
        <Head title="Équipes" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">
                                <span class="block mt-2 md:mt-0 flex align-items-center">
                                    <Button label="Ajouter un paiement" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />
                                    <Button label="Supprimer la sélection" icon="pi pi-trash" class="p-button-sm p-button-danger"
                                        :disabled="!hasSelectedPayments" @click="confirmDeleteSelected" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <div class="flex items-center gap-2">
                                <IconField class="mr-2">
                                    <InputIcon><i class="pi pi-search" /></InputIcon>
                                    <InputText v-model="filters['global'].value" placeholder="Rechercher..." class="p-inputtext-sm" />
                                </IconField>
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="p-button-sm" />

                                <Button icon="pi pi-download" label="Exporter" class="p-button-text p-button-secondary p-button-sm font-bold" @click="exportCSV" />
                                <Button icon="pi pi-columns" class="p-button-text p-button-secondary" @click="op.toggle($event)" />
                            </div>
                        </template>
                    </Toolbar>
                    <OverlayPanel ref="op" class="quantum-overlay">
                        <div class="p-2 space-y-3">
                            <span class="text-[10px] font-black uppercase text-slate-400 block border-b pb-2">Colonnes actives</span>
                            <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header" optionValue="field"
                                         display="chip" class="w-64 quantum-multiselect" />
                        </div>
                    </OverlayPanel>
                    <DataTable ref="dt" :value="payments.data" dataKey="id" :paginator="true" :rows="10"
                        v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['payable_name', 'category', 'status', 'amount']"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} paiements"
                        responsiveLayout="scroll"
                        v-model:selection="selectedPayments">

                        <Column selectionMode="multiple" headerStyle="width: 3rem" frozen></Column>

                        <template v-for="col in visibleColumns" :key="col.field">
                            <Column :field="col.field" :header="col.header" :sortable="true" style="min-width: 12rem;">
                                <template #body="slotProps">
                                    <span v-if="col.field === 'amount'">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(slotProps.data.amount) }}
                                    </span>
                                    <span v-else-if="col.field === 'payment_date'">
                                        {{ new Date(slotProps.data.payment_date).toLocaleDateString('fr-FR') }}
                                    </span>
                                    <span v-else-if="col.field === 'status'" >
                                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                                    </span>
                                    <span v-else-if="col.field === 'paid_by_user.name'">
                                        {{ slotProps.data.paid_by_user ? slotProps.data.paid_by_user.name : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'payable_name'">
                                        {{ slotProps.data.payable_name }}
                                    </span>
                                    <span v-else>
                                        {{ slotProps.data[col.field] }}
                                    </span>
                                </template>

                                <template #filter="{ filterModel }" v-if="col.field === 'status'">
                                    <Dropdown v-model="filterModel.value" :options="paymentStatuses" optionLabel="label" optionValue="value" placeholder="Tous les statuts" class="p-column-filter" showClear>
                                    </Dropdown>
                                </template>

                                <template #filter="{ filterModel }" v-if="col.field === 'category'">
                                    <MultiSelect v-model="filterModel.value" :options="paymentCategories" optionLabel="label" optionValue="value" placeholder="Toutes les catégories" class="p-column-filter">
                                    </MultiSelect>
                                </template>

                                <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'payable_name'">
                                    <InputText v-model="filterModel.value" type="text" @keydown.enter="filterCallback()" class="p-column-filter" placeholder="Rechercher par nom"/>
                                </template>

                            </Column>
                        </template>

                        <Column headerStyle="min-width:10rem;" header="Actions" bodyStyle="text-align: right" frozen alignFrozen="right">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editPayment(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deletePayment(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="paymentDialog" modal :header="dialogTitle" :style="{ width: '45rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-4">Mettez à jour les informations du paiement.</span>

                        <div class="flex flex-column gap-3">
                            <div class="flex items-center gap-4">
                                <label for="amount" class="font-semibold w-24">Montant *</label>
                                <InputNumber id="amount" v-model="form.amount" mode="currency" currency="USD" locale="fr-FR"
                                    :class="{ 'p-invalid': submitted && !form.amount }" class="flex-auto" />
                            </div>
                            <small class="p-error" v-if="form.errors.amount">{{ form.errors.amount }}</small>

                            <div class="flex items-center gap-4">
                                <label for="payment_date" class="font-semibold w-24">Date de Paiement *</label>
                                <Calendar id="payment_date" v-model="form.payment_date" dateFormat="dd/mm/yy" showIcon
                                    :class="{ 'p-invalid': submitted && !form.payment_date }" class="flex-auto" />
                            </div>
                            <small class="p-error" v-if="form.errors.payment_date">{{ form.errors.payment_date }}</small>

                            <div class="flex items-center gap-4">
                                <label for="payment_method" class="font-semibold w-24">Méthode de Paiement</label>
                                <Dropdown id="payment_method" v-model="form.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value"
                                    placeholder="Sélectionner une méthode" class="flex-auto" />
                            </div>
                            <small class="p-error" v-if="form.errors.payment_method">{{ form.errors.payment_method }}</small>

                            <div class="flex items-center gap-4">
                                <label for="reference" class="font-semibold w-24">Référence</label>
                                <InputText id="reference" v-model.trim="form.reference" class="flex-auto" autocomplete="off" />
                            </div>
                            <small class="p-error" v-if="form.errors.reference">{{ form.errors.reference }}</small>

                            <div class="flex items-center gap-4">
                                <label for="category" class="font-semibold w-24">Catégorie</label>
                                <Dropdown id="category" v-model="form.category" :options="paymentCategories" optionLabel="label" optionValue="value"
                                    placeholder="Sélectionner une catégorie" class="flex-auto" />
                            </div>
                            <small class="p-error" v-if="form.errors.category">{{ form.errors.category }}</small>

                            <div class="flex items-center gap-4">
                                <label for="paid_by" class="font-semibold w-24">Payé par *</label>
                                <Dropdown id="paid_by" v-model="form.paid_by" :options="users" optionLabel="name" optionValue="id"
                                    placeholder="Sélectionner un utilisateur" :class="{ 'p-invalid': submitted && !form.paid_by }" class="flex-auto" />
                            </div>
                            <small class="p-error" v-if="form.errors.paid_by">{{ form.errors.paid_by }}</small>

                            <div class="flex items-center gap-4">
                                <label for="payable" class="font-semibold w-24">Payable à *</label>
                                <Dropdown id="payable" v-model="form.payable_type" :options="payables" optionLabel="name"
                                    placeholder="Sélectionner une entité" :class="{ 'p-invalid': submitted && (!form.payable_type || !form.payable_id) }" class="flex-auto"
                                    @change="event => { form.payable_type = event.value.type; form.payable_id = event.value.id; }">
                                    <template #value="slotProps">
                                        <span v-if="slotProps.value">{{ getPayableName(slotProps.value[0], slotProps.value[1]) }}</span>
                                        <span v-else>{{ slotProps.placeholder }}</span>
                                    </template>
                                    <template #option="slotProps">
                                        <span>{{ slotProps.option.name }} ({{ slotProps.option.type.split('\\').pop() }})</span>
                                    </template>
                                </Dropdown>
                            </div>
                            <small class="p-error" v-if="form.errors.payable_id || form.errors.payable_type">Veuillez sélectionner une entité payable.</small>

                            <div class="flex items-center gap-4">
                                <label for="notes" class="font-semibold w-24">Notes</label>
                                <InputText id="notes" v-model.trim="form.notes" class="flex-auto" autocomplete="off" />
                            </div>
                            <small class="p-error" v-if="form.errors.notes">{{ form.errors.notes }}</small>

                            <div class="flex items-center gap-4">
                                <label for="status" class="font-semibold w-24">Statut</label>
                                <Dropdown id="status" v-model="form.status" :options="paymentStatuses" optionLabel="label" optionValue="value"
                                    placeholder="Sélectionner un statut" class="flex-auto" />
                            </div>
                            <small class="p-error" v-if="form.errors.status">{{ form.errors.status }}</small>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="savePayment" :loading="form.processing"></Button>
                        </div>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Les styles restent inchangés et sont toujours valides */
.p-datatable .p-datatable-header {
    border-bottom: 1px solid var(--surface-d);
}

.p-datatable .p-column-header-content {
    justify-content: space-between;
}

/* ... autres styles de boutons ... */
</style>
