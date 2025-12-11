<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// Import des composants PrimeVue
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider'; // Ajout pour la mise en page
// ... (Autres imports si nécessaire)

const props = defineProps({
    payments: Object,
    filters: Object,
    users: Array, // Utilisateurs (peuvent être paid_by et payable)
    payables: Array, // Ancienne liste agrégée (conservée pour getPayableName)

    // NOUVELLES PROPS AJOUTÉES POUR LE FILTRAGE DYNAMIQUE:
    employees: Array,
    suppliers: Array,
    teams: Array, // Ajouté si vous voulez la logique 'assignable' aussi
});

const toast = useToast();
const confirm = useConfirm();

const paymentDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedPayments = ref(null);
const dt = ref();

// Colonnes dynamiques pour le DataTable
const allColumns = ref([
    { field: 'amount', header: 'Montant', default: true },
    { field: 'payment_date', header: 'Date de Paiement', default: true },
    { field: 'payment_method', header: 'Méthode', default: true },
    { field: 'reference', header: 'Référence', default: true },
    { field: 'category', header: 'Catégorie', default: true },
    { field: 'status', header: 'Statut', default: true },
    { field: 'paid_by_user.name', header: 'Payé par', default: true },
    { field: 'payable_name', header: 'Payable à', default: true },
    { field: 'notes', header: 'Notes', default: false },
]);
const selectedColumns = ref(allColumns.value.filter(col => col.default));
const visibleColumns = computed(() => selectedColumns.value);
const { user } = usePage().props.auth;

const form = useForm({
    id: null,
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

// ------------------------------------------------------------------
// LOGIQUE DE SÉLECTION POLYMORPHIQUE (PAYABLE)
// ------------------------------------------------------------------

// 1. Liste des types (modèles) payables
const payableTypes = [
    { label: 'Employé', value: 'App\\Models\\Employee' },
    { label: 'Fournisseur', value: 'App\\Models\\Supplier' },
    { label: 'Utilisateur', value: 'App\\Models\\User' },
];

// Watcher pour réinitialiser payable_id si payable_type change
watch (() => form.payable_type, (newType, oldType) => {
    if (newType !== oldType) {
        form.payable_id = null;
    }
});

/**
 * Retourne la liste des entités payables disponibles
 * en fonction du type (modèle) sélectionné.
 * C'EST L'ÉQUIVALENT DE `getAssignablesForActivity` appliqué à `payable`.
 */
const getPayableOptions = (type) => {
    switch (type) {
        case 'App\\Models\\Employee':
            return props.employees;
        case 'App\\Models\\Supplier':
            return props.suppliers;
        case 'App\\Models\\User':
            return props.users;
        default:
            return [];
    }
};


// 3. Entités payables filtrées basées sur le type sélectionné (utilisé dans le Dropdown)
const filteredPayables = computed(() => {
    return getPayableOptions(form.payable_type);
});


// ------------------------------------------------------------------
// LOGIQUE DE SUPPRESSION MULTIPLE (Conservée)
// ------------------------------------------------------------------

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
            // Logique de suppression ici
            console.log("Suppression multiple lancée pour:", selectedPayments.value.map(p => p.id));
        },
    });
};

const deletePayment = (payment) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer le paiement de ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(payment.amount)} ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Non',
        acceptLabel: 'Oui',
        acceptClass: 'p-button-danger',

        accept: () => {
             // Ici, vous appelleriez l'endpoint de suppression via Inertia
             // router.delete(route('payments.destroy', payment.id), { ... });
            toast.add({ severity: 'info', summary: 'Suppression', detail: 'Fonction de suppression non implémentée.', life: 3000 });
        }
    });
};

// ------------------------------------------------------------------
// LOGIQUE DE FORMULAIRE DE PAIEMENT (openNew, hideDialog, editPayment, savePayment)
// ------------------------------------------------------------------

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    form.payment_date = new Date();
    form.paid_by = user?.id;
    paymentDialog.value = true;
};

const hideDialog = () => {
    paymentDialog.value = false;
    submitted.value = false;
    form.reset();
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
    // Initialisation du payable
    form.payable_type = payment.payable_type;
    form.payable_id = payment.payable_id;
    form.category = payment.category;
    editing.value = true;
    paymentDialog.value = true;
};

