<script setup>
import { ref, computed, watch, reactive } from 'vue';
import debounce from 'lodash/debounce';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// Importations ajoutées pour la sélection de colonnes et l'export
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import SplitButton from 'primevue/splitbutton';
import Divider from 'primevue/divider';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import TreeSelect from 'primevue/treeselect';
import Checkbox from 'primevue/checkbox';
import InputNumber from 'primevue/inputnumber';
import Avatar from 'primevue/avatar';

const props = defineProps({
    maintenances: Object,
    filters: Object,
    equipments: Array,
    users: Array,
    teams: Object,
    regions: Array,
    tasks: Array, // Assumant que les tâches sont disponibles pour lier les activités
    spareParts: Array, // Requis pour la sélection de pièces
    equipmentTree: Array,
    // Vous devrez ajouter les sous-traitants (subcontractors) ici quand ils seront disponibles

    // subcontractors: Array,
});
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

const maintenanceDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const searchFilter = ref(props.filters?.search || '');
const dt = ref(); // Référence au DataTable pour l'export
const op = ref(); // Référence à l'OverlayPanel pour la sélection de colonnes

// --- SYSTÈME DE FILTRES AVANCÉS (V11 Custom) ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    title: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'assignable.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    priority: { value: null, matchMode: FilterMatchMode.EQUALS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS },
    'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        title: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'assignable.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        status: { value: null, matchMode: FilterMatchMode.EQUALS },
        priority: { value: null, matchMode: FilterMatchMode.EQUALS },
        type: { value: null, matchMode: FilterMatchMode.EQUALS },
        'region.designation': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
};

// Colonnes pour la sélection
const allColumns = ref([
    { field: 'title', header: t('maintenances.columns.title') },
    { field: 'equipments', header: t('maintenances.columns.equipments') },
    { field: 'assignable', header: t('maintenances.columns.assignable') },
    { field: 'status', header: t('maintenances.columns.status') },
    { field: 'priority', header: t('maintenances.columns.priority') },
    { field: 'scheduled_start_date', header: t('maintenances.columns.scheduled_start_date') },
    { field: 'description', header: t('maintenances.columns.description') },
    { field: 'estimated_duration', header: t('maintenances.columns.estimated_duration') },
    { field: 'cost', header: t('maintenances.columns.cost') },
    { field: 'region.designation', header: t('maintenances.columns.region') },
    { field: 'recurrence_type', header: t('maintenances.columns.recurrence_type') },
    { field: 'recurrence_interval', header: t('maintenances.columns.recurrence_interval') },
    { field: 'recurrence_month_interval', header: t('maintenances.columns.recurrence_month_interval') },
    { field: 'recurrence_days', header: t('maintenances.columns.recurrence_days') },
    { field: 'recurrence_day_of_month', header: t('maintenances.columns.recurrence_day_of_month') },
    { field: 'recurrence_month', header: t('maintenances.columns.recurrence_month') },
]);
const visibleColumns = ref(allColumns.value.slice(0, 5).map(col => col.field)); // Affiche les 5 premières par défaut

const toggleColumnSelection = (event) => {
    op.value.toggle(event);
};


// État pour les enfants sélectionnés pour les instructions
const selectedChildrenForInstructions = ref({});
const showAdvancedInstructions = ref(false);

// État pour la fonctionnalité de copie d'instructions
const copyInstructionsDialog = ref(false);
const sourceNodeKeyForCopy = ref(null);
const selectedCopyTargets = ref({}); // Will hold the selection from TreeSelect

// État pour les groupes d'instructions dépliés/repliés
const expandedInstructionGroups = ref({});

// --- NOUVEAU : Logique pour la création d'activités ---
const activityCreationDialog = ref(false);
const selectedMaintenanceForActivity = ref(null);

// --- HARMONISATION DU STATUT ICI ---
const activityStatusOptions = ref([
    { label: t('maintenances.status.scheduled'), value: 'scheduled' },
    { label: t('maintenances.status.in_progress'), value: 'in_progress' },
    { label: t('maintenances.status.completed'), value: 'completed' },
    { label: t('maintenances.status.completed_with_issues'), value: 'completed_with_issues' },
    { label: t('maintenances.status.suspended'), value: 'suspended' },
    { label: t('maintenances.status.canceled'), value: 'canceled' },
    { label: t('maintenances.status.awaiting_resources'), value: 'awaiting_resources' },
    { label: t('maintenances.status.to_be_reviewed_later'), value: 'to_be_reviewed_later' },
]);

const activityCreationForm = useForm({
    maintenance_id: null,
    activities: [], // Tableau pour une ou plusieurs activités
});


const addActivityToForm = () => {
    activityCreationForm.activities.push({
        // --- NOUVEAUX CHAMPS ---
        title: `${t('maintenances.activityDialog.newActivity')} ${selectedMaintenanceForActivity.value?.title || ''}`,
        status: 'scheduled', // --- HARMONISATION DU STATUT ICI ---
        assignable_type: null, // Pour choisir entre Technicien/Équipe
        assignable_id: null,
        equipment_ids: selectedMaintenanceForActivity.value?.equipments.map(eq => eq.id) || [],
        actual_start_time: selectedMaintenanceForActivity.value?.scheduled_start_date ? new Date(selectedMaintenanceForActivity.value.scheduled_start_date) : null,
        actual_end_time: selectedMaintenanceForActivity.value?.scheduled_end_date ? new Date(selectedMaintenanceForActivity.value.scheduled_end_date) : null,
        spare_parts: [], // Tableau pour les pièces détachées { id, quantity_used }
        instructions: [], // Tableau pour les instructions { label, type, is_required }
    });
};

const form = useForm({
    id: null,
    title: '',
    description: '',
    assignable_type: null,
    assignable_id: null, // Initialiser à null pour éviter des problèmes si non sélectionné
    type: 'Préventive',
    status: 'Planifiée',
    priority: 'Moyenne',
    scheduled_start_date: null,
    scheduled_end_date: null,
    estimated_duration: null,
    cost: null,
    region_id: null,
    recurrence_type: null, // Nouvelle propriété pour le type de récurrence
    recurrence_interval: null, // Intervalle pour quotidienne, trimestrielle, semestrielle, annuelle
    recurrence_month_interval: null, // Intervalle en mois pour la récurrence mensuelle
    recurrence_days: [], // Nouvelle propriété pour les jours de la semaine (pour hebdomadaire)
    recurrence_day_of_month: null, // Nouvelle propriété pour le jour du mois (pour mensuel)
    recurrence_month: null, // Nouvelle propriété pour le mois (pour annuel)
    reminder_days: null, // Jours de rappel avant exécution
    custom_recurrence_config: null, // Pour la récurrence personnalisée
    node_instructions: {}, // Pour les instructions spécifiques aux noeuds,
    related_equipments: {}, // Pour les équipements liés (TreeSelect model)
});

// Options pour les listes déroulantes
const maintenanceTypes = ref(['Préventive', 'Corrective', 'Améliorative', 'Périodique']);
// --- HARMONISATION DU STATUT ICI ---
const maintenanceStatuses = computed(() => [
    { label: t('maintenances.status.scheduled'), value: 'scheduled' },
    { label: t('maintenances.status.in_progress'), value: 'in_progress' },
    { label: t('maintenances.status.completed'), value: 'completed' },
    { label: t('maintenances.status.completed_with_issues'), value: 'completed_with_issues' },
    { label: t('maintenances.status.suspended'), value: 'suspended' },
    { label: t('maintenances.status.canceled'), value: 'canceled' },
    { label: t('maintenances.status.awaiting_resources'), value: 'awaiting_resources' },
    { label: t('maintenances.status.to_be_reviewed_later'), value: 'to_be_reviewed_later' },
]);

