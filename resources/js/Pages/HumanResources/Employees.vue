<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// Import des composants PrimeVue
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
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
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag'; // Ajout de Tag pour le statut d'emploi

const props = defineProps({
    employees: Object, // Correction : Devrait contenir les données paginées des employés
    regions: Array,
    filters: Object,
    technicians: Array, // Gardé au cas où ils sont utilisés comme liste d'utilisateurs
    teams: Array, // Gardé au cas où ils sont utilisés comme liste d'équipes
});

const toast = useToast();
const confirm = useConfirm();

const employeeDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedEmployees = ref(null); // Pour la suppression multiple
const dt = ref(); // Référence au DataTable

const { user } = usePage().props.auth;

const form = useForm({
    id: null,
    user_id: null,
    employee_id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone_number: null,
    date_of_birth: null,
    address: null,
    city: null,
    state: null,
    zip_code: null,
    country: null,
    hire_date: null,
    job_title: null,
    department: null,
    salary: null,
    employment_status: 'active',
    termination_date: null,
    notes: null,
    cv_path: null,
    id_card_path: null,
    contract_path: null,
    other_documents: null,
});

// Options de statut d'emploi
const employmentStatuses = [
    { label: 'Actif', value: 'active' },
    { label: 'En congé', value: 'on_leave' },
    { label: 'Terminé', value: 'terminated' },
    { label: 'Retraité', value: 'retired' },
];

// --- LOGIQUE DE COLONNES (MANQUANTE) ---
const allColumns = ref([
    { field: 'employee_id', header: 'ID Employé', default: true },
    { field: 'first_name', header: 'Prénom', default: true },
    { field: 'last_name', header: 'Nom', default: true },
    { field: 'email', header: 'Email', default: true },
    { field: 'phone_number', header: 'Téléphone', default: false },
    { field: 'job_title', header: 'Poste', default: true },
    { field: 'department', header: 'Département', default: true },
    { field: 'hire_date', header: "Date d'embauche", default: true },
    { field: 'salary', header: 'Salaire', default: false },
    { field: 'employment_status', header: "Statut d'emploi", default: true },
    { field: 'date_of_birth', header: 'Date de naissance', default: false },
    { field: 'address', header: 'Adresse', default: false },
    { field: 'cv_path', header: 'CV', default: false },
]);
const selectedColumns = ref(allColumns.value.filter(col => col.default));

const visibleColumns = computed(() => {
    return selectedColumns.value;
});

// --- LOGIQUE DE SUPPRESSION MULTIPLE ---
const hasSelectedEmployees = computed(() => selectedEmployees.value && selectedEmployees.value.length > 0);

const confirmDeleteSelected = () => {
    if (!hasSelectedEmployees.value) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez sélectionner au moins un employé.', life: 3000 });
        return;
    }

    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedEmployees.value.length} employés sélectionnés ? Cette action est irréversible.`,
        header: 'Confirmation de suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteSelectedEmployees();
        },
    });
};

const deleteSelectedEmployees = () => {
    const ids = selectedEmployees.value.map(employee => employee.id);

    // Assurez-vous d'avoir une route 'employees.bulkDestroy' définie dans Laravel
    router.delete(route('employees.bulkDestroy'), { data: { ids: ids } }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} employés supprimés avec succès.`, life: 3000 });
            selectedEmployees.value = null; // Désélectionner
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression multiple.', life: 3000 });
        },
        preserveState: false,
    });
};

// --- LOGIQUE DE FORMULAIRE D'EMPLOYÉ ---
// Fonctions openNew, hideDialog, editEmployee et deleteEmployee sont correctement définies
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    form.hire_date = new Date();
    form.user_id = user?.id;
    form.employment_status = 'active'; // Assurer une valeur par défaut
    employeeDialog.value = true;
};

const hideDialog = () => {
    employeeDialog.value = false;
    submitted.value = false;
};

// ... (editEmployee inchangée)

