<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    equipments: Object,
    filters: Object,
    equipmentTypes: Array,
    regions: Array,
    users: Array,
    parentEquipments: Array,
});

const toast = useToast();
const confirm = useConfirm();
const equipmentDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const equipmentTypeDialog = ref(false);
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    tag: '',
    designation: '',
    brand: '',
    model: '',
    serial_number: '',
    status: 'en stock',
    location: '',
    quantity: null,
    purchase_date: null,
    warranty_end_date: null,
    equipment_type_id: null,
    region_id: null,
    parent_id: null,
    characteristics: [],
    quantity: 1,
});

const equipmentTypeForm = useForm({
    id: null,
    name: '',
});

const statusOptions = ref([
    { label: 'En Service', value: 'en service' },
    { label: 'En Panne', value: 'en panne' },
    { label: 'En Maintenance', value: 'en maintenance' },
    { label: 'Hors Service', value: 'hors service' },
    { label: 'En Stock', value: 'en stock' },
]);

const isChild = computed(() => !!form.parent_id);
const isStockStatus = computed(() => form.status === 'en stock');
const parentIsStock = computed(() => props.parentEquipments.find(p => p.id === form.parent_id)?.status === 'en stock');

// Watch for changes in parent_id to pre-fill child equipment data
watch(() => form.parent_id, (newParentId) => {

    if (newParentId) {
        const parent = props.parentEquipments.find(p => p.id === newParentId);
        console.log('parent', parent.characteristics);
        if (parent) {
            form.designation = parent.designation;
            form.brand = parent.brand;
            form.model = parent.model;
            form.equipment_type_id = parent.equipment_type_id;
            form.region_id = parent.region_id;
            form.characteristics = parent.characteristics && parent.characteristics.length > 0 ? JSON.parse(JSON.stringify(parent.characteristics)) : [{ name: '', value: '' }];
        } else if (!editing.value) { // If parent is not found or cleared, and not editing, reset
            form.reset('designation', 'brand', 'model', 'equipment_type_id', 'characteristics');
            form.characteristics = [{ name: '', value: '' }];
        }

    } else {
        // If parent is cleared, and we are not editing, reset the fields
        if (!editing.value) {
            form.reset('designation', 'brand', 'model', 'equipment_type_id', 'characteristics');
            form.characteristics = [{ name: '', value: '' }];
        }
    }
});


const openNew = () => {
    form.reset();
    form.characteristics = [{ name: '', value: '' }];
    editing.value = false;
    submitted.value = false;
    equipmentDialog.value = true;
};

const hideDialog = () => {
    equipmentDialog.value = false;
    submitted.value = false;
};

const editEquipment = (equipment) => {
    form.id = equipment.id;
    form.tag = equipment.tag;
    form.designation = equipment.designation;
    form.brand = equipment.brand;
    form.model = equipment.model;
    form.serial_number = equipment.serial_number;
    form.status = equipment.status;
    form.location = equipment.location;
    form.purchase_date = equipment.purchase_date ? new Date(equipment.purchase_date) : null;
    form.quantity = equipment.quantity;
    form.warranty_end_date = equipment.warranty_end_date ? new Date(equipment.warranty_end_date) : null;
    form.equipment_type_id = equipment.equipment_type_id;
    form.region_id = equipment.region_id;
    form.parent_id = equipment.parent_id;
    form.characteristics = equipment.characteristics.length > 0 ? [...equipment.characteristics] : [{ name: '', value: '' }];

    // If editing a child equipment, pre-fill brand, model, and equipment_type_id from its parent
    if (equipment.parent_id && equipment.parent) {
        form.brand = equipment.parent.brand;
        form.model = equipment.parent.model;
        form.region_id = equipment.parent.region_id;
        form.equipment_type_id = equipment.parent.equipment_type_id; // This line is redundant if the next line is uncommented
        form.characteristics = equipment.parent.characteristics && equipment.parent.characteristics.length > 0 ? JSON.parse(JSON.stringify(equipment.parent.characteristics)) : [{ name: '', value: '' }];
    }


    editing.value = true;
    equipmentDialog.value = true;
};

const saveEquipment = () => {
    submitted.value = true;

    // Validation for parent equipment
    if (!isChild.value && (!form.designation || !form.equipment_type_id)) {
        toast.add({ severity: 'error', summary: 'Erreur de validation', detail: 'La désignation et le type sont requis.', life: 3000 });
        return;
    }

    // Validation for child equipment
    if (isChild.value && (!form.quantity || form.quantity < 1)) {
        toast.add({ severity: 'error', summary: 'Erreur de validation', detail: 'La quantité à créer est requise.', life: 3000 });
        return;
    }

    const url = editing.value ? route('equipments.update', form.id) : route('equipments.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            equipmentDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Succès',
                detail: `Équipement ${editing.value ? 'mis à jour' : 'créé'} avec succès`,
                life: 3000
            });
            form.reset();
        },
        onError: (errors) => {
            console.error('Erreur lors de la sauvegarde de l\'équipement', errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la sauvegarde.', life: 3000 });
        }
    });
};

