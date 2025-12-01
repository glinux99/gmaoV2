<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({ // Changed from maintenances to tasks
    tasks: Object,
    filters: Object,
    equipments: Array,
    users: Array,
    teams: Object,
    regions: Array,
    equipmentTree: Array,
    // Vous devrez ajouter les sous-traitants (subcontractors) ici quand ils seront disponibles

    // subcontractors: Array,
});
const toast = useToast();
const confirm = useConfirm();
// Changed from maintenanceDialog to taskDialog
const maintenanceDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

// État pour les enfants sélectionnés pour les instructions
const selectedChildrenForInstructions = ref({});
const showAdvancedInstructions = ref(false);

// État pour la fonctionnalité de copie d'instructions
const copyInstructionsDialog = ref(false);
const sourceNodeKeyForCopy = ref(null);
const selectedCopyTargets = ref({}); // Will hold the selection from TreeSelect

// État pour les groupes d'instructions dépliés/repliés
const expandedInstructionGroups = ref({});


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
    requester_name: '', // Nom du demandeur
    department: '', // Service destinateur
    requires_shutdown: true, // Équipement hors tension (Oui/Non, par défaut Oui)
    node_instructions: {}, // Pour les instructions spécifiques aux noeuds,
    images: [], // Pour les images liées à la tâche
    related_equipments: {}, // Pour les équipements liés (TreeSelect model)
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


const openNew = () => { // Changed from maintenance to task
    form.reset();
    editing.value = false;
    submitted.value = false;
    showAdvancedInstructions.value = false;
    maintenanceDialog.value = true; // Changed from maintenanceDialog to taskDialog
    selectedChildrenForInstructions.value = {};

    // Set default planned dates for new maintenance
    const today = new Date();
    const defaultStartTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 7, 0); // Today at 7:00
    const defaultEndTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 16, 0); // Today at 16:00
    form.planned_start_date = defaultStartTime;
    form.planned_end_date = defaultEndTime;
};

