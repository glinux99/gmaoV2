<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
   import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    labels: Array,
    filters: Object,
});

const characteristicTypes = ref([
    { label: 'Texte', value: 'text' },
    { label: 'Nombre', value: 'number' },
    { label: 'Date', value: 'date' },
    { label: 'image', value: 'text' },
    { label: 'Boolean', value: 'boolean' },
    { label: 'Liste déroulante', value: 'select' },
]);

const toast = useToast();
const confirm = useConfirm();

const labelDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    designation: '',
    description: '',
    color: 'ff0000',
    characteristics: [], // Ajouter pour gérer les caractéristiques
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

const editLabel = (label) => {
    form.id = label.id;
    form.designation = label.designation;
    form.description = label.description;
    form.color = label.color.replace('#', '');
    // Cloner les caractéristiques pour éviter la mutation directe du prop
    form.characteristics = label.label_characteristics ? JSON.parse(JSON.stringify(label.label_characteristics)) : [];
    editing.value = true;
    labelDialog.value = true;
};

const saveLabel = () => {
    submitted.value = true;
    if (!form.designation) {
        return;
    }

    const hasChangedCharacteristics = form.isDirty && (
        JSON.stringify(form.characteristics) !== JSON.stringify(props.labels.data.find(l => l.id === form.id)?.label_characteristics || [])
    );

    if (editing.value && hasChangedCharacteristics) {
        confirm.require({
            message: 'Vous avez modifié des caractéristiques. La modification ou la suppression de caractéristiques peut affecter les données existantes. Voulez-vous continuer ?',
            header: 'Confirmation de modification',
            icon: 'pi pi-exclamation-triangle',
            acceptClass: 'p-button-warning',
            accept: () => {
                submitForm();
            },
        });
    } else {
        submitForm();
    }
};

const submitForm = () => {
    const url = editing.value ? route('labels.update', form.id) : route('labels.store');
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data,
        color: '#' + data.color,
        characteristics: data.characteristics.filter(c => c.name.trim() !== '') // Filtrer les caractéristiques vides
    })).submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Label ${editing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du label", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        }
    });
};

const deleteLabel = (label) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer le label "${label.designation}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('labels.destroy', label.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Label supprimé avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

// Fonctions pour gérer les caractéristiques dans le formulaire
const addCharacteristic = () => {
    form.characteristics.push({ id: null, name: '', type: 'text', is_required: false });
};