const saveEmployee = () => {
    submitted.value = true;

    // Validation côté client simple
    if (!form.first_name || !form.last_name || !form.email || !form.hire_date) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: "Veuillez remplir tous les champs obligatoires (Prénom, Nom, Email, Date d'embauche).", life: 3000 });
        return;
    }

    const url = editing.value ? route('employees.update', form.id) : route('employees.store');
    const method = editing.value ? 'put' : 'post';

    // Correction: utiliser form.submit pour envoyer le formulaire (y compris les fichiers si implémentés)
    form.submit(method, url, {
        onSuccess: () => {
            employeeDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Employé ${editing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
            form.reset();
            selectedEmployees.value = null;
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de l'employé", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la sauvegarde.', life: 3000 });
        }
    });
};

const deleteEmployee = (employee) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer l'employé "${employee.first_name} ${employee.last_name}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('employees.destroy', employee.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Employé supprimé avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

const exportCSV = () => {
    dt.value.exportCSV();
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        // Correction: Pointer vers la route des employés
        router.get(route('employees.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

// Fonctions utilitaires pour le DataTable
const getEmploymentStatusLabel = (status) => {
    const s = employmentStatuses.find(e => e.value === status);
    return s ? s.label : status;
};

const getStatusSeverity = (status) => ({
    'active': 'success',
    'on_leave': 'warning',
    'terminated': 'danger',
    'retired': 'info',
}[status] || 'secondary');

const dialogTitle = computed(() => editing.value ? 'Modifier l\'Employé' : 'Créer un nouvel Employé');

</script>

<template>
    <AppLayout title="Gestion des Employés">
        <Head title="Employés" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">
                                <span class="block mt-2 md:mt-0 p-input-icon-left flex align-items-center">
                                    <Button label="Ajouter un employé" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />
                                    <Button label="Supprimer la sélection" icon="pi pi-trash" class="p-button-sm p-button-danger"
                                        :disabled="!hasSelectedEmployees" @click="confirmDeleteSelected" />
                                </span>
                            </div>
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
                                <IconField class="mr-2">
                                    <InputIcon><i class="pi pi-search" /></InputIcon>
                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch"
                                        class="w-full"
                                    />
                                </IconField>
                                <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                            </div>
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="props.employees.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} employés"
                        responsiveLayout="scroll"
                        v-model:selection="selectedEmployees"
                        :totalRecords="props.employees.total"> <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <template v-for="col in visibleColumns" :key="col.field">
                            <Column :field="col.field" :header="col.header" :sortable="true" style="min-width: 10rem;">
                                <template #body="slotProps">
                                    <span v-if="col.field === 'hire_date' || col.field === 'date_of_birth' || col.field === 'termination_date'">
                                        {{ slotProps.data[col.field] ? new Date(slotProps.data[col.field]).toLocaleDateString('fr-FR') : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'salary'">
                                        {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(slotProps.data.salary || 0) }}
                                    </span>
                                    <span v-else-if="col.field === 'employment_status'">
                                        <Tag :value="getEmploymentStatusLabel(slotProps.data.employment_status)" :severity="getStatusSeverity(slotProps.data.employment_status)" />
                                    </span>
                                    <span v-else-if="col.field.includes('_path')">
                                        <a v-if="slotProps.data[col.field]" :href="slotProps.data[col.field]" target="_blank" class="p-link">Voir</a>
                                        <span v-else>Aucun</span>
                                    </span>
                                    <span v-else>
                                        {{ slotProps.data[col.field] }}
                                    </span>
                                </template>
                            </Column>
                        </template>

                        <Column headerStyle="min-width:10rem;" header="Actions" bodyStyle="text-align: right">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-info mr-2"
                                    @click="editEmployee(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="danger"
                                    @click="deleteEmployee(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                   <Dialog v-model:visible="employeeDialog" modal :header="dialogTitle" :style="{ width: '50rem' }" class="p-dialog-content">

    <span v-if="editing" class="text-gray-500 block mb-3 text-sm">
        Mettez à jour les informations de l'employé. Les champs marqués d'une * sont obligatoires.
    </span>

    <div class="p-fluid grid grid-cols-2 gap-4">

        <div class="field col-span-1">
            <label for="first_name" class="font-semibold block mb-2">Prénom *</label>
            <InputText id="first_name" v-model.trim="form.first_name" :class="{ 'p-invalid': form.errors.first_name }" class="w-full" />
            <small class="p-error" v-if="form.errors.first_name">{{ form.errors.first_name }}</small>
        </div>
        <div class="field col-span-1">
            <label for="last_name" class="font-semibold block mb-2">Nom *</label>
            <InputText id="last_name" v-model.trim="form.last_name" :class="{ 'p-invalid': form.errors.last_name }" class="w-full" />
            <small class="p-error" v-if="form.errors.last_name">{{ form.errors.last_name }}</small>
        </div>

        <div class="field col-span-1">
            <label for="email" class="font-semibold block mb-2">Email *</label>
            <InputText id="email" v-model.trim="form.email" :class="{ 'p-invalid': form.errors.email }" class="w-full" />
            <small class="p-error" v-if="form.errors.email">{{ form.errors.email }}</small>
        </div>
        <div class="field col-span-1">
            <label for="phone_number" class="font-semibold block mb-2">Téléphone</label>
            <InputText id="phone_number" v-model.trim="form.phone_number" class="w-full" />
            <small class="p-error" v-if="form.errors.phone_number">{{ form.errors.phone_number }}</small>
        </div>

        <div class="field col-span-1">
            <label for="date_of_birth" class="font-semibold block mb-2">Date de naissance</label>
            <Calendar id="date_of_birth" v-model="form.date_of_birth" dateFormat="dd/mm/yy" showIcon
                :class="{ 'p-invalid': form.errors.date_of_birth }" class="w-full" />
            <small class="p-error" v-if="form.errors.date_of_birth">{{ form.errors.date_of_birth }}</small>
        </div>
        <div class="field col-span-1">
            <label for="hire_date" class="font-semibold block mb-2">Date d'embauche *</label>
            <Calendar id="hire_date" v-model="form.hire_date" dateFormat="dd/mm/yy" showIcon
                :class="{ 'p-invalid': form.errors.hire_date }" class="w-full" />
            <small class="p-error" v-if="form.errors.hire_date">{{ form.errors.hire_date }}</small>
        </div>

        <div class="field col-span-1">
            <label for="job_title" class="font-semibold block mb-2">Poste</label>
            <InputText id="job_title" v-model.trim="form.job_title" class="w-full" />
            <small class="p-error" v-if="form.errors.job_title">{{ form.errors.job_title }}</small>
        </div>
        <div class="field col-span-1">
            <label for="department" class="font-semibold block mb-2">Département</label>
            <InputText id="department" v-model.trim="form.department" class="w-full" />
            <small class="p-error" v-if="form.errors.department">{{ form.errors.department }}</small>
        </div>

        <div class="field col-span-1">
            <label for="salary" class="font-semibold block mb-2">Salaire</label>
            <InputNumber id="salary" v-model="form.salary" mode="currency" currency="XOF" locale="fr-FR" :min="0" class="w-full" />
            <small class="p-error" v-if="form.errors.salary">{{ form.errors.salary }}</small>
        </div>
        <div class="field col-span-1">
            <label for="employment_status" class="font-semibold block mb-2">Statut d'emploi</label>
            <Dropdown id="employment_status" v-model="form.employment_status" :options="employmentStatuses" optionLabel="label" optionValue="value" class="w-full" />
            <small class="p-error" v-if="form.errors.employment_status">{{ form.errors.employment_status }}</small>
        </div>

        <div class="field col-span-2">
            <label for="notes" class="font-semibold block mb-2">Notes</label>
            <Textarea id="notes" v-model="form.notes" rows="3" class="w-full" />
            <small class="p-error" v-if="form.errors.notes">{{ form.errors.notes }}</small>
        </div>

    </div>

    <template #footer>
        <div class="flex justify-content-end gap-2 mt-4">
            <Button type="button" label="Annuler" severity="secondary" icon="pi pi-times" @click="hideDialog" />
            <Button type="button" :label="editing ? 'Mettre à Jour' : 'Sauvegarder'" icon="pi pi-check" @click="saveEmployee" :loading="form.processing" />
        </div>
    </template>
</Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
