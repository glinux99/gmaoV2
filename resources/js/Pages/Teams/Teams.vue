<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
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
import Calendar from 'primevue/calendar'; // Nécessaire pour la date
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const props = defineProps({
    regions: Array,
    filters: Object,
    technicians: Array,
    teams: Array,
});

const toast = useToast();
const confirm = useConfirm();

const labelDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedTeams = ref(null); // NOUVEAU: Pour la suppression multiple

const { user } = usePage().props.auth;

const form = useForm({
    id: null,
    name: '',
    team_leader_id: null,
    creation_date: null,
    members: [], // Tableau d'IDs des techniciens
});

// --- LOGIQUE DE SUPPRESSION MULTIPLE (NOUVEAU) ---

const hasSelectedTeams = computed(() => selectedTeams.value && selectedTeams.value.length > 0);

const confirmDeleteSelected = () => {
    if (!hasSelectedTeams.value) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez sélectionner au moins une équipe.', life: 3000 });
        return;
    }

    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedTeams.value.length} équipes sélectionnées ?`,
        header: 'Confirmation de suppression multiple',
        icon: 'pi pi-exclamation-triangle',
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
    const ids = selectedTeams.value.map(team => team.id);

    // Assurez-vous d'avoir une route 'teams.bulkDestroy' définie en POST (avec _method DELETE si nécessaire)
    router.post(route('teams.bulkDestroy'), { ids: ids }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} équipes supprimées avec succès.`, life: 3000 });
            selectedTeams.value = null; // Désélectionner
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression multiple.', life: 3000 });
        },
        preserveState: false,
    });
};


// --- LOGIQUE DE FORMULAIRE ---

const updateTeamMembers = (event) => {
    // Si le chef d'équipe est sélectionné, l'ajouter aux membres (si non présent)
    const leaderId = event?.value || form.team_leader_id;
    if (leaderId && !form.members.includes(leaderId)) {
        form.members = [...form.members, leaderId];
    }
};

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    // Pré-remplir la date de création avec aujourd'hui
    form.creation_date = new Date();
    labelDialog.value = true;
};

const hideDialog = () => {
    labelDialog.value = false;
    submitted.value = false;
};

const editTeam = (team) => {
    form.id = team.id;
    form.name = team.name;
    form.team_leader_id = team.team_leader_id;
    form.creation_date = team.creation_date ? new Date(team.creation_date) : null;
    form.members = team.members.map(member => member.id);
    editing.value = true;
    labelDialog.value = true;
};

