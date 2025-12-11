<script setup>
import { ref, computed } from 'vue';
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
import Tag from 'primevue/tag';
import FileUpload from 'primevue/fileupload';
import Textarea from 'primevue/textarea'; // Ajout de Textarea pour la raison/notes

const props = defineProps({
    leaves: Object, // Les congés paginés (Inertia paginated response)
    filters: Object,
    users: Array, // Liste de tous les utilisateurs (pour user_id et approved_by)
});

const toast = useToast();
const confirm = useConfirm();

const leaveDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedLeaves = ref(null);
const dt = ref();
const fileUploadRef = ref(null);

const { user } = usePage().props.auth;

const form = useForm({
    id: null,
    user_id: null,
    type: 'annuel',
    start_date: null,
    end_date: null,
    reason: null,
    status: 'pending',
    approved_by: null,
    approval_date: null,
    // Note : Pour l'upload, on stocke l'objet File ici
    document_file: null, // Renommé pour plus de clarté dans le formulaire d'upload
    document_path: null, // Pour stocker le chemin si on est en édition
    notes: null,
});

// --- LOGIQUE DE SUPPRESSION MULTIPLE (Inchagnée) ---

const hasSelectedLeaves = computed(() => selectedLeaves.value && selectedLeaves.value.length > 0);

const confirmDeleteSelected = () => {
    if (!hasSelectedLeaves.value) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez sélectionner au moins un congé.', life: 3000 });
        return;
    }

    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedLeaves.value.length} congés sélectionnés ?`,
        header: 'Confirmation de suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteSelectedLeaves();
        },
    });
};

const deleteSelectedLeaves = () => {
    const ids = selectedLeaves.value.map(leave => leave.id);

    // Utilisation de la méthode DELETE pour la suppression en vrac (méthode Laravel/Inertia recommandée)
    router.delete(route('leaves.bulkDestroy'), {
        data: { ids: ids }, // Important : passer les IDs dans data pour une requête DELETE Inertia
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} congés supprimés avec succès.`, life: 3000 });
            selectedLeaves.value = null;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression multiple.', life: 3000 });
        },
        preserveState: false,
    });
};

// --- LOGIQUE DE FORMULAIRE DE CONGÉ ---

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    form.user_id = user?.id;
    form.start_date = new Date();
    form.end_date = new Date();
    form.status = 'pending';
    form.document_file = null; // Réinitialiser le fichier d'upload
    form.document_path = null;
    if (fileUploadRef.value) {
        fileUploadRef.value.clear();
    }
    leaveDialog.value = true;
};

const hideLeaveDialog = () => {
    leaveDialog.value = false;
    submitted.value = false;
};

const editLeave = (leave) => {
    form.reset(); // Réinitialiser pour éviter les restes

    // Remplissage du formulaire à partir des données de congé
    form.id = leave.id;
    form.user_id = leave.user_id;
    form.type = leave.type;
    form.start_date = leave.start_date ? new Date(leave.start_date) : null;
    form.end_date = leave.end_date ? new Date(leave.end_date) : null;
    form.reason = leave.reason;
    form.status = leave.status;
    form.approved_by = leave.approved_by;
    form.approval_date = leave.approval_date ? new Date(leave.approval_date) : null;

    // Conserver le chemin existant, mais réinitialiser le fichier d'upload
    form.document_path = leave.document_path;
    form.document_file = null;
    form.notes = leave.notes;

    // Assurez-vous de vider le composant FileUpload si nécessaire
    if (fileUploadRef.value) {
        fileUploadRef.value.clear();
    }

    editing.value = true;
    leaveDialog.value = true;
};

const saveLeave = () => {
    submitted.value = true;

    if (!form.user_id || !form.type || !form.start_date || !form.end_date || !form.reason) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: "Veuillez remplir tous les champs obligatoires (Demandeur, Type, Dates, Raison).", life: 3000 });
        return;
    }

    const url = editing.value ? route('leaves.update', form.id) : route('leaves.store');

    // IMPORTANT : Utiliser post même pour une mise à jour (PUT) si on gère des fichiers
    // Inertia utilise la méthode `post` avec `_method: 'put'` en coulisse
    // et `forceFormData: true` pour envoyer les fichiers correctement.
    form.post(url, {
        forceFormData: true, // S'assure que FormData est utilisé pour l'upload
        onSuccess: () => {
            leaveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Congé ${editing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
            form.reset();
            selectedLeaves.value = null;
            if (fileUploadRef.value) {
                fileUploadRef.value.clear();
            }
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du congé", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs du formulaire.', life: 3000 });
        },
        // Surcharge de la méthode si on est en édition (car on utilise form.post)
        ...(editing.value && { _method: 'put' })
    });
};