const maintenancePriorities = computed(() => [t('maintenances.priority.low'), t('maintenances.priority.medium'), t('maintenances.priority.high'), t('maintenances.priority.urgent')]);
const assignableTypes = ref([
    { label: t('maintenances.assignableTypes.none'), value: null },
    { label: t('maintenances.assignableTypes.user'), value: 'App\\Models\\User' },
    { label: t('maintenances.assignableTypes.team'), value: 'App\\Models\\Team' },
    // { label: 'Sous-traitant', value: 'App\\Models\\Subcontractor' },
]);

// Liste dynamique des personnes/équipes à assigner
const assignables = computed(() => {
    if (form.assignable_type === 'App\\Models\\User') {
        return props.users;
    }
    if (form.assignable_type === 'App\\Models\\Team') {
        return props.teams;
    }
    // if (form.assignable_type === 'App\\Models\\Subcontractor') {
    //     return props.subcontractors;
    // }
    return [];
});

// Options pour la récurrence
const recurrenceTypes = ref([
    { label: t('maintenances.recurrence.none'), value: null },
    { label: t('maintenances.recurrence.daily'), value: 'daily' },
    { label: t('maintenances.recurrence.weekly'), value: 'weekly' },
    { label: t('maintenances.recurrence.monthly'), value: 'monthly' },
    { label: t('maintenances.recurrence.quarterly'), value: 'quarterly' },
    { label: t('maintenances.recurrence.biannual'), value: 'biannual' },
    { label: t('maintenances.recurrence.annual'), value: 'annual' },
    { label: t('maintenances.recurrence.custom'), value: 'custom' },
]);
const daysOfWeek = ref([
    { label: 'Lundi', value: 1 }, { label: 'Mardi', value: 2 }, { label: 'Mercredi', value: 3 },
    { label: 'Jeudi', value: 4 }, { label: 'Vendredi', value: 5 }, { label: 'Samedi', value: 6 }, { label: 'Dimanche', value: 0 }
]);

const instructionValueTypes = ref([
    { label: 'Texte', value: 'text' },
    { label: 'Nombre', value: 'number' },
    { label: 'Date', value: 'date' },
    { label: 'Image', value: 'image' },
    { label: 'Signature', value: 'signature' },
    { label: 'Booléen', value: 'boolean' },
]);

const months = ref([
    { label: 'Janvier', value: 1 },
    { label: 'Février', value: 2 },
    { label: 'Mars', value: 3 },
    { label: 'Avril', value: 4 },
    { label: 'Mai', value: 5 },
    { label: 'Juin', value: 6 },
    { label: 'Juillet', value: 7 },
    { label: 'Août', value: 8 },
    { label: 'Septembre', value: 9 },
    { label: 'Octobre', value: 10 },
    { label: 'Novembre', value: 11 },
    { label: 'Décembre', value: 12 },
]);

// Réinitialiser l'assignable_id quand le type change
// watch(() => form.assignable_type, (newValue) => { form.assignable_id = null; });
watch(() => form.recurrence_type, (newValue) => {
    form.recurrence_interval = null;
    form.recurrence_days = [];
    form.recurrence_day_of_month = null;
    form.recurrence_month_interval = null; // Reset for monthly interval
    form.recurrence_month = null;
});

// Surveiller les équipements liés pour préparer les instructions de nœud
watch(() => form.related_equipments, (newSelection, oldSelection) => {
    const newKeys = newSelection ? Object.keys(newSelection) : [];
    const oldKeys = oldSelection ? Object.keys(oldSelection) : [];

    // Supprimer les instructions pour les nœuds désélectionnés
    oldKeys.forEach(key => {
        if (!newKeys.includes(key)) {
            delete form.node_instructions[key];
        }
    });
});

// Surveiller les équipements liés pour mettre à jour les enfants cochés par défaut
watch(() => form.related_equipments, (newSelection) => {
    const newSelectedChildren = {};
    if (newSelection) {
        const selectedKeys = Object.keys(newSelection).filter(key => newSelection[key].checked);
        configurableNodes.value.forEach(node => {
            if (selectedKeys.includes(node.key)) {
                newSelectedChildren[node.key] = true;
            }
        });
    }
    selectedChildrenForInstructions.value = newSelectedChildren;

    const relevantKeys = Object.keys(newSelectedChildren);
    Object.keys(form.node_instructions).forEach(key => {
        if (!relevantKeys.includes(key)) {
            delete form.node_instructions[key];
        }
    });
}, { deep: true });


const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    showAdvancedInstructions.value = false;
    maintenanceDialog.value = true;
    selectedChildrenForInstructions.value = {};

    // Set default scheduled dates for new maintenance
    const today = new Date();
    const defaultStartTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 7, 0); // Today at 7:00
    const defaultEndTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 16, 0); // Today at 16:00
    form.scheduled_start_date = defaultStartTime;
    form.scheduled_end_date = defaultEndTime;
};

const hideDialog = () => {
    maintenanceDialog.value = false;
    submitted.value = false;
};

const editMaintenance = (maintenance) => {
    form.id = maintenance.id;
    form.title = maintenance.title;
    form.description = maintenance.description;
    form.assignable_type = maintenance.assignable_type;
    form.assignable_id = maintenance.assignable_id;
    form.type = maintenance.type;
    form.status = maintenance.status;
    form.priority = maintenance.priority;
    form.scheduled_start_date = maintenance.scheduled_start_date ? new Date(maintenance.scheduled_start_date) : null;
    form.scheduled_end_date = maintenance.scheduled_end_date ? new Date(maintenance.scheduled_end_date) : null;
    form.estimated_duration = maintenance.estimated_duration;
    form.cost = maintenance.cost;
    form.region_id = maintenance.region_id;
    form.recurrence_type = maintenance.recurrence_type;
    form.recurrence_interval = maintenance.recurrence_interval;
    form.recurrence_days = maintenance.recurrence_days || []; // Déjà un tableau grâce au cast Laravel
    form.recurrence_day_of_month = maintenance.recurrence_day_of_month;

    // Transformer les équipements pour le TreeSelect
    const relatedEquipmentsForTree = {};
    if (maintenance.equipments) {
        maintenance.equipments.forEach(eq => {
            relatedEquipmentsForTree[String(eq.id)] = { checked: true, partialChecked: false };
        });
    }
    form.related_equipments = relatedEquipmentsForTree;

    // Transformer les instructions
    form.node_instructions = maintenance.instructions ? maintenance.instructions.reduce((acc, instruction) => {
        const key = String(instruction.equipment_id);
        if (!acc[key]) acc[key] = [];
        acc[key].push({ label: instruction.label, type: instruction.type, is_required: instruction.is_required });
        return acc;
    }, {}) : {};

    // Initialiser les enfants sélectionnés si des instructions existent
    showAdvancedInstructions.value = false;
    editing.value = true;
    maintenanceDialog.value = true;
};

const saveMaintenance = () => {
    submitted.value = true;
    if (!form.title || Object.keys(form.related_equipments).length === 0 || !form.type) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez remplir les champs obligatoires.', life: 3000 });
        return;
    }

    // Préparer les données pour la soumission
   // const equipmentIds = form.related_equipments ? Object.keys(form.related_equipments).filter(key => form.related_equipments[key].checked) : [];
// Corrigé (pour garantir des nombres si les clés sont des chaînes) :
const equipmentIds = form.related_equipments
    ? Object.keys(form.related_equipments)
        .filter(key => form.related_equipments[key].checked)
        .map(key => parseInt(key, 10)) // Convertir les clés en entiers
    : [];
    // Dans saveMaintenance:
const data = {
    ...form.data(),
    equipment_ids: equipmentIds,
    scheduled_start_date: form.scheduled_start_date ? new Date(form.scheduled_start_date).toISOString().slice(0, 19).replace('T', ' ') : null,
    scheduled_end_date: form.scheduled_end_date ? new Date(form.scheduled_end_date).toISOString().slice(0, 19).replace('T', ' ') : null,
};

    // Assurer que assignable_id est null si assignable_type est null
    if (!form.assignable_type) {
        data.assignable_id = null;
    }
    console.log(form);
    if (editing.value) {
        router.put(route('maintenances.update', form.id), data, {
            onSuccess: () => {
                maintenanceDialog.value = false;
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Maintenance mise à jour avec succès.', life: 3000 });
                form.reset();
            },
            onError: (errors) => {
                console.error("Erreur lors de la mise à jour de la maintenance", errors);
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
                form.errors = errors;
            }
        });
    } else {
    console.log(data);
        router.post(route('maintenances.store'), data, {
        onSuccess: () => {
            maintenanceDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Maintenance créée avec succès.', life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la maintenance", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue.', life: 3000 });
            // Réassigner les erreurs au formulaire pour les afficher dans l'UI
            form.errors = errors;
        }
    });
    }
};

