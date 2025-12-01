<script setup>
import Divider from 'primevue/divider'
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// Composants PrimeVue utilisés dans le template (s'assurer qu'ils sont importés dans le Layout ou le main.js)
// import Toolbar from 'primevue/toolbar';
// import Button from 'primevue/button';
// ...

const props = defineProps({
    equipments: Object,
    filters: Object,
    equipmentTypes: Array,
    regions: Array,
    users: Array,
    parentEquipments: Array,
    labels: Array, // Ajout de la prop pour les labels
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
    quantity: null, // Rétention d'une seule instance
    purchase_date: null,
    warranty_end_date: null,
    equipment_type_id: null,
    region_id: null,
    parent_id: null,
    label_id: null, // Ajout pour le label sélectionné
    characteristics: [],
});

const equipmentTypeForm = useForm({
    id: null,
    name: '',
    description: '', // Ajouté car utilisé dans le template
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

const characteristicTypes = ref([
    { label: 'Texte', value: 'text' },
    { label: 'Nombre', value: 'number' },
    { label: 'Date', value: 'date' },
    { label: 'Fichier', value: 'file' },
    { label: 'Oui/Non', value: 'boolean' },
]);
const parentIsStock = computed(() => props.parentEquipments.find(p => p.id === form.parent_id)?.status === 'en stock');

// Watch for changes in parent_id to pre-fill child equipment data
watch(() => form.parent_id, (newParentId) => {
    // Réinitialisation des caractéristiques à l'état de base pour éviter les données résiduelles
    const initialCharacteristics = [{ name: '', type: 'text', value: null }];

    if (newParentId) {
        const parent = props.parentEquipments.find(p => p.id === newParentId);
        if (parent) {
            // ... (autres remplissages)

            // --- CORRECTION CLÉ : Remplissage des caractéristiques du PARENT ---
            // Assurer que parent.characteristics existe et est un objet/tableau
            if (parent.characteristics) {
                // Convertir en objet si JSON stringifié
                let parentCharacteristics = parent.characteristics;
                if (typeof parentCharacteristics === 'string') {
                    try {
                        parentCharacteristics = JSON.parse(parentCharacteristics);
                    } catch (e) {
                        console.error("Erreur d'analyse des caractéristiques parent JSON:", e);
                        parentCharacteristics = {};
                    }
                }

                // S'assurer que c'est un objet (clé: valeur) ou un tableau d'objets {name, value}
                const entries = Array.isArray(parentCharacteristics)
                    ? parentCharacteristics.map(c => [c.name, c.value])
                    : Object.entries(parentCharacteristics);

                form.characteristics = entries.map(([name, value]) => ({
                    name,
                    value,
                    // Pour simplifier, on prend 'text' si on ne peut pas deviner le type
                    type: typeof value === 'number' ? 'number' : (typeof value === 'boolean' ? 'boolean' : (value && (new Date(value)).toString() !== 'Invalid Date' ? 'date' : 'text'))
                }));
            } else {
                form.characteristics = []; // S'assurer qu'il s'agit d'un tableau vide
            }

            // Si le parent n'a pas de caractéristiques, on reste sur l'initialisation de base.
            if (form.characteristics.length === 0) {
                form.characteristics = initialCharacteristics;
            }

        } else if (!editing.value) {
            form.reset('designation', 'brand', 'model', 'equipment_type_id', 'characteristics');
            form.characteristics = initialCharacteristics;
        }

    } else {
        if (!editing.value) {
            form.reset('designation', 'brand', 'model', 'equipment_type_id', 'characteristics');
            form.characteristics = initialCharacteristics;
        }
    }
}, { immediate: false });

// Watch for changes in label_id to add characteristics from the selected label
watch(() => form.label_id, (newLabelId) => {
    if (newLabelId) {
        const label = props.labels.find(l => l.id === newLabelId);
        if (label && label.label_characteristics) {
            label.label_characteristics.forEach(labelChar => {
                // Vérifier si une caractéristique avec le même nom n'existe pas déjà
                const exists = form.characteristics.some(existingChar => existingChar.name === labelChar.name);
                if (!exists) {
                    form.characteristics.push({
                        name: labelChar.name,
                        type: labelChar.type,
                        value: null // Valeur initiale vide
                    });
                }
            });
        }
    }
}, { immediate: false });

const openNew = () => {
    form.reset();
    form.characteristics = [{ name: '', type: 'text', value: null }]; // Assurer l'initialisation
    editing.value = false;
    submitted.value = false;
    equipmentDialog.value = true;
};

const hideDialog = () => {
    equipmentDialog.value = false;
    submitted.value = false;
};

const editEquipment = (equipment) => {
    // Remplissage des champs de l'équipement
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
    form.label_id = equipment.label_id;

    // --- CORRECTION CLÉ : GESTION DES CARACTÉRISTIQUES POUR ÉDITION ---
    let characteristicsArray = [];

    if (equipment.characteristics) {
        if (typeof equipment.characteristics === 'string') {
            try {
                const parsedCharacteristics = JSON.parse(equipment.characteristics);
                // Si c'est un OBJET {name: value, ...}, le convertir en tableau
                if (!Array.isArray(parsedCharacteristics)) {
                    characteristicsArray = Object.entries(parsedCharacteristics).map(([name, value]) => ({
                        name,
                        // Tenter de deviner le type si non fourni, sinon 'text' par défaut
                        type: typeof value === 'number' ? 'number' : (typeof value === 'boolean' ? 'boolean' : (value && (new Date(value)).toString() !== 'Invalid Date' ? 'date' : 'text')),
                        value,
                    }));
                } else {
                    characteristicsArray = parsedCharacteristics;
                }
            } catch (e) {
                console.error("Erreur d'analyse des caractéristiques JSON:", e);
                characteristicsArray = [];
            }
        } else if (typeof equipment.characteristics === 'object' && equipment.characteristics !== null) {
            // Si c'est déjà un objet JS (pas stringifié)
            if (Array.isArray(equipment.characteristics)) {
                characteristicsArray = equipment.characteristics;
            } else {
                // On suppose que c'est un objet {name: value} et on le transforme
                characteristicsArray = Object.entries(equipment.characteristics).map(([name, value]) => ({
                    name,
                    type: typeof value === 'number' ? 'number' : (typeof value === 'boolean' ? 'boolean' : (value && (new Date(value)).toString() !== 'Invalid Date' ? 'date' : 'text')),
                    value,
                }));
            }
        }
    }

    // 2. Traitement et conversion finale pour le formulaire
    form.characteristics = characteristicsArray.map(char => {
        // Si le type est 'date' et que la valeur est une chaîne, convertissez-la en objet Date
        if (char.type === 'date' && char.value && typeof char.value === 'string') {
            char.value = new Date(char.value);
        }

        return {
            name: char.name,
            // CORRECTION : Utiliser char.type ou 'text' par défaut si non défini
            type: char.type || 'text',
            value: char.value
        };
    });

    // Assure qu'il y a au moins un champ si l'objet était vide (pour l'ajout)
    if (form.characteristics.length === 0) {
        form.characteristics.push({ name: '', type: 'text', value: null });
    }
    // --- FIN CORRECTION ---

    editing.value = true;
    equipmentDialog.value = true;
};

const saveEquipment = () => {
    submitted.value = true;

    // Validation côté client simple (la validation complète doit être faite côté serveur)
    if (!form.designation || !form.equipment_type_id) {
        toast.add({ severity: 'error', summary: 'Erreur de validation', detail: 'La désignation et le type sont requis.', life: 3000 });
        return; // Stoppe la fonction si la validation échoue
    }
    if (isChild.value && (!form.quantity || form.quantity < 1)) {
        toast.add({ severity: 'error', summary: 'Erreur de validation', detail: 'La quantité à créer est requise.', life: 3000 });
        return; // Stoppe la fonction si la validation échoue
    }

    form.clearErrors(); // Bonne pratique : effacer les erreurs précédentes

    const url = editing.value ? route('equipments.update', form.id) : route('equipments.store');
    const method = editing.value ? 'put' : 'post'; // NOTE: Inertia.js (useForm) gère automatiquement l'envoi de `_method: 'put'` si vous utilisez `.submit('put', url, ...)`

    // Vérifier si des fichiers sont présents dans les caractéristiques
    const hasFiles = form.characteristics.some(char => char.type === 'file' && char.value instanceof File);

    if (hasFiles) {
        // Utiliser `transform` pour préparer les données, notamment pour les fichiers.
        form.transform((originalData) => {
            const formData = new FormData();

            for (const key in originalData) {
                // CORRECTION: Ajouter l'ID pour la mise à jour (nécessaire si la route l'exige)
                if (key === 'id' && editing.value) {
                    formData.append(key, originalData[key]);
                    continue;
                }

                if (key !== 'characteristics' && originalData[key] !== null) {
                    // Traiter les dates et autres valeurs non-fichier
                    if (key !== 'id') { // On gère l'ID séparément
                        formData.append(key, originalData[key] && originalData[key] instanceof Date ? originalData[key].toISOString().split('T')[0] : originalData[key]);
                    }
                }
            }

            originalData.characteristics.forEach((char, index) => {
                formData.append(`characteristics[${index}][name]`, char.name || '');
                formData.append(`characteristics[${index}][type]`, char.type || 'text');
                if (char.type === 'file' && char.value instanceof File) {
                    // Gérer le cas où un fichier est sélectionné (File object)
                    formData.append(`characteristics[${index}][value]`, char.value);
                } else if (char.value !== null && char.value !== undefined) {
                    // Gérer les autres types (texte, nombre, date, booléen)
                    formData.append(`characteristics[${index}][value]`, char.value);
                }
            });
            return formData;
        })
        // NOTE: Pour les envois avec FormData (multi-part/form-data) contenant des fichiers,
        // il est souvent préférable d'utiliser 'post' avec le _method: 'put' dans le FormData.
        .submit(method, url, { // Utiliser 'post' avec Inertia pour FormData
            onSuccess: () => {
                hideDialog();
                toast.add({ severity: 'success', summary: 'Succès', detail: `Équipement ${editing.value ? 'mis à jour' : 'créé'} avec succès.`, life: 3000 });
            },
            onError: (errors) => {
                console.error("Erreur lors de la sauvegarde de l'équipement", errors);
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
            },
            forceFormData: true, // S'assurer que FormData est utilisé
        });
    } else {
        // Si aucun fichier n'est présent, utiliser la méthode submit normale
        form.submit(method, url, {
            onSuccess: () => {
                hideDialog();
                toast.add({ severity: 'success', summary: 'Succès', detail: `Équipement ${editing.value ? 'mis à jour' : 'créé'} avec succès.`, life: 3000 });
            },
            onError: (errors) => {
                console.error("Erreur lors de la sauvegarde de l'équipement", errors);
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
            },
        });
    }
};

const deleteEquipment = (equipment) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer l'équipement "${equipment.tag || equipment.designation}" ?`,
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
        onSuccess: () => {
            hideDialog();
            toast.add({ severity: 'success', summary: 'Succès', detail: `Équipement ${editing.value ? 'mis à jour' : 'créé'} avec succès.`, life: 3000 });
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de l'équipement", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
        },

    });
};