const removeCharacteristic = (index) => {
    const characteristic = form.characteristics[index];
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la caractéristique "${characteristic.name || 'vide'}" ? Cette action est irréversible.`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            form.characteristics.splice(index, 1);
            toast.add({ severity: 'info', summary: 'Information', detail: 'La caractéristique sera supprimée lors de la sauvegarde du label.', life: 4000 });
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
        router.get(route('labels.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

onMounted(() => {
    // Pour que l'édition fonctionne, le contrôleur doit retourner les caractéristiques avec chaque label
    // via la pagination d'Inertia.
    // Le contrôleur a été mis à jour avec Label::with('labelCharacteristics').
});

const dialogTitle = computed(() => editing.value ? 'Modifier le Label' : 'Créer un nouveau Label');

</script>

<template>
    <AppLayout title="Gestion des Labels">
        <Head title="Labels" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">

                                <span class="block mt-2 md:mt-0 p-input-icon-left">
                                    <Button label="Ajouter un label" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />  <i class="pi pi-search" />
                                </span>
                            </div>
                        </template>

                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable ref="dt" :value="labels.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} labels"
                        responsiveLayout="scroll">
                        <template #header>

                        </template>

                        <Column field="designation" header="Nom" :sortable="true" headerStyle="width:20%; min-width:10rem;">
                            <template #body="slotProps">

                                {{ slotProps.data.designation }}
                            </template>
                        </Column>
                        <Column header="Couleur" headerStyle="width:15%; min-width:8rem;">
                            <template #body="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <div :style="{ backgroundColor: slotProps.data.color, width: '24px', height: '24px', borderRadius: '4px', border: '1px solid #ccc' }"></div>
                                    <span>{{ slotProps.data.color }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="description" header="Description" headerStyle="width:30%; min-width:10rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.description }}
                            </template>
                        </Column>
                        <Column header="Caractéristiques" headerStyle="width:25%; min-width:10rem;">
                            <template #body="slotProps">
                                <div class="flex flex-wrap gap-1">
                                    <Tag v-for="char in slotProps.data.label_characteristics" :key="char.id" :value="char.name" severity="success"></Tag>
                                </div>
                            </template>
                        </Column>
                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editLabel(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error"
                                    @click="deleteLabel(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="labelDialog" modal :header="dialogTitle" :style="{ width: '40rem' }">
                        <span v-if="editing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations du label.</span>
                        <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">Créez un nouveau label.</span>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="designation" class="font-semibold w-24">Désignation</label>
                            <InputText id="designation" v-model.trim="form.designation" required="true" autofocus
                                :class="{ 'p-invalid': submitted && !form.designation }" class="flex-auto" autocomplete="off" />
                        </div>
                        <small class="p-invalid" v-if="submitted && !form.designation">Le nom est requis.</small>
                        <small class="p-error" v-if="form.errors.designation">{{ form.errors.designation }}</small>

                        <div class="flex items-center gap-4 mb-4">
                            <label for="description" class="font-semibold w-24">Description</label>
                            <Textarea id="description" v-model="form.description" rows="3" cols="20" class="flex-auto" />
                        </div>
                        <small class="p-error" v-if="form.errors.description">{{ form.errors.description }}</small>

                        <div class="flex items-center gap-4 mb-8">
                            <label for="color" class="font-semibold w-24">Couleur</label>
                            <div class="p-inputgroup flex-auto">
                                <ColorPicker v-model="form.color" inputId="color" class="w-full" />
                                <span class="p-inputgroup-addon">#{{ form.color }}</span>
                            </div>
                        </div>
                        <small class="p-error" v-if="form.errors.color">{{ form.errors.color }}</small>

                        <Divider />

                        <!-- Section pour les caractéristiques -->
                        <div class="field">
                            <label class="font-semibold">Caractéristiques</label>
                            <div v-for="(characteristic, index) in form.characteristics" :key="index" class="flex items-center gap-2 mb-2">
                                <!--
                                    Pour implémenter la modification conditionnelle, vous pouvez ajouter la prop :disabled ici.
                                    Exemple : :disabled="shouldBeDisabled(characteristic)"
                                    La fonction shouldBeDisabled(characteristic) vérifierait (probablement via un appel API)
                                    si des valeurs ont déjà été enregistrées pour cette caractéristique.
                                -->
                                <InputText v-model="characteristic.name" placeholder="Nom" class="w-1/2" />
                                <Dropdown v-model="characteristic.type" :options="characteristicTypes" optionLabel="label" optionValue="value" placeholder="Type" class="w-1/3" :disabled="characteristic.id !== null"
                                 />
                                <div class="flex items-center w-auto gap-2">
                                    <Checkbox v-model="characteristic.is_required" :binary="true" :inputId="`required-${index}`" />
                                    <label :for="`required-${index}`">Requis</label>
                                </div>

                                <Button icon="pi pi-trash" class="p-button-rounded " severity="error" @click="removeCharacteristic(index)" />
                            </div>
                             <small class="p-error" v-if="form.errors.characteristics">{{ form.errors.characteristics }}</small>

                            <Button label="Ajouter une caractéristique" icon="pi pi-plus" class="p-button-text mt-2" @click="addCharacteristic" />
                        </div>


                        <Divider />


                        <div class="flex justify-end gap-2">
                            <Button type="button" label="Annuler" severity="secondary" @click="hideDialog"></Button>
                            <Button type="button" label="Sauvegarder" @click="saveLabel" :loading="form.processing"></Button>
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

.p-colorpicker-preview {
    border-radius: 4px;
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
