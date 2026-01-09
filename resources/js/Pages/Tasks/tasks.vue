<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
const activeStep = ref(1)
const props = defineProps({ // Changed from maintenances to tasks
    tasks: Object,
    filters: Object,
    equipments: Array,
    users: Array,
    teams: Object,
    regions: Array,
    departments: Array,
    equipmentTree: Array,
    spareParts: Array, // Ajout des pièces détachées
    // Vous devrez ajouter les sous-traitants (subcontractors) ici quand ils seront disponibles

    categories: Array,
    // subcontractors: Array,
});
const toast = useToast();
const confirm = useConfirm();
// Changed from maintenanceDialog to taskDialog
const taskDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const dt = ref(); // Référence au DataTable pour l'export
const selectedTasks = ref([]);
const op = ref(); // Référence à l'OverlayPanel pour la sélection de colonnes

// Colonnes pour la sélection
const allColumns = ref([
    { field: 'title', header: 'Titre' },
    { field: 'equipments', header: 'Équipement(s)' },
    { field: 'assignable', header: 'Assigné à' },
    { field: 'status', header: 'Statut' },
    { field: 'priority', header: 'Priorité' },
    { field: 'planned_start_date', header: 'Début Planifié' },
]);
const visibleColumns = ref(allColumns.value.map(col => col.field)); // Affiche toutes les colonnes par défaut

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'title': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'assignable.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'status': { value: null, matchMode: FilterMatchMode.EQUALS },
    'priority': { value: null, matchMode: FilterMatchMode.EQUALS },
});

const initFilters = () => {
    filters.value = {
        'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'title': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'assignable.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'status': { value: null, matchMode: FilterMatchMode.EQUALS },
        'priority': { value: null, matchMode: FilterMatchMode.EQUALS },
    };
    search.value = ''; // Also reset the old search ref if it's still used by performSearch
};

// Pour les lignes étendues dans le DataTable
const expandedRows = ref([]);

// État pour les enfants sélectionnés pour les instructions
const selectedChildrenForInstructions = ref({});
const showAdvancedInstructions = ref(false);

// État pour la fonctionnalité de copie d'instructions
const copyInstructionsDialog = ref(false);
const sourceNodeKeyForCopy = ref(null);
const selectedCopyTargets = ref({}); // Will hold the selection from TreeSelect

// État pour les groupes d'instructions dépliés/repliés
const expandedInstructionGroups = ref({});

// Dialog pour la création de pièce détachée
const sparePartDialog = ref(false);
const sparePartForm = useForm({ name: '', reference: '', category_id: null, stock_quantity: 0, unit_estimated_cost: 0 });


const form = useForm({ // Changed from maintenance to task
    id: null,
    title: '',
    description: '',
    assignable_type: null,
    assignable_id: null,
    maintenance_type: 'Préventive',
    status: 'Planifiée',
    priority: 'Moyenne',
    planned_start_date: null,
    planned_end_date: null,
    time_spent: null,
    estimated_cost: null,
    region_id: null,
    recurrence_type: null, // Nouvelle propriété pour le type de récurrence
    recurrence_interval: null, // Intervalle pour quotidienne, trimestrielle, semestrielle, annuelle
    recurrence_month_interval: null, // Intervalle en mois pour la récurrence mensuelle
    recurrence_days: [], // Nouvelle propriété pour les jours de la semaine (pour hebdomadaire)
    recurrence_day_of_month: null, // Nouvelle propriété pour le jour du mois (pour mensuel)
    recurrence_month: null, // Nouvelle propriété pour le mois (pour annuel)
    reminder_days: null, // Jours de rappel avant exécution
    custom_recurrence_config: null, // Pour la récurrence personnalisée
    requester_department: null, // Nom du demandeur
    department: '', // Service destinateur
    requires_shutdown: true, // Équipement hors tension (Oui/Non, par défaut Oui)
    instructions: {}, // Pour les instructions spécifiques aux noeuds,
    images: [], // Pour les images liées à la tâche
    related_equipments: {}, // Pour les équipements liés (TreeSelect model),
    jobber: 0,
    service_order_description: 'x', // Description du bon de commande
    service_order_cost: 0, // Coût du bon de commande
    spare_parts: [], // Pour les pièces détachées
});