const saveTeam = () => {
    submitted.value = true;

    // Logique pour générer le nom si vide (utilise la computed property)
    if (!form.name.trim()) {
        form.name = teamNameComputed.value;
    }

    // Assurez-vous que le chef d'équipe est dans la liste des membres avant de soumettre
    if (form.team_leader_id && !form.members.includes(form.team_leader_id)) {
        form.members.push(form.team_leader_id);
    }

    // Si team_leader_id n'est toujours pas défini (même si optionnel), utilisez l'utilisateur actuel
    if (!form.team_leader_id && user?.id) {
        form.team_leader_id = user.id;
    }

    // Validation minimale côté client
    if (!form.name.trim() || !form.team_leader_id) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: "Le nom de l'équipe et le chef d'équipe sont requis.", life: 3000 });
        return;
    }


    const url = editing.value ? route('teams.update', form.id) : route('teams.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Équipe ${editing.value ? 'mise à jour' : 'créée'} avec succès`, life: 3000 });
            form.reset();
            selectedTeams.value = null; // Réinitialiser la sélection
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de l'équipe", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deleteTeam = (team) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer l'équipe "${team.name}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('teams.destroy', team.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipe supprimée avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('teams.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};


const teamNameComputed = computed(() => {
    if (form.team_leader_id) {
        const teamLeader = props.technicians.find(tech => tech.id === form.team_leader_id);
        if (teamLeader) {
            const dateToUse = form.creation_date ? new Date(form.creation_date) : new Date();
            const today = dateToUse;

            const formattedDate = today.getFullYear().toString() +
                                (today.getMonth() + 1).toString().padStart(2, '0') +
                                today.getDate().toString().padStart(2, '0');
            return `Équipe-${teamLeader.name.substring(0, 3).toUpperCase()}-${formattedDate}`;
        }
    }
    return '';
});

const dialogTitle = computed(() => editing.value ? 'Modifier l\'Équipe' : 'Créer une nouvelle Équipe');

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
                                <span class="block mt-2 md:mt-0 p-input-icon-left flex align-items-center">
                                    <Button label="Ajouter une equipe" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />
                                    <Button label="Supprimer la sélection" icon="pi pi-trash" class="p-button-sm p-button-danger mr-2"
                                        :disabled="!hasSelectedTeams" @click="confirmDeleteSelected" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <IconField class="mr-2">
                                <InputIcon><i class="pi pi-search" /></InputIcon>
                                <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                            </IconField>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="teams" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} équipes"
                        responsiveLayout="scroll"
                        :selection="selectedTeams" @update:selection="selectedTeams = $event">

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <Column field="name" header="Nom de l'équipe" :sortable="true" headerStyle="width:25%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.name }}
                            </template>
                        </Column>
                        <Column field="team_leader.name" header="Chef d'équipe" :sortable="true" headerStyle="width:20%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.team_leader ? slotProps.data.team_leader.name : 'N/A' }}
                            </template>
                        </Column>
                        <Column field="members" header="Membres" headerStyle="width:35%; min-width:15rem;">
                            <template #body="slotProps">
                                <span v-if="slotProps.data.members && slotProps.data.members.length > 0">
                                    <span v-for="(member, index) in slotProps.data.members" :key="member.id">
                                        {{ member.name }}{{ index < slotProps.data.members.length - 1 ? ', ' : '' }}
                                    </span>
                                </span>
                                <span v-else>Aucun membre</span>
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions" bodyStyle="text-align: right">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editTeam(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteTeam(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-4">Mettez à jour les informations de l'équipe.</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="team_leader" class="font-semibold w-24">Chef d'équipe *</label>
                            <Dropdown id="team_leader" v-model="form.team_leader_id" :options="technicians" optionLabel="name" optionValue="id" placeholder="Sélectionner un chef d'équipe" class="flex-auto"
                                :class="{ 'p-invalid': submitted && !form.team_leader_id }" @change="updateTeamMembers" />
                        </div>
                        <small class="p-error block mb-4" v-if="form.errors.team_leader_id">{{ form.errors.team_leader_id }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="name" class="font-semibold w-24">Nom de l'équipe</label>
                            <InputText id="name" v-model.trim="form.name" autofocus :placeholder="teamNameComputed"
                                :class="{ 'p-invalid': submitted && !form.name.trim() && !teamNameComputed }" class="flex-auto" autocomplete="off" />
                        </div>
                         <small class="p-error block mb-4" v-if="form.errors.name">{{ form.errors.name }}</small>
                         <small class="p-info block mb-4 text-sm" v-else-if="!form.name && teamNameComputed">Le nom sera généré automatiquement : **{{ teamNameComputed }}**</small>


                        <div class="flex items-center gap-4 mb-4">
                            <label for="creation_date" class="font-semibold w-24">Date de création</label>
                            <Calendar id="creation_date" v-model="form.creation_date" dateFormat="dd/mm/yy" showIcon class="flex-auto" />
                        </div>
                        <small class="p-error block mb-4" v-if="form.errors.creation_date">{{ form.errors.creation_date }}</small>

                        <div class="flex items-center gap-4 mb-4">
    <label for="members" class="font-semibold w-24">Membres (Techniciens)</label>

    <MultiSelect id="members" v-model="form.members" :options="technicians" optionLabel="name" optionValue="id"
                 placeholder="Sélectionner les membres" :filter="true" class="w-full" :style="{ width: '30rem' }" display="chip" />

</div>
<small class="p-error block mb-4" v-if="form.errors.members">{{ form.errors.members }}</small>
                        <div class="flex justify-end gap-2 pt-4">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveTeam" :loading="form.processing"></Button>
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
