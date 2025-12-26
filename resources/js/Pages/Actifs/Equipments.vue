<script setup>
import Divider from 'primevue/divider'
import { ref, computed, watch, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useConfirm } from 'primevue/useconfirm';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';


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
});
const { t } = useI18n();

const toast = useToast();
const confirm = useConfirm();
const equipmentDialog = ref(false);
const deleteEquipmentsDialog = ref(false);
const submitted = ref(false);
const activeStep = ref(1);
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
const selectedEquipments = ref([]);
const op = ref(); // Référence à l'OverlayPanel pour la sélection de colonnes

// Colonnes pour la sélection
const allColumns = ref([
    { field: 'tag', header: computed(() => t('equipments.table.tag')) },
    { field: 'designation', header: computed(() => t('equipments.table.designation')) },
    { field: 'equipmentType.name', header: computed(() => t('equipments.table.type')) },
    { field: 'brand', header: computed(() => t('equipments.table.brand')) },
    { field: 'model', header: computed(() => t('equipments.table.model')) },
    { field: 'region.designation', header: computed(() => t('equipments.table.region')) },
    { field: 'status', header: computed(() => t('equipments.table.status')) },
    { field: 'quantity', header: computed(() => t('equipments.table.quantity')) },
]);
const visibleColumns = ref(allColumns.value.map(col => col.field)); // Affiche toutes les colonnes par défaut

const statusOptions = ref([
    { label: computed(() => t('equipments.statusOptions.in_service')), value: 'en service' },
    { label: computed(() => t('equipments.statusOptions.down')), value: 'en panne' },
    { label: computed(() => t('equipments.statusOptions.in_maintenance')), value: 'en maintenance' },
    { label: computed(() => t('equipments.statusOptions.out_of_service')), value: 'hors service' },
    { label: computed(() => t('equipments.statusOptions.in_stock')), value: 'en stock' },
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
    const initialCharacteristics = [{ name: '', type: 'text', value: null }];

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
    form.characteristics = [{ name: '', type: 'text', value: null }];
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
    // Suppression des validations requises côté client pour la création

    form.clearErrors();

    const url = editing.value ? route('equipments.update', form.id) : route('equipments.store');
    const method = editing.value ? 'post' : 'post'; // Utiliser 'post' dans la méthode .submit() pour Inertia avec FormData

    // CORRECTION : Nous allons toujours utiliser la transformation en FormData pour garantir
    // que les tableaux de caractéristiques sont correctement sérialisés, même sans fichier.
    // Et c'est OBLIGATOIRE en cas de fichier.

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
            // CORRECTION: Utiliser les erreurs du formulaire pour afficher un message d'erreur plus détaillé.
            const errorDetail = Object.values(errors).flat().join(' ; ');
            toast.add({ severity: 'error', summary: t('equipments.toast.saveError'), detail: errorDetail || t('equipments.toast.error'), life: 5000 });
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

const dialogTitle = computed(() => editing.value ? t('equipments.dialog.editTitle') : t('equipments.dialog.createTitle'));

const bulkDeleteButtonIsDisabled = computed(() => !selectedEquipments.value || selectedEquipments.value.length < 2);
const showBulkDeleteButton = computed(() => selectedEquipments.value && selectedEquipments.value.length >= 2);

const deleteButtonLabel = computed(() => {
    if (selectedEquipments.value && selectedEquipments.value.length > 0) {
        return t('equipments.deleteSelected', { count: selectedEquipments.value.length });
    }
    return t('equipments.deleteSelectedSingle');
});
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
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                        {{ t('equipments.title') }} <span class="text-indigo-600">GMAO</span>
                    </h1>
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('equipments.subtitle') }}</p>
                </div>
                <div class="flex gap-2">
                    <Button :label="t('equipments.addNew')" icon="pi pi-plus"
                            class="p-button-indigo shadow-lg shadow-indigo-200" @click="openNew" />
                </div>
            </div>

            <div class="bg-white/70 backdrop-blur-md border border-white shadow-sm rounded-2xl p-4 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex gap-2 items-center">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" :placeholder="t('equipments.searchPlaceholder')"
                                       class="p-inputtext-sm border-none bg-slate-100 rounded-xl w-64" @input="performSearch" />
                        </IconField>
                        <Button v-if="showBulkDeleteButton" :label="deleteButtonLabel" icon="pi pi-trash" severity="danger"
                                class="p-button-sm" @click="confirmDeleteSelected" :disabled="bulkDeleteButtonIsDisabled" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Button icon="pi pi-download" :label="t('equipments.export')"
                                class="p-button-text p-button-secondary p-button-sm font-bold" @click="exportCSV" />
                        <Button icon="pi pi-columns" class="p-button-text p-button-secondary" @click="op.toggle($event)" />
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="equipments.data" ref="dt" dataKey="id" v-model:selection="selectedEquipments" :paginator="true" :rows="10"
                           paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                           :currentPageReportTemplate="t('equipments.table.report')"
                           class="p-datatable-sm quantum-table">

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-if="visibleColumns.includes('tag')" field="tag" :header="t('equipments.table.tag')" :sortable="true" style="min-width: 8rem;">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-md">{{ data.tag }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('designation')" field="designation" :header="t('equipments.table.designation')" :sortable="true" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <div class="font-bold text-slate-800 tracking-tight cursor-pointer" @click="editEquipment(data)">{{ data.designation }}</div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('equipmentType.name')" field="equipmentType.name" :header="t('equipments.table.type')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            <span v-if="slotProps.data.parent_id && slotProps.data.parent">{{ slotProps.data.parent.equipment_type?.name }}</span>
                            <span v-else>{{ slotProps.data.equipment_type?.name }}</span>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('brand')" field="brand" :header="t('equipments.table.brand')" :sortable="true" style="min-width: 8rem;"></Column>
                    <Column v-if="visibleColumns.includes('model')" field="model" :header="t('equipments.table.model')" :sortable="true" style="min-width: 8rem;"></Column>

                    <Column v-if="visibleColumns.includes('region.designation')" field="region.designation" :header="t('equipments.table.region')" :sortable="true" style="min-width: 8rem;">
                        <template #body="slotProps">{{ slotProps.data.region?.designation }}</template>
                    </Column>

                    <Column v-if="visibleColumns.includes('status')" field="status" :header="t('equipments.table.status')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            <Tag :value="t(`equipments.statusOptions.${slotProps.data.status.replace(/ /g, '_')}`)" :severity="getStatusSeverity(slotProps.data.status)" class="uppercase text-[9px] px-2" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('quantity')" field="quantity" :header="t('equipments.table.quantity')" :sortable="true" style="min-width: 8rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.status === 'en stock' ? slotProps.data.quantity : 'N/A' }}
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editEquipment(data)" class="!text-slate-400 hover:!bg-indigo-50 hover:!text-indigo-600 transition-all" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteEquipment(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" v-tooltip.top="'Supprimer'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <OverlayPanel ref="op" class="quantum-overlay">
            <div class="p-2 space-y-3">
                <span class="text-[10px] font-black uppercase text-slate-400 block border-b pb-2">Colonnes actives</span>
                <MultiSelect v-model="visibleColumns" :options="allColumns" optionLabel="header" optionValue="field"
                             display="chip" class="w-64 quantum-multiselect" />
            </div>
        </OverlayPanel>