const deleteEquipment = (equipment) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer l'équipement "${equipment.tag}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: 'Annuler',
        acceptLabel: 'Supprimer',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('equipments.destroy', equipment.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipement supprimé avec succès', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la suppression.', life: 3000 });
                }
            });
        },
    });
};

const openNewEquipmentType = () => {
    equipmentTypeForm.reset();
    equipmentTypeDialog.value = true;
};

const hideEquipmentTypeDialog = () => {
    equipmentTypeDialog.value = false;
    equipmentTypeForm.reset();
};

const saveEquipmentType = () => {
    equipmentTypeForm.post(route('equipment-types.store'), {
        onSuccess: (page) => {
            equipmentTypeDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Succès',
                detail: 'Type d\'équipement créé avec succès',
                life: 3000
            });
            // Optionally, update the main form's equipment_type_id to the newly created one
            const newEquipmentType = page.props.equipmentTypes.find(et => et.name === equipmentTypeForm.name);
            if (newEquipmentType) {
                form.equipment_type_id = newEquipmentType.id;
            }
            equipmentTypeForm.reset();
        },
        onError: (errors) => {
            console.error('Erreur lors de la sauvegarde du type d\'équipement', errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la sauvegarde du type d\'équipement.', life: 3000 });
        }
    });
};

const addCharacteristic = () => {
    form.characteristics.push({ name: '', value: '' });
};

