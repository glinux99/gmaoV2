<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    regions: Array,
    filters: Object,
    technicians: Array,
    teams: Array, // Changed from regions to teams
});

const toast = useToast();
const confirm = useConfirm();

const labelDialog = ref(false);
const submitted = ref(false); // Used for form validation
const editing = ref(false); // To determine if we are editing or creating
const search = ref(props.filters?.search || '');

const { user } = usePage().props.auth; // Get authenticated user

const form = useForm({
    id: null,
    name: '', // Nom de l'équipe
    team_leader_id: null, // ID du chef d'équipe
    creation_date: null, // Date de création de l'équipe
    members: [], // Membres de l'équipe (techniciens)
});

const updateTeamMembers = () => {
    // Ensure the team leader is always part of the members array
    if (form.team_leader_id && !form.members.includes(form.team_leader_id)) {
        form.members.push(form.team_leader_id);
    }
};

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
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
    form.creation_date = team.creation_date;
    // Automatically set team name if not already set and a team leader is selected
    if (!form.name && form.team_leader_id) {
        const teamLeader = props.technicians.find(tech => tech.id === form.team_leader_id);
        if (teamLeader) {
            form.name = `Équipe de ${teamLeader.name}`;
        }
    }
    form.members = team.members.map(member => member.id); // Assuming members are stored as an array of user IDs
    editing.value = true;
    labelDialog.value = true;
};

const saveTeam = () => {
    submitted.value = true;

    if (!form.name.trim()) {
        if (form.team_leader_id) {
            const teamLeader = props.technicians.find(tech => tech.id === form.team_leader_id);
            if (teamLeader) {
                form.name = teamNameComputed.value;
            }
        }
    }
    const url = editing.value ? route('teams.update', form.id) : route('teams.store');
    const method = editing.value ? 'put' : 'post';

    // Set the current user as the team leader if not already set
    if (!form.team_leader_id) {
        form.team_leader_id = user.id;
    }
    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Région ${editing.value ? 'mise à jour' : 'créée'} avec succès`, life: 3000 });
            form.reset();
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
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Région supprimée avec succès', life: 3000 });
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

// Assuming you have a way to get a list of all users for team leader and members
// This would typically come from a prop or an API call

// Fetch users on mount (example)
onMounted(() => {
    // Example: router.get(route('users.index'), { only: ['users'] }, { onSuccess: (page) => { technicians.value = page.props.users; } });
});

onMounted(() => {});

const teamNameComputed = computed(() => {
    if (form.team_leader_id) {
        const teamLeader = props.technicians.find(tech => tech.id === form.team_leader_id);
        if (teamLeader) {
            const dateToUse = form.creation_date ? new Date(form.creation_date) : new Date();
            const today = dateToUse;

            const formattedDate = today.getFullYear().toString() + (today.getMonth() + 1).toString().padStart(2, '0') + today.getDate().toString().padStart(2, '0');
            return `${formattedDate} - Équipe de ${teamLeader.name}`;
        }
    }
    return '';
});

const dialogTitle = computed(() => editing.value ? 'Modifier l\'Équipe' : 'Créer une nouvelle Équipe');
</script>

<template>
    <AppLayout title="Gestion des Régions">
        <Head title="Régions" />

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

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />  <i class="pi pi-search" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="teams" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} équipes"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="name" header="Nom de l'équipe" :sortable="true" headerStyle="width:30%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.name }}
                            </template>
                        </Column>
                        <Column field="team_leader.name" header="Chef d'équipe" headerStyle="width:20%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.team_leader ? slotProps.data.team_leader.name : 'N/A' }}
                            </template>
                        </Column>
                        <Column field="members" header="Membres" headerStyle="width:30%; min-width:15rem;">
                            <template #body="slotProps">
                                <span v-if="slotProps.data.members && slotProps.data.members.length > 0">
                                    <span v-for="(member, index) in slotProps.data.members" :key="member.id">
                                        {{ member.name }}{{ index < slotProps.data.members.length - 1 ? ', ' : '' }}
                                    </span>
                                </span>
                                <span v-else>Aucun membre</span>
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editTeam(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteTeam(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations de l'équipe.</span>



                        <div class="flex items-center gap-4 mb-4">
                            <label for="team_leader" class="font-semibold w-24">Chef d'équipe</label>
                            <Dropdown id="team_leader" v-model="form.team_leader_id" :options="technicians" optionLabel="name" optionValue="id" placeholder="Sélectionner un chef d'équipe" class="flex-auto" @change="updateTeamMembers" />
                        </div>
                        <small class="p-error" v-if="form.errors.team_leader_id">{{ form.errors.team_leader_id }}</small>
                           <div class="flex items-center gap-4 mb-4">
                            <label for="name" class="font-semibold w-24">Nom de l'équipe</label>
                            <InputText id="name" v-model.trim="form.name" autofocus :placeholder="teamNameComputed"
                                :class="{ 'p-invalid': submitted && !form.name && !editing }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.name && !editing">Le nom de l'équipe est recommandé.</small>
                        <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                        <div class="flex items-center gap-4 mb-4">
                            <label for="creation_date" class="font-semibold w-24">Date de création</label>
                            <Calendar id="creation_date" v-model="form.creation_date" dateFormat="yy-mm-dd" showIcon class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.creation_date">{{ form.errors.creation_date }}</small>
                        <div class="flex items-center gap-4 mb-4">
                            <label for="members" class="font-semibold w-24">Membres (Techniciens)</label>
                            <MultiSelect id="members" v-model="form.members" :options="technicians" optionLabel="name" optionValue="id" placeholder="Sélectionner les membres" :filter="true" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.members">{{ form.errors.members }}</small>

                        <div class="flex justify-end gap-2">
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
.p-datatable .p-datatable-header {
    border-bottom: 1px solid var(--surface-d);
}

.p-datatable .p-column-header-content {
    justify-content: space-between;
}

.p-button.p-button-warning, .p-button.p-button-warning:hover {
    background: var(--orange-500);
    border-color: var(--orange-500);
}

.p-button.p-button-warning:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--orange-700), 0 1px 2px 0 black;
}

.p-button.p-button-success, .p-button.p-button-success:hover {
    background: var(--green-500);
    border-color: var(--green-500);
}

.p-button.p-button-success:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--green-700), 0 1px 2px 0 black;
}

.p-button.p-button-danger, .p-button.p-button-danger:hover {
    background: var(--red-500);
    border-color: var(--red-500);
}

.p-button.p-button-danger:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--red-700), 0 1px 2px 0 black;
}
</style>