<Dialog
    v-model:visible="equipmentDialog"
    modal
    :header="false"
    class="quantum-dialog w-full max-w-6xl shadow-2xl"
    :pt="{
        root: { class: 'border-none rounded-[2rem] overflow-hidden bg-white' },
        mask: { style: 'backdrop-filter: blur(6px); background: rgba(0,0,0,0.4)' },
        content: { class: 'p-0' }
    }"
>
    <div class="p-6 bg-slate-950 text-white flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="bg-emerald-500 p-3 rounded-2xl shadow-lg shadow-emerald-500/20">
                <i class="pi pi-database text-xl"></i>
            </div>
            <div>
                <h2 class="text-lg font-black uppercase tracking-tight">{{ form.id ? 'Fiche Asset Expert' : 'Nouvel Enregistrement Asset' }}</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Configuration Master Data & Cycle de Vie</p>
            </div>
        </div>
        <Button icon="pi pi-times" text rounded severity="secondary" @click="hideDialog" class="text-white opacity-50 hover:opacity-100" />
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

            <div class="md:col-span-7 space-y-6">

                <div class="p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-[11px] font-black text-slate-500 uppercase">Spécifications d'Usine</span>
                    </div>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-1">Équipement Maître / Parent</label>
                            <Dropdown v-model="form.parent_id" :options="parentEquipments" optionLabel="designation" optionValue="id" filter class="quantum-input-v16 w-full" />
                        </div>
                        <div class="col-span-4 field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-1">Code Asset / Tag</label>
                            <InputText v-model="form.tag" class="quantum-input-v16 font-mono uppercase text-emerald-600" />
                        </div>
                        <div class="col-span-8 field">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-1">Nom de l'Unité</label>
                            <InputText v-model="form.designation" class="quantum-input-v16 font-bold" />
                        </div>
                        <div class="col-span-6 field mt-2">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-1">Numéro de Série (S/N)</label>
                            <InputText v-model="form.serial_number" class="quantum-input-v16" placeholder="SN-XXXX-XXXX" />
                        </div>
                        <div class="col-span-6 field mt-2">
                            <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-1">Code Barre / QR</label>
                            <InputText v-model="form.barcode" class="quantum-input-v16" placeholder="EAN-13" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="text-[11px] font-black text-slate-500 uppercase italic">Attributs Techniques Personnalisables</span>
                        </div>
                        <Button icon="pi pi-plus" label="Ajouter un attribut" @click="addCharacteristic" text class="p-button-sm text-[10px] font-black text-emerald-600 uppercase" />
                    </div>

                    <div class="p-2 bg-white rounded-3xl border border-slate-100 shadow-inner min-h-[150px] max-h-[300px] overflow-y-auto">
                        <div v-for="(char, index) in form.characteristics" :key="index"
                             class="flex gap-2 p-2 mb-2 bg-slate-50 border border-slate-100 rounded-2xl items-center group transition-all hover:bg-white hover:shadow-md hover:border-emerald-200">
                            <InputText v-model="char.name" placeholder="Paramètre" class="flex-1 border-none bg-transparent text-xs font-bold focus:ring-0" />
                            <Dropdown v-model="char.type" :options="characteristicTypes" optionLabel="label" optionValue="value" class="w-32 border-none bg-slate-200/50 rounded-xl text-[10px]" />
                            <div class="flex-[1.5]">
                                <InputText v-if="char.type === 'text'" v-model="char.value" class="w-full border-none bg-transparent text-xs" />
                                <InputNumber v-else-if="char.type === 'number'" v-model="char.value" class="w-full text-xs" />
                                <Calendar v-else-if="char.type === 'date'" v-model="char.value" dateFormat="dd/mm/yy" />
                                <InputSwitch v-else-if="char.type === 'boolean'" v-model="char.value" />
                            </div>
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="removeCharacteristic(index)" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label class="text-[10px] font-extrabold text-slate-400 uppercase ml-2 mb-1 block">Consignes de sécurité / Remarques</label>
                    <textarea v-model="form.notes" rows="3" class="w-full p-4 rounded-3xl border-slate-100 bg-slate-50 text-xs focus:ring-emerald-500" placeholder="Équipement critique, nécessite habilitation HT..."></textarea>
                </div>
            </div>

            <div class="md:col-span-5 space-y-4">

                <div class="p-6 bg-emerald-950 rounded-[2.5rem] text-white shadow-xl relative overflow-hidden shadow-emerald-900/20">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="pi pi-shield text-6xl"></i>
                    </div>

                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 mb-6">Gestion Opérationnelle</h4>

                    <div class="space-y-5">
                        <div class="field">
                            <label class="text-[9px] font-black opacity-40 uppercase mb-2 block">État de l'unité</label>
                            <Dropdown v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full bg-white/5 border-white/10 text-white rounded-2xl" />
                        </div>
                        <div class="field">
                            <label class="text-[9px] font-black opacity-40 uppercase mb-2 block">Catégorie Technique</label>
                            <div class="flex gap-2">
                                <Dropdown v-model="form.equipment_type_id" :options="equipmentTypes" optionLabel="name" optionValue="id" class="flex-1 bg-white/5 border-white/10 text-white rounded-2xl" />
                                <Button icon="pi pi-plus" @click="openNewEquipmentType" class="bg-emerald-500 border-none text-white rounded-xl" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-white rounded-[2rem] border border-slate-100 shadow-sm space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="field">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Constructeur</label>
                            <InputText v-model="form.brand" class="quantum-input-sm w-full" placeholder="Ex: Siemens" />
                        </div>
                        <div class="field">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Modèle / Réf</label>
                            <InputText v-model="form.model" class="quantum-input-sm w-full" placeholder="Ex: S7-1200" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 border-t border-slate-50 pt-4">
                        <div class="field">
                            <label class="text-[9px] font-black text-slate-400 uppercase italic">Coût Acquisition</label>
                            <InputNumber v-model="form.price" mode="currency" currency="EUR" locale="fr-FR" class="quantum-input-sm w-full" />
                        </div>
                        <div class="field">
                            <label class="text-[9px] font-black text-slate-400 uppercase italic">Puissance (kW/VA)</label>
                            <InputNumber v-model="form.puissance" suffix=" kW" class="quantum-input-sm w-full" />
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-slate-900 rounded-[2rem] text-white space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <label class="text-[9px] font-black opacity-40 uppercase mb-1 block">Date Mise en Service</label>
                            <Calendar v-model="form.purchase_date" class="quantum-calendar-dark" showIcon iconDisplay="input" />
                        </div>
                        <div class="flex-1">
                            <label class="text-[9px] font-black opacity-40 uppercase mb-1 block">Fin de Garantie</label>
                            <Calendar v-model="form.warranty_end_date" class="quantum-calendar-dark" showIcon iconDisplay="input" />
                        </div>
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black opacity-40 uppercase mb-1 block">Fréquence de maintenance préventive (Jours)</label>
                        <InputNumber v-model="form.maintenance_interval" suffix=" jours" class="w-full bg-white/5 border-none rounded-xl" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-slate-100 p-4 rounded-3xl border border-slate-200">
                        <span class="block text-[8px] font-black text-slate-400 uppercase mb-1">Entité / Région</span>
                        <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" class="w-full bg-transparent border-none p-0 text-xs font-bold" />
                    </div>
                    <div class="bg-emerald-50 p-4 rounded-3xl border border-emerald-100 flex flex-col justify-center">
                        <span class="block text-[8px] font-black text-emerald-400 uppercase mb-1 text-center">Localisation Géo</span>
                        <InputText v-model="form.location" class="bg-transparent border-none p-0 text-center text-xs font-black text-emerald-800 uppercase" placeholder="COORDONNÉES" />
                    </div>
                </div>

                <div class="p-4 border-2 border-dashed border-slate-200 rounded-[2rem] hover:border-emerald-400 transition-colors cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-all">
                            <i class="pi pi-upload"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-black text-slate-600 uppercase">Manuel Technique / Schéma</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase">PDF, JPG (Max 10MB)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template #footer>
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center w-full rounded-b-[2rem]">
            <Button :label="t('equipments.dialog.cancel')" icon="pi pi-times" class="p-button-text p-button-secondary font-bold text-xs" @click="hideDialog" />
            <div class="flex gap-3">
                <Button v-if="form.id" label="Dupliquer" icon="pi pi-copy" severity="secondary" outlined class="rounded-2xl px-4 font-bold text-xs" />
                <Button :label="t('equipments.dialog.save')" icon="pi pi-check-circle"
                        class="p-button-emerald px-10 rounded-2xl shadow-xl shadow-emerald-100 font-black uppercase text-xs tracking-widest"
                        @click="saveEquipment" :loading="form.processing" />
            </div>
        </div>
    </template>