const deleteLeave = (leave) => {
    // La logique de suppression est correcte
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer le congé de "${leave.user?.name || 'N/A'}" du ${new Date(leave.start_date).toLocaleDateString()} au ${new Date(leave.end_date).toLocaleDateString()} ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('leaves.destroy', leave.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Congé supprimé avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

// Modification : on ne fait qu'affecter le fichier à la variable du formulaire
const onFileUpload = (event) => {
    // Si l'upload est en mode "basic", event.files contient l'objet File
    form.document_file = event.files[0];
    toast.add({ severity: 'info', summary: 'Fichier sélectionné', detail: `Prêt à être envoyé : ${event.files[0].name}`, life: 3000 });
};

// ... (exportCSV inchangé)

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('leaves.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

// ... (leaveTypes, leaveStatuses, getStatusSeverity inchangés)

const dialogTitle = computed(() => editing.value ? 'Modifier le Congé' : 'Demander un nouveau Congé');

// Colonnes dynamiques pour le DataTable
const allColumns = ref([
    { field: 'user.name', header: 'Demandeur', default: true },
    { field: 'type', header: 'Type de Congé', default: true },
    { field: 'start_date', header: 'Date de Début', default: true },
    { field: 'end_date', header: 'Date de Fin', default: true },
    { field: 'reason', header: 'Raison', default: true },
    { field: 'status', header: 'Statut', default: true },
    { field: 'approved_by_user.name', header: 'Approuvé par', default: false },
    { field: 'approval_date', header: "Date d'Approbation", default: false },
    { field: 'notes', header: 'Notes', default: false },
    { field: 'document_path', header: 'Document', default: false },
]);
const selectedColumns = ref(allColumns.value.filter(col => col.default));
const visibleColumns = computed(() => selectedColumns.value);

const getLeaveTypeLabel = (value) => {
    const type = leaveTypes.value.find(t => t.value === value);
    return type ? type.label : value;
};

const getLeaveStatusLabel = (value) => {
    const status = leaveStatuses.value.find(s => s.value === value);
    return status ? status.label : value;
};

</script>

<template>
    <AppLayout title="Gestion des Congés">
        <Head title="Congés" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <Button label="Demander un congé" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />
                            <Button label="Supprimer la sélection" icon="pi pi-trash" class="p-button-sm p-button-danger mr-2"
                                :disabled="!hasSelectedLeaves" @click="confirmDeleteSelected" />
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

                    <DataTable ref="dt" :value="leaves.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]" :totalRecords="leaves.total"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} congés"
                        responsiveLayout="scroll"
                        v-model:selection="selectedLeaves">

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <template v-for="col in visibleColumns" :key="col.field">
                            <Column :field="col.field" :header="col.header" :sortable="true" style="min-width: 10rem;">
                                <template #body="slotProps">
                                    <span v-if="col.field === 'user.name'">
                                        {{ slotProps.data.user ? slotProps.data.user.name : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'type'">
                                        {{ getLeaveTypeLabel(slotProps.data.type) }}
                                    </span>
                                    <span v-else-if="col.field === 'start_date' || col.field === 'end_date' || col.field === 'approval_date'">
                                        {{ slotProps.data[col.field] ? new Date(slotProps.data[col.field]).toLocaleDateString('fr-FR') : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'status'">
                                        <Tag :value="getLeaveStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" />
                                    </span>
                                    <span v-else-if="col.field === 'approved_by_user.name'">
                                        {{ slotProps.data.approved_by_user ? slotProps.data.approved_by_user.name : 'N/A' }}
                                    </span>
                                    <span v-else-if="col.field === 'document_path'">
                                        <a v-if="slotProps.data.document_path" :href="slotProps.data.document_path" target="_blank" class="p-link">Voir</a>
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
                                    @click="editLeave(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="danger"
                                    @click="deleteLeave(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                   <Dialog v-model:visible="leaveDialog" modal :header="dialogTitle" :style="{ width: '50rem' }" class="p-dialog-content">

    <span v-if="editing" class="text-gray-500 block mb-3 text-sm">
        Mettez à jour les informations du congé. Les champs marqués d'une * sont obligatoires.
    </span>

    <div class="p-fluid grid grid-cols-2 gap-4">

        <div class="field col-span-1">
            <label for="user_id" class="font-semibold block mb-2">Demandeur *</label>
            <Dropdown id="user_id" v-model="form.user_id" :options="users" optionLabel="name" optionValue="id"
                placeholder="Sélectionner un utilisateur" :class="{ 'p-invalid': form.errors.user_id }" class="w-full" filter showClear />
            <small class="p-error" v-if="form.errors.user_id">{{ form.errors.user_id }}</small>
        </div>

        <div class="field col-span-1">
            <label for="type" class="font-semibold block mb-2">Type de Congé *</label>
            <Dropdown id="type" v-model="form.type" :options="leaveTypes" optionLabel="label" optionValue="value"
                placeholder="Sélectionner un type" :class="{ 'p-invalid': form.errors.type }" class="w-full" />
            <small class="p-error" v-if="form.errors.type">{{ form.errors.type }}</small>
        </div>

        <div class="field col-span-1">
            <label for="start_date" class="font-semibold block mb-2">Date de Début *</label>
            <Calendar id="start_date" v-model="form.start_date" dateFormat="dd/mm/yy" showIcon
                :class="{ 'p-invalid': form.errors.start_date }" class="w-full" />
            <small class="p-error" v-if="form.errors.start_date">{{ form.errors.start_date }}</small>
        </div>

        <div class="field col-span-1">
            <label for="end_date" class="font-semibold block mb-2">Date de Fin *</label>
            <Calendar id="end_date" v-model="form.end_date" dateFormat="dd/mm/yy" showIcon
                :class="{ 'p-invalid': form.errors.end_date }" class="w-full" />
            <small class="p-error" v-if="form.errors.end_date">{{ form.errors.end_date }}</small>
        </div>

        <div class="field col-span-2">
            <label for="reason" class="font-semibold block mb-2">Raison *</label>
            <Textarea id="reason" v-model.trim="form.reason" rows="3"
                :class="{ 'p-invalid': form.errors.reason }" class="w-full" />
            <small class="p-error" v-if="form.errors.reason">{{ form.errors.reason }}</small>
        </div>

        <div class="field col-span-2">
            <label for="document_file" class="font-semibold block mb-2">Document Justificatif</label>
            <FileUpload ref="fileUploadRef" mode="basic" name="document_file" accept="image/*,.pdf" :maxFileSize="1000000"
                @select="onFileUpload" :auto="false" :customUpload="true" chooseLabel="Choisir un fichier"
                :disabled="form.processing" class="w-full" />

            <span v-if="form.document_path && !form.document_file" class="text-sm mt-2 block text-gray-500 dark:text-gray-400">
                Document actuel : <a :href="form.document_path" target="_blank" class="text-primary-500 hover:text-primary-600">Voir le fichier</a>
            </span>
            <small class="p-error" v-if="form.errors.document_file">{{ form.errors.document_file }}</small>
        </div>

        <template v-if="editing">
            <div class="field col-span-1">
                <label for="status" class="font-semibold block mb-2">Statut</label>
                <Dropdown id="status" v-model="form.status" :options="leaveStatuses" optionLabel="label" optionValue="value"
                    placeholder="Sélectionner un statut" :class="{ 'p-invalid': form.errors.status }" class="w-full" />
                <small class="p-error" v-if="form.errors.status">{{ form.errors.status }}</small>
            </div>

            <div class="field col-span-1" v-if="form.status && form.status !== 'pending'">
                <label for="approved_by" class="font-semibold block mb-2">Approuvé par</label>
                <Dropdown id="approved_by" v-model="form.approved_by" :options="users" optionLabel="name" optionValue="id"
                    placeholder="Sélectionner un approbateur" :class="{ 'p-invalid': form.errors.approved_by }" class="w-full" filter showClear />
                <small class="p-error" v-if="form.errors.approved_by">{{ form.errors.approved_by }}</small>
            </div>
        </template>

        <div class="field col-span-1" v-if="editing && form.status && form.status !== 'pending'">
            <label for="approval_date" class="font-semibold block mb-2">Date d'Approbation</label>
            <Calendar id="approval_date" v-model="form.approval_date" dateFormat="dd/mm/yy" showIcon class="w-full" />
            <small class="p-error" v-if="form.errors.approval_date">{{ form.errors.approval_date }}</small>
        </div>

        <div class="field col-span-2">
            <label for="notes" class="font-semibold block mb-2">Notes Internes</label>
            <InputText id="notes" v-model.trim="form.notes" class="w-full" />
            <small class="p-error" v-if="form.errors.notes">{{ form.errors.notes }}</small>
        </div>

    </div>
    <template #footer>
        <div class="flex justify-content-end gap-2 mt-4">
            <Button type="button" label="Annuler" severity="secondary" icon="pi pi-times" @click="hideLeaveDialog" />
            <Button type="button" :label="editing ? 'Mettre à Jour' : 'Sauvegarder'" icon="pi pi-check" @click="saveLeave" :loading="form.processing" />
        </div>
    </template>
</Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
