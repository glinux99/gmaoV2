<script setup>
import Divider from 'primevue/divider'
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useConfirm } from 'primevue/useconfirm';
import OverlayPanel from 'primevue/overlaypanel';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import FileUpload from 'primevue/fileupload';


// Composants PrimeVue utilisés dans le template (s'assurer qu'ils sont importés dans le Layout ou le main.js)
// ...

const props = defineProps({
    equipments: Object,
    filters: Object,
    equipmentTypes: Array,
    regions: Array,
    users: Array,
    parentEquipments: Array,
    labels: Array,
    queryParams: Object,
    import_errors: Array, // Pour les erreurs d'importation
});
const { t } = useI18n();

const toast = useToast();
const confirm = useConfirm();
const equipmentDialog = ref(false);
const deleteEquipmentsDialog = ref(false);
const submitted = ref(false);
const activeStep = ref(1);
const insufficientQuantityDialog = ref(false); // Nouvelle variable pour le dialogue
const editing = ref(false);
const equipmentTypeDialog = ref(false);
const regionFilter = ref(props.filters?.region_id || null);
const importDialog = ref(false); // Pour le dialogue d'importation
const search = ref(props.filters?.search || '');
const loading = ref(false);

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'tag': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'equipment_type.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'status': { value: null, matchMode: FilterMatchMode.EQUALS },
    'region_id': { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({
    first: props.equipments.from - 1,
    rows: props.equipments.per_page || 10,
    sortField: props.queryParams?.sortField || 'created_at',
    sortOrder: props.queryParams?.sortOrder === 'desc' ? -1 : 1,
    filters: filters.value
});

const initFilters = () => {
    filters.value.global.value = null;
    filters.value.tag.constraints[0].value = null;
    filters.value.designation.constraints[0].value = null;
    filters.value['equipment_type.name'].constraints[0].value = null;
    filters.value['region.designation'].constraints[0].value = null;
    filters.value.status.value = null;
    regionFilter.value = null;
    performSearch();
};

const form = useForm({
    id: null,
    tag: '',
    designation: '',
    brand: '',
    model: '',
    serial_number: '',
    puissance: null,
    price: null,
    status: 'en stock',
    location: '',
    quantity: null,
    purchase_date: null,
    warranty_end_date: null,
    equipment_type_id: null,
    region_id: null,
    parent_id: null,
    label_id: null,
    characteristics: [],
});

const equipmentTypeForm = useForm({
    id: null,
    name: '',
    description: '',
});
const importForm = useForm({
    file: null,
});

const selectedEquipments = ref([]);
const op = ref(); // Référence à l'OverlayPanel pour la sélection de colonnes

// Colonnes pour la sélection
const allColumns = ref([
    { field: 'tag', header: computed(() => t('equipments.table.tag')) },
    { field: 'designation', header: 'Désignation' },
    { field: 'equipment_type.name', header: 'Type' },
    { field: 'brand', header: 'Marque' },
    { field: 'model', header: 'Modèle' },
    { field: 'serial_number', header: 'N° de série' },
    { field: 'region.designation', header: 'Région' },
    { field: 'location', header: 'Localisation' },
    { field: 'status', header: 'Statut' },
    { field: 'quantity', header: 'Quantité' },
    { field: 'price', header: 'Prix' },
    { field: 'purchase_date', header: 'Date d\'achat' },
    { field: 'warranty_end_date', header: 'Fin de garantie' },
    { field: 'user.name', header: 'Responsable' }
]);
const visibleColumns = ref(['tag', 'designation', 'equipment_type.name', 'region.designation', 'status', 'quantity']);

const statusOptions = computed(() => [
    { label: t('equipments.statusOptions.in_service'), value: 'en service' },
    { label: t('equipments.statusOptions.down'), value: 'en panne' },
    { label: t('equipments.statusOptions.in_maintenance'), value: 'en maintenance' },
    { label: t('equipments.statusOptions.out_of_service'), value: 'hors service' },
    { label: t('equipments.statusOptions.in_stock'), value: 'en stock' },
]);

const isChild = computed(() => !!form.parent_id);
const isStockStatus = computed(() => form.status === 'en stock');
const showPuissance = computed(() => ['en service', 'en maintenance'].includes(form.status));

const characteristicTypes = ref([
    { label: computed(() => t('equipments.characteristicTypes.text')), value: 'text' },
    { label: computed(() => t('equipments.characteristicTypes.number')), value: 'number' },
    { label: computed(() => t('equipments.characteristicTypes.date')), value: 'date' },
    { label: computed(() => t('equipments.characteristicTypes.file')), value: 'file' },
    { label: computed(() => t('equipments.characteristicTypes.boolean')), value: 'boolean' },
]);
const parentIsStock = computed(() => props.parentEquipments.find(p => p.id === form.parent_id)?.status === 'en stock');

// Watch for changes in parent_id
watch(() => form.parent_id, (newParentId) => {
    const initialCharacteristics = [
        { name: 'risk_vibration', type: 'number', value: 0 },
        { name: 'risk_chaleur', type: 'number', value: 0 },
        { name: 'risk_cycle', type: 'number', value: 0 },
        { name: 'risk_bruit', type: 'number', value: 0 },
        { name: 'risk_electrique', type: 'number', value: 0 },
        { name: '', type: 'text', value: null }
    ];

    if (newParentId) {
        const parent = props.parentEquipments.find(p => p.id === newParentId);
        if (parent) {
            // ... (autres remplissages)

            // CORRECTION CLÉ : Remplissage des caractéristiques du PARENT (gardé tel quel, car la logique est correcte)
            let parentCharacteristics = parent.characteristics || [];
            if (typeof parentCharacteristics === 'string') {
                try {
                    parentCharacteristics = JSON.parse(parentCharacteristics);
                } catch (e) {
                    console.error("Erreur d'analyse des caractéristiques parent JSON:", e);
                    parentCharacteristics = [];
                }
            }

            const entries = Array.isArray(parentCharacteristics)
                ? parentCharacteristics.map(c => [c.name, c.value, c.type]) // Si on a le type
                : Object.entries(parentCharacteristics).map(([name, value]) => [name, value]);

            form.characteristics = entries.map(([name, value, type]) => ({
                name,
                value,
                // Utiliser le type s'il est présent, sinon tenter de deviner
                type: type || (typeof value === 'number' ? 'number' : (typeof value === 'boolean' ? 'boolean' : (value && (new Date(value)).toString() !== 'Invalid Date' ? 'date' : 'text')))
            }));

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

// Watch for changes in label_id (logique gardée, mais s'assurer de bien gérer les types)
watch(() => form.label_id, (newLabelId) => {
    if (newLabelId) {
        const label = props.labels.find(l => l.id === newLabelId);
        if (label && label.label_characteristics) {
            label.label_characteristics.forEach(labelChar => {
                const exists = form.characteristics.some(existingChar => existingChar.name === labelChar.name);
                if (!exists) {
                    form.characteristics.push({
                        name: labelChar.name,
                        type: labelChar.type || 'text', // S'assurer qu'un type est toujours défini
                        value: null
                    });
                }
            });
        }
    }
}, { immediate: false });

const openNew = () => {
    form.reset();
    form.characteristics = [
        { name: 'risk_vibration', type: 'number', value: 0 },
        { name: 'risk_chaleur', type: 'number', value: 0 },
        { name: 'risk_cycle', type: 'number', value: 0 },
        { name: 'risk_bruit', type: 'number', value: 0 },
        { name: 'risk_electrique', type: 'number', value: 0 },
    ];
    editing.value = false;
    activeStep.value = 1;
    submitted.value = false;
    equipmentDialog.value = true;
};

const hideDialog = () => {
    equipmentDialog.value = false;
    submitted.value = false;
};

const editEquipment = (equipment) => {
    // Remplissage des champs de l'équipement (gardé tel quel)
    form.id = equipment.id;
    form.tag = equipment.tag;
    form.designation = equipment.designation;
    form.brand = equipment.brand;
    form.model = equipment.model;
    form.puissance = equipment.puissance;
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

    // Gestion des caractéristiques (gardé tel quel, car la logique est correcte pour la lecture)
    let characteristicsArray = [];

    if (equipment.characteristics) {
        if (typeof equipment.characteristics === 'string') {
            try {
                const parsedCharacteristics = JSON.parse(equipment.characteristics);
                if (!Array.isArray(parsedCharacteristics)) {
                    characteristicsArray = Object.entries(parsedCharacteristics).map(([name, value]) => ({
                        name,
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
            if (Array.isArray(equipment.characteristics)) {
                characteristicsArray = equipment.characteristics;
            } else {
                characteristicsArray = Object.entries(equipment.characteristics).map(([name, value]) => ({
                    name,
                    type: typeof value === 'number' ? 'number' : (typeof value === 'boolean' ? 'boolean' : (value && (new Date(value)).toString() !== 'Invalid Date' ? 'date' : 'text')),
                    value,
                }));
            }
        }
    }

    form.characteristics = characteristicsArray.map(char => {
        // CORRECTION : S'assurer que les valeurs de fichier qui sont des URL/chemins ne sont pas transformées en objet File
        // Inertia JS s'attend à un objet File pour l'upload, ou à la valeur actuelle (string) pour la rétention.
        let charValue = char.value;
        if (char.type === 'date' && charValue && typeof charValue === 'string') {
            charValue = new Date(charValue);
        }

        return {
            name: char.name,
            type: char.type || 'text',
            value: charValue
        };
    });

    if (form.characteristics.length === 0) {
        form.characteristics.push({ name: '', type: 'text', value: null });
    }

    activeStep.value = 1;
    editing.value = true;
    equipmentDialog.value = true;
};

/**
 * 🚀 Fonction CORRIGÉE : Sauvegarde de l'équipement.
 *
 * La CORRECTION CLÉ est l'utilisation de `forceFormData: true` et la gestion
 * unifiée de la transformation des données vers `FormData` pour TOUS les envois
 * (avec ou sans fichier). Cela garantit que les tableaux complexes
 * comme `characteristics` sont correctement reçus dans le Controller.
 */
const saveEquipment = () => {
    submitted.value = true;

    // Validation côté client simple
    if (form.parent_id && !form.quantity) {
        form.setError('quantity', t('equipments.validation.quantityRequiredForChild'));
        return;
    }
    // Suppression des validations requises côté client pour la création

    form.clearErrors();

    const url = editing.value ? route('equipments.update', form.id) : route('equipments.store');
    const method = editing.value ? 'post' : 'post'; // Utiliser 'post' dans la méthode .submit() pour Inertia avec FormData

    // CORRECTION : Nous allons toujours utiliser la transformation en FormData pour garantir
    // que les tableaux de caractéristiques sont correctement sérialisés, même sans fichier.
    // Et c'est OBLIGATOIRE en cas de fichier.
      console.log(form);
    form.transform((originalData) => {
        const formData = new FormData();

        // 1. Ajouter l'ID pour la mise à jour (si nécessaire) et la méthode PUT/PATCH
        if (editing.value) {
            formData.append('_method', 'PUT'); // Correction pour simuler la méthode PUT/PATCH
        }

        // 2. Ajouter les champs simples du formulaire (hors caractéristiques)
        for (const key in originalData) {
            // Ignorer l'ID et les caractéristiques ici, car nous les traitons séparément
            if (key === 'id' || key === 'characteristics') continue;

            const value = originalData[key];

            if (value !== null && value !== undefined) {
                // Traiter les dates: convertir l'objet Date en chaîne 'YYYY-MM-DD'
                if (value instanceof Date) {
                    formData.append(key, value.toISOString().split('T')[0]);
                } else if (key !== 'id') {
                    // Ajouter toute autre valeur simple
                    formData.append(key, value);
                }
            }
        }

        // 3. Ajouter les caractéristiques (tableaux)
        originalData.characteristics.forEach((char, index) => {
            formData.append(`characteristics[${index}][name]`, char.name || '');
            formData.append(`characteristics[${index}][type]`, char.type || 'text');

            if (char.type === 'file' && char.value instanceof File) {
                // C'est un nouveau fichier à uploader
                formData.append(`characteristics[${index}][value]`, char.value);
            } else if (char.value !== null && char.value !== undefined) {
                // C'est une valeur non-fichier (texte, nombre, date) ou le chemin d'un fichier existant
                formData.append(`characteristics[${index}][value]`, char.value);
            }
            // Si char.value est null ou undefined, il n'est pas ajouté, ce qui est généralement correct pour les champs vides.
        });

        return formData; // Retourner le FormData
    })

    // 4. Exécuter l'envoi
    .submit('post', url, { // On utilise 'post' car nous avons simulé 'PUT' avec _method
        onSuccess: () => {
            hideDialog();
            toast.add({ severity: 'success', summary: t('equipments.toast.success'), detail: editing.value ? t('equipments.toast.updateSuccess') : t('equipments.toast.createSuccess'), life: 3000 });
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de l'équipement", errors);
            if (errors.quantity && errors.quantity.includes('La quantité du parent est insuffisante ou le parent n\'est pas en stock.')) {
                insufficientQuantityDialog.value = true; // Ouvrir le dialogue spécifique
            } else {
                 insufficientQuantityDialog.value = true;
                // CORRECTION: Utiliser les erreurs du formulaire pour afficher un message d'erreur plus détaillé.
                const errorDetail = Object.values(errors).flat().join(' ; ');
                toast.add({ severity: 'error', summary: t('equipments.toast.saveError'), detail: errorDetail || t('equipments.toast.error'), life: 5000 });
            }
        },
        forceFormData: true, // IMPORTANT: Force l'envoi en multipart/form-data

    });
};


// Le reste du code est conservé car il est fonctionnel ou n'est pas la cause de l'erreur
const deleteEquipment = (equipment) => {
 confirm.require({
 message: t('equipments.confirm.deleteMessage', { name: equipment.tag || equipment.designation }),
 header: t('equipments.confirm.deleteHeader'),
 icon: 'pi pi-info-circle',
 rejectClass: 'p-button-secondary p-button-outlined',
 rejectLabel: t('equipments.confirm.cancel'),
 acceptLabel: t('equipments.confirm.delete'),
 acceptClass: 'p-button-danger',
 accept: () => {
 router.delete(route('equipments.destroy', equipment.id), {
 onSuccess: () => {
 toast.add({ severity: 'success', summary: t('equipments.toast.success'), detail: t('equipments.toast.deleteSuccess'), life: 3000 });
 },
 onError: () => {
 toast.add({ severity: 'error', summary: t('equipments.toast.error'), detail: t('equipments.toast.deleteError'), life: 3000 });
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

const confirmDeleteSelected = () => {
    confirm.require({
        message: t('equipments.confirm.bulkDeleteMessage'),
        header: t('equipments.confirm.deleteHeader'),
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: t('equipments.confirm.cancel'),
        acceptLabel: t('equipments.confirm.delete'),
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteSelectedEquipments();
        },
    });
};

const deleteSelectedEquipments = () => {
    console.log(selectedEquipments);
    router.post(route('equipments.bulkdestroy'), {

            ids: selectedEquipments.value.map(e => e.id),

        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('equipments.toast.success'), detail: t('equipments.toast.bulkDeleteSuccess'), life: 3000 });
            selectedEquipments.value = null;
            deleteEquipmentsDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: t('equipments.toast.error'), detail: t('equipments.toast.bulkDeleteError'), life: 3000 });
        }
    });
};

const hideDeleteEquipmentsDialog = () => {
    deleteEquipmentsDialog.value = false;
};

const saveEquipmentType = () => {
    equipmentTypeForm.post(route('equipment-types.store'), {
        onSuccess: () => {
            hideEquipmentTypeDialog(); // CORRECTION: Utiliser hideEquipmentTypeDialog
            toast.add({ severity: 'success', summary: t('equipments.toast.success'), detail: t('equipments.toast.typeCreateSuccess'), life: 3000 });
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du type d'équipement", errors);
            const errorDetail = Object.values(errors).flat().join(' ; ');
            toast.add({ severity: 'error', summary: t('equipments.toast.error'), detail: errorDetail || t('equipments.toast.typeSaveError'), life: 3000 });
        },

    });
};

const addCharacteristic = () => {
    form.characteristics.push({ name: '', type: 'text', value: null });
};

const removeCharacteristic = (index) => {
    form.characteristics.splice(index, 1);
};
// 1. Fonction de mise à jour API
const updateParentQuantityApi = (parentId, newQuantity, onFinishCallback) => {
    router.put(route('equipments.update-quantity', parentId),
        { quantity: newQuantity },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: t('common.success'),
                    detail: t('equipments.toast.parentQuantityUpdated'),
                    life: 3000
                });
                // On n'exécute la suite QUE si l'API a réussi
                if (onFinishCallback) onFinishCallback();
            },
            onError: (errors) => {
                console.error("Erreurs validation:", errors);
                toast.add({
                    severity: 'error',
                    summary: t('common.error'),
                    detail: t('equipments.toast.parentQuantityUpdateError'),
                    life: 3000
                });
            }
        }
    );
};

// 2. Fonction de traitement du dialogue
const insufficienQty = () => {
    const parent = props.parentEquipments.find(p => p.id === form.parent_id);

    if (parent) {
        const currentParentQty = parseFloat(parent.quantity) || 0;
        const movementQty = parseFloat(form.quantity) || 0;

        // Calcul : On déduit la quantité du stock parent
        const finalQuantity = movementQty;

        // On passe saveEquipment comme "callback" pour qu'il s'exécute après le succès
        updateParentQuantityApi(form.parent_id, finalQuantity, () => {
            insufficientQuantityDialog.value = false;
            saveEquipment();
        });
    } else {
        // Si pas de parent, on ferme et on tente de sauvegarder quand même
        insufficientQuantityDialog.value = false;
        saveEquipment();
    }
};

const loadLazyData = () => {
    loading.value = true;
    const queryParams = {
        ...lazyParams.value,
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
        per_page: lazyParams.value.rows,
        sortOrder: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
        search: filters.value.global.value,
        region_id: regionFilter.value,
    };

    router.get(route('equipments.index'), queryParams, {
        preserveState: true,
        onFinish: () => { loading.value = false; }
    });
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData();
};

let timeoutId = null;
const performSearch = () => {
    loadLazyData();
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

const dialogTitle = computed(() => editing.value ? t('equipments.dialog.editTitle') : t('equipments.dialog.createTitle'));

const bulkDeleteButtonIsDisabled = computed(() => !selectedEquipments.value || selectedEquipments.value.length < 2);
const showBulkDeleteButton = computed(() => selectedEquipments.value && selectedEquipments.value.length >= 2);

const deleteButtonLabel = computed(() => {
    if (selectedEquipments.value && selectedEquipments.value.length > 0) {
        return t('equipments.deleteSelected', { count: selectedEquipments.value.length });
    }
    return t('equipments.deleteSelectedSingle');
});

const equipmentStats = computed(() => {
    const total = props.equipments.data.length;
    const in_service = props.equipments.data.filter(e => e.status === 'en service').length;
    const in_stock = props.equipments.data.filter(e => e.status === 'en stock').length;
    const down = props.equipments.data.filter(e => e.status === 'en panne').length;
    return { total, in_service, in_stock, down };
});
const openImportDialog = () => {
 importForm.reset();
 importDialog.value = true;
};

const importEquipments = (event) => {
    const file = event.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    // Pas de region_id pour les équipements, mais on pourrait ajouter d'autres champs si nécessaire

    router.post(route('equipments.import'), formData, {
        onSuccess: () => {
            importDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('equipments.toast.importStarted'), life: 3000 });
        },
        onError: (errors) => {
            console.error("Erreur lors de l'importation des équipements", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l\'envoi du fichier.', life: 3000 });
        }
    });
}
// Exposer les fonctions et variables nécessaires au template
defineExpose({
    openNew, hideDialog, editEquipment, saveEquipment, deleteEquipment,
    openNewEquipmentType, hideEquipmentTypeDialog, saveEquipmentType,
    addCharacteristic, removeCharacteristic, performSearch, exportCSV, getStatusSeverity, showPuissance, confirmDeleteSelected, deleteSelectedEquipments, hideDeleteEquipmentsDialog, bulkDeleteButtonIsDisabled, deleteButtonLabel, showBulkDeleteButton,
    dt, form, equipmentDialog, equipmentTypeDialog, submitted, editing, search,
    statusOptions, characteristicTypes, isChild, isStockStatus, dialogTitle,
    parentIsStock, equipmentTypeForm
});
</script>


<template>
    <AppLayout :title="t('equipments.title')">
        <Head :title="t('equipments.headTitle')" />
        <Toast />
        <ConfirmDialog />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                 <div class="flex items-center gap-4">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200 text-white text-2xl">
 <i class="pi pi-cog"></i>
                    </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                        {{ t('equipments.title') }} <span class="text-primary-600">GMAO</span>
                    </h1>
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('equipments.subtitle') }}</p>
                </div>
                </div>
                <div class="flex gap-2">
                    <!-- NOUVEAU: Bouton d'importation -->
                    <Button :label="t('common.import')" icon="pi pi-upload" severity="secondary" outlined @click="openImportDialog" />
                    <Button :label="t('equipments.addNew')" icon="pi pi-plus"
                            class=" shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-box text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ equipmentStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Équipements</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-check-circle text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ equipmentStats.in_service }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">En Service</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center"><i class="pi pi-exclamation-triangle text-2xl text-red-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ equipmentStats.down }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">En Panne</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-inbox text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ equipmentStats.in_stock }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">En Stock</div>
                    </div>
                </div>
            </div>

            <!-- NOUVEAU: Affichage des erreurs d'importation -->
            <div v-if="import_errors && import_errors.length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <h3 class="font-bold">Erreurs lors de la dernière importation :</h3>
                <ul class="list-disc list-inside mt-2 text-sm">
                    <li v-for="(error, index) in import_errors" :key="index">{{ error }}</li>
                </ul>
            </div>



            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="equipments.data" ref="dt" dataKey="id" v-model:selection="selectedEquipments" :paginator="true"
                           :rows="equipments.per_page" :rowsPerPageOptions="[10, 25, 50, 100]"
                           v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['tag', 'designation', 'equipment_type.name', 'region.designation', 'status']"
                           paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                           :currentPageReportTemplate="t('equipments.table.report')"
                           :lazy="true" @page="onPage($event)" @sort="onPage($event)"
                           :totalRecords="equipments.total" :loading="loading"
                           v-model:first="lazyParams.first"
                           :sortField="lazyParams.sortField" :sortOrder="lazyParams.sortOrder"
                           class="p-datatable-sm quantum-table">

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                   <template #header>
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">

        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">

            <Dropdown
                v-model="regionFilter"
                :options="regions"
                optionLabel="designation"
                optionValue="id"
                :placeholder="t('equipments.filterByRegion') || 'Filtrer par région'"
                showClear
                @change="performSearch"
                class="w-full md:w-64 h-11 !rounded-2xl !border-slate-200 !bg-slate-50/50 focus:!ring-2 focus:!ring-primary-500/20 focus:!bg-white transition-all duration-200"
            />

            <IconField iconPosition="left" class="w-full md:w-80">
                <InputIcon class="pi pi-search text-slate-400" />
                <InputText
                    v-model="filters['global'].value"
                    @input="onPage(lazyParams)"
                    :placeholder="t('equipments.searchPlaceholder')"
                    class="w-full h-11 rounded-2xl border-slate-200 bg-slate-50/50 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-all duration-200"
                />
            </IconField>
        </div>

        <div class="flex items-center gap-2">
            <Button
                icon="pi pi-filter-slash"
                outlined
                severity="secondary"
                @click="initFilters"
                class="rounded-xl h-11"
                v-tooltip.bottom="t('common.resetFilters')"
            />
            <Button
                icon="pi pi-download"
                text
                rounded
                severity="secondary"
                @click="exportCSV"
                class="h-11 w-11"
                v-tooltip.bottom="t('common.export')"
            />
            <Button
                icon="pi pi-cog"
                text
                rounded
                severity="secondary"
                @click="op.toggle($event)"
                class="h-11 w-11"
                v-tooltip.bottom="'Colonnes'"
            />
        </div>

    </div>
</template>

                    <Column v-if="visibleColumns.includes('tag')" field="tag" :header="t('equipments.table.tag')" :sortable="true" style="min-width: 8rem;">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-md">{{ data.tag }}</span>
                        </template>
                        <template #filter="{ filterModel }"><InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('equipments.table.filter.tag')" /></template>
                    </Column>

                    <Column v-if="visibleColumns.includes('designation')" field="designation" :header="t('equipments.table.designation')" :sortable="true" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <div class="font-bold text-slate-800 tracking-tight cursor-pointer" @click="editEquipment(data)">{{ data.designation }}</div>
                        </template>
                        <template #filter="{ filterModel }"><InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('equipments.table.filter.designation')" /></template>
                    </Column>

                    <Column v-if="visibleColumns.includes('equipment_type.name')" field="equipment_type.name" :header="t('equipments.table.type')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            <span v-if="slotProps.data.parent_id && slotProps.data.parent">{{ slotProps.data.parent.equipment_type?.name }}</span>
                            <span v-else>{{ slotProps.data.equipment_type?.name }}</span>
                        </template>
                        <template #filter="{ filterModel }"><InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('equipments.table.filter.type')" /></template>
                    </Column>

                    <Column v-if="visibleColumns.includes('brand')" field="brand" :header="t('equipments.table.brand')" :sortable="true" style="min-width: 8rem;"></Column>
                    <Column v-if="visibleColumns.includes('model')" field="model" :header="t('equipments.table.model')" :sortable="true" style="min-width: 8rem;"></Column>

                    <Column v-if="visibleColumns.includes('serial_number')" field="serial_number" header="N° de série" :sortable="true" style="min-width: 10rem;"></Column>

                    <Column v-if="visibleColumns.includes('region.designation')" field="region.designation" :header="t('equipments.table.region')" :sortable="true" style="min-width: 8rem;">
                        <template #body="slotProps">{{ slotProps.data.region?.designation }}</template>
                        <template #filter="{ filterModel }"><InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('equipments.table.filter.region')" /></template>
                    </Column>

                    <Column v-if="visibleColumns.includes('location')" field="location" header="Localisation" :sortable="true" style="min-width: 10rem;"></Column>

                    <Column v-if="visibleColumns.includes('status')" field="status" :header="t('equipments.table.status')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            <Tag :value="t(`equipments.statusOptions.${slotProps.data.status.replace(/ /g, '_')}`, slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" class="uppercase text-[9px] px-2" />
                        </template>
                        <template #filter="{ filterModel }"><Dropdown v-model="filterModel.value" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('equipments.table.filter.status')" class="p-column-filter" showClear /></template>
                    </Column>

                    <Column v-if="visibleColumns.includes('quantity')" field="quantity" :header="t('equipments.table.quantity')" :sortable="true" style="min-width: 8rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.quantity }}
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('price')" field="price" header="Prix" :sortable="true" style="min-width: 8rem;">
                        <template #body="{ data }">
                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(data.price) }}
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('purchase_date')" field="purchase_date" header="Date d'achat" :sortable="true" style="min-width: 10rem;">
                        <template #body="{ data }">
                            {{ data.purchase_date ? new Date(data.purchase_date).toLocaleDateString() : '' }}
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('warranty_end_date')" field="warranty_end_date" header="Fin de garantie" :sortable="true" style="min-width: 10rem;">
                        <template #body="{ data }">
                            {{ data.warranty_end_date ? new Date(data.warranty_end_date).toLocaleDateString() : '' }}
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('user.name')" field="user.name" header="Responsable" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.user?.name }}
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editEquipment(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteEquipment(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" v-tooltip.top="'Supprimer'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>





<Dialog
    v-model:visible="equipmentDialog"
    modal
    :header="false"
    :closable="false"
    :style="{ width: '95vw', maxWidth: '1150px' }"
    :contentStyle="{ maxHeight: '90vh', overflow: 'auto' }"
    :pt="{
        root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl bg-white' },
        mask: { style: 'backdrop-filter: blur(8px)' }
    }"
>
    <div class="px-8 py-6 bg-slate-900 rounded-xl text-white flex justify-between items-center relative z-50">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-primary-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-primary-500/20">
                <i class="pi pi-database text-2xl"></i>
            </div>
            <div>
                <h4 class="text-lg font-black text-slate-100 m-0 uppercase tracking-tight">
                    {{ form.id ? 'Fiche Asset Expert' : 'Nouvel Enregistrement Asset' }}
                </h4>
                <p class="text-xs text-slate-500 m-0 font-bold uppercase tracking-widest">Master Data & Cycle de Vie Technique</p>
            </div>
        </div>
        <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
    </div>

    <div class="p-8 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

            <div class="md:col-span-7 space-y-6">

                <div class="p-7 bg-slate-50 rounded-[1rem] border border-slate-100">
                    <div class="flex items-center gap-2 mb-6 italic">
                        <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                        <span class="text-[11px] font-black text-slate-500 uppercase">Identité & Origine</span>
                    </div>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Unité Parent (Hierarchie)</label>
                            <Dropdown v-model="form.parent_id" :options="parentEquipments" optionLabel="designation" optionValue="id" filter class="w-full rounded-xl border-slate-200" placeholder="Aucun parent" />
                        </div>

                        <div class="col-span-4 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Tag / Code Asset</label>
                            <InputText v-model="form.tag" class="w-full py-3 rounded-xl border-slate-200 font-mono text-primary-600 font-bold" placeholder="TAG-000" />
                        </div>

                        <div class="col-span-8 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Désignation complète</label>
                            <InputText v-model="form.designation" class="w-full py-3 rounded-xl border-slate-200 font-bold" placeholder="Nom de l'équipement" />
                        </div>

                        <div class="col-span-6 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Marque (Brand)</label>
                            <InputText v-model="form.brand" class="w-full py-3 rounded-xl border-slate-200" />
                        </div>

                        <div class="col-span-6 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Modèle</label>
                            <InputText v-model="form.model" class="w-full py-3 rounded-xl border-slate-200" />
                        </div>

                        <div class="col-span-6 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">S/N (Serial Number)</label>
                            <InputText v-model="form.serial_number" class="w-full py-3 rounded-xl border-slate-200 font-mono" />
                        </div>

                        <div class="col-span-6 flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Quantité en stock</label>
                            <InputNumber v-model="form.quantity" showButtons :min="0" class="w-full" inputClass="py-3 rounded-xl border-slate-200" />
                        </div>
                    </div>
                </div>

                <div class="p-7 bg-white rounded-[1rem] border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-6 italic">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span class="text-[11px] font-black text-slate-500 uppercase">Localisation & Propriété</span>
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Région / Site</label>
                            <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" class="rounded-xl border-slate-200" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Localisation précise</label>
                            <InputText v-model="form.location" placeholder="Ex: Hangar A / Zone 4" class="py-3 rounded-xl border-slate-200" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Responsable (User)</label>
                            <Dropdown v-model="form.user_id" :options="users" optionLabel="name" optionValue="id" filter class="rounded-xl border-slate-200" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Étiquette / Label</label>
                            <Dropdown v-model="form.label_id" :options="labels" optionLabel="name" optionValue="id" class="rounded-xl border-slate-200" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <span class="text-[11px] font-black text-slate-500 uppercase italic">Spécifications Techniques Additionnelles</span>
                        <Button icon="pi pi-plus" label="Ajouter" @click="addCharacteristic" text class="text-[10px] font-black text-primary-600 uppercase" />
                    </div>
                    <div class="p-3 bg-slate-50 rounded-[1rem] border-2 border-dashed border-slate-200 max-h-[250px] overflow-y-auto">
                        <div v-for="(char, index) in form.characteristics" :key="index" class="flex gap-3 p-2 mb-2 bg-white rounded-xl items-center shadow-sm border border-slate-100">
                            <InputText v-model="char.name" placeholder="Nom (ex: Voltage)" class="flex-1 text-xs border-none font-bold" />
                            <InputText v-model="char.value" placeholder="Valeur" class="flex-1 text-xs border-none" />
                            <Button icon="pi pi-trash" severity="danger" text @click="removeCharacteristic(index)" class="p-button-sm" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-5 space-y-6">

                <div class="p-8 bg-primary-950 rounded-[1rem] text-white shadow-xl relative overflow-hidden">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-primary-400 mb-6 italic">Statut Opérationnel</h4>
                    <div class="space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[9px] font-black opacity-40 uppercase">État actuel</label>
                            <Dropdown v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full bg-white/5 border-white/10 text-white rounded-xl" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[9px] font-black opacity-40 uppercase">Catégorie d'équipement</label>
                            <Dropdown v-model="form.equipment_type_id" :options="equipmentTypes" optionLabel="name" optionValue="id" class="w-full bg-white/5 border-white/10 text-white rounded-xl" />
                        </div>
                    </div>
                </div>

                <div class="p-7 bg-white rounded-[1rem] border border-slate-100 shadow-sm space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Prix d'Achat</label>
                            <InputNumber v-model="form.price" mode="currency" currency="USD" class="w-full" inputClass="py-3 bg-slate-50 border-none rounded-xl font-bold text-slate-700" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Puissance</label>
                            <InputNumber v-model="form.puissance" suffix=" kW" class="w-full" inputClass="py-3 bg-slate-50 border-none rounded-xl font-bold text-slate-700" />
                        </div>
                    </div>
                </div>

                <div class="p-7 bg-slate-900 rounded-[1rem] text-white space-y-5 shadow-2xl">
                    <div class="flex items-center gap-2 mb-2">
                         <i class="pi pi-calendar text-primary-400 text-xs"></i>
                         <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Chronologie</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[9px] font-black opacity-40 uppercase">Date d'Acquisition</label>
                        <Calendar v-model="form.purchase_date" class="w-full" inputClass="bg-white/5 border-none text-white rounded-xl py-3 text-xs" showIcon />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[9px] font-black opacity-40 uppercase">Expiration Garantie</label>
                        <Calendar v-model="form.warranty_end_date" class="w-full" inputClass="bg-white/5 border-none text-white rounded-xl py-3 text-xs" showIcon />
                    </div>
                </div>

            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-10 py-6 bg-slate-50 border-t border-slate-100">
            <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="font-bold uppercase text-[10px] tracking-widest" />
            <div class="flex gap-4">
                <Button :label="form.id ? 'Mettre à jour' : 'Enregistrer Asset'"
                        icon="pi pi-check-circle"

                        class="px-12 h-14 rounded-2xl shadow-xl shadow-primary-200 font-black uppercase tracking-widest text-xs"
                        @click="saveEquipment" :loading="form.processing" />
            </div>
        </div>
    </template>
</Dialog>

<!-- Nouveau Dialogue pour Quantité Insuffisante -->
<Dialog
    v-model:visible="insufficientQuantityDialog"
    modal
    :header="false"
    :closable="false"
    :style="{ width: '90vw', maxWidth: '500px' }"
    :pt="{
        root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl bg-white' },
        mask: { style: 'backdrop-filter: blur(8px)' }
    }"
>
    <div class="px-8 py-6 bg-red-600 text-white flex justify-between items-center relative z-50">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-white shadow-inner">
                <i class="pi pi-exclamation-circle text-2xl animate-pulse"></i>
            </div>
            <div>
                <h4 class="font-black text-white m-0 uppercase tracking-tight leading-none">
                    {{ t('equipments.insufficientQuantityDialog.title') }}
                </h4>
                <p class="text-[10px] text-red-100 m-0 mt-1 font-bold uppercase tracking-widest opacity-80">
                    {{ t('equipments.insufficientQuantityDialog.subtitle') || 'Alerte de Stock' }}
                </p>
            </div>
        </div>
        <Button
            icon="pi pi-times"
            variant="text"
            rounded
            @click="insufficientQuantityDialog = false"
            class="text-white hover:bg-white/10"
        />
    </div>
    <div class="p-8 bg-white space-y-6">
        <div class="flex flex-col items-center text-center gap-4">
            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center">
                <i class="pi pi-box text-3xl text-red-400 opacity-50"></i>
            </div>

            <div class="space-y-2">
                <p class="text-slate-900 font-bold text-base leading-relaxed">
                    {{ t('equipments.insufficientQuantityDialog.message') }}
                </p>
                <p class="text-slate-500 text-xs italic font-medium">
                    L'exécution de ce mouvement pourrait entraîner un stock négatif ou une rupture de service.
                </p>
            </div>
        </div>

        <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl flex gap-3 items-start">
            <i class="pi pi-info-circle text-amber-600 mt-1"></i>
            <p class="text-[11px] font-bold text-amber-800 leading-tight uppercase tracking-tight">
                Voulez-vous forcer cette opération ou annuler pour régulariser l'inventaire ?
            </p>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-8 py-6 bg-slate-50 border-t border-slate-100">
            <Button
                :label="t('equipments.insufficientQuantityDialog.cancel')"
                icon="pi pi-arrow-left"
                text
                severity="secondary"
                @click="insufficientQuantityDialog = false"
                class="font-black uppercase text-[10px] tracking-widest"
            />

            <Button
                :label="t('equipments.insufficientQuantityDialog.performMovement')"
                icon="pi pi-bolt"
                severity="danger"
                class="px-8 h-12 rounded-2xl shadow-xl shadow-red-100 font-black uppercase tracking-widest text-xs"
                @click="insufficienQty"
            />
        </div>
    </template>
</Dialog>


<Dialog
    v-model:visible="equipmentTypeDialog"
    modal
    :header="false"
    :closable="false"
    :style="{ width: '90vw', maxWidth: '500px' }"
    :contentStyle="{ maxHeight: '80vh', overflow: 'hidden' }"
    :pt="{
        root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl bg-white' },
        mask: { style: 'backdrop-filter: blur(8px)' }
    }"