</Dialog>
<Dialog
    v-model:visible="equipmentTypeDialog"
    modal
    :header="false"
    :style="{ width: '30rem' }"
    class="quantum-dialog"
    :pt="{
        root: { class: 'border-none rounded-[2rem] bg-white shadow-2xl' },
        mask: { style: 'backdrop-filter: blur(4px)' }
    }"
>
    <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
            <i class="pi pi-tags text-lg"></i>
        </div>
        <div>
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-700">{{ t('equipments.typeDialog.title') }}</h3>
            <p class="text-[9px] font-bold text-slate-400 uppercase">Configuration des catégories d'actifs</p>
        </div>
    </div>

    <div class="p-8 space-y-5">
        <div class="field">
            <label for="equipment_type_name" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-tighter">
                {{ t('equipments.typeDialog.name') }}
            </label>
            <InputText
                id="equipment_type_name"
                v-model.trim="equipmentTypeForm.name"
                required
                autofocus
                :placeholder="t('equipments.typeDialog.name')"
                class="quantum-input-v16 w-full font-bold"
                :class="{ 'p-invalid': equipmentTypeForm.errors.name }"
            />
            <small class="p-error text-[10px] font-bold mt-1 block" v-if="equipmentTypeForm.errors.name">
                <i class="pi pi-times-circle mr-1"></i> {{ equipmentTypeForm.errors.name }}
            </small>
        </div>

        <div class="field">
            <label for="equipment_type_description" class="text-[10px] font-black uppercase text-slate-400 ml-2 mb-2 block tracking-tighter">
                {{ t('equipments.typeDialog.description') }}
            </label>
            <Textarea
                id="equipment_type_description"
                v-model.trim="equipmentTypeForm.description"
                rows="3"
                class="w-full p-4 rounded-2xl bg-slate-50 border-slate-100 text-sm focus:ring-2 focus:ring-emerald-500 transition-all"
                :class="{ 'p-invalid': equipmentTypeForm.errors.description }"
            />
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-4 pb-4">
            <Button :label="t('equipments.typeDialog.cancel')" icon="pi pi-times" @click="hideEquipmentTypeDialog" class="p-button-text p-button-secondary font-bold text-xs" />
            <Button
                :label="t('equipments.typeDialog.create')"
                icon="pi pi-check"
                @click="saveEquipmentType"
                :loading="equipmentTypeForm.processing"
                class="p-button-emerald px-6 rounded-xl shadow-lg shadow-emerald-100 font-black uppercase text-[10px]"
            />
        </div>
    </template>
</Dialog>
    </AppLayout>
</template>

<style scoped>
/* STYLE V11 CUSTOM TOKENS */
.p-button-indigo {
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