// Options pour les listes déroulantes
const taskTypes = ref(['Préventive', 'Corrective', 'Améliorative', 'Périodique']); // Changed from maintenanceTypes to taskTypes
const taskStatuses = ref(['Planifiée', 'En cours', 'En attente', 'Terminée', 'Annulée', 'En retard']); // Changed from maintenanceStatuses to taskStatuses
const taskPriorities = ref(['Basse', 'Moyenne', 'Haute', 'Urgente']); // Changed from maintenancePriorities to taskPriorities
const assignableTypes = ref([
    { label: 'Aucun', value: null },
    { label: 'Technicien', value: 'App\\Models\\User' },
    { label: 'Équipe', value: 'App\\Models\\Team' },
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
    { label: 'Aucune', value: null },
    { label: 'Quotidienne', value: 'daily' },
    { label: 'Hebdomadaire', value: 'weekly' },
    { label: 'Mensuelle', value: 'monthly' },
    { label: 'Trimestrielle', value: 'quarterly' },
    { label: 'Semestrielle', value: 'biannual' },
    { label: 'Annuelle', value: 'annual' },
    { label: 'Personnalisée', value: 'custom' },
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
            delete form.instructions[key];
        }
    });
});

// Surveiller les équipements liés pour mettre à jour les enfants cochés par défaut
watch(() => form.related_equipments, (newSelection) => {
    const newSelectedChildren = {}; // This is for UI state, not for form submission
    if (newSelection) {
        const selectedKeys = Object.keys(newSelection).filter(key => newSelection[key].checked);
        configurableNodes.value.forEach(node => {
            if (selectedKeys.includes(node.key)) {
                newSelectedChildren[node.key] = true;
            }
        });
    }
    selectedChildrenForInstructions.value = newSelectedChildren;

    const relevantKeys = Object.keys(newSelectedChildren); // These are the keys of currently selected equipments

}, { deep: true });


const openNew = () => { // Changed from maintenance to task
    form.reset();
    editing.value = false;
    submitted.value = false;
    showAdvancedInstructions.value = false;
    taskDialog.value = true; // Changed from taskDialog to taskDialog
    selectedChildrenForInstructions.value = {};

    // Set default planned dates for new maintenance
    const today = new Date();
    const defaultStartTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 7, 0); // Today at 7:00
    const defaultEndTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 16, 0); // Today at 16:00
    form.planned_start_date = defaultStartTime;
    form.planned_end_date = defaultEndTime;
};

const hideDialog = () => { // Changed from taskDialog to taskDialog
    taskDialog.value = false; // Changed from taskDialog to taskDialog
    submitted.value = false;
};

const editTask = (maintenance) => { // Changed from maintenance to task

    form.id = maintenance.id;
    form.title = maintenance.title;
    form.description = maintenance.description;
    form.assignable_type = maintenance.assignable_type;
    form.assignable_id = maintenance.assignable_id;
    form.maintenance_type = maintenance.maintenance_type;
    form.status = maintenance.status;
    form.priority = maintenance.priority;
    form.planned_start_date = maintenance.planned_start_date ? new Date(maintenance.planned_start_date) : null;
    form.planned_end_date = maintenance.planned_end_date ? new Date(maintenance.planned_end_date) : null;
    form.time_spent = maintenance.time_spent;
    form.estimated_cost = maintenance.estimated_cost;
    form.region_id = maintenance.region_id;
    form.recurrence_type = maintenance.recurrence_type;
    form.recurrence_interval = maintenance.recurrence_interval;
    form.recurrence_days = maintenance.recurrence_days || []; // Déjà un tableau grâce au cast Laravel
    form.recurrence_day_of_month = maintenance.recurrence_day_of_month;
    form.recurrence_month_interval = maintenance.recurrence_month_interval;
    form.recurrence_month = maintenance.recurrence_month;
    form.reminder_days = maintenance.reminder_days;
    form.custom_recurrence_config = maintenance.custom_recurrence_config;
    form.requester_department = maintenance.requester_department;
    form.department = maintenance.department;
    form.requires_shutdown = maintenance.requires_shutdown ?? true; // Default to true if null/undefined

    // Transformer les instructions plates en objet groupé par equipment_id
    const groupedInstructions = {};
    if (maintenance.instructions && Array.isArray(maintenance.instructions)) {
        maintenance.instructions.forEach(inst => {
            const key = String(inst.equipment_id);
            if (!groupedInstructions[key]) groupedInstructions[key] = [];
            groupedInstructions[key].push({ ...inst });
        });
    }
    form.instructions = groupedInstructions;
    form.images = maintenance.images || [];
    // Transformer les équipements pour le TreeSelect
    const relatedEquipmentsForTree = {};
    if (maintenance.equipments) {
        maintenance.equipments.forEach(eq => {
            relatedEquipmentsForTree[String(eq.id)] = { checked: true, partialChecked: false };
        });
    }
    form.related_equipments = relatedEquipmentsForTree;

    console.log(form.assignable_id);
    // Initialiser les enfants sélectionnés si des instructions existent
    showAdvancedInstructions.value = false;
    editing.value = true; // Changed from maintenance to task
    taskDialog.value = true; // Changed from taskDialog to taskDialog
};

