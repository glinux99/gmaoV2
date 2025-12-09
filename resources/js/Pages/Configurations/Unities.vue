<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    unities: Array, // Changed from labels to unities
    filters: Object,
});

const toast = useToast();
const confirm = useConfirm();

const labelDialog = ref(false);
const submitted = ref(false); // Used for form validation
const editing = ref(false); // To determine if we are editing or creating
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    designation: '',
    abreviation: '', // Changed from description to label
});

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true; // Reusing labelDialog for unit dialog
};

const hideDialog = () => {
    labelDialog.value = false;
    submitted.value = false;
};
const editUnit = (unit) => { // Changed from editLabel to editUnit
    form.id = unit.id;
    form.designation = unit.designation;
    form.abreviation = unit.abreviation; // Changed from description to label
    editing.value = true;
    labelDialog.value = true;
};

const saveUnit = () => { // Changed from saveLabel to saveUnit
    submitted.value = true;
    if (!form.designation) {
        return;
    }

    const url = editing.value ? route('unities.update', form.id) : route('unities.store'); // Changed route
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Unité ${editing.value ? 'mise à jour' : 'créée'} avec succès`, life: 3000 }); // Changed message
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de l'unité", errors); // Changed message
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deleteUnit = (unit) => { // Changed from deleteLabel to deleteUnit
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer l'unité "${unit.designation}" ?`, // Changed message
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('unities.destroy', unit.id), { // Changed route
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Unité supprimée avec succès', life: 3000 }); // Changed message
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression de l\'unité.', life: 3000 }); // Changed message
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
        router.get(route('unities.index'), { search: search.value }, { // Changed route
            preserveState: true,
            replace: true,
        });
    }, 300);
};
onMounted(() => {
    // Vous pouvez initialiser des choses ici si nécessaire
});
const dialogTitle = computed(() => editing.value ? 'Modifier l\'Unité' : 'Créer une nouvelle Unité'); // Changed dialog title
</script>

<template>
    <AppLayout title="Gestion des Unités">
        <Head title="Unités" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">

                                <span class="block mt-2 md:mt-0 p-input-icon-left">                                    <Button label="Ajouter une unité" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />


                                </span>
                            </div>
                        </template>

                         <template #end>
 <IconField class="mr-2"><InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
            </IconField>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="unities" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} unités"
                        responsiveLayout="scroll"> <!-- Changed totalRecords message -->
                        <template #header>

                        </template>

                        <Column field="designation" header="Nom" :sortable="true" headerStyle=" min-width:10rem;">
                            <template #body="slotProps">

                                {{ slotProps.data.designation }}
                            </template>
                        </Column>
                        <Column field="abreviation" header="Symbole" headerStyle=" min-width:8rem;"> <!-- Changed from Couleur to Label -->
                            <template #body="slotProps">
                                {{ slotProps.data.abreviation || slotProps.data.designation.split(' ').map(word => word[0]).join('').toUpperCase() }} <!-- Displaying label -->
                            </template>
                        </Column>
                        <!-- Removed Description column as it's not part of units -->
                        <Column  header="Actions" >
                            <template #body="slotProps" >

                                    <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info" @click="editUnit(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteUnit(slotProps.data)" /> <!-- Changed deleteLabel to deleteUnit -->


                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations de l'unité.</span>
                        <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">Créez une nouvelle unité.</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="designation" class="font-semibold w-24">Désignation</label>
                            <InputText id="designation" v-model.trim="form.designation" required="true" autofocus
                                :class="{ 'p-invalid': submitted && !form.designation }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.designation">Le nom est requis.</small>
                        <small class="p-error" v-if="form.errors.designation">{{ form.errors.designation }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="label" class="font-semibold w-24">Symbole</label> <!-- Changed from description to label -->

                            <InputText id="label" v-model="form.abreviation"
                                :class="{ 'p-invalid': submitted && !form.abreviation }" class="flex-auto" autocomplete="off" /> <!-- Changed to InputText for label -->
                        </div>
                         <!-- Validation for label -->
                        <small class="p-error" v-if="form.errors.abreviation">{{ form.errors.abreviation }}</small> <!-- Error for label -->

                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveUnit" :loading="form.processing"></Button> <!-- Changed saveLabel to saveUnit -->
                        </div>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Styles spécifiques si nécessaire */
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