const savePayment = () => {
    submitted.value = true;

    if (form.processing) return;

    if (!form.amount || !form.payment_date || !form.paid_by || !form.payable_type || !form.payable_id) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: "Veuillez remplir tous les champs obligatoires.", life: 3000 });
        return;
    }

    const url = editing.value ? route('payroll.update', form.id) : route('payroll.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            paymentDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Paiement ${editing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
            form.reset();
            selectedPayments.value = null;
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du paiement", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs.', life: 3000 });
        }
    });
};
// Options pour les dropdowns (conservées)
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
    // Utilisation de la liste agrégée props.payables pour la DataTable (moins de requêtes)

    const payable = props.payables.find(p => p.type === payableType && p.id === payableId);
    const typeLabel = payableTypes.find(t => t.value === payableType)?.label || payableType.split('\\').pop();
    return payable ? `${payable.name} (${typeLabel})` : 'N/A';
};


const exportCSV = () => {
    dt.value.exportCSV();
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('payments.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const dialogTitle = computed(() => editing.value ? 'Modifier le Paiement' : 'Créer un nouveau Paiement');

</script>

<template>
    <AppLayout title="Gestion des Paiements">
        <Head title="Paiements" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-start md:align-items-center">
                                <span class="block mt-2 md:mt-0 flex align-items-center">
                                    <Button label="Ajouter un paiement" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />
                                    <Button label="Supprimer la sélection" icon="pi pi-trash" class="p-button-sm p-button-danger"
                                        :disabled="!hasSelectedPayments" @click="confirmDeleteSelected" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header"
                                placeholder="Afficher les colonnes" display="chip" class="mr-2" />
                            <IconField class="mr-2">
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                            </IconField>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="payments.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} paiements"
                        responsiveLayout="scroll"
                        v-model:selection="selectedPayments">

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <template v-for="col in visibleColumns" :key="col.field">
                            <Column :field="col.field" :header="col.header" :sortable="true" style="min-width: 10rem;">
                                <template #body="slotProps">
                                    <span v-if="col.field === 'amount'">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(slotProps.data.amount) }}
                                    </span>
                                    <span v-else-if="col.field === 'payment_date'">
                                        {{ slotProps.data.payment_date ? new Date(slotProps.data.payment_date).toLocaleDateString('fr-FR') : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'status'">
                                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                                    </span>
                                    <span v-else-if="col.field === 'paid_by_user.name'">

                                        {{ slotProps.data.paid_by ? slotProps.data.paid_by.name : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'payable_name'">

                                        {{ getPayableName(slotProps.data.payable_type, slotProps.data.payable_id) }}
                                    </span>
                                    <span v-else>
                                        {{ slotProps.data[col.field] }}
                                    </span>
                                </template>
                            </Column>
                        </template>

                        <Column headerStyle="min-width:10rem;" header="Actions" bodyStyle="text-align: right">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editPayment(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded" severity="error"
                                    @click="deletePayment(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="paymentDialog" modal :header="dialogTitle" :style="{ width: '50rem' }" class="p-dialog-content">

                        <span v-if="editing" class="text-gray-500 block mb-3 text-sm">
                            Mettez à jour les informations du paiement. Les champs marqués d'une * sont obligatoires.
                        </span>

                        <div class="p-fluid grid grid-cols-2 gap-4">

                            <div class="field col-span-1">
                                <label for="amount" class="font-semibold block mb-2">Montant *</label>
                                <InputNumber
                                    id="amount"
                                    v-model="form.amount"
                                    mode="currency"
                                    currency="XOF"
                                    locale="fr-FR"
                                    :class="{ 'p-invalid': submitted && !form.amount }"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.amount">{{ form.errors.amount }}</small>
                            </div>

                            <div class="field col-span-1">
                                <label for="payment_date" class="font-semibold block mb-2">Date de Paiement *</label>
                                <Calendar
                                    id="payment_date"
                                    v-model="form.payment_date"
                                    dateFormat="dd/mm/yy"
                                    showIcon
                                    :class="{ 'p-invalid': submitted && !form.payment_date }"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.payment_date">{{ form.errors.payment_date }}</small>
                            </div>

                            <div class="field col-span-1">
                                <label for="payable_type" class="font-semibold block mb-2">Type Payable *</label>
                                <Dropdown
                                    id="payable_type"
                                    v-model="form.payable_type"
                                    :options="payableTypes"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner le type (Ex: Employé)"
                                    :class="{ 'p-invalid': submitted && !form.payable_type }"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.payable_type">{{ form.errors.payable_type }}</small>
                            </div>

                            <div class="field col-span-1">
                                <label for="payable_id" class="font-semibold block mb-2">Payable à *</label>
                                <Dropdown
                                    id="payable_id"
                                    v-model="form.payable_id"
                                    :options="filteredPayables"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Sélectionner l'entité spécifique"
                                    :disabled="!form.payable_type"
                                    :class="{ 'p-invalid': submitted && !form.payable_id }"
                                    class="w-full"
                                    filter
                                    showClear
                                />
                                <small class="p-error" v-if="form.errors.payable_id">
                                    Veuillez sélectionner l'entité spécifique.
                                </small>
                            </div>

                            <div class="field col-span-1">
                                <label for="payment_method" class="font-semibold block mb-2">Méthode de Paiement</label>
                                <Dropdown
                                    id="payment_method"
                                    v-model="form.payment_method"
                                    :options="paymentMethods"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner une méthode"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.payment_method">{{ form.errors.payment_method }}</small>
                            </div>

                            <div class="field col-span-1">
                                <label for="category" class="font-semibold block mb-2">Catégorie</label>
                                <Dropdown
                                    id="category"
                                    v-model="form.category"
                                    :options="paymentCategories"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner une catégorie"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.category">{{ form.errors.category }}</small>
                            </div>

                            <div class="field col-span-1">
                                <label for="paid_by" class="font-semibold block mb-2">Payé par *</label>
                                <Dropdown
                                    id="paid_by"
                                    v-model="form.paid_by"
                                    :options="users"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Sélectionner l'utilisateur payeur"
                                    :class="{ 'p-invalid': submitted && !form.paid_by }"
                                    class="w-full"
                                    filter
                                />
                                <small class="p-error" v-if="form.errors.paid_by">{{ form.errors.paid_by }}</small>
                            </div>

                            <div class="field col-span-1">
                                <label for="status" class="font-semibold block mb-2">Statut</label>
                                <Dropdown
                                    id="status"
                                    v-model="form.status"
                                    :options="paymentStatuses"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner un statut"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.status">{{ form.errors.status }}</small>
                            </div>

                            <div class="field col-span-2">
                                <label for="reference" class="font-semibold block mb-2">Référence</label>
                                <InputText
                                    id="reference"
                                    v-model.trim="form.reference"
                                    autocomplete="off"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.reference">{{ form.errors.reference }}</small>
                            </div>

                            <div class="field col-span-2">
                                <label for="notes" class="font-semibold block mb-2">Notes</label>
                                <Textarea
                                    id="notes"
                                    v-model.trim="form.notes"
                                    rows="3"
                                    class="w-full"
                                />
                                <small class="p-error" v-if="form.errors.notes">{{ form.errors.notes }}</small>
                            </div>

                        </div>
                        <template #footer>
                            <div class="flex justify-content-end gap-2 mt-4">
                                <Button type="button" label="Annuler" severity="secondary" icon="pi pi-times" @click="hideDialog" />
                                <Button type="submit" :label="editing ? 'Mettre à Jour' : 'Sauvegarder'" icon="pi pi-check" @click="savePayment" :loading="form.processing" />
                            </div>
                        </template>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Les styles restent inchangés */
.p-datatable .p-datatable-header {
    border-bottom: 1px solid var(--surface-d);
}

.p-datatable .p-column-header-content {
    justify-content: space-between;
}

/* Vous pouvez ajouter ceci si vous n'utilisez pas nativement les classes Tailwind 'grid-cols-2' */
.grid-cols-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.col-span-1 {
    grid-column: span 1 / span 1;
}
.col-span-2 {
    grid-column: span 2 / span 2;
}
.gap-4 {
    gap: 1rem; /* 1rem = 16px */
}
</style>