const saveMaintenance = () => { // Changed from maintenance to task
    submitted.value = true;
    if (!form.title || !form.related_equipments || !form.maintenance_type) {
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

    // Convertir instructions en un tableau d'objets pour la soumission
    const instructionsData = Object.keys(form.instructions).flatMap(nodeKey =>
        form.instructions[nodeKey].map(instruction => ({ ...instruction, equipment_id: parseInt(nodeKey) }))
    );

    // Dans saveMaintenance:

const data = {
    ...form.data(),
    spare_parts: form.spare_parts.map(sp => ({
        id: sp.id,
        quantity_used: sp.quantity_used
    })),
    equipment_ids: equipmentIds,
    instructions: instructionsData,
    planned_start_date: form.planned_start_date ? new Date(form.planned_start_date).toISOString().slice(0, 19).replace('T', ' ') : null,
    planned_end_date: form.planned_end_date ? new Date(form.planned_end_date).toISOString().slice(0, 19).replace('T', ' ') : null, // Utiliser le nouveau tableau d'instructions
};
console.log("data");
console.log(data);
    // Assurer que assignable_id est null si assignable_type est null
    if (!form.assignable_type) {
        data.assignable_id = null;
    }
    data.instructions = instructionsData;
    if (editing.value) {
        router.put(route('tasks.update', form.id), data, { // Changed from maintenances.update to tasks.update
            onSuccess: () => {
                taskDialog.value = false; // Changed from taskDialog to taskDialog
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Ordre de Travail mise à jour avec succès.', life: 3000 });
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
        router.post(route('tasks.store'), data, { // Changed from maintenances.store to tasks.store
        onSuccess: () => {
            taskDialog.value = false; // Changed from taskDialog to taskDialog
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Ordre de Travail créée avec succès.', life: 3000 });
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

const deleteTask = (task) => { // Changed from deleteTask to deleteTask
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la tâche "${task.title}" ?`, // Changed from maintenance to task
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => { // Changed from maintenance to task
            router.delete(route('tasks.destroy', task.id), { // Changed from maintenances.destroy to tasks.destroy
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Ordre de Travail supprimée.', life: 3000 });
                }
            });
        },
    });
};

const deleteSelectedTasks = () => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedTasks.value.length} tâches sélectionnées ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('tasks.bulkDestroy'), {
                ids: selectedTasks.value.map(t => t.id)
            }, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Tâches supprimées avec succès.', life: 3000 });
                    selectedTasks.value = [];
                }
            });
        },
    });
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('tasks.index'), { search: search.value }, { // Changed from maintenances.index to tasks.index
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const getStatusSeverity = (status) => {
    const severities = { 'Planifiée': 'info', 'En cours': 'warning', 'Terminée': 'success', 'Annulée': 'secondary', 'En retard': 'danger', 'En attente': 'contrast' };
    return severities[status] || null;
};

const getPrioritySeverity = (priority) => {
    const severities = { 'Basse': 'info', 'Moyenne': 'success', 'Haute': 'warning', 'Urgente': 'danger' };
    return severities[priority] || null;
};

const dialogTitle = computed(() => editing.value ? 'Modifier la Ordre de Travail' : 'Créer une nouvelle Ordre de Travail'); // Changed from Maintenance to Ordre de Travail
const bulkDeleteButtonIsDisabled = computed(() => !selectedTasks.value || selectedTasks.value.length < 2);

const taskStats = computed(() => {
    const total = props.tasks.data.length;
    const inProgress = props.tasks.data.filter(t => t.status === 'En cours').length;
    const completed = props.tasks.data.filter(t => t.status === 'Terminée').length;
    return { total, inProgress, completed };
});

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
        const parentLabel = parent ? parent.label : 'Équipements de premier niveau';

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
    if (!form.instructions[nodeKey]) {
        form.instructions[nodeKey] = [];
    }
    form.instructions[nodeKey].push({ label: '', type: 'text', is_required: false });
};

const removeInstruction = (nodeKey, index) => {
    if (form.instructions[nodeKey] && form.instructions[nodeKey][index]) {
        form.instructions[nodeKey].splice(index, 1);
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
    const sourceInstructions = form.instructions[sourceNodeKeyForCopy.value];
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
            form.instructions[targetKey] = JSON.parse(JSON.stringify(sourceInstructions));
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

// Transformation de l'arbre des départements pour TreeSelect
const transformedDepartments = computed(() => {
    if (!props.departments || !Array.isArray(props.departments)) {
        return [];
    }

    const transformNode = (node) => {
        if (!node) return null;
        return {
            key: node.label, // Utilise le label comme clé unique
            label: node.label,
            children: node.children ? node.children.map(transformNode).filter(n => n) : []
        };
    };

    return props.departments.map(transformNode).filter(n => n);
});

const addSparePart = () => {
    form.spare_parts.push({ id: null, quantity_used: 1 });
};

const removeSparePart = (index) => {
    form.spare_parts.splice(index, 1);
};

const openNewSparePart = () => {
    sparePartForm.reset();
    sparePartDialog.value = true;
};

const createSparePart = () => {
    sparePartForm.post(route('spare-parts.store'), {
        onSuccess: () => {
            sparePartDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Pièce détachée créée avec succès.', life: 3000 });
            // Recharger les pièces détachées si nécessaire, ou ajouter la nouvelle à la liste props.spareParts
            router.reload({ only: ['spareParts'] });
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la création de la pièce détachée.', life: 3000 });
        }
    });
};

// Calcul automatique du coût du service
const serviceOrderCost = computed(() => {
    return form.spare_parts.reduce((total, usedPart) => {
        if (!usedPart.id) return total; // Skip if no spare part selected
        const partDetails = props.spareParts.find(p => p.id === usedPart.id);
        const price = partDetails?.unit_estimated_cost || 0; // Utiliser unit_estimated_cost
        return total + (price * (usedPart.quantity_used || 0));
    }, 0);
});

// Mettre à jour le champ service_order_cost du formulaire lorsque serviceOrderCost change
watch(serviceOrderCost, (newCost) => {
    form.service_order_cost = newCost;
    if (newCost > 0 && !form.service_order_description) {
        form.service_order_description = 'Paiement des pièces détachées et autres';
    }
});

// Fonction pour grouper les instructions par équipement pour l'affichage
const groupInstructionsByEquipment = (instructions, equipments) => {
    if (!instructions || !equipments) return [];

    const equipmentMap = new Map(equipments.map(e => [e.id, e]));
    const grouped = instructions.reduce((acc, instruction) => {
        const equipmentId = instruction.equipment_id;
        if (!acc[equipmentId]) {
            acc[equipmentId] = {
                equipment: equipmentMap.get(equipmentId) || { id: equipmentId, designation: `Équipement ID ${equipmentId}` },
                instructions: []
            };
        }
        acc[equipmentId].instructions.push(instruction);
        return acc;
    }, {});

    return Object.values(grouped);
};

// Fonction pour sauvegarder les instructions modifiées depuis la vue détaillée
const updateInstructions = (taskId, instructions) => {
    router.put(route('tasks.updateInstructions', taskId), { instructions }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Instructions mises à jour.', life: 3000 });
            router.reload({ only: ['tasks'] }); // Recharger les données des tâches
        },
        onError: (errors) => toast.add({ severity: 'error', summary: 'Erreur', detail: 'La mise à jour des instructions a échoué.', life: 3000 })
    });
};
</script>

<template>
    <AppLayout title="Gestion des Ordre de Travails">

        <Head title="Ordres de Travail" />
        <Toast />
        <ConfirmDialog />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-briefcase text-2xl text-white"></i>
                    </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">

                        Ordres de Travail <span class="text-primary-600">GMAO</span>
                    </h1>
                    <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">Planification & Suivi des Tâches</p>
                </div></div>
                <div class="flex gap-2">
                    <Button label="Nouvel Ordre de Travail" icon="pi pi-plus"
                            class="shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-briefcase text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ taskStats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tâches Totales</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 flex items-center justify-center"><i class="pi pi-spin pi-spinner text-2xl text-amber-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ taskStats.inProgress }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">En Cours</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-check-circle text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ taskStats.completed }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Terminées</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable :value="props.tasks.data" ref="dt" dataKey="id" v-model:selection="selectedTasks" :paginator="true" :rows="10"
                           v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['title', 'assignable.name', 'status', 'priority']"
                           paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                           currentPageReportTemplate="{first} à {last} sur {totalRecords}"
                           class="p-datatable-sm quantum-table">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher une tâche..."
                                           class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button v-if="!bulkDeleteButtonIsDisabled" :label="`Supprimer (${selectedTasks.length})`" icon="pi pi-trash" severity="danger" @click="deleteSelectedTasks" />
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" v-tooltip.bottom="'Réinitialiser les filtres'" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" v-tooltip.bottom="'Exporter'" />
                                <Button icon="pi pi-columns" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="'Colonnes'" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-if="visibleColumns.includes('title')" header="Titre de la tâche" minWidth="300px">
                        <template #body="{ data }">
                            <div class="font-bold text-slate-800 tracking-tight cursor-pointer" @click="editTask(data)">{{ data.title }}</div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('equipments')" header="Équipement(s)" minWidth="250px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.equipments?.length" class="flex flex-wrap gap-1">
                                <Tag v-for="equipment in slotProps.data.equipments" :key="equipment.id" :value="equipment.designation" class="!bg-slate-200 !text-slate-700 !font-bold" />
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('assignable')" field="assignable.name" header="Assigné à" minWidth="200px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.assignable" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100">
                                <Avatar :label="slotProps.data.assignable.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ slotProps.data.assignable.name }}</span>
                            </div>
                             <span v-else class="text-slate-400 text-xs italic">Non assigné</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.constraints[0].value" type="text" class="p-column-filter" placeholder="Filtrer par assigné" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('status')" field="status" header="Statut" minWidth="150px">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="uppercase text-[9px] px-2" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="taskStatuses" placeholder="Filtrer par statut" class="p-column-filter" showClear />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('priority')" field="priority" header="Priorité" minWidth="150px">
                         <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: getPrioritySeverity(data.priority) === 'danger' ? '#ef4444' : getPrioritySeverity(data.priority) === 'warning' ? '#f59e0b' : getPrioritySeverity(data.priority) === 'success' ? '#10b981' : '#64748b' }"></div>
                                <span class="text-xs font-medium">{{ data.priority }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('planned_start_date')" field="planned_start_date" header="Début Planifié" minWidth="200px">
                        <template #body="slotProps">
                            <span class="font-mono text-sm bg-blue-50 text-blue-600 px-2 py-1 rounded-md">{{ new Date(slotProps.data.planned_start_date).toLocaleString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editTask(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" text rounded @click="deleteTask(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" v-tooltip.top="'Supprimer'" />
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
    v-model:visible="taskDialog"
    modal
    :header="false" :closable="false"
    class="quantum-dialog w-full max-w-7xl overflow-hidden"
    :pt="{
        root: { class: 'border-none shadow-3xl bg-white rounded-xl' },
        mask: { style: 'backdrop-filter: blur(15px); background: rgba(15, 23, 42, 0.8)' }
    }"
>
    <div class="flex flex-col h-[90vh]">

        <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center shadow-lg relative z-50">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-blue-500/20 rounded-lg border border-blue-500/30">
                    <i class="pi pi-shield text-blue-400 text-xl"></i>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-widest text-white leading-none">{{ dialogTitle }}</h2>
                    <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1 italic">Console d'administration GMAO v2025</span>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="flex flex-col items-end mr-4">
                    <span class="text-[9px] font-bold text-slate-400 uppercase mb-1">Niveau d'urgence</span>
                    <SelectButton v-model="form.priority" :options="taskPriorities" class="p-selectbutton-sm custom-dark-priority" />
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="taskDialog = false" class="text-white hover:bg-white/10" />
            </div>
        </div>

        <div class="flex flex-1 overflow-hidden bg-slate-50">
            <div class="w-24 border-r border-slate-200 bg-white flex flex-col items-center py-10 gap-8">
                <div v-for="i in 3" :key="i"
                     @click="activeStep = i"
                     :class="['w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500 cursor-pointer hover:scale-110 shadow-sm',
                              activeStep >= i ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-300 border border-slate-100']">
                    <i :class="['pi text-lg', i==1 ? 'pi-info-circle' : i==2 ? 'pi-calendar-clock' : 'pi-wrench']"></i>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-12 scroll-smooth bg-white rounded-l-[3rem] shadow-inner relative">

                <div class="flex gap-4 mb-10 bg-slate-100/50 p-2 rounded-2xl w-fit border border-slate-100">
                    <button v-for="step in [{v:1, l:'IDENTIFICATION'}, {v:2, l:'PLANIFICATION'}, {v:3, l:'PROTOCOLE & PIÈCES'}]"
                            :key="step.v" @click="activeStep = step.v"
                            :class="['px-6 py-2 rounded-xl text-[10px] font-black tracking-widest transition-all uppercase', activeStep === step.v ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400']">
                        {{ step.l }}
                    </button>
                </div>

                <div v-if="activeStep === 1" class="max-w-4xl mx-auto space-y-10 animate-fade-in">
                    <header class="border-l-4 border-blue-600 pl-6 space-y-1">
                        <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Contexte Mission</h1>
                        <p class="text-slate-400 font-medium">Définissez l'objet, les actifs et la localisation de l'intervention.</p>
                    </header>

                    <div class="grid grid-cols-12 gap-8">
                        <div class="col-span-12">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Objet de l'intervention</label>
                            <InputText v-model="form.title" class="w-full p-4 text-xl font-bold border-none bg-slate-50 rounded-2xl focus:ring-4 focus:ring-blue-50" placeholder="Ex: Remplacement des filtres groupe A..." />
                        </div>

                        <div class="col-span-6">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Nature des travaux</label>
                            <Dropdown v-model="form.maintenance_type" :options="taskTypes" class="w-full p-2 bg-slate-50 border-none rounded-2xl" />
                        </div>

                        <div class="col-span-6">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Actifs Concernés (Équipements)</label>
                            <TreeSelect v-model="form.related_equipments" :options="transformedEquipmentTree" selectionMode="checkbox" display="chip" placeholder="Choisir les équipements" class="w-full bg-slate-50 border-none rounded-2xl" />
                        </div>

                        <div class="col-span-6">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Région Administrative</label>
                            <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter class="w-full bg-slate-50 border-none rounded-2xl" />
                        </div>

                        <div class="col-span-6">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Département Bénéficiaire</label>
                            <TreeSelect v-model="form.department" :options="transformedDepartments" placeholder="Service" class="w-full bg-slate-50 border-none rounded-2xl" />
                        </div>

                        <div class="col-span-12 p-8 bg-slate-900 rounded-[2.5rem] text-white flex items-center gap-8 shadow-2xl">
                            <div class="flex-1 space-y-4">
                                <label class="text-[9px] font-black text-blue-400 uppercase tracking-widest block italic">Responsable de l'Exécution (Assignation)</label>
                                <div class="flex gap-4">
                                    <Dropdown v-model="form.assignable_type" :options="assignableTypes" optionLabel="label" optionValue="value" class="w-1/3 bg-white/10 border-none text-white font-bold" />
                                    <Dropdown v-model="form.assignable_id" :options="assignables" optionLabel="name" optionValue="id" filter class="flex-1 bg-white/10 border-none text-white" />
                                </div>
                            </div>
                            <div class="h-16 w-16 bg-blue-500/20 rounded-full flex items-center justify-center">
                                <i class="pi pi-users text-3xl text-blue-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeStep === 2" class="max-w-4xl mx-auto space-y-12 animate-fade-in">
                    <header class="border-l-4 border-purple-600 pl-6 space-y-1">
                        <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Planification</h1>
                        <p class="text-slate-400 font-medium">Définissez la fenêtre temporelle et les cycles automatiques.</p>
                    </header>

                    <div class="grid grid-cols-2 gap-10">
                        <div class="space-y-8">
                            <div class="p-6 bg-slate-50 rounded-xl border border-slate-100">
                                <label class="text-[10px] font-black text-slate-400 uppercase mb-4 block tracking-widest">Début Planifié</label>
                                <DatePicker v-model="form.planned_start_date" showTime hourFormat="24" class="w-full" />
                            </div>
                            <div class="p-6 bg-slate-50 rounded-xl border border-slate-100">
                                <label class="text-[10px] font-black text-slate-400 uppercase mb-4 block tracking-widest">Échéance de fin</label>
                                <DatePicker v-model="form.planned_end_date" showTime hourFormat="24" class="w-full" />
                            </div>
                        </div>

                        <div class="p-8 bg-primary-50 rounded-[1rem] border border-primary-100 space-y-6 flex flex-col justify-start shadow-sm">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="pi pi-sync text-primary-600 text-xl"></i>
                                <span class="text-xs font-black text-primary-900 uppercase tracking-widest">Récurrence</span>
                            </div>
                            <Dropdown v-model="form.recurrence_type" :options="recurrenceTypes" optionLabel="label" optionValue="value" class="w-full" />

                            <div v-if="form.recurrence_type" class="pt-6 border-t border-primary-200 mt-4 space-y-4 animate-slide-in">
                                <span class="text-[10px] font-bold text-primary-700 uppercase">Intervalle</span>
                                <InputNumber v-model="form.recurrence_interval" class="w-full" suffix=" cycles" />
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase mb-3 block tracking-widest">Description détaillée</label>
                            <Textarea v-model="form.description" rows="5" class="w-full p-6 bg-slate-50 border-none rounded-[1rem] text-slate-700 shadow-inner" placeholder="Notes additionnelles pour l'équipe terrain..." />
                        </div>
                    </div>
                </div>

                <div v-if="activeStep === 3" class="max-w-4xl mx-auto space-y-12 animate-fade-in">
                    <header class="border-l-4 border-emerald-600 pl-6 flex justify-between items-end">
                        <div class="space-y-1">
                            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Ressources & Procédures</h1>
                            <p class="text-slate-400 font-medium">Inventaire des pièces et liste de contrôle technique.</p>
                        </div>
                        <Button label="Ajouter pièce" icon="pi pi-plus" size="small" variant="outlined" rounded @click="addSparePart" />
                    </header>

                    <div class="bg-white rounded-[1rem] border border-slate-100 shadow-sm overflow-hidden">
                        <DataTable :value="form.spare_parts" class="p-datatable-sm" responsiveLayout="scroll">
                            <Column header="Pièce / Référence" class="pl-8 py-4">
                                <template #body="{ data }">
                                    <Dropdown v-model="data.id" :options="props.spareParts" optionLabel="name" optionValue="id" filter class="w-full border-none bg-transparent" placeholder="Rechercher une pièce..." />
                                </template>
                            </Column>
                            <Column header="Qté" class="w-32">
                                <template #body="{ data }">
                                    <InputNumber v-model="data.quantity_used" :min="1" class="w-full bg-slate-50 rounded-xl" />
                                </template>
                            </Column>
                            <Column class="w-16 text-right pr-6">
                                <template #body="{ index }">
                                    <Button icon="pi pi-trash" severity="danger" variant="text" rounded @click="removeSparePart(index)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <div class="space-y-8">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 px-2">
                            <i class="pi pi-check-circle text-emerald-500"></i> Points de contrôle par actif
                        </h3>

                        <div v-for="node in configurableNodes" :key="node.key" class="rounded-[1rem] border border-slate-100 bg-slate-50/50 overflow-hidden shadow-sm mb-6">
                            <div class="px-8 py-4 bg-slate-800 text-white flex justify-between items-center">
                                <span class="text-[10px] font-black tracking-widest uppercase">{{ node.label }}</span>
                                <Button icon="pi pi-plus" label="Ajouter point" size="small" variant="text" class="text-blue-300 text-[9px] font-black uppercase" @click="addInstruction(node.key)" />
                            </div>

                            <div class="p-8 space-y-4">
                                <div v-for="(inst, iIdx) in form.instructions[node.key]" :key="iIdx" class="flex gap-4 items-center animate-fade-in group">
                                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-xs font-black shadow-sm border border-slate-100 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                        {{ iIdx + 1 }}
                                    </div>
                                    <InputText v-model="inst.label" class="flex-1 bg-white border-none rounded-2xl p-4 shadow-sm" placeholder="Action à réaliser..." />
                                    <Dropdown v-model="inst.type" :options="instructionValueTypes" optionLabel="label" optionValue="value" class="w-40 bg-white border-none rounded-2xl" />
                                    <Button icon="pi pi-trash" severity="danger" variant="text" rounded @click="removeInstruction(node.key, iIdx)" />
                                </div>
                                <div v-if="!form.instructions[node.key]?.length" class="text-center py-4 text-slate-400 italic text-xs">
                                    Aucune instruction pour cet équipement.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-80 bg-slate-50 p-8 flex flex-col justify-between border-l border-slate-200">
                <div class="space-y-8">
                    <div class="space-y-3">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block text-center italic">Cycle Opérationnel</label>
                        <Dropdown v-model="form.status" :options="taskStatuses" class="w-full border-none shadow-xl bg-white rounded-2xl font-black text-slate-800 p-1" />
                    </div>

                    <div class="p-8 bg-white rounded-[1rem] shadow-xl shadow-slate-200/50 space-y-8 border border-slate-100">
                        <div class="field">
                            <span class="text-[9px] font-black text-slate-400 uppercase block mb-3 uppercase">Budget Estimé</span>
                            <InputNumber v-model="form.estimated_cost" mode="currency" currency="USD" locale="fr-FR" class="w-full text-2xl font-black text-slate-900" />
                        </div>
                        <Divider class="my-0" />
                        <div class="field">
                            <span class="text-[9px] font-black text-slate-400 uppercase block mb-3 text-blue-500">Temps d'exécution (min)</span>
                            <InputNumber v-model="form.time_spent" suffix=" min" class="w-full font-black text-xl text-blue-600" />
                        </div>
                    </div>

                    <div :class="['p-8 rounded-[1rem] border-2 transition-all duration-500 cursor-pointer shadow-lg',
                                  form.requires_shutdown ? 'bg-red-50 border-red-200 shadow-red-100' : 'bg-slate-100 border-transparent opacity-40']"
                         @click="form.requires_shutdown = !form.requires_shutdown">
                        <div class="flex items-center gap-4">
                            <Checkbox v-model="form.requires_shutdown" :binary="true" />
                            <span :class="['text-xs font-black uppercase tracking-tight', form.requires_shutdown ? 'text-red-700' : 'text-slate-500']">Consignation (LOTO)</span>
                        </div>
                        <p v-if="form.requires_shutdown" class="text-[9px] text-red-600 mt-4 font-black animate-pulse leading-tight uppercase">Arrêt total requis</p>
                    </div>
                </div>

                <Button label="Enregistrer l'Ordre" icon="pi pi-check-circle" severity="success" class="w-full h-20 rounded-[1rem] font-black tracking-widest uppercase shadow-2xl mt-8" @click="saveMaintenance" :loading="form.processing" />
            </div>
        </div>

        <div class="px-12 py-6 bg-white border-t border-slate-100 flex justify-between items-center bg-white/80 backdrop-blur-md relative z-50">
            <Button v-if="activeStep > 1" label="Précédent" icon="pi pi-arrow-left" severity="secondary" variant="text" @click="activeStep--" class="font-black text-xs uppercase tracking-widest" />
            <div v-else class="w-[100px]"></div>

            <div class="flex gap-3">
                <div v-for="i in 3" :key="i" :class="['h-2 rounded-full transition-all duration-500', activeStep == i ? 'bg-blue-600 w-12' : 'bg-slate-200 w-3']"></div>
            </div>

            <Button v-if="activeStep < 3" label="Continuer" icon="pi pi-arrow-right" iconPos="right" @click="activeStep++" class="px-14 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-slate-300" />
            <div v-else class="w-[140px]"></div>
        </div>
    </div>
</Dialog>

                    <!-- Dialog pour créer une nouvelle pièce détachée -->
                    <Dialog v-model:visible="sparePartDialog" modal header="Créer une nouvelle pièce détachée" :style="{ width: '30rem' }">
                        <div class="p-fluid">
                            <div class="field">
                                <label for="spare_part_name" class="font-semibold">Nom</label>
                                <InputText id="spare_part_name" v-model.trim="sparePartForm.name" :class="{ 'p-invalid': sparePartForm.errors.name }" />
                                <small class="p-error">{{ sparePartForm.errors.name }}</small>
                            </div>
                            <div class="field">
                                <label for="spare_part_reference" class="font-semibold">Référence</label>
                                <InputText id="spare_part_reference" v-model.trim="sparePartForm.reference" :class="{ 'p-invalid': sparePartForm.errors.reference }" />
                                <small class="p-error">{{ sparePartForm.errors.reference }}</small>
                            </div>
                            <div class="field">
                                <label for="spare_part_category" class="font-semibold">Catégorie</label>
                                <Dropdown
                                    id="spare_part_category"
                                    v-model="sparePartForm.category_id"
                                    :options="props.categories"
                                    optionLabel="designation"
                                    optionValue="id"
                                    placeholder="Sélectionner une catégorie"
                                    :class="{ 'p-invalid': sparePartForm.errors.category_id }"
                                />
                                <small class="p-error">{{ sparePartForm.errors.category_id }}</small>
                            </div>
                            <div class="field">
                                <label for="spare_part_stock_quantity" class="font-semibold">Quantité en stock</label>
                                <InputNumber id="spare_part_stock_quantity" v-model="sparePartForm.stock_quantity" :min="0" :class="{ 'p-invalid': sparePartForm.errors.stock_quantity }" />
                                <small class="p-error">{{ sparePartForm.errors.stock_quantity }}</small>
                            </div>
                            <div class="field">
                                <label for="spare_part_unit_estimated_cost" class="font-semibold">Coût unitaire</label>
                                <InputNumber id="spare_part_unit_estimated_cost" v-model="sparePartForm.unit_estimated_cost" mode="currency" currency="USD" locale="fr-FR" :min="0" :class="{ 'p-invalid': sparePartForm.errors.unit_estimated_cost }" />
                                <small class="p-error">{{ sparePartForm.errors.unit_estimated_cost }}</small>
                            </div>
                        </div>
                        <template #footer>
                            <Button label="Annuler" icon="pi pi-times" @click="sparePartDialog = false" class="p-button-text" />
                            <Button label="Créer" icon="pi pi-check" @click="createSparePart" :loading="sparePartForm.processing" />
                        </template>
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
</style>