const deleteMaintenance = (maintenance) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la maintenance "${maintenance.title}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('maintenances.destroy', maintenance.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Maintenance supprimée.', life: 3000 });
                }
            });
        },
    });
};

const availableEquipmentsForActivity = () => {
    // Retourne tous les équipements disponibles dans les props
    // pour permettre la sélection sans restriction basée sur la maintenance parente
    return props.equipments;
};
const getAssignablesForActivity = (activity) => {
    if (activity.assignable_type === 'App\\Models\\User') {
        return props.users;
    }
    if (activity.assignable_type === 'App\\Models\\Team') {
        return props.teams;
    }
    return [];
};

const addInstructionToActivity = (activityIndex) => {
    activityCreationForm.activities[activityIndex].instructions.push({ label: '', type: 'text', is_required: false });
};

const removeInstructionFromActivity = (activityIndex, instructionIndex) => {
    activityCreationForm.activities[activityIndex].instructions.splice(instructionIndex, 1);
};

const openActivityCreationDialog = (maintenance, multiple = false) => {
    selectedMaintenanceForActivity.value = maintenance;
    activityCreationForm.reset();
    activityCreationForm.maintenance_id = maintenance.id;

    // Ajouter une activité par défaut, ou plusieurs si demandé
    const count = multiple ? 2 : 1; // Ajoute 2 activités si "plusieurs" est cliqué
    for (let i = 0; i < count; i++) {
        addActivityToForm();
    }

    activityCreationDialog.value = true;
};

const addSparePartToActivity = (activityIndex) => {
    activityCreationForm.activities[activityIndex].spare_parts.push({ id: null, quantity_used: 1 });
};

const removeSparePartFromActivity = (activityIndex, partIndex) => {
    activityCreationForm.activities[activityIndex].spare_parts.splice(partIndex, 1);
};

const removeActivityFromForm = (index) => {
    activityCreationForm.activities.splice(index, 1);
};

const submitActivities = () => {
    console.log({...activityCreationForm});
    // Vous devez implémenter la route et la logique côté baend pour `activities.bulkStore`
    activityCreationForm.post(route('activities.bulkStore'), {
        onSuccess: () => {
            activityCreationDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Activités créées avec succès.', life: 3000 });
        },
        onError: (errors) => {
            console.error(errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'La création des activités a échoué.', life: 4000 });
        }
    });
};

const activityItems = (maintenance) => [
    {
        label: 'Créer une activité',
        icon: 'pi pi-fw pi-bolt',
        command: () => openActivityCreationDialog(maintenance, false)
    },
    {
        label: 'Créer plusieurs activités',
        icon: 'pi pi-fw pi-copy',
        command: () => openActivityCreationDialog(maintenance, true)
    }
];

const applyFilters = debounce(() => {
    router.get(route('maintenances.index'), { search: searchFilter.value }, {
        preserveState: true,
        replace: true,
    });
}, 400);

const exportCSV = () => {
    dt.value.exportCSV();
};

const getStatusSeverity = (status) => {
    const severities = {
        'scheduled': 'info',
        'in_progress': 'warning',
        'completed': 'success',
        'completed_with_issues': 'danger',
        'suspended': 'warning',
        'canceled': 'secondary',
        'awaiting_resources': 'help',
        'to_be_reviewed_later': 'contrast',
        'En retard': 'danger' // Garder pour compatibilité si 'En retard' est un statut distinct
    };
    return severities[status] || null;
};

const getPrioritySeverity = (priority) => {
    const severities = { 'Basse': 'info', 'Moyenne': 'success', 'Haute': 'warning', 'Urgente': 'danger' };
    return severities[priority] || null;
};

const dialogTitle = computed(() => editing.value ? t('maintenances.formDialog.editTitle') : t('maintenances.formDialog.createTitle'));

// Récupère les nœuds pour lesquels on peut configurer des instructions.
// Si un parent est sélectionné, on prend ses enfants.
// Si un enfant est sélectionné, on le prend lui-même.
const configurableNodes = computed(() => {
    if (!form.related_equipments || Object.keys(form.related_equipments).length === 0) {
        return [];
    }
    const selectedKeys = Object.keys(form.related_equipments).filter(key => form.related_equipments[key]);
    const nodes = new Map();

    const findNodeRecursive = (n, key) => {
        if (n.key === key) {
            // Si le nœud sélectionné est un parent, on ajoute ses enfants
            if (n.children && n.children.length > 0) {
                n.children.forEach(child => {
                    if (!nodes.has(child.key)) {
                        nodes.set(child.key, child);
                    }
                });
            } else { // Sinon, c'est un enfant, on l'ajoute lui-même
                if (!nodes.has(n.key)) {
                    nodes.set(n.key, n);
                }
            }
            return n;
        }
        if (n.children) {
            for (const child of n.children) {
                const found = findNodeRecursive(child, key);
                if (found) return found;
            }
        }
        return null;
    };

    selectedKeys.forEach(key => {
        transformedEquipmentTree.value.forEach(rootNode => findNodeRecursive(rootNode, key));
    });
    return Array.from(nodes.values());
});

// Regroupe les nœuds configurables par leur parent direct.
const groupedConfigurableNodes = computed(() => {
    if (!configurableNodes.value.length) {
        return [];
    }

    const groups = new Map();

    // Fonction récursive pour trouver le parent d'un nœud dans l'arbre
    const findParent = (nodes, childKey) => {
        for (const node of nodes) {
            if (node.children && node.children.some(child => child.key === childKey)) {
                return node;
            }
            if (node.children) {
                const parent = findParent(node.children, childKey);
                if (parent) return parent;
            }
        }
        return null;
    };

    configurableNodes.value.forEach(node => {
        const parent = findParent(transformedEquipmentTree.value, node.key);
        const parentKey = parent ? parent.key : 'root'; // 'root' pour les orphelins
        const parentLabel = parent ? parent.label : t('maintenances.formDialog.parentGroupLabel');

        if (!groups.has(parentKey)) {
            groups.set(parentKey, { parent: { key: parentKey, label: parentLabel }, children: [] });
        }
        groups.get(parentKey).children.push(node);
    });

    return Array.from(groups.values());
});

const toggleInstructionGroup = (parentKey) => {
    // Par défaut, les groupes sont considérés comme ouverts.
    // Si la clé n'existe pas, on la met à `false` pour la fermer.
    // Sinon, on inverse sa valeur.
    expandedInstructionGroups.value[parentKey] = !(expandedInstructionGroups.value[parentKey] ?? true);
};

const isGroupExpanded = (parentKey) => expandedInstructionGroups.value[parentKey] ?? true;

const addInstruction = (nodeKey) => {
    if (!form.node_instructions[nodeKey]) {
        form.node_instructions[nodeKey] = [];
    }
    form.node_instructions[nodeKey].push({ label: '', type: 'text', is_required: false });
};

const removeInstruction = (nodeKey, index) => {
    if (form.node_instructions[nodeKey] && form.node_instructions[nodeKey][index]) {
        form.node_instructions[nodeKey].splice(index, 1);
    }
};

// Logique pour copier les instructions
const openCopyDialog = (sourceKey) => {
    // Reset state
    sourceNodeKeyForCopy.value = sourceKey;
    selectedCopyTargets.value = {}; // Reset selection
    copyInstructionsDialog.value = true;
};