const addCharacteristic = () => {
    form.characteristics.push({ name: '', type: 'text', value: null });
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

// Exposer les fonctions et variables nécessaires au template
defineExpose({
    openNew, hideDialog, editEquipment, saveEquipment, deleteEquipment,
    openNewEquipmentType, hideEquipmentTypeDialog, saveEquipmentType,
    addCharacteristic, removeCharacteristic, performSearch, exportCSV, getStatusSeverity,
    dt, form, equipmentDialog, equipmentTypeDialog, submitted, editing, search,
    statusOptions, characteristicTypes, isChild, isStockStatus, dialogTitle,
    parentIsStock, equipmentTypeForm
});
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
                        responsiveLayout="scroll"
                        ref="dt"> <Column field="tag" header="Tag" :sortable="true" style="min-width: 8rem;"></Column>
                        <Column field="designation" header="Désignation" :sortable="true" style="min-width: 10rem;"></Column>
                        <Column field="equipmentType.name" header="Type" :sortable="true" style="min-width: 10rem;">
                             <template #body="slotProps">
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
                        <Column field="region.designation" header="Région" :sortable="true" style="min-width: 8rem;">
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
                        <div class="p-fluid p-grid"> <div class="col-12">
                                <div class="field">
                                    <label for="parent_id" class="font-semibold">Équipement Parent (pour créer à partir du stock)</label>
                                    <Dropdown id="parent_id" class="w-full" v-model="form.parent_id" :options="parentEquipments" optionLabel="designation" optionValue="id" placeholder="Optionnel: Sélectionner un parent" :showClear="true" filter />
                                    <small class="p-error">{{ form.errors.parent_id }}</small>
                                </div>
                            </div>

                            <div class="p-grid col-12">
                                <div class="col-12 md:col-6 field" v-if="!isChild">
                                    <label for="tag" class="font-semibold">Tag</label>
                                    <InputText id="tag" class="w-full" v-model.trim="form.tag" :class="{ 'p-invalid': form.errors.tag }" />
                                    <small class="p-error">{{ form.errors.tag }}</small>
                                </div>

                                <div :class="{'col-12': isChild, 'col-12 md:col-6': !isChild}" class="field">
                                    <label for="designation" class="font-semibold">Désignation</label>
                                    <InputText id="designation" class="w-full" v-model.trim="form.designation" required :class="{ 'p-invalid': submitted && !form.designation || form.errors.designation }" :disabled="isChild" />
                                    <small class="p-error">{{ form.errors.designation }}</small>
                                </div>
                            </div>

                            <div class="p-grid col-12">
                                <div class="col-12 md:col-6 field">
    <label for="equipment_type_id" class="font-semibold">Type d'équipement</label>
    <div class="flex align-items-center gap-2">
        <Dropdown id="equipment_type_id" class="flex-grow-1" v-model="form.equipment_type_id" :options="equipmentTypes" optionLabel="name" optionValue="id" placeholder="Sélectionner un type" :class="{ 'p-invalid': submitted && !form.equipment_type_id || form.errors.equipment_type_id }" filter :disabled="isChild" />
        <Button icon="pi pi-plus" class="p-button-sm p-button-rounded p-button-secondary" severity="secondary" @click="openNewEquipmentType" title="Ajouter un nouveau type" />
    </div>
    <small class="p-error" v-if="submitted && !form.equipment_type_id">Le type d'équipement est requis.</small>
    <small class="p-error">{{ form.errors.equipment_type_id }}</small>
</div>
                                <div class="col-12 md:col-6 field">
                                    <label for="region_id" class="font-semibold">Région</label>
                                    <Dropdown id="region_id" class="w-full" v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" placeholder="Sélectionner une région" />
                                    <small class="p-error">{{ form.errors.region_id }}</small>
                                </div>
                            </div>



                            <div class="p-grid col-12">
                                <div class="col-12 md:col-6 field">
                                    <label for="brand" class="font-semibold">Marque</label>
                                    <InputText id="brand" class="w-full" v-model.trim="form.brand" :disabled="isChild && form.parent_id" :class="{ 'p-invalid': form.errors.brand }" />
                                    <small class="p-error">{{ form.errors.brand }}</small>
                                </div>
                                <div class="col-12 md:col-6 field">
                                    <label for="model" class="font-semibold">Modèle</label>
                                    <InputText id="model" class="w-full" v-model.trim="form.model" :disabled="isChild && form.parent_id" :class="{ 'p-invalid': form.errors.model }" />
                                    <small class="p-error">{{ form.errors.model }}</small>
                                </div>
                            </div>

                            <div class="p-grid col-12">
                                <div class="col-12 md:col-6 field">
                                    <label for="serial_number" class="font-semibold">Numéro de série</label>
                                    <InputText id="serial_number" class="w-full" v-model.trim="form.serial_number" :class="{ 'p-invalid': form.errors.serial_number }" />
                                    <small class="p-error">{{ form.errors.serial_number }}</small>
                                </div>
                                <div class="col-12 md:col-6 field">
                                    <label for="status" class="font-semibold">Statut</label>
                                    <Dropdown id="status" class="w-full" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner un statut" :class="{ 'p-invalid': form.errors.status }" />
                                    <small class="p-error">{{ form.errors.status }}</small>
                                </div>
                            </div>

                            <div class="p-grid col-12">
                                <div class="col-12 field" v-if="!isChild && isStockStatus">
                                    <label for="quantity" class="font-semibold">Quantité en stock</label>
                                    <InputNumber id="quantity" class="w-full" v-model="form.quantity" :min="0" required :class="{ 'p-invalid': submitted && form.quantity === null || form.errors.quantity }" />
                                    <small class="p-error" v-if="submitted && form.quantity === null">La quantité est requise pour le stock.</small>
                                    <small class="p-error">{{ form.errors.quantity }}</small>
                                </div>
                                <div class="col-12 field" v-else-if="isChild && parentIsStock">
                                    <label for="quantity" class="font-semibold">Quantité à créer</label>
                                    <InputNumber id="quantity" class="w-full" v-model="form.quantity" :min="1" required :class="{ 'p-invalid': submitted && !form.quantity || form.errors.quantity }" />
                                    <small class="p-error" v-if="submitted && !form.quantity">La quantité à créer est requise.</small>
                                    <small class="p-error">{{ form.errors.quantity }}</small>
                                </div>
                            </div>

                            <div class="p-grid col-12">
                                <div class="col-12 md:col-6 field">
                                    <label for="purchase_date" class="font-semibold">Date d'achat</label>
                                    <Calendar id="purchase_date" class="w-full" v-model="form.purchase_date" dateFormat="dd/mm/yy" :disabled="isChild" :class="{ 'p-invalid': form.errors.purchase_date }" />
                                    <small class="p-error">{{ form.errors.purchase_date }}</small>
                                </div>
                                <div class="col-12 md:col-6 field">
                                    <label for="warranty_end_date" class="font-semibold">Fin de garantie</label>
                                    <Calendar id="warranty_end_date" class="w-full" v-model="form.warranty_end_date" dateFormat="dd/mm/yy" :disabled="isChild" :class="{ 'p-invalid': form.errors.warranty_end_date }" />
                                    <small class="p-error">{{ form.errors.warranty_end_date }}</small>
                                </div>
                            </div>

                            <div class="col-12 field">
                                <label for="location" class="font-semibold">Emplacement</label>
                                <InputText id="location" class="w-full" v-model.trim="form.location" :class="{ 'p-invalid': form.errors.location }" />
                                <small class="p-error">{{ form.errors.location }}</small>
                            </div>

                         <div class="col-12">
    <Divider />
      <div class="p-grid col-12">
                                <div class="col-12 field">
                                    <label for="label_id" class="font-semibold">Importer les caracteristiques preexistant</label>
                                    <Dropdown id="label_id" class="w-full" v-model="form.label_id" :options="labels" optionLabel="designation" optionValue="id" placeholder="Optionnel: Sélectionner un label pour ajouter ses caractéristiques" :showClear="true" filter />
                                </div>
                            </div>
    <div class="field">
<Divider></Divider>

        <div v-for="(char, index) in form.characteristics" :key="index" class="flex flex-wrap align-items-start mb-1 gap-1">

            <div class="flex-grow-1">
                <InputText
                    v-model="char.name"
                    placeholder="Nom de la caractéristique"
                    :class="{ 'p-invalid': submitted && !char.name || form.errors[`characteristics.${index}.name`] }"
                    :disabled="isChild && form.parent_id"
                    class="w-full"
                />
                <small class="p-error" v-if="form.errors[`characteristics.${index}.name`]">{{ form.errors[`characteristics.${index}.name`] }}</small>
            </div>

            <div class="flex-grow-1">
                <Dropdown
                    v-model="char.type"
                    :options="characteristicTypes"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Type"
                    :disabled="isChild && form.parent_id"
                    class="w-full"
                />
            </div>

            <div class="flex-grow-2">

                <InputText
                    v-if="char.type === 'text'"
                    v-model="char.value"
                    placeholder="Valeur (Texte)"
                    :disabled="isChild && form.parent_id"
                    class="w-full"
                    :class="{ 'p-invalid': form.errors[`characteristics.${index}.value`] }"
                />
                <InputNumber
                    v-else-if="char.type === 'number'"
                    v-model="char.value"
                    placeholder="Valeur (Nombre)"
                    :disabled="isChild && form.parent_id"
                    class="w-full"
                    :class="{ 'p-invalid': form.errors[`characteristics.${index}.value`] }"
                />
                <Calendar
                    v-else-if="char.type === 'date'"
                    v-model="char.value"
                    dateFormat="dd/mm/yy"
                    placeholder="Valeur (Date)"
                    :disabled="isChild && form.parent_id"
                    class="w-full"
                    :class="{ 'p-invalid': form.errors[`characteristics.${index}.value`] }"
                />

                <div v-else-if="char.type === 'file'" class="flex flex-column gap-2 w-full">
    <div class="p-inputgroup">
        <input
            type="file"
            :id="'file-' + index"
            @change="char.value = $event.target.files[0]"
            class="p-inputtext w-full p-2 border border-gray-300 rounded-lg"
            :disabled="isChild && form.parent_id"
        />

        <Button
            v-if="char.value"
            icon="pi pi-times"
            severity="danger"
            @click="char.value = null"
            :disabled="isChild && form.parent_id"
        />
    </div>

    <small v-if="char.value" class="mt-1 flex align-items-center">
        <a v-if="typeof char.value === 'string'" :href="`/storage/${char.value}`" target="_blank" class="text-primary hover:underline font-medium">
            <i class="pi pi-download mr-2"></i>
            Fichier existant: {{ char.value.split('/').pop() }}
        </a>
        <!-- <span v-else-if="char.value instanceof File" class="text-success">
            <i class="pi pi-file mr-2"></i>
            Nouveau fichier à uploader: **{{ char.value.name }}**
        </span> -->
    </small>
    <small v-else-if="isChild && form.parent_id" class="text-color-secondary">
        Le fichier est hérité du stock. Non modifiable.
    </small>

    <InputError :message="form.errors[`characteristics.${index}.value`]" class="mt-2" />
</div>

                <div v-else-if="char.type === 'boolean'" class="flex align-items-center h-full">
                    <InputSwitch v-model="char.value" :disabled="isChild && form.parent_id" />
                </div>

                <InputText
                    v-else
                    v-model="char.value"
                    placeholder="Valeur"
                    :disabled="isChild && form.parent_id"
                    class="w-full"
                    :class="{ 'p-invalid': form.errors[`characteristics.${index}.value`] }"
                />

                <small class="p-error" v-if="form.errors[`characteristics.${index}.value`]">{{ form.errors[`characteristics.${index}.value`] }}</small>
            </div>

            <div>
                <Button
                    icon="pi pi-trash"
                    class="p-button-danger p-button-rounded"
                    @click="removeCharacteristic(index)"
                    :disabled="(form.characteristics.length === 1) || (isChild && form.parent_id)"
                />
            </div>
        </div>

        <Button
            label="Ajouter une caractéristique"
            icon="pi pi-plus"
            class="p-button-text"
            @click="addCharacteristic"
            :disabled="isChild && form.parent_id"
        />
    </div>
</div>

                        </div>

                        <template #footer>
                            <Button label="Annuler" icon="pi pi-times" @click="hideDialog" class="p-button-text"/>
                            <Button label="Sauvegarder" icon="pi pi-check" @click="saveEquipment" :loading="form.processing" />
                        </template>
                    </Dialog>

                    <Dialog v-model:visible="equipmentTypeDialog" modal header="Nouveau Type d'Équipement" :style="{ width: '25rem' }">
    <div class="p-fluid compact-grid">
        <div class="field col-12">
            <label for="equipment_type_name" class="font-semibold">Nom du type</label>
            <InputText
                id="equipment_type_name"
                v-model.trim="equipmentTypeForm.name"
                required
                autofocus
                :class="{ 'p-invalid': equipmentTypeForm.errors.name }"
                class="w-full"
            />
            <small class="p-error" v-if="equipmentTypeForm.errors.name">{{ equipmentTypeForm.errors.name }}</small>
        </div>

        <div class="field col-12">
            <label for="equipment_type_description" class="font-semibold">Description</label>
            <Textarea
                id="equipment_type_description"
                v-model.trim="equipmentTypeForm.description"
                rows="3"
                :class="{ 'p-invalid': equipmentTypeForm.errors.description }"
                class="w-full"
            />
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
/* Correction des classes de grille si le projet utilise PrimeFlex */
.p-grid {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.5rem;
    margin-left: -0.5rem;
    margin-top: -0.5rem;
}
.p-grid > [class*="col"] {
    padding: 0.5rem;
}
.col-12 { width: 100%; }
.md\:col-6 { width: 50%; } /* Si vous utilisez les classes md:col-6 de PrimeFlex */

/* S'assurer que les éléments flex-grow-x se comportent bien dans le dialogue des caractéristiques */
.flex-grow-1 { flex-grow: 1; }
.flex-grow-2 { flex-grow: 2; }
</style>