const hideDialog = () => { // Changed from maintenanceDialog to taskDialog
    maintenanceDialog.value = false; // Changed from maintenanceDialog to taskDialog
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
    form.cost = maintenance.cost;
    form.region_id = maintenance.region_id;
    form.recurrence_type = maintenance.recurrence_type;
    form.recurrence_interval = maintenance.recurrence_interval;
    form.recurrence_days = maintenance.recurrence_days || []; // Déjà un tableau grâce au cast Laravel
    form.recurrence_day_of_month = maintenance.recurrence_day_of_month;
    form.recurrence_month_interval = maintenance.recurrence_month_interval;
    form.recurrence_month = maintenance.recurrence_month;
    form.reminder_days = maintenance.reminder_days;
    form.custom_recurrence_config = maintenance.custom_recurrence_config;
    form.requester_name = maintenance.requester_name;
    form.department = maintenance.department;
    form.requires_shutdown = maintenance.requires_shutdown ?? true; // Default to true if null/undefined

    form.images = maintenance.images || [];
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
    console.log(form.assignable_id);
    // Initialiser les enfants sélectionnés si des instructions existent
    showAdvancedInstructions.value = false;
    editing.value = true; // Changed from maintenance to task
    maintenanceDialog.value = true; // Changed from maintenanceDialog to taskDialog
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
    // Dans saveMaintenance:
const data = {
    ...form.data(),
    equipment_ids: equipmentIds,
    planned_start_date: form.planned_start_date ? new Date(form.planned_start_date).toISOString().slice(0, 19).replace('T', ' ') : null,
    planned_end_date: form.planned_end_date ? new Date(form.planned_end_date).toISOString().slice(0, 19).replace('T', ' ') : null,
};

    // Assurer que assignable_id est null si assignable_type est null
    if (!form.assignable_type) {
        data.assignable_id = null;
    }
    console.log(form);
    if (editing.value) {
        router.put(route('tasks.update', form.id), data, { // Changed from maintenances.update to tasks.update
            onSuccess: () => {
                maintenanceDialog.value = false; // Changed from maintenanceDialog to taskDialog
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
            maintenanceDialog.value = false; // Changed from maintenanceDialog to taskDialog
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

const getStatusSeverity = (status) => {
    const severities = { 'Planifiée': 'info', 'En cours': 'warning', 'Terminée': 'success', 'Annulée': 'secondary', 'En retard': 'danger', 'En attente': 'contrast' };
    return severities[status] || null;
};

const getPrioritySeverity = (priority) => {
    const severities = { 'Basse': 'info', 'Moyenne': 'success', 'Haute': 'warning', 'Urgente': 'danger' };
    return severities[priority] || null;
};

const dialogTitle = computed(() => editing.value ? 'Modifier la Ordre de Travail' : 'Créer une nouvelle Ordre de Travail'); // Changed from Maintenance to Ordre de Travail

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
    <AppLayout title="Gestion des Ordre de Travails">

        <Head title="Ordre de Travails" />

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                    <Toolbar class="mb-4">
                        <template #start>
                            <Button label="Nouvelle Ordre de Travail" icon="pi pi-plus" class="p-button-success mr-2"
                                @click="openNew" />
                        </template>
                        <template #end>
                            <span class="p-input-icon-left">
                                <i class="pi pi-search" />
                                <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                            </span>
                        </template>
                    </Toolbar>

                    <DataTable :value="props.tasks.data" dataKey="id" :paginator="true" :rows="10"
                        responsiveLayout="scroll" :row-class="() => 'cursor-pointer'">
                        <Column field="title" header="Titre" :sortable="true" style="min-width: 12rem;"></Column>
                        <Column header="Équipement(s)" :sortable="true" style="min-width: 12rem;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.equipments && slotProps.data.equipments.length > 0">
                                    <template v-if="slotProps.data.equipments.length === 1">
                                        {{ slotProps.data.equipments[0].designation }}
                                    </template>
                                    <template v-else>
                                        <Tag v-for="equipment in slotProps.data.equipments" :key="equipment.id" :value="equipment.designation" class="mr-1 mb-1" />
                                    </template>
                                </div>
                            </template>
                        </Column>
                        <Column field="assignable.name" header="Assigné à" :sortable="true" style="min-width: 10rem;">
                            <template #body="slotProps">
                                <Tag v-if="slotProps.data.assignable" :value="slotProps.data.assignable.name" />
                            </template>
                        </Column>
                        <Column field="status" header="Statut" :sortable="true" style="min-width: 8rem;">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.status"
                                    :severity="getStatusSeverity(slotProps.data.status)" />
                            </template>
                        </Column>
                        <Column field="priority" header="Priorité" :sortable="true" style="min-width: 8rem;">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.priority"
                                    :severity="getPrioritySeverity(slotProps.data.priority)" />
                            </template>
                        </Column>
                        <Column field="planned_start_date" header="Début Planifié" :sortable="true"
                        style="min-width: 12rem;">
                            <template #body="slotProps">
                            <span class="text-sm">{{ new Date(slotProps.data.planned_start_date).toLocaleString() }}</span>
                            </template>
                        </Column>
                        <Column headerStyle="min-width:8rem;" header="Actions">
                            <template #body="slotProps">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-info mr-2"
                                    @click="editTask(slotProps.data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="deleteTask(slotProps.data)"/>
                            </template>
                        </Column>
                    </DataTable>
                    <!-- Changed from maintenanceDialog to taskDialog -->
                    <Dialog v-model:visible="maintenanceDialog" modal :header="dialogTitle" :style="{ width: '50rem' }">
                        <div class="p-fluid">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="title" class="font-semibold">Titre</label>
                                    <InputText id="title" class="w-full" v-model.trim="form.title"
                                        :class="{ 'p-invalid': submitted && !form.title }" />
                                    <small class="p-error">{{ form.errors.title }}</small>
                                </div>
                                <div class="field ">

                                    <label for="related_equipments" class="font-semibold">Équipements Liés</label>
                                    <TreeSelect v-model="form.related_equipments" :options="transformedEquipmentTree"
                                        placeholder="Sélectionner des équipements" filter selectionMode="checkbox"
                                        display="chip" class="w-full" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="assignable_type" class="font-semibold">Assigner à (Type)</label>
                                    <Dropdown id="assignable_type" class="w-full" v-model="form.assignable_type"
                                        :options="assignableTypes" optionLabel="label" optionValue="value"
                                        placeholder="Type d'assignation" />
                                    <small class="p-error">{{ form.errors.assignable_type }}</small>
                                </div>
                                <div class="field">
                                    <label for="assignable_id" class="font-semibold">Assigner à (Nom)  </label>
                                    <Dropdown id="assignable_id" class="w-full" v-model="form.assignable_id"
                                        :options="assignables" :key="form.assignable_type" optionLabel="name" optionValue="id"
                                        placeholder="Sélectionner une personne/équipe" :disabled="!form.assignable_type || assignables.length === 0"
                                        filter />
                                    <small class="p-error">{{ form.errors.assignable_id }}</small>
                                </div>
                            </div>

                            <div class="field">
                                <label for="description" class="font-semibold">Description</label>
                                <Textarea id="description" class="w-full" v-model="form.description" rows="3" />
                                <small class="p-error">{{ form.errors.description }}</small>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="type" class="font-semibold">Type de Ordre de Travail</label>
                                    <Dropdown id="type" class="w-full" v-model="form.maintenance_type" :options="taskTypes"
                                        placeholder="Sélectionner un type" />
                                    <small class="p-error">{{ form.errors.type }}</small>
                                </div>
                                <div class="field">
                                    <label for="status" class="font-semibold">Statut</label>
                                    <Dropdown id="status" class="w-full" v-model="form.status" :options="taskStatuses"
                                        placeholder="Sélectionner un statut" />
                                    <small class="p-error">{{ form.errors.status }}</small>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="priority" class="font-semibold">Priorité</label>
                                    <Dropdown id="priority" class="w-full" v-model="form.priority" :options="taskPriorities"
                                        placeholder="Sélectionner une priorité" />
                                    <small class="p-error">{{ form.errors.priority }}</small>
                                </div>
                                <div class="field">
                                    <label for="time_spent" class="font-semibold">Durée Estimée
                                        (minutes)</label>
                                    <InputNumber id="time_spent" class="w-full"
                                        v-model="form.time_spent" :min="0" />
                                    <small class="p-error">{{ form.errors.time_spent }}</small>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="planned_start_date" class="font-semibold">
                                        {{ form.status === 'Planifiée' ? 'Date de début planifiée' : 'Date de début' }}</label>
                                    <Calendar id="planned_start_date" class="w-full"
                                        v-model="form.planned_start_date" showTime dateFormat="dd/mm/yy" showIcon />
                                    <small class="p-error">{{ form.errors.planned_start_date }}</small>
                                </div>
                                <div class="field">
                                    <label for="planned_end_date" class="font-semibold">
                                        {{ form.status === 'Planifiée'? 'Date de fin planifiée' : 'Date de fin' }}</label>
                                    <Calendar id="planned_end_date" class="w-full" v-model="form.planned_end_date"
                                        showTime dateFormat="dd/mm/yy" showIcon />
                                    <small class="p-error">{{ form.errors.planned_end_date }}</small>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="field">
                                    <label for="cost" class="font-semibold">Coût / Budget</label>
                                    <InputNumber id="cost" class="w-full" v-model="form.cost" mode="currency"
                                        currency="XOF" locale="fr-FR" :min="0" />
                                    <small class="p-error">{{ form.errors.cost }}</small>
                                </div>
                                <div class="field">
                                    <label for="region_id" class="font-semibold">Région</label>
                                    <Dropdown id="region_id" class="w-full" v-model="form.region_id"
                                        :options="props.regions" optionLabel="designation" optionValue="id"
                                        placeholder="Sélectionner une région" filter />
                                    <small class="p-error">{{ form.errors.region_id }}</small>
                                </div>
                            </div>

                            <div class="field">
                                <label for="recurrence_type" class="font-semibold">Type de Récurrence</label>
                                <Dropdown id="recurrence_type" class="w-full" v-model="form.recurrence_type"
                                    :options="recurrenceTypes" optionLabel="label" optionValue="value"
                                    placeholder="Sélectionner un type de récurrence" />
                                <small class="p-error">{{ form.errors.recurrence_type }}</small>
                            </div>

                            <div v-if="form.recurrence_type === 'daily'" class="field">
                                <label for="recurrence_interval" class="font-semibold">Nombre de jours</label>
                                <InputNumber id="recurrence_interval" class="w-full" v-model="form.recurrence_interval"
                                    :min="1" :max="365" />
                                <small class="p-error">{{ form.errors.recurrence_interval }}</small>
                            </div>

                            <div v-if="form.recurrence_type === 'weekly'" class="field">
                                <label for="recurrence_days" class="font-semibold">Jours de la semaine</label>
                                <MultiSelect id="recurrence_days" class="w-full" v-model="form.recurrence_days"
                                    :options="daysOfWeek" optionLabel="label" optionValue="value"
                                    placeholder="Sélectionner les jours" display="chip" />
                                <small class="p-error">{{ form.errors.recurrence_days }}</small>
                            </div>

                            <div v-if="form.recurrence_type === 'monthly'" class="field">
                                <label for="recurrence_day_of_month" class="font-semibold">Jour du mois</label>
                                <InputNumber id="recurrence_day_of_month" class="w-full"
                                    v-model="form.recurrence_day_of_month" :min="1" :max="31" />
                                <small class="p-error">{{ form.errors.recurrence_day_of_month }}</small>
                            </div>
                            <div v-if="form.recurrence_type === 'monthly'" class="field">
                                <label for="recurrence_month_interval" class="font-semibold">Nombre de mois</label>
                                <InputNumber id="recurrence_month_interval" class="w-full"
                                    v-model="form.recurrence_month_interval" :min="1" :max="12" />
                                <small class="p-error">{{ form.errors.recurrence_month_interval }}</small>
                            </div>

                            <div v-if="['quarterly', 'biannual', 'annual'].includes(form.recurrence_type)"
                                class="field">
                                <div class="field">
                                    <label for="planned_start_date_recurrence" class="font-semibold">Date de début de
                                        récurrence</label>
                                    <Calendar id="planned_start_date_recurrence" class="w-full"
                                        v-model="form.planned_start_date" dateFormat="dd/mm/yy" showIcon />
                                    <small class="p-error">{{ form.errors.planned_start_date }}</small>
                                </div>
                            </div>

                            <div v-if="['quarterly', 'biannual', 'annual'].includes(form.recurrence_type)"
                                class="field">
                                <label for="recurrence_interval" class="font-semibold">Nombre de {{ form.recurrence_type
                                    === 'quarterly' ?
                                    'trimestres' : (form.recurrence_type === 'biannual' ? 'semestres' : 'années')
                                    }}</label>
                                <InputNumber id="recurrence_interval" class="w-full" v-model="form.recurrence_interval"
                                    :min="1" />
                                <small class="p-error">{{ form.errors.recurrence_interval }}</small>
                            </div>
                            <div v-if="['quarterly', 'biannual', 'annual'].includes(form.recurrence_type)"
                                class="field">
                                <label for="reminder_days" class="font-semibold">Jours de rappel avant exécution</label>
                                <InputNumber id="reminder_days" class="w-full" v-model="form.reminder_days" :min="0" />
                                <small class="p-error">{{ form.errors.reminder_days }}</small>
                            </div>

                            <!-- Exemple pour trimestriel/semestriel/annuel avec choix du mois et jour: -->
                            <!-- Vous devrez définir `months` dans votre script setup si vous utilisez ce bloc -->

                            <div v-if="form.recurrence_type === 'quarterly' || form.recurrence_type === 'biannual' || form.recurrence_type === 'annual'"
                                class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label for="recurrence_month" class="font-semibold">Mois de début</label>
                                    <Dropdown id="recurrence_month" class="w-full" v-model="form.recurrence_month"
                                        :options="months" optionLabel="label" optionValue="value"
                                        placeholder="Sélectionner un mois" />
                                    <small class="p-error">{{ form.errors.recurrence_month }}</small>
                                </div>
                                <div class="field">
                                    <label for="recurrence_day_of_month_complex" class="font-semibold">Jour du
                                        mois</label>
                                    <InputNumber id="recurrence_day_of_month_complex" class="w-full"
                                        v-model="form.recurrence_day_of_month" :min="1" :max="31" />
                                    <small class="p-error">{{ form.errors.recurrence_day_of_month }}</small>
                                </div>
                            </div>

                            <!-- Nouveau champ pour la configuration personnalisée de la récurrence -->
                            <div v-if="form.recurrence_type === 'custom'" class="field">
                                <label for="custom_recurrence_config" class="font-semibold">Configuration personnalisée
                                    de la
                                    récurrence</label>
                                <Textarea id="custom_recurrence_config" class="w-full"
                                    v-model="form.custom_recurrence_config" rows="3"
                                    placeholder="Ex: 'every 2 weeks on Monday and Friday'" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="field">
                                <label for="requester_name" class="font-semibold">Demandeur</label>
                                <InputText id="requester_name" class="w-full" v-model.trim="form.requester_name"
                                    :class="{ 'p-invalid': form.errors.requester_name }" />
                                <small class="p-error">{{ form.errors.requester_name }}</small>
                            </div>
                            <div class="field">
                                <label for="department" class="font-semibold">Service Destinateur</label>
                                <InputText id="department" class="w-full" v-model.trim="form.department"
                                    :class="{ 'p-invalid': form.errors.department }" />
                                <small class="p-error">{{ form.errors.department }}</small>
                            </div>
                        </div>

                        <div class="field flex items-center mt-3">
                            <Checkbox
                                id="requires_shutdown"
                                v-model="form.requires_shutdown"
                                :binary="true"
                                :class="{ 'p-invalid': form.errors.requires_shutdown }"
                            />
                            <label for="requires_shutdown" class="ml-2 font-semibold">
                                L'équipement doit être mis hors tension
                            </label>
                            <small class="p-error">{{ form.errors.requires_shutdown }}</small>
                        </div>


                        <div class="field">
                            <!-- <label for="images" class="font-semibold">Images</label>
                            <FileUpload
                                mode="basic"
                                name="images[]"
                                url="/api/upload-task-image"
                                accept="image/*"
                                :maxFileSize="1000000"
                                :auto="true"
                                chooseLabel="Ajouter des images"
                                multiple
                            /> -->
                        </div>

                        <!-- Section de Configuration Avancée pour les Noeuds -->
                        <div class="field mt-4">
                            <Button label="Configurer les instructions avancées" icon="pi pi-cog"
                                class="p-button-secondary p-button-text"
                                @click="showAdvancedInstructions = !showAdvancedInstructions" />
                        </div>

                        <div v-if="showAdvancedInstructions" class="p-4 shadow-lg bg-white rounded-lg mt-4 border border-gray-200">

    <h3 class="text-xl font-bold mb-4 flex items-center text-gray-900 border-b border-gray-200 pb-3">
        <i class="pi pi-cog mr-2 text-blue-600"></i> Configuration des Instructions
    </h3>

    <div v-if="!configurableNodes || configurableNodes.length === 0" class="p-3 mb-4 bg-gray-50 rounded border border-gray-300">
        <p class="text-sm text-gray-700 m-0">
            <i class="pi pi-info-circle mr-2 text-blue-600"></i> Veuillez **sélectionner des équipements** pour activer la configuration des instructions.
        </p>
    </div>

    <div v-else>
        <div v-for="group in groupedConfigurableNodes" :key="group.parent.key" class="mb-4 border border-gray-300 rounded overflow-hidden">

            <div
                class="flex justify-between items-center p-3 bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer border-b border-gray-300"
                @click="toggleInstructionGroup(group.parent.key)"
            >
                <h4 class="font-bold text-lg m-0 text-gray-900">
                    <i class="pi pi-folder-open mr-2 text-blue-600"></i> {{ group.parent.label }}
                </h4>
                <Button
                    :icon="isGroupExpanded(group.parent.key) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
                    class="p-button-text p-button-secondary p-button-rounded"
                />
            </div>

            <div v-if="isGroupExpanded(group.parent.key)" class="p-4 bg-white">

                <div v-for="child in group.children" :key="child.key">

                    <div class="mb-4 p-3 border border-gray-200 rounded bg-gray-50">

                        <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200">
                            <h6 class="font-bold text-lg m-0 text-blue-600">{{ child.label }}</h6>

                            <div class="flex justify-end space-x-2">
                                <Button
                                    v-if="form.node_instructions[child.key] && form.node_instructions[child.key].length > 0"
                                    icon="pi pi-copy"
                                    label="Copier"
                                    class="p-button-sm p-button-outlined p-button-secondary"
                                    @click="openCopyDialog(child.key)"
                                />
                                <Button
                                    icon="pi pi-plus"
                                    label="Ajouter"
                                    class="p-button-sm p-button-primary"
                                    @click="addInstruction(child.key)"
                                />
                            </div>
                        </div>

                        <div v-if="form.node_instructions[child.key] && form.node_instructions[child.key].length > 0" class="flex flex-col space-y-3">
                            <div v-for="(instruction, index) in form.node_instructions[child.key]" :key="index" class="p-3 border border-gray-300 rounded bg-white">

                                <div class="grid grid-cols-12 gap-x-2 items-center">

                                    <div class="col-span-12 md:col-span-5">
                                        <InputText v-model="instruction.label" placeholder="Libellé de l'instruction" class="w-full" />
                                    </div>
                                    <small class="p-error">{{ form.errors[`node_instructions.${child.key}.${index}.label`] }}</small>
                                    <div class="col-span-12 md:col-span-3">
                                        <Dropdown
                                            v-model="instruction.type"
                                            :options="instructionValueTypes"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Type de Valeur"
                                            class="w-full"
                                        />
                                    </div>

                                    <div class="col-span-12 md:col-span-2 flex items-center">
                                        <Checkbox
                                            v-model="instruction.is_required"
                                            :binary="true"
                                            :inputId="`required-${child.key}-${index}`"
                                            class="mr-2"
                                        />
                                        <label :for="`required-${child.key}-${index}`" class="text-sm font-medium text-gray-700">Requis</label>
                                    </div>

                                    <div class="col-span-12 md:col-span-1 flex justify-end">
                                        <Button
                                            icon="pi pi-trash"
                                            class="p-button-danger p-button-text p-button-rounded"
                                            @click="removeInstruction(child.key, index)"
                                            v-tooltip.top="'Supprimer cette instruction'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-3 text-center text-gray-600 text-sm border border-dashed border-gray-300 rounded">
                            <i class="pi pi-exclamation-triangle mr-2"></i> Aucune instruction n'a été ajoutée. Cliquez sur **Ajouter** pour commencer.
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

                        <!-- Dialog pour copier les instructions -->
                        <Dialog v-model:visible="copyInstructionsDialog" modal header="Copier les instructions"
                            :style="{ width: '35rem' }">
                            <p v-if="sourceNodeForCopy">
                                Copier les instructions de <strong>{{ sourceNodeForCopy.label }}</strong> vers les
                                équipements sélectionnés
                                ci-dessous.
                                <br>
                                <small>Attention : Les instructions existantes sur les équipements cibles seront
                                    remplacées.</small>
                            </p>

                            <div class="field mt-4" v-if="copyTargetsTree.length > 0">
                                <label for="copy-targets" class="font-semibold">Équipements Cibles</label>
                                <TreeSelect id="copy-targets" v-model="selectedCopyTargets" :options="copyTargetsTree"
                                    placeholder="Sélectionner les équipements" selectionMode="checkbox" display="chip"
                                    class="w-full mt-2" >
                                      <template #empty></template>
                                    </TreeSelect>
                            </div>
                            <template #footer>
                                <Button label="Annuler" icon="pi pi-times" @click="copyInstructionsDialog = false"
                                    class="p-button-text" />
                                <Button label="Appliquer" icon="pi pi-check" @click="applyCopyInstructions"
                                    :disabled="totalSelectedCopyTargets === 0" />
                            </template>
                        </Dialog>
                        <template #footer>
                            <Button label="Annuler" icon="pi pi-times" @click="hideDialog" class="p-button-text" />
                            <Button label="Sauvegarder" icon="pi pi-check" @click="saveMaintenance"
                                :loading="form.processing" />
                        </template>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Vous pouvez ajouter des styles spécifiques ici si nécessaire */
</style>