const sourceNodeForCopy = computed(() => {
    if (!sourceNodeKeyForCopy.value) return null;
    return configurableNodes.value.find(n => n.key === sourceNodeKeyForCopy.value);
});

// Create a tree structure for the copy targets TreeSelect
const copyTargetsTree = computed(() => {
    const sourceKey = sourceNodeKeyForCopy.value;
    if (!sourceKey) return [];

    // Get keys of all valid targets (checked and not the source)
    const validTargetKeys = new Set(
        configurableNodes.value
            .filter(n => n.key !== sourceKey && selectedChildrenForInstructions.value[n.key])
            .map(n => n.key)
    );

    // Recursive function to build the tree, only including valid targets
    const buildTree = (nodes) => {
        return nodes.map(node => {
            const children = node.children ? buildTree(node.children) : [];
            // A node is included if it's a valid target itself, or if it has children that are valid targets.
            if (validTargetKeys.has(node.key) || children.length > 0) {
                return { ...node, children: children.length > 0 ? children : undefined };
            }
            return null;
        }).filter(Boolean); // Filter out null entries
    };

    return buildTree(transformedEquipmentTree.value);
});

// Group copy targets by their parent
const groupedCopyTargetNodes = computed(() => {
    const sourceKey = sourceNodeKeyForCopy.value;
    if (!sourceKey) return [];

    const validTargets = configurableNodes.value.filter(n => n.key !== sourceKey && selectedChildrenForInstructions.value[n.key]);
    const groups = new Map();

    // Function to find parent of a node
    const findParent = (nodes, childKey) => {
        for (const node of nodes) {
            if (node.children && node.children.some(child => child.key === childKey)) {
                return node;
            }
            if (node.children) {
                const parent = findParent(node.children, childKey);
                if (parent) return parent;
            }
        }
        return null;
    };

    validTargets.forEach(targetNode => {
        const parent = findParent(transformedEquipmentTree.value, targetNode.key);
        if (parent) {
            if (!groups.has(parent.key)) {
                groups.set(parent.key, {
                    parent: parent,
                    children: []
                });
            }
            groups.get(parent.key).children.push(targetNode);
        }
    });

    return Array.from(groups.values());
});

const copyTargetNodes = computed(() => {
    if (!sourceNodeKeyForCopy.value) return [];
    // Retourne tous les enfants cochés sauf le nœud source
    return configurableNodes.value.filter(n => n.key !== sourceNodeKeyForCopy.value && selectedChildrenForInstructions.value[n.key]);
});

const applyCopyInstructions = () => {
    const sourceInstructions = form.node_instructions[sourceNodeKeyForCopy.value];
    if (!sourceInstructions) return;

    const targetsToApply = new Set(); // Use a Set to avoid duplicates
    const selectedKeys = Object.keys(selectedCopyTargets.value).filter(key => selectedCopyTargets.value[key].checked);

    // Recursive function to find a node in the tree
    const findNode = (nodes, key) => {
        for (const node of nodes) {
            if (node.key === key) return node;
            if (node.children) {
                const found = findNode(node.children, key);
                if (found) return found;
            }
        }
        return null;
    };

    // Recursive function to collect all leaf nodes (actual equipments) from a starting node
    const collectLeafKeys = (node) => {
        let keys = [];
        if (!node.children || node.children.length === 0) {
            keys.push(node.key);
        } else {
            node.children.forEach(child => {
                keys.push(...collectLeafKeys(child));
            });
        }
        return keys;
    };

    selectedKeys.forEach(key => {
        const node = findNode(copyTargetsTree.value, key);
        if (node) {
            const leafKeys = collectLeafKeys(node);
            leafKeys.forEach(leafKey => targetsToApply.add(leafKey));
        }
    });

    targetsToApply.forEach(targetKey => {
        if (targetKey) { // Ensure targetKey is not undefined
            form.node_instructions[targetKey] = JSON.parse(JSON.stringify(sourceInstructions));
        }
    });


    copyInstructionsDialog.value = false;
};

// Helper to manage selection in the copy dialog
const handleParentSelection = (group, event) => {
    if (event.target.checked) {
        selectedCopyTargets.value[group.parent.key] = group.children.map(c => c.key);
    } else {
        delete selectedCopyTargets.value[group.parent.key];
    }
};

const isParentSelected = (group) => {
    const selectedInChildren = selectedCopyTargets.value[group.parent.key] || [];
    return selectedInChildren.length === group.children.length;
};

const isParentIndeterminate = (group) => {
    const selectedInChildren = selectedCopyTargets.value[group.parent.key] || [];
    return selectedInChildren.length > 0 && selectedInChildren.length < group.children.length;
};

const totalSelectedCopyTargets = computed(() => {
    return Object.keys(selectedCopyTargets.value).filter(k => selectedCopyTargets.value[k]).length;
});
// Transformation de l'arbre d'équipements pour TreeSelect
const transformedEquipmentTree = computed(() => {
    if (!props.equipmentTree || typeof props.equipmentTree !== 'object') {
        return [];
    }

    const transformNode = (node) => {
        if (!node) return null;
        return {
            key: String(node.id),
            label: node.label,
            icon: 'pi pi-fw pi-cog',
            children: node.children ? node.children.map(transformNode).filter(n => n) : []
        };
    };

    // Convertir l'objet de premier niveau en tableau et transformer chaque nœud
    return Object.values(props.equipmentTree).map(transformNode).filter(n => n);
});


</script>

