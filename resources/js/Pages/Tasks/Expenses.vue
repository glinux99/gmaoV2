<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
// Importations des composants PrimeVue
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
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import MultiSelect from 'primevue/multiselect'; // Nouveau pour les colonnes

const props = defineProps({
    expenses: Object, // Contient l'array de groupes renvoyé par le contrôleur
    filters: Object,
    users: Array,
    expensables: {
        type: Array,
        default: () => [
            { id: 5, type: 'App\\Models\\Activity', title: 'Activité: gffggfggf' },
            // ... autres
        ]
    }
});

const toast = useToast();
const confirm = useConfirm();

const expenseDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const dt = ref();
const selectedExpenses = ref([]); // Nouveau : pour la validation groupée

// --- Colonnes Dynamiques ---
const allColumns = ref([
    { field: 'description', header: 'Description', default: true },
    { field: 'amount', header: 'Montant', default: true },
    { field: 'expense_date', header: 'Date', default: true },
    { field: 'category', header: 'Catégorie', default: true },
    { field: 'status', header: 'Statut', default: true },
    { field: 'user.name', header: 'Créé par', default: true },
    // Colonnes additionnelles, désactivées par défaut ou optionnelles
    { field: 'expensable_title', header: 'Lié à', default: true }, // Titre du groupe
    { field: 'approved_by', header: 'Approuvé par', default: false },
    { field: 'notes', header: 'Notes', default: false },
    // Ajoutez toute autre colonne de détail ici
]);
const selectedColumns = ref(allColumns.value.filter(col => col.default));

// Propriété calculée pour les colonnes visibles
const visibleColumns = computed(() => {
    return selectedColumns.value;
});

// État du formulaire... (inchangé)
const selectedExpensableType = ref(null);
const selectedExpensable = ref(null);

const form = useForm({
    id: null,
    description: '',
    amount: null,
    expense_date: new Date(),
    category: 'other',
    user_id: null,
    expensable_type: null,
    expensable_id: null,
    notes: '',
    receipt_path: null,
    status: 'pending',
});

// Options de listes déroulantes... (inchangées)
const expensableTypes = ref([
    { label: 'Activité', value: 'App\\Models\\Activity' },
    { label: 'Tâche', value: 'App\\Models\\Task' },
]);
const expenseCategories = ref([
    { label: 'Pièces', value: 'parts' },
    { label: 'Main d\'œuvre', value: 'labor' },
    { label: 'Déplacement', value: 'travel' },
    { label: 'Service externe', value: 'external_service' },
    { label: 'Autre', value: 'other' },
]);

// --- NOUVEAU : Flattening des données pour le DataTable unique ---
const flatExpenses = computed(() => {
    const flat = [];
    props.expenses.forEach(group => {
        // Ajouter les propriétés du groupe à chaque détail
        group.details.forEach(detail => {
            flat.push({
                ...detail,
                expensable_title: group.expensable_title, // Ajout du titre du groupe pour l'affichage
                group_category: group.category,
                // ... d'autres propriétés de groupe si nécessaires
            });
        });
    });
    return flat;
});
// -----------------------------------------------------------------

// Fonctions openNew, hideDialog, editExpense... (inchangées ou très peu)

const editExpense = (expense) => {
    form.id = expense.id;
    form.description = expense.description;
    form.amount = parseFloat(expense.amount);
    form.expense_date = expense.expense_date ? new Date(expense.expense_date) : null;
    form.category = expense.category;
    form.user_id = expense.user_id;
    form.expensable_type = expense.expensable_type;
    form.expensable_id = expense.expensable_id;
    form.notes = expense.notes;
    form.status = expense.status;

    selectedExpensableType.value = expense.expensable_type;
    selectedExpensable.value = props.expensables.find(e => e.id === expense.expensable_id && e.type === expense.expensable_type) || null;

    editing.value = true;
    expenseDialog.value = true;
};

const updateExpenseStatus = (expense, newStatus) => {
    const action = newStatus === 'approved' ? 'approuver' : 'rejeter';
    confirm.require({
        message: `Êtes-vous sûr de vouloir ${action} cette dépense ?`,
        header: `Confirmation de ${action}`,
        icon: 'pi pi-info-circle',
        acceptClass: newStatus === 'approved' ? 'p-button-success' : 'p-button-danger',
        accept: () => {
            router.put(route('expenses.updateStatus', expense.id), { status: newStatus }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: `Dépense ${action}e.`, life: 3000 });
                },
                onError: (errors) => {
                    console.error("Erreur lors de la mise à jour du statut:", errors);
                    toast.add({ severity: 'error', summary: 'Erreur', detail: `La mise à jour du statut a échoué.`, life: 3000 });
                }
            });
        }
    });
};