>
    <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-900/20">
                <i class="pi pi-tags text-xl"></i>
            </div>
            <div>
                <h4 class="font-black text-slate-100 m-0 uppercase tracking-tight">
                    {{ t('equipments.typeDialog.title') }}
                </h4>
                <p class="text-[10px] text-slate-500 m-0 font-bold uppercase tracking-widest">Configuration des catégories</p>
            </div>
        </div>
        <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideEquipmentTypeDialog" class="text-white hover:bg-white/10" />
    </div>

    <div class="p-8 bg-white space-y-6">
        <div class="flex flex-col gap-2">
            <label for="equipment_type_name" class="text-xs font-black text-slate-500 uppercase ml-1">
                {{ t('equipments.typeDialog.name') }}
            </label>
            <IconField iconPosition="left">
                <InputIcon class="pi pi-tag" />
                <InputText
                    id="equipment_type_name"
                    v-model.trim="equipmentTypeForm.name"
                    required
                    autofocus
                    :placeholder="t('equipments.typeDialog.name')"
                    class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50 font-bold"
                    :class="{ 'p-invalid': equipmentTypeForm.errors.name }"
                />
            </IconField>
            <small v-if="equipmentTypeForm.errors.name" class="text-red-500 font-bold italic text-[10px] ml-1">
                {{ equipmentTypeForm.errors.name }}
            </small>
        </div>

        <div class="flex flex-col gap-2">
            <label for="equipment_type_description" class="text-xs font-black text-slate-500 uppercase ml-1">
                {{ t('equipments.typeDialog.description') }}
            </label>
            <textarea
                id="equipment_type_description"
                v-model.trim="equipmentTypeForm.description"
                rows="4"
                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-primary-50 transition-all text-sm outline-none"
                :placeholder="t('equipments.typeDialog.description')"
            ></textarea>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
            <Button
                :label="t('equipments.typeDialog.cancel')"
                icon="pi pi-times"
                text
                severity="secondary"
                @click="hideEquipmentTypeDialog"
                class="font-bold uppercase text-[10px] tracking-widest"
            />
            <Button
                :label="t('equipments.typeDialog.create')"
                icon="pi pi-check-circle"
                severity="primary"
                class="px-8 h-12 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                @click="saveEquipmentType"
                :loading="equipmentTypeForm.processing"
            />
        </div>
    </template>