<template>
    <AppLayout :title="t('maintenances.title')">

        <Head title="Maintenances" />

        <Toast />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50 p-4 md:p-10 font-sans">

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-wrench text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tighter text-slate-900 md:text-4xl">{{ t('maintenances.headTitle') }}</h1>
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">{{ t('maintenances.subtitle') }}</p>
                    </div>
                </div>

                <div class="flex w-full items-center gap-3 lg:w-auto">
                    <button @click="exportCSV" class="flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-emerald-600 shadow-sm transition-all hover:bg-emerald-50 active:scale-95 lg:flex-none">
                        <i class="pi pi-file-excel"></i> {{ t('maintenances.exportExcel') }}
                    </button>
                    <button @click="openNew" class="flex flex-[2] items-center justify-center gap-2 rounded-2xl bg-primary-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-primary-100 transition-all hover:bg-primary-700 active:scale-95 lg:flex-none">
                        <i class="pi pi-plus-circle"></i> {{ t('maintenances.new') }}
                    </button>
                </div>
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-[1rem] border border-white bg-white/50 p-4 shadow-sm backdrop-blur-md">
                <!-- <div class="relative flex-1 min-w-[280px]">
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input v-model="searchFilter" type="text" :placeholder="t('maintenances.searchPlaceholder')" @input="applyFilters"
                           class="w-full rounded-2xl border-none bg-white py-3 pl-12 text-sm font-semibold shadow-inner focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div class="flex items-center gap-2">
                    <Button icon="pi pi-filter-slash" text @click="applyFilters" class="h-12 w-12 !text-slate-400 hover:!text-red-500" v-tooltip.bottom="t('maintenances.resetTooltip')" />
                    <Button icon="pi pi-columns" text @click="toggleColumnSelection" class="h-12 w-12 !text-slate-400 hover:!text-primary-500" v-tooltip.bottom="t('maintenances.columnsTooltip')" />
                </div> -->
            </div>

            <div class="overflow-hidden rounded-[1rem] border border-white bg-white shadow-2xl shadow-slate-200/60">
                <DataTable :value="maintenances.data" ref="dt" dataKey="id" :paginator="true" :rows="10"
                    scrollable scrollHeight="600px" class="v11-table"
                    v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['title', 'assignable.name', 'status', 'priority', 'type', 'region.designation']"
                    removableSort>

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('maintenances.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="toggleColumnSelection" />
                                <!-- <Button v-if="selectedMaintenances && selectedMaintenances.length > 0"
                                        :label="t('common.deleteSelected', { count: selectedMaintenances.length })"
                                        icon="pi pi-trash" severity="danger" raised
                                        @click="deleteSelectedMaintenances" /> -->
                            </div>
                        </div>
                    </template>
                    <Column selectionMode="multiple" headerStyle="width: 4rem" class="pl-8"></Column>

  <Column v-if="visibleColumns.includes('title')" :header="t('maintenances.columns.title')" minWidth="300px">
                        <template #body="{ data }">
                            <div class="group flex cursor-pointer items-center gap-4 py-2" @click="editMaintenance(data)">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 transition-all group-hover:bg-primary-100 text-primary-500">
                                    <i class="pi pi-tag text-xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-black tracking-tight text-slate-800">{{ data.title }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-primary-500">ID: #{{ data.id.toString().padStart(4, '0') }}</span>
                                </div>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
 <InputText v-model="filterModel.constraints[0].value" type="text" class="p-column-filter" :placeholder="t('maintenances.filter.title')" />

                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('equipments')" :header="t('maintenances.columns.equipments')" minWidth="250px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.equipments?.length" class="flex flex-wrap gap-1">
                                <Tag v-for="equipment in slotProps.data.equipments" :key="equipment.id" :value="equipment.designation" class="!bg-slate-200 !text-slate-700 !font-bold" />
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('assignable')" field="assignable.name" :header="t('maintenances.columns.assignable')" minWidth="200px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.assignable" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100">
                                <Avatar :label="slotProps.data.assignable.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ slotProps.data.assignable.name }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('maintenances.filter.assignable')" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('status')" field="status" :header="t('maintenances.columns.status')" minWidth="150px">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="maintenanceStatuses" optionLabel="label" optionValue="value"
                                      :placeholder="t('maintenances.filter.status')" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('priority')" field="priority" :header="t('maintenances.columns.priority')" minWidth="150px">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.priority" :severity="getPrioritySeverity(slotProps.data.priority)" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="maintenancePriorities"
                                      :placeholder="t('maintenances.filter.priority')" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('scheduled_start_date')" field="scheduled_start_date" :header="t('maintenances.columns.scheduled_start_date')" minWidth="200px">
                        <template #body="slotProps">
                            <span class="font-mono text-sm bg-primary-50 text-primary-600 px-2 py-1 rounded-md">{{ new Date(slotProps.data.scheduled_start_date).toLocaleString() }}</span>
                        </template>
                    </Column>

                    <!-- Colonnes additionnelles cachées par défaut -->
                    <Column v-if="visibleColumns.includes('type')" field="type" :header="t('maintenances.columns.type')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.type }}
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="maintenanceTypes"
                                      :placeholder="t('maintenances.filter.type')" class="p-column-filter" showClear />
                        </template>
                    </Column>
                    <Column v-if="visibleColumns.includes('description')" field="description" :header="t('maintenances.columns.description')" :sortable="true" style="min-width: 15rem;"></Column>
                    <Column v-if="visibleColumns.includes('estimated_duration')" field="estimated_duration" :header="t('maintenances.columns.estimated_duration')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.estimated_duration }} min
                        </template>
                    </Column>
                    <Column v-if="visibleColumns.includes('cost')" field="cost" :header="t('maintenances.columns.cost')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.cost }} XOF
                        </template>
                    </Column>
                    <Column v-if="visibleColumns.includes('region.designation')" field="region.designation" :header="t('maintenances.columns.region')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.region ? slotProps.data.region.designation : 'N/A' }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter"
                                       :placeholder="t('maintenances.filter.region')" />
                        </template>
                    </Column>
                    <Column v-if="visibleColumns.includes('recurrence_type')" field="recurrence_type" :header="t('maintenances.columns.recurrence_type')" :sortable="true" style="min-width: 10rem;">
                        <template #body="slotProps">
                            {{ slotProps.data.recurrence_type || 'Aucune' }}
                        </template>
                    </Column>
                    <Column :header="t('maintenances.columns.actions')" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editMaintenance(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" />
                                <Button icon="pi pi-bolt" text rounded @click="openActivityCreationDialog(data, false)" class="!text-slate-400 hover:!bg-green-50 hover:!text-green-600 transition-all" v-tooltip.top="'Créer une activité'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteMaintenance(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                    <div class="font-semibold mb-3">{{ t('maintenances.columnSelector.title') }}</div>
                    <MultiSelect
                        v-model="visibleColumns"
                        :options="allColumns"
                        optionLabel="header"
                        optionValue="field"
                        display="chip"
                        :placeholder="t('maintenances.columnSelector.placeholder')"
                        class="w-full max-w-xs"  />
                </OverlayPanel>
            </div>
        </div>

        <!-- Le reste du template (Dialogs) reste majoritairement inchangé dans sa logique, mais pourrait bénéficier d'un restylage V11 si désiré -->
        <!-- Pour la concision, je garde les dialogues existants. Ils peuvent être stylisés comme dans Technicians.vue -->

        <!-- NOUVEAU : Dialogue de création d'activités -->
        <Dialog v-model:visible="activityCreationDialog" modal
         :header="false" :closable="false"
        class="v12-enterprise-dialog w-full max-w-7xl"
        :pt="{
            root: { class: 'border-none shadow-2xl' },
            header: { class: 'bg-slate-50 border-b border-slate-200 p-4' },
            content: { class: 'p-0' }
        }">

    <template #header>
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-slate-900 rounded-xl flex items-center justify-center">
                <i class="pi pi-layers text-white text-xs"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 leading-none">{{ t('maintenances.activityDialog.batchManagerTitle') }}</h2>
                <p class="text-[10px] text-slate-500 font-medium mt-1 uppercase tracking-wider">{{ t('maintenances.activityDialog.batchManagerSubtitle') }}</p>
            </div>
        </div>
    </template>

    <div v-if="selectedMaintenanceForActivity" class="flex flex-col h-[75vh]">

        <div class="bg-slate-900 px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-6">
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-primary-400 uppercase tracking-[0.2em]">{{ t('maintenances.activityDialog.parentMaintenance') }}</span>
                    <h3 class="text-white text-sm font-bold flex items-center gap-2">
                        {{ selectedMaintenanceForActivity.title }}
                        <i class="pi pi-external-link text-[10px] text-slate-500"></i>
                    </h3>
                </div>
                <div class="h-8 w-px bg-slate-800"></div>
                <div class="space-y-1">
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ t('maintenances.activityDialog.activityVolume') }}</span>
                    <p class="text-white text-sm font-bold">{{ activityCreationForm.activities.length }} Item(s)</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button :label="t('maintenances.activityDialog.newActivity')" icon="pi pi-plus"
                        class="p-button-sm p-button-raised bg-primary-600 border-none text-[11px] font-bold"
                        @click="addActivityToForm" />
            </div>
        </div>

        <div class="flex-1 overflow-y-auto bg-slate-50/50 p-6 space-y-6">

            <div v-for="(activity, index) in activityCreationForm.activities" :key="index"
                 class="bg-white border border-slate-200 rounded-lg shadow-sm transition-all hover:border-slate-300">

                <div class="flex items-center px-4 py-2 border-b border-slate-100 bg-white sticky top-0 z-10">
                    <span class="text-[10px] font-black text-slate-400 mr-4">#0{{ index + 1 }}</span>
                    <InputText v-model="activity.title"
                              class="flex-1 !bg-transparent !border-none !shadow-none font-bold text-slate-800 focus:ring-0 py-1"
                              :placeholder="t('maintenances.activityDialog.activityNamePlaceholder')" />

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 px-3 py-1 bg-slate-100 rounded text-[10px] font-bold text-slate-600 uppercase">
                            {{ t('maintenances.activityDialog.statusLabel') }} <Dropdown v-model="activity.status" :options="activityStatusOptions" optionLabel="label" optionValue="value" class="v12-minimal-dropdown" />
                        </div>
                        <Button icon="pi pi-clone" class="p-button-text p-button-secondary p-button-sm" v-tooltip.top="t('maintenances.activityDialog.duplicateTooltip')" />
                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="removeActivityFromForm(index)" />
                    </div>
                </div>

                <div class="p-2 grid grid-cols-12 gap-2">
                     <div class="col-span-12 lg:col-span-7 space-y-4">
                        <div class="p-2 grid grid-cols-12 gap-2">
                             <div class="col-span-12 lg:col-span-6 space-y-4">
                        <div class="p-4 border border-slate-100 rounded-md bg-slate-50/30">
                            <h5 class="text-[10px] font-black text-slate-900 uppercase mb-3 flex items-center gap-2">
                                <i class="pi pi-user text-primary-500"></i> {{ t('maintenances.activityDialog.attribution') }}
                            </h5>
                            <div class="space-y-3">
                                <div class="field">
                                    <Dropdown v-model="activity.assignable_type" :options="assignableTypes" optionLabel="label" optionValue="value"
                                              :placeholder="t('maintenances.activityDialog.entityTypePlaceholder')" class="w-full v12-input" />
                                </div>
                                <div class="field">
                                    <Dropdown v-model="activity.assignable_id" :options="getAssignablesForActivity(activity)"
                                              optionLabel="name" optionValue="id" filter :disabled="!activity.assignable_type"
                                              :placeholder="t('maintenances.activityDialog.responsiblePlaceholder')" class="w-full v12-input" />
                                </div>
                            </div>
                        </div>


                    </div>

                    <div class="col-span-12 lg:col-span-6 space-y-4">
                        <div class="p-4 border border-slate-100 rounded-md flex flex-col">
                            <h5 class="text-[10px] font-black text-slate-900 uppercase mb-3 flex items-center gap-2">
                                <i class="pi pi-box text-primary-500"></i> {{ t('maintenances.activityDialog.concernedAssets') }}
                            </h5>
                            <MultiSelect v-model="activity.equipment_ids" :options="availableEquipmentsForActivity(index)"
                                         optionLabel="designation" optionValue="id" display="chip"
                                         class="w-full v12-input flex-1" filter :placeholder="t('maintenances.activityDialog.searchEquipmentsPlaceholder')" />
                            <div class="mt-3 p-3 bg-primary-50 rounded border border-primary-100">
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="font-bold text-primary-700">{{ t('maintenances.activityDialog.estimatedCost') }}</span>
                                    <InputNumber v-model="activity.cost" mode="currency" currency="XOF" class="quantum-input-transparent text-right font-black" />
                                </div>
                            </div>
                        </div>
                    </div>
                        </div>
                         <div class="p-4 border border-slate-100 rounded-md bg-slate-50/30">
                            <h5 class="text-[10px] font-black text-slate-900 uppercase mb-3 flex items-center gap-2">
                                <i class="pi pi-calendar text-primary-500"></i> {{ t('maintenances.activityDialog.planning') }}
                            </h5>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label class="text-[9px] font-bold text-slate-500 uppercase">{{ t('maintenances.activityDialog.priority') }}</label>
                                    <Dropdown v-model="activity.priority" :options="maintenancePriorities" class="w-full v12-input" />
                                </div>
                                <div class="field">
                                    <label class="text-[9px] font-bold text-slate-500 uppercase">{{ t('maintenances.activityDialog.durationInMinutes') }}</label>
                                    <InputNumber v-model="activity.estimated_duration" class="w-full v12-input" />
                                </div>
                                <div class="field col-span-2">
                                    <label class="text-[9px] font-bold text-slate-500 uppercase">{{ t('maintenances.activityDialog.executionPeriod') }}</label>
                                    <Calendar v-model="activity.actual_start_time" showTime hourFormat="24" :placeholder="t('maintenances.activityDialog.start')" class="w-full v12-input mb-1" />
                                    <Calendar v-model="activity.actual_end_time" showTime hourFormat="24" :placeholder="t('maintenances.activityDialog.end')" class="w-full v12-input" />
                                </div>
                            </div>
                        </div>
                     </div>

                    <div class="col-span-12 lg:col-span-5 space-y-4">
                        <div class="p-4 border border-slate-100 rounded-md bg-white shadow-inner flex flex-col h-[180px]">
                            <div class="flex justify-between items-center mb-2">
                                <h5 class="text-[10px] font-black text-slate-900 uppercase">{{ t('maintenances.activityDialog.checklist') }}</h5>
                                <Button icon="pi pi-plus" class="p-button-text p-button-sm !p-0 h-4 w-4" @click="addInstructionToActivity(index)" />
                            </div>
                            <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                <div v-for="(instr, iIdx) in activity.instructions" :key="iIdx" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded border border-slate-100">
                                    <Checkbox v-model="instr.is_required" binary class="scale-75" />
                                    <InputText v-model="instr.label" :placeholder="t('maintenances.activityDialog.instructionPlaceholder')" class="flex-1 text-[10px] border-none !bg-transparent p-0" />
                                       <Dropdown v-model="instr.type"
                          :options="instructionValueTypes"
                          optionLabel="label"
                          optionValue="value"
                          :placeholder="t('maintenances.activityDialog.selectType')"
                          class="v12-instruction-type-dropdown flex-1 text-[9px]" />
                                    <Button icon="pi pi-trash" class="p-button-danger p-button-text p-button-sm !p-0" @click="removeInstructionFromActivity(index, iIdx)" />
                                </div>
                                <div v-if="!activity.instructions?.length" class="text-center py-4 text-[10px] text-slate-400 italic font-medium">{{ t('maintenances.activityDialog.zeroInstructions') }}</div>
                            </div>
                        </div>

                        <div class="p-4 border border-slate-100 rounded-md bg-white shadow-inner flex flex-col h-[180px]">
                            <div class="flex justify-between items-center mb-2">
                                <h5 class="text-[10px] font-black text-slate-900 uppercase">{{ t('maintenances.activityDialog.spareParts') }}</h5>
                                <Button icon="pi pi-plus" class="p-button-text p-button-sm !p-0 h-4 w-4" @click="addSparePartToActivity(index)" />
                            </div>
                            <div class="flex-1 overflow-hidden space-y-1">
        <div v-for="(part, pIdx) in activity.spare_parts" :key="pIdx"
             class="flex items-center gap-1 bg-slate-50 p-1 rounded border border-slate-100 min-w-0">

            <Dropdown v-model="part.id" :options="spareParts" optionLabel="reference" optionValue="id" filter
                      class="flex-1 min-w-0 text-[9px] border-none !bg-transparent v12-spare-select" />

            <InputNumber v-model="part.quantity_used" :min="1"
                         inputClass="w-[80px] text-[9px] p-0 text-center font-black bg-white border border-slate-200 rounded shadow-sm"
                         class="shrink-0" />

            <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm !p-0 shrink-0 w-4 h-4"
                    @click="removeSparePartFromActivity(index, pIdx)" />
        </div>
    </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-between items-center shrink-0">
            <Button :label="t('maintenances.activityDialog.resetForm')" icon="pi pi-refresh" class="p-button-text p-button-secondary text-xs font-bold" />

            <div class="flex items-center gap-3">
                <Button :label="t('maintenances.activityDialog.cancel')" class="p-button-text p-button-secondary font-bold text-xs" @click="activityCreationDialog = false" />
                <Button :label="t('maintenances.activityDialog.validateAndDeploy')" icon="pi pi-send"
                        class="bg-slate-900 border-none px-6 py-2.5 rounded text-xs font-black tracking-widest shadow-lg hover:bg-slate-800"
                        @click="submitActivities" :loading="activityCreationForm.processing" />
            </div>
        </div>
    </div>