const updateMultipleExpenseStatus = (expenses, newStatus) => {
    const action = newStatus === 'approved' ? 'approuver' : 'rejeter';
    const ids = expenses.map(e => e.id);

    confirm.require({
        message: `Êtes-vous sûr de vouloir ${action} les ${ids.length} dépenses sélectionnées ?`,
        header: `Confirmation de ${action}`,
        icon: 'pi pi-info-circle',
        acceptClass: newStatus === 'approved' ? 'p-button-success' : 'p-button-danger',
        accept: () => {
            router.put(route('expenses.updateGroupStatus'), { ids: ids, status: newStatus }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} dépenses ${newStatus === 'approved' ? 'approuvées' : 'rejetées'}.`, life: 3000 });
                    selectedExpenses.value = []; // Désélectionner
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: `La mise à jour groupée a échoué.`, life: 3000 });
                }
            });
        }
    });
};




// Fonctions saveExpense, deleteExpense, updateExpenseStatus... (inchangées)
// ...

const updateGroupStatus = (newStatus) => {
    if (!selectedExpenses.value || selectedExpenses.value.length === 0) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez sélectionner au moins une dépense.', life: 3000 });
        return;
    }

    const action = newStatus === 'approved' ? 'approuver' : 'rejeter';
    const ids = selectedExpenses.value.map(e => e.id);

    confirm.require({
        message: `Êtes-vous sûr de vouloir ${action} les ${ids.length} dépenses sélectionnées ?`,
        header: `Confirmation de ${action}`,
        icon: 'pi pi-info-circle',
        acceptClass: newStatus === 'approved' ? 'p-button-success' : 'p-button-danger',
        accept: () => {
            router.put(route('expenses.updateGroupStatus'), { ids: ids, status: newStatus }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} dépenses ${newStatus === 'approved' ? 'approuvées' : 'rejetées'}.`, life: 3000 });
                    selectedExpenses.value = []; // Désélectionner
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: `La mise à jour groupée a échoué.`, life: 3000 });
                }
            });
        }
    });
};

// Autres fonctions (getStatusSeverity, getCategoryLabel, filteredExpensables, dialogTitle) inchangées...

const getStatusSeverity = (status) => ({
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    paid: 'info',
}[status] || 'secondary');

const getCategoryLabel = (category) => {
    const cat = expenseCategories.value.find(c => c.value === category);
    return cat ? cat.label : category;
};
// ...

const exportCSV = () => {
    dt.value.exportCSV();
};
</script>
<template>
    <AppLayout title="Gestion des Dépenses">

        <Head title="Dépenses" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <Button label="Nouvelle Dépense" icon="pi pi-plus" class="mr-2"
                                @click="openNew" />

                            <Button
                                label="Approuver Sélection"
                                icon="pi pi-check-circle"
                                class="p-button-success mr-2"
                                :disabled="!selectedExpenses.length"
                                @click="updateGroupStatus('approved')"
                                v-tooltip.top="'Approuver les dépenses sélectionnées'"
                            />
                            <Button
                                label="Rejeter Sélection"
                                icon="pi pi-times-circle"
                                class="p-button-danger"
                                :disabled="!selectedExpenses.length"
                                @click="updateGroupStatus('rejected')"
                                v-tooltip.top="'Rejeter les dépenses sélectionnées'"
                            />
                        </template>
                        <template #end>
                            <div class="flex items-center gap-2">
                                <MultiSelect
                                    v-model="selectedColumns"
                                    :options="allColumns"
                                    optionLabel="header"
                                    placeholder="Afficher les colonnes"
                                    display="chip"
                                />

                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                                </IconField>
                                <Button
                                    label="Exporter"
                                    icon="pi pi-download"
                                    class="p-button-help"
                                    @click="exportCSV($event)"
                                />
                            </div>
                        </template>
                    </Toolbar>

                    <DataTable :value="flatExpenses" ref="dt" dataKey="id" :paginator="true" :rows="10"
                        responsiveLayout="scroll" :globalFilterFields="['description', 'user.name', 'expensable_title', 'category']"
                        v-model:selection="selectedExpenses">

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        <template #footer>
                            <div class="flex justify-end font-semibold">
                                Total des dépenses sélectionnées: {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(selectedExpenses.reduce((sum, expense) => sum + parseFloat(expense.amount), 0)) }}
                            </div>
                        </template>

                        <template v-for="col in visibleColumns" :key="col.field">
                            <Column :field="col.field" :header="col.header" :sortable="true" style="min-width: 10rem;">
                                <template #body="slotProps">
                                    <span v-if="col.field === 'amount'">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(slotProps.data.amount) }}
                                    </span>
                                    <span v-else-if="col.field === 'expense_date'">
                                        {{ new Date(slotProps.data.expense_date).toLocaleDateString('fr-FR') }}
                                    </span>
                                    <span v-else-if="col.field === 'category'">
                                        <Tag :value="getCategoryLabel(slotProps.data.category)" />
                                    </span>
                                    <span v-else-if="col.field === 'user.name'">
                                        {{ slotProps.data.user ? slotProps.data.user.name : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'status'">
                                        <Tag :value="slotProps.data.status"
                                            :severity="getStatusSeverity(slotProps.data.status)" />
                                    </span>
                                    <span v-else-if="col.field === 'approved_by'">
                                        {{ slotProps.data.approved_by ? slotProps.data.approved_by.name : 'N/A' }}
                                    </span>
                                    <span v-else>
                                        {{ slotProps.data[col.field] }}
                                    </span>
                                </template>
                            </Column>
                        </template>

                        <Column headerStyle="min-width:12rem;" header="Actions">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-info"
                                        @click="editExpense(slotProps.data)" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-danger"
                                        @click="deleteExpense(slotProps.data)" />

                                    <template v-if="slotProps.data.status === 'pending'">
                                        <Button icon="pi pi-check" class="p-button-rounded p-button-success"
                                            @click="updateExpenseStatus(slotProps.data, 'approved')" v-tooltip.top="'Approuver (Individuel)'" />
                                        <Button icon="pi pi-times" class="p-button-rounded p-button-danger"
                                            @click="updateExpenseStatus(slotProps.data, 'rejected')" v-tooltip.top="'Rejeter (Individuel)'" />
                                    </template>
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="expenseDialog" modal :header="dialogTitle" :style="{ width: '45rem' }">
                        </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