</Dialog>

<!-- NOUVEAU: Boîte de dialogue pour l'importation -->
<Dialog v-model:visible="importDialog" modal header="Importer des Équipements" :style="{ width: '40rem' }">
    <div class="p-fluid">
        <p class="mb-4 text-sm text-slate-600">
            Importez des équipements via un fichier CSV ou Excel. Assurez-vous que votre fichier respecte la structure suivante : <br>
            <code class="bg-slate-100 p-1 rounded text-xs">Tag, Désignation, Type, Région, Statut</code>
        </p>
        <FileUpload
                        name="file"
                        @select="onFileSelect"
                        :multiple="false"
                        accept=".csv,.txt,.xls,.xlsx"
                        :maxFileSize="1000000"
                        chooseLabel="Choisir un fichier"
                        :auto="true"
                        customUpload
                        @uploader="importEquipments"
                    >
                        <template #empty>
                            <p>Glissez-déposez un fichier ici pour le téléverser.</p>
                        </template>
                    </FileUpload>
    </div>
</Dialog>
    </AppLayout>
</template>

<style scoped>
/* STYLE V11 CUSTOM TOKENS */
.p-button-primary {
    background: #4f46e5;
    border: none;
    color: white;
    font-weight: 700;
    border-radius: 12px;
}

.card-v11 :deep(.p-datatable-thead > tr > th) {
    background: #fdfdfd;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.card-v11 :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s;
}

.card-v11 :deep(.p-datatable-tbody > tr:hover) {
    background: #f8faff !important;
}
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