</Dialog>

                  <Dialog v-model:visible="maintenanceDialog" modal
        :header="false" :closable="false"
        class="quantum-dialog w-full max-w-7xl"
        :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">

 <div class="px-8 py-5 bg-slate-900 rounded-xl text-white flex justify-between items-center relative z-50">
 <div class="flex items-center gap-4">
 <div class="p-2.5 bg-primary-500/20 rounded-xl border border-primary-500/30">
 <i :class="['pi pi-shield', 'text-primary-400 text-xl']"></i>
 </div>
 <div class="flex flex-col">
 <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">
 {{ t('maintenances.formDialog.technicalReportTitle') }}
 </h2>
 <span class="text-[9px] text-primary-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">
 {{ t('maintenances.formDialog.gmaoConsole') }}
 </span>
 </div>
 </div>
 <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
 </div>
    <div class="p-2">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

            <div class="md:col-span-7 space-y-6">

                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-5">
                    <h3 class="text-xs font-black uppercase tracking-widest text-primary-600 mb-2 flex items-center gap-2">
                        <i class="pi pi-tag"></i> {{ t('maintenances.formDialog.missionId') }}
                    </h3>

                    <div class="field">
                        <label for="title" class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('maintenances.formDialog.interventionTitle') }}</label>
                        <InputText id="title" v-model.trim="form.title"
                                  class="w-full quantum-input !bg-white"
                                  :class="{ 'p-invalid': submitted && !form.title }"
                                  :placeholder="t('maintenances.formDialog.interventionTitlePlaceholder')" />
                        <small class="p-error block mt-1" v-if="form.errors.title">{{ form.errors.title }}</small>
                    </div>

                    <div class="field">

                        <label for="related_equipments" class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('maintenances.formDialog.concernedEquipments') }}</label>
                        <TreeSelect id="related_equipments" v-model="form.related_equipments"
                                    :options="transformedEquipmentTree"
                                    :placeholder="t('maintenances.formDialog.selectAssets')" :expandedKeys="expandedKeys"
                                    filter selectionMode="checkbox" display="chip"
                                    class="w-full quantum-input !bg-white" />
                        <small class="p-error block mt-1" v-if="form.errors.related_equipments">{{ form.errors.related_equipments }}</small>
                    </div>

                    <div class="field">
                        <label for="description" class="text-[10px] font-bold uppercase text-slate-500 mb-1 block ml-1">{{ t('maintenances.formDialog.detailedDescription') }}</label>
                        <Textarea id="description" v-model="form.description" rows="4"
                                  class="w-full p-4 rounded-2xl border border-slate-200 focus:ring-2 focus:ring-primary-500 text-sm bg-white"
                                  :placeholder="t('maintenances.formDialog.generalInstructions')" />
                        <small class="p-error block mt-1" v-if="form.errors.description">{{ form.errors.description }}</small>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-700 flex items-center gap-2">
                            <i class="pi pi-sitemap text-primary-500"></i> {{ t('maintenances.formDialog.configPerEquipment') }}
                        </h3>
                        <Button :label="showAdvancedInstructions ? t('maintenances.formDialog.hideControlPoints') : t('maintenances.formDialog.showControlPoints')"
                                :icon="showAdvancedInstructions ? 'pi pi-chevron-up' : 'pi pi-cog'"
                                class="p-button-text p-button-sm font-bold text-primary-600"
                                @click="showAdvancedInstructions = !showAdvancedInstructions" />
                    </div>

                    <div v-if="showAdvancedInstructions" class="space-y-4 transition-all">
                        <div v-if="!configurableNodes || configurableNodes.length === 0"
                             class="p-8 border-2 border-dashed border-slate-200 rounded-3xl text-center bg-slate-50/50">
                            <i class="pi pi-exclamation-circle text-slate-300 text-3xl mb-3 block"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-tight">{{ t('maintenances.formDialog.noEquipmentSelected') }}</p>
                        </div>

                        <div v-else v-for="group in groupedConfigurableNodes" :key="group.parent.key"
                             class="border border-slate-100 rounded-3xl overflow-hidden bg-white shadow-sm">
                            <div class="flex justify-between items-center p-4 bg-slate-50/80 cursor-pointer hover:bg-slate-100 transition-colors"
                                 @click="toggleInstructionGroup(group.parent.key)">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm">
                                        <i class="pi pi-folder text-slate-400 text-xs"></i>
                                    </div>
                                    <span class="font-black text-xs text-slate-700 uppercase tracking-wider">{{ group.parent.label }}</span>
                                </div>
                                <i :class="isGroupExpanded(group.parent.key) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="text-[10px] text-slate-400"></i>
                            </div>

                            <div v-if="isGroupExpanded(group.parent.key)" class="p-4 space-y-6 border-t border-slate-50">
                                <div v-for="child in group.children" :key="child.key" class="p-5 rounded-2xl bg-slate-50/50 border border-slate-100">
                                    <div class="flex flex-wrap justify-between items-center gap-4 mb-4 border-b border-slate-200/60 pb-3">
                                        <span class="text-xs font-black text-primary-600 uppercase">{{ child.label }}</span>
                                        <div class="flex gap-2">
                                            <Button v-if="form.node_instructions[child.key]?.length"
                                                    icon="pi pi-copy" :label="t('maintenances.formDialog.clone')"
                                                    class="p-button-text p-button-sm text-[10px] font-bold"
                                                    @click="openCopyDialog(child.key)" />
                                            <Button icon="pi pi-plus" :label="t('maintenances.formDialog.addInstruction')"
                                                    class="p-button-primary p-button-sm text-[10px] font-bold shadow-sm"
                                                    @click="addInstruction(child.key)" />
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div v-for="(instruction, index) in form.node_instructions[child.key]" :key="index"
                                             class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-3 rounded-xl border border-slate-200 shadow-sm relative group">

                                            <div class="md:col-span-6">
                                                <InputText v-model="instruction.label" :placeholder="t('maintenances.formDialog.instructionLabelPlaceholder')" class="w-full p-inputtext-sm border-none shadow-none font-medium" />
                                            </div>
                                            <div class="md:col-span-3">
                                                <Dropdown v-model="instruction.type" :options="instructionValueTypes"
                                                          optionLabel="label" optionValue="value" class="w-full p-inputtext-sm border-slate-100" />
                                            </div>
                                            <div class="md:col-span-2 flex items-center justify-center gap-2">
                                                <Checkbox v-model="instruction.is_required" binary :inputId="`req-${child.key}-${index}`" />
                                                <label :for="`req-${child.key}-${index}`" class="text-[9px] font-black uppercase text-slate-400 cursor-pointer">{{ t('maintenances.formDialog.required') }}</label>
                                            </div>
                                            <div class="md:col-span-1 flex justify-end">
                                                <Button icon="pi pi-trash" class="p-button-danger p-button-text p-button-sm opacity-0 group-hover:opacity-100 transition-opacity"
                                                        @click="removeInstruction(child.key, index)" />
                                            </div>
                                        </div>
                                        <div v-if="!form.node_instructions[child.key]?.length" class="text-center py-4 text-[10px] font-bold text-slate-400 uppercase tracking-tighter italic">
                                            {{ t('maintenances.formDialog.noSpecificInstruction') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-5 space-y-6">

                <div class="p-8 bg-slate-900 rounded-[1rem] text-white shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-6 opacity-10">
                        <i class="pi pi-calendar-plus text-6xl"></i>
                    </div>

                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] mb-8 text-primary-400 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                        {{ t('maintenances.formDialog.logisticsAndTiming') }}
                    </h4>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-slate-400 mb-2 block ml-1">{{ form.status === 'Planifiée' ? t('maintenances.formDialog.plannedStart') : t('maintenances.formDialog.actualStart') }}</label>
                                <Calendar v-model="form.scheduled_start_date" showTime dateFormat="dd/mm/yy" showIcon class="quantum-calendar-dark w-full" />
                                <small class="p-error block mt-1 text-red-400" v-if="form.errors.scheduled_start_date">{{ form.errors.scheduled_start_date }}</small>
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-slate-400 mb-2 block ml-1">{{ form.status === 'Planifiée'? t('maintenances.formDialog.plannedEnd') : t('maintenances.formDialog.actualEnd') }}</label>
                                <Calendar v-model="form.scheduled_end_date" showTime dateFormat="dd/mm/yy" showIcon class="quantum-calendar-dark w-full" />
                                <small class="p-error block mt-1 text-red-400" v-if="form.errors.scheduled_end_date">{{ form.errors.scheduled_end_date }}</small>
                            </div>
                        </div>

                        <div class="field bg-white/5 p-4 rounded-2xl border border-white/10">
                            <label class="text-[9px] font-bold uppercase text-slate-400 mb-3 block">{{ t('maintenances.formDialog.interventionResponsible') }}</label>
                            <div class="flex gap-2">
                                <Dropdown v-model="form.assignable_type" :options="assignableTypes" optionLabel="label" optionValue="value"
                                          :placeholder="t('maintenances.formDialog.assignableTypePlaceholder')" class="w-1/3 bg-transparent border-white/20 text-white" />
                                <Dropdown v-model="form.assignable_id" :options="assignables" optionLabel="name" optionValue="id"
                                          :placeholder="t('maintenances.formDialog.assignableSelectPlaceholder')" :disabled="!form.assignable_type" filter
                                          class="flex-1 bg-transparent border-white/20 text-white" />
                            </div>
                            <small class="p-error block mt-1 text-red-400" v-if="form.errors.assignable_id">{{ form.errors.assignable_id }}</small>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-slate-400 mb-2 block ml-1">{{ t('maintenances.formDialog.maintenanceType') }}</label>
                                <Dropdown v-model="form.type" :options="maintenanceTypes" class="w-full bg-white/5 border-white/10 text-white rounded-xl" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-slate-400 mb-2 block ml-1">{{ t('maintenances.formDialog.currentStatus') }}</label>
                                <Dropdown v-model="form.status" :options="maintenanceStatuses" optionLabel="label" optionValue="value"
                                          class="w-full bg-white/5 border-white/10 text-white rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-slate-400 mb-2 block ml-1">{{ t('maintenances.formDialog.priorityLevel') }}</label>
                                <Dropdown v-model="form.priority" :options="maintenancePriorities" class="w-full bg-white/5 border-white/10 text-white rounded-xl" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-slate-400 mb-2 block ml-1">{{ t('maintenances.formDialog.interventionRegion') }}</label>
                                <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter
                                          class="w-full bg-white/5 border-white/10 text-white rounded-xl" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-primary-50 rounded-[1rem] border border-primary-100 shadow-sm space-y-6">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-primary-700 flex items-center gap-2">
                        <i class="pi pi-sync"></i> {{ t('maintenances.formDialog.recurrenceCycle') }}
                    </h4>

                    <div class="space-y-5">
                        <div class="field">
                            <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block ml-1">{{ t('maintenances.formDialog.frequency') }}</label>
                            <Dropdown v-model="form.recurrence_type" :options="recurrenceTypes" optionLabel="label" optionValue="value"
                                      class="w-full quantum-input !bg-white shadow-none" />
                        </div>

                        <div v-if="form.recurrence_type === 'daily'" class="field animate-fade-in">
                            <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block">{{ t('maintenances.formDialog.dailyInterval') }}</label>
                            <InputNumber v-model="form.recurrence_interval" :min="1" :max="365" suffix=" jours" class="w-full" />
                        </div>

                        <div v-if="form.recurrence_type === 'weekly'" class="field animate-fade-in p-4 bg-white rounded-2xl border border-primary-100">
                            <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block">{{ t('maintenances.formDialog.daysSelection') }}</label>
                            <MultiSelect v-model="form.recurrence_days" :options="daysOfWeek" optionLabel="label" optionValue="value"
                                         display="chip" class="w-full border-none p-0" />
                        </div>

                        <div v-if="form.recurrence_type === 'monthly'" class="grid grid-cols-2 gap-4 animate-fade-in">
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block ml-1">{{ t('maintenances.formDialog.dayOfMonth') }}</label>
                                <InputNumber v-model="form.recurrence_day_of_month" :min="1" :max="31" class="w-full" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block ml-1">{{ t('maintenances.formDialog.everyNMonths') }}</label>
                                <InputNumber v-model="form.recurrence_month_interval" :min="1" :max="12" suffix=" mois" class="w-full" />
                            </div>
                        </div>

                        <div v-if="['quarterly', 'biannual', 'annual'].includes(form.recurrence_type)" class="space-y-4 animate-fade-in">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block ml-1">{{ t('maintenances.formDialog.startMonth') }}</label>
                                    <Dropdown v-model="form.recurrence_month" :options="months" optionLabel="label" optionValue="value" class="w-full bg-white" />
                                </div>
                                <div class="field">
                                    <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block ml-1">{{ t('maintenances.formDialog.dayOfMonth') }}</label>
                                    <InputNumber v-model="form.recurrence_day_of_month" :min="1" :max="31" class="w-full" />
                                </div>
                            </div>
                            <div class="field p-4 bg-primary-600 rounded-2xl text-white">
                                <label class="text-[9px] font-bold uppercase opacity-70 mb-2 block">{{ t('maintenances.formDialog.earlyReminder') }}</label>
                                <div class="flex items-center gap-3">
                                    <InputNumber v-model="form.reminder_days" :min="0" suffix=" jours" class="flex-1 quantum-input-dark" />
                                    <i class="pi pi-bell animate-bounce text-primary-200"></i>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.recurrence_type === 'custom'" class="field animate-fade-in">
                            <label class="text-[9px] font-bold uppercase text-primary-400 mb-2 block">{{ t('maintenances.formDialog.customRule') }}</label>
                            <Textarea v-model="form.custom_recurrence_config" rows="2" class="w-full quantum-input !bg-white" :placeholder="t('maintenances.formDialog.customRulePlaceholder')" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-6 bg-white rounded-3xl border border-slate-100 flex flex-col items-center text-center shadow-sm group hover:border-primary-300 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center mb-3 group-hover:bg-primary-50">
                            <i class="pi pi-clock text-slate-400 group-hover:text-primary-500"></i>
                        </div>
                        <label for="estimated_duration" class="text-[8px] font-black uppercase text-slate-400 tracking-tighter mb-1 block">{{ t('maintenances.formDialog.estimatedDuration') }}</label>
                        <InputNumber id="estimated_duration" v-model="form.estimated_duration" suffix=" min" class="quantum-input-transparent" :min="0" />
                    </div>

                    <div class="p-6 bg-white rounded-3xl border border-slate-100 flex flex-col items-center text-center shadow-sm group hover:border-emerald-300 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center mb-3 group-hover:bg-emerald-50">
                            <i class="pi pi-wallet text-slate-400 group-hover:text-emerald-500"></i>
                        </div>
                        <label for="cost" class="text-[8px] font-black uppercase text-slate-400 tracking-tighter mb-1 block">{{ t('maintenances.formDialog.estimatedBudget') }}</label>
                        <InputNumber id="cost" v-model="form.cost" mode="currency" currency="XOF" locale="fr-FR" class="quantum-input-transparent text-emerald-600 font-bold" :min="0" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-4 py-2">
            <Button :label="t('maintenances.formDialog.cancelChanges')" icon="pi pi-times" class="p-button-text p-button-secondary font-bold" @click="hideDialog" />
            <div class="flex gap-3">
                 <Button :label="t('maintenances.formDialog.savePlan')" icon="pi pi-check-circle"
                        class="p-button-primary px-8 py-4 !rounded-2xl shadow-xl shadow-primary-100 font-black tracking-tight transition-all hover:scale-[1.02] active:scale-95"
                        @click="saveMaintenance" :loading="form.processing" />
            </div>
        </div>
    </template>
</Dialog>
    </AppLayout>
</template>

<style>
/* Style spécifique pour la table afin d'affiner le rendu Tailwind avec PrimeVue */
.v11-table .p-datatable-thead > tr > th {
    background: #f8fafc !important;
    color: #94a3b8 !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15em !important;
    padding: 1.5rem 1rem !important;
    border: none !important;
}

.v11-table .p-datatable-tbody > tr {
    transition: all 0.2s ease;
}

.v11-table .p-datatable-tbody > tr:hover {
    background: #f1f5f9/50 !important;
}

.p-dialog-mask {
    backdrop-filter: blur(4px);
}
</style>
