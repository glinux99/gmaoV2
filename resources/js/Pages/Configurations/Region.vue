<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    regions: Array,
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
    designation: '', // Nom de la région
    type_centrale: null, // Type de centrale
    puissance_centrale: null, // Puissance de la centrale
});

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

const editRegion = (region) => {
    form.id = region.id;
    form.designation = region.designation;
    form.type_centrale = region.type_centrale;
    form.puissance_centrale = region.puissance_centrale;
    editing.value = true;
    labelDialog.value = true;
};

const saveRegion = () => {
    submitted.value = true;
    if (!form.designation) {
        return;
    }


    const url = editing.value ? route('engins.update', form.id) : route('engins.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Région ${editing.value ? 'mise à jour' : 'créée'} avec succès`, life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la région", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deleteRegion = (region) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la région "${region.designation}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('regions.destroy', region.id), {
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
        router.get(route('regions.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const typeCentraleOptions = ref([
    { label: 'Thermique', value: 'thermique' },
    { label: 'Solaire', value: 'solaire' },
    { label: 'Hydraulique', value: 'hydraulique' },
    { label: 'Eolienne', value: 'eolienne' },
]);

onMounted(() => {});

const dialogTitle = computed(() => editing.value ? 'Modifier la Région' : 'Créer une nouvelle Région');

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
                                <span class="block mt-2 md:mt-0 p-input-icon-left">
                                    <Button label="Ajouter une région" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />  <i class="pi pi-search" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="regions" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} régions"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="designation" header="Désignation" :sortable="true" headerStyle="width:30%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.designation }}
                            </template>
                        </Column>
                        <Column field="type_centrale" header="Type de Centrale" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.type_centrale }}
                            </template>
                        </Column>
                        <Column field="puissance_centrale" header="Puissance de la Centrale (MW)" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.puissance_centrale }}
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editRegion(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteRegion(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations de la région.</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="designation" class="font-semibold w-24">Désignation</label>
                            <InputText id="designation" v-model.trim="form.designation" required="true" autofocus
                                :class="{ 'p-invalid': submitted && !form.designation }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.designation">La désignation est requise.</small>
                        <small class="p-error" v-if="form.errors.designation">{{ form.errors.designation }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="type_centrale" class="font-semibold w-24">Type de Centrale</label>
                            <Dropdown id="type_centrale" v-model="form.type_centrale" :options="typeCentraleOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner un type" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.type_centrale">{{ form.errors.type_centrale }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="puissance_centrale" class="font-semibold w-24">Puissance Centrale (MW)</label>
                            <InputNumber id="puissance_centrale" v-model="form.puissance_centrale" mode="decimal" :min="0" :maxFractionDigits="2" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.puissance_centrale">{{ form.errors.puissance_centrale }}</small>

                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveRegion" :loading="form.processing"></Button>
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