const removeCharacteristic = (index) => {
    form.characteristics.splice(index, 1);
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('equipments.index'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const dt = ref();
const exportCSV = () => {
 dt.value.exportCSV();
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'en service': return 'success';
        case 'en panne': return 'danger';
        case 'en maintenance': return 'warning';
        case 'hors service': return 'secondary';
        case 'en stock': return 'info';
        default: return null;
    }
};

const dialogTitle = computed(() => editing.value ? 'Modifier l\'Équipement' : 'Créer un nouvel Équipement');

</script>

<template>
    <AppLayout title="Gestion des Équipements">
        <Head title="Équipements" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <div class="flex flex-column md:flex-row md:justify-content-between md:align-items-center">
                                <span class="block mt-2 md:mt-0 p-input-icon-left flex align-items-center gap-2">
                                    <Button label="Ajouter un équipement" icon="pi pi-plus" class="p-button-sm mr-2" @click="openNew" />

                                    <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                                    <i class="pi pi-search" />

                                </span>
                            </div>
                        </template>
                        <template #end>
                            <Button label="Exporter" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)" />
                        </template>
                    </Toolbar>

                    <DataTable :value="equipments.data" dataKey="id" :paginator="true" :rows="10"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} équipements"
                        responsiveLayout="scroll">

                        <Column field="tag" header="Tag" :sortable="true" style="min-width: 8rem;"></Column>
                        <Column field="designation" header="Désignation" :sortable="true" style="min-width: 10rem;"></Column>
                        <Column field="equipmentType.name" header="Type" :sortable="true" style="min-width: 10rem;">
                             <template #body="slotProps">
                                <!-- Afficher le nom du type de l'équipement parent si l'équipement actuel est un enfant -->
                                <span v-if="slotProps.data.parent_id && slotProps.data.parent">
                                    {{ slotProps.data.parent.equipment_type?.name }}
                                </span>
                                <span v-else>
                                {{ slotProps.data.equipment_type?.name }}
                                </span>
                            </template>
                        </Column>
                        <Column field="brand" header="Marque" :sortable="true" style="min-width: 8rem;"></Column>
                        <Column field="model" header="Modèle" :sortable="true" style="min-width: 8rem;"></Column>
                        <Column field="region.name" header="Région" :sortable="true" style="min-width: 8rem;">
                             <template #body="slotProps">
                                {{ slotProps.data.region?.designation }}
                            </template>
                        </Column>
                        <Column field="status" header="Statut" :sortable="true" style="min-width: 10rem;">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                            </template>
                        </Column>
                        <Column field="quantity" header="Quantité" :sortable="true" style="min-width: 8rem;">
                            <template #body="slotProps">
                                {{ slotProps.data.status === 'en stock' ? slotProps.data.quantity : 'N/A' }}
                            </template>
                        </Column>

                        <Column headerStyle="min-width:10rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded mr-2" severity="info"
                                    @click="editEquipment(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded" severity="danger"
                                    @click="deleteEquipment(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Dialog v-model:visible="equipmentDialog" modal :header="dialogTitle" :style="{ width: '50rem' }">
                        <div class="p-fluid">
                            <!-- Row 1: Parent Equipment -->
                            <div class="field" >
                                <label for="parent_id" class="font-semibold">Équipement Parent (pour créer à partir du stock)</label>
                                <Dropdown id="parent_id" class="w-full" v-model="form.parent_id" :options="parentEquipments" optionLabel="designation" optionValue="id" placeholder="Optionnel: Sélectionner un parent" :showClear="true" filter />
                                <small class="p-error">{{ form.errors.parent_id }}</small>
                            </div>
                            <!-- Row 2: Tag & Designation -->
                            <div class="grid grid-cols-2 gap-2"v-if="!isChild" >
                                <div class="field" >
                                    <label for="tag" class="font-semibold">Tag (pour le parent ou le nouvel équipement)</label>
                                    <InputText id="tag" class="w-full" v-model.trim="form.tag" />
                                    <small class="p-error">{{ form.errors.tag }}</small>
                                </div>
                                <div class="field">
                                    <label for="designation" class="font-semibold">Désignation</label>
                                    <InputText id="designation" class="w-full" v-model.trim="form.designation" required :class="{ 'p-invalid': submitted && !form.designation }" />
                                    <small class="p-invalid" v-if="submitted && !form.designation">La désignation est requise.</small>
                                    <small class="p-error">{{ form.errors.designation }}</small>
                                </div>
                            </div>
                            <div v-if="isChild">
                                <label for="designation" class="font-semibold">Désignation</label>
                                    <InputText id="designation" class="w-full" v-model.trim="form.designation" required :class="{ 'p-invalid': submitted && !form.designation }" />
                                    <small class="p-invalid" v-if="submitted && !form.designation">La désignation est requise.</small>
                                    <small class="p-error">{{ form.errors.designation }}</small>
                            </div>
                            <!-- Row 3: Type & Region -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <div class="flex align-items-center justify-content-between">
                                        <label for="equipment_type_id" class="font-semibold">Type d'équipement</label>
                                        <Button icon="pi pi-plus" class="p-button-text p-button-sm" label="Nouveau type" @click="openNewEquipmentType" />
                                    </div>
                                    <Dropdown id="equipment_type_id" class="w-full" v-model="form.equipment_type_id" :options="equipmentTypes" optionLabel="name" optionValue="id" placeholder="Sélectionner un type" :class="{ 'p-invalid': submitted && !form.equipment_type_id }" filter :disabled="isChild" />
                                    <small class="p-invalid" v-if="submitted && !form.equipment_type_id">Le type d'équipement est requis.</small>
                                    <small class="p-error">{{ form.errors.equipment_type_id }}</small>
                                </div>
                                <div class="field">
                                    <label for="region_id" class="font-semibold">Région</label>
                                    <Dropdown id="region_id" class="w-full" v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" placeholder="Sélectionner une région" />
                                    <small class="p-error">{{ form.errors.region_id }}</small>
                                </div>
                            </div>
                            <!-- Row 4: Brand & Model -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="brand" class="font-semibold">Marque</label>
                                    <InputText id="brand" class="w-full" v-model.trim="form.brand" :disabled="isChild && form.parent_id" />
                                    <small class="p-error">{{ form.errors.brand }}</small>
                                </div>
                                <div class="field">
                                    <label for="model" class="font-semibold">Modèle</label>
                                    <InputText id="model" class="w-full" v-model.trim="form.model" :disabled="isChild && form.parent_id" />
                                    <small class="p-error">{{ form.errors.model }}</small>
                                </div>
                            </div>
                            <!-- Row 5: Serial Number & Status -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="serial_number" class="font-semibold">Numéro de série</label>
                                    <InputText id="serial_number" class="w-full" v-model.trim="form.serial_number" />
                                    <small class="p-error">{{ form.errors.serial_number }}</small>
                                </div>
                                <div class="field">
                                    <label for="status" class="font-semibold">Statut</label>
                                    <Dropdown id="status" class="w-full" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner un statut" />
                                    <small class="p-error">{{ form.errors.status }}</small>
                                </div>
                            </div>
                            <!-- Row 6: Quantity (for parent or child) -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field" v-if="!isChild && isStockStatus">
                                    <label for="quantity" class="font-semibold">Quantité en stock</label>
                                    <InputNumber id="quantity" class="w-full" v-model="form.quantity" :min="0" required :class="{ 'p-invalid': submitted && form.quantity === null }" />
                                    <small class="p-invalid" v-if="submitted && form.quantity === null">La quantité est requise pour le stock.</small>
                                    <small class="p-error">{{ form.errors.quantity }}</small>
                                </div>
                                <div class="field" v-if="isChild && parentIsStock">
                                    <label for="quantity" class="font-semibold">Quantité à créer</label>
                                    <InputNumber id="quantity" class="w-full" v-model="form.quantity" :min="1" required :class="{ 'p-invalid': submitted && !form.quantity }" />
                                    <small class="p-invalid" v-if="submitted && !form.quantity">La quantité est requise.</small>
                                    <small class="p-error">{{ form.errors.quantity }}</small>
                                </div>
                            </div>
                            <!-- Row 7: Purchase Date & Warranty -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="purchase_date" class="font-semibold">Date d'achat</label>
                                    <Calendar id="purchase_date" class="w-full" v-model="form.purchase_date" dateFormat="dd/mm/yy" :disabled="isChild" />
                                    <small class="p-error">{{ form.errors.purchase_date }}</small>
                                </div>
                                <div class="col-6">
                                    <label for="warranty_end_date" class="font-semibold">Fin de garantie</label>
                                    <Calendar id="warranty_end_date" class="w-full" v-model="form.warranty_end_date" dateFormat="dd/mm/yy" :disabled="isChild" />
                                    <small class="p-error">{{ form.errors.warranty_end_date }}</small>
                                </div>
                             <!-- Row 8: Location -->
                            </div>
                            <div class="">
                                <div class="field">
                                    <label for="location" class="font-semibold">Emplacement</label>
                                    <InputText id="location" class="w-full" v-model.trim="form.location" />
                                    <small class="p-error">{{ form.errors.location }}</small>
                                </div>                             </div>

                            <!-- Characteristics (only for parent) -->
                            <div>
                                <Divider />
                                <div class="field"> <label class="font-semibold">Caractéristiques</label> <div v-for="(char, index) in form.characteristics" :key="index" class="flex align-items-center mb-2 gap-2"> <div class="flex-grow-1"> <InputText v-model="char.name" placeholder="Nom de la caractéristique" :class="{ 'p-invalid': submitted && !char.name }" :disabled="isChild && form.parent_id" /> </div> <div class="flex-grow-1"> <InputText v-model="char.value" placeholder="Valeur" :disabled="isChild && form.parent_id" /> </div> <div> <Button icon="pi pi-trash" class="p-button-danger p-button-rounded" @click="removeCharacteristic(index)" :disabled="(form.characteristics.length === 1) || (isChild && form.parent_id)" /> </div> </div> <Button label="Ajouter une caractéristique" icon="pi pi-plus" class="p-button-text" @click="addCharacteristic" :disabled="isChild && form.parent_id" /> </div>
                                <small class="p-error" v-if="form.errors['characteristics.0.name']">{{ form.errors['characteristics.0.name'] }}</small>
                            </div>

                        </div>

                        <template #footer>
                            <Button label="Annuler" icon="pi pi-times" @click="hideDialog" class="p-button-text"/>
                            <Button label="Sauvegarder" icon="pi pi-check" @click="saveEquipment" :loading="form.processing" />
                        </template>
                    </Dialog>

                    <!-- Equipment Type Dialog -->
                    <Dialog v-model:visible="equipmentTypeDialog" modal header="Nouveau Type d'Équipement" :style="{ width: '30rem' }">
                        <div class="p-fluid">
                            <div class="field">
                                <label for="equipment_type_name" class="font-semibold">Nom du type</label>
                                <InputText id="equipment_type_name" v-model.trim="equipmentTypeForm.name" required="true" autofocus :class="{ 'p-invalid': equipmentTypeForm.errors.name }" />
                                <small class="p-error" v-if="equipmentTypeForm.errors.name">{{ equipmentTypeForm.errors.name }}</small>
                            </div>
                            <div class="field">
                                <label for="equipment_type_description" class="font-semibold">Description</label>
                                <InputText id="equipment_type_description" v-model.trim="equipmentTypeForm.description" :class="{ 'p-invalid': equipmentTypeForm.errors.description }" />
                                <small class="p-error" v-if="equipmentTypeForm.errors.description">{{ equipmentTypeForm.errors.description }}</small>
                            </div>
                        </div>
                        <template #footer>
                            <Button label="Annuler" icon="pi pi-times" @click="hideEquipmentTypeDialog" class="p-button-text" />
                            <Button label="Créer" icon="pi pi-check" @click="saveEquipmentType" :loading="equipmentTypeForm.processing" />
                        </template>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Styles spécifiques si nécessaire */
.p-datatable .p-column-header-content {
    justify-content: space-between;
}
</style>
