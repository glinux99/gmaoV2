<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
// Layout
import AppLayout from '@/sakai/layout/AppLayout.vue';
// PrimeVue Components
import { useToast } from 'primevue/usetoast';
import Timeline from 'primevue/timeline';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Divider from 'primevue/divider';
import SelectButton from 'primevue/selectbutton';
import Calendar from 'primevue/calendar';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Tooltip from 'primevue/tooltip';

// --- PROPS ---
const props = defineProps({
    activities: Object,
    spareParts: Array,
    users: Array,
    teams: Array,
    tasks: Array,
});

const toast = useToast();

// --- GESTION DES MODALES ET DE L'ACTION ---
const activityDialogVisible = ref(false);
const selectedActivity = ref(null);
const sparePartDialogVisible = ref(false);
const isCreatingSubActivity = ref(false);

const sparePartData = ref({
    ids: [],
    quantity: 1,
    index: -1,
    type: 'used'
});

// --- FORMULAIRE INERTIA ---
const form = useForm({
    id: null,
    problem_resolution_description: '',
    proposals: '',
    instructions: '',
    additional_information: '',
    status: '',
    actual_start_time: null,
    scheduled_start_time: null,
    actual_end_time: null,
    jobber: '',
    spare_parts_used: [],
    spare_parts_returned: [],
    user_id: null,
    service_order_cost: 0,
    service_order_description: 'Paiement des pièces détachées et autres',
    instruction_answers: {},
    parent_activity_id: null,
    task_id: null,
    maintenance_id: null,
});


// --- UTILITIES ---

const parseJson = (data) => {
    if (typeof data === 'string' && data.length > 0) {
        try {
            return JSON.parse(data);
        } catch (e) {
            console.warn("Erreur de parsing JSON. Retourne un tableau vide.", e);
            return [];
        }
    }
    return Array.isArray(data) ? data : [];
};

const getSparePartReference = (id) => {
    const part = props.spareParts.find(p => p.id === id);
    return part ? part.reference : 'Inconnu';
};

// --- LOGIQUE D'AFFICHAGE ET STYLE ---

const getIconForActivity = (status) => {
    switch (status) {
        case 'scheduled': return 'pi pi-calendar';
        case 'in_progress': return 'pi pi-spin pi-spinner';
        case 'completed': return 'pi pi-check-circle';
        case 'completed_with_issues': return 'pi pi-exclamation-circle';
        case 'suspended': return 'pi pi-pause';
        case 'canceled': return 'pi pi-times-circle';
        case 'awaiting_resources': return 'pi pi-hourglass';
        default: return 'pi pi-info-circle';
    }
};

const getColorForActivity = (status) => {
    switch (status) {
        case 'scheduled': return '#60A5FA';
        case 'in_progress': return '#FBBF24';
        case 'completed': return '#34D399';
        case 'completed_with_issues': return '#EF4444';
        case 'suspended': return '#F97316';
        case 'canceled': return '#9CA3AF';
        case 'awaiting_resources': return '#A855F7';
        default: return '#A78BFA';
    }
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
        'En attente': 'contrast'
    };
    return severities[status] || null;
};
// Fonction nécessaire pour la DataTable
const getPrioritySeverity = (priority) => {
    const severities = {
        'high': 'danger',
        'medium': 'warning',
        'low': 'info',
        'urgent': 'danger'
    };
    return severities[priority?.toLowerCase()] || 'secondary';
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'scheduled': return 'Planifiée';
        case 'in_progress': return 'En cours';
        case 'completed': return 'Terminée';
        case 'completed_with_issues': return 'Terminée avec problèmes';
        case 'suspended': return 'Suspendue';
        case 'canceled': return 'Annulée';
        case 'awaiting_resources': return 'En attente de ressources';
        case 'to_be_reviewed_later': return 'À revoir plus tard';
        default: return status;
    }
};


// --- PRÉPARATION DES DONNÉES D'INSTRUCTIONS (sécurisée) ---
// Note: Cette fonction ne doit pas appeler parseJson si les données sont déjà parsées
const formatInstructionAnswer = (activity) => {
    const answers = activity.instruction_answers;
    // Gère le cas où activity.instructions est une chaîne JSON ou un tableau
    const instructions = activity.task?.instructions || parseJson(activity.instructions) || [];

    if (!answers || answers.length === 0) {
        return [];
    }

    return answers.map(answer => {
        const instructionId = answer.task_instruction_id ?? answer.activity_instruction_id;
        const instruction = instructions.find(i => i.id === instructionId);

        const label = instruction ? instruction.label : `Instruction ID ${instructionId} (Introuvable)`;
        let value = answer.value;

        if (instruction && instruction.type === 'boolean') {
            value = value === '1' || value === 1 || value === true ? 'Oui' : 'Non';
        } else if (instruction && instruction.type === 'date' && value) {
            try {
                value = new Date(value).toLocaleDateString('fr-FR');
            } catch {
                value = 'Date invalide';
            }
        } else if (value === null || value === undefined || value === '') {
             value = 'Non renseigné';
        }

        return { label, value };
    });
};

// --- PROPRIÉTÉ CALCULÉE CLÉ (Correction de l'erreur de boucle récursive) ---
const currentActivities = computed(() => {
    return props.activities.data.map(activity => {
        // 1. Parsing des champs JSON
        const sparePartsUsed = parseJson(activity.spare_parts_used);
        const sparePartsReturned = parseJson(activity.spare_parts_returned);
        const instructionAnswers = parseJson(activity.instruction_answers);

        const parsedActivity = {
            ...activity,
            spare_parts_used: sparePartsUsed,
            spare_parts_returned: sparePartsReturned,
            instruction_answers: instructionAnswers,
        };

        // 2. Formatage des réponses d'instruction (Pré-calcul)
        const formattedAnswers = formatInstructionAnswer(parsedActivity);

        // 3. Préparation des tooltips (Pré-calcul pour la DataTable)
        const usedPartsTooltip = sparePartsUsed
            .map(p => `${p.quantity} x ${getSparePartReference(p.id)}`)
            .join(', ');

        const returnedPartsTooltip = sparePartsReturned
            .map(p => `${p.quantity} x ${getSparePartReference(p.id)}`)
            .join(', ');

        // 4. Préparation de la chaîne de tooltip pour les réponses (Pré-calcul)
        const instructionAnswersTooltip = formattedAnswers
            .map(a => `${a.label}: ${a.value}`)
            .join(' | ');

        // Retourne l'objet avec les données parsées ET les données de rendu pré-calculées
        return {
            ...parsedActivity,
            formatted_instruction_answers: formattedAnswers,
            used_parts_tooltip: usedPartsTooltip,
            returned_parts_tooltip: returnedPartsTooltip,
            instruction_answers_tooltip: instructionAnswersTooltip,
        };
    });
});
// -------------------------------------------------------------------------


// --- ÉTAT DE L'AFFICHAGE ET FILTRES ---
const viewMode = ref('timeline'); // 'timeline' or 'table'
const viewOptions = ref([
    { icon: 'pi pi-list', value: 'timeline' },
    { icon: 'pi pi-table', value: 'table' }
]);

const displayFields = ref([]);
const defaultDisplayFields = [
    'actual_end_time',
    'proposals',
    'jobber',
    'additional_information',
    'spare_parts_used',
];
const availableDisplayOptions = ref([
    { label: 'Heure de fin réelle', value: 'actual_end_time' },
    { label: 'Propositions/Recommandations', value: 'proposals' },
    { label: 'Intervenant', value: 'jobber' },
    { label: 'Informations Additionnelles', value: 'additional_information' },
    { label: 'Pièces Utilisées', value: 'spare_parts_used' },
    { label: 'Pièces Retournées', value: 'spare_parts_returned' },
]);

onMounted(() => {
    displayFields.value = [...defaultDisplayFields];
});


// --- CALCUL DU COÛT ---
const serviceOrderCost = computed(() => {
    return form.spare_parts_used.reduce((total, usedPart) => {
        const partDetails = props.spareParts.find(p => p.id === usedPart.id);
        const price = partDetails?.price || 0;
        return total + (price * usedPart.quantity);
    }, 0);
});

// --- LOGIQUE DES ACTIONS ---

const hideDialog = () => {
    activityDialogVisible.value = false;
    form.reset();
    selectedActivity.value = null;
    isCreatingSubActivity.value = false;
    form.parent_activity_id = null;
};

// Fonction pour l'édition d'une activité existante
const editActivity = (activity) => {
    isCreatingSubActivity.value = false;
    selectedActivity.value = activity;

    // Remplir le formulaire avec les données existantes
    form.id = activity.id;
    form.problem_resolution_description = activity.problem_resolution_description || '';
    form.proposals = activity.proposals || '';

    // Utilisation de l'activité non mappée pour les instructions (plus sûr)
    const rawActivity = props.activities.data.find(a => a.id === activity.id);

    // --- Correction: Assurer que form.instructions est une chaîne ---
    const instructions = rawActivity?.instructions || '';
    if (instructions && typeof instructions !== 'string') {
        try {
            form.instructions = JSON.stringify(instructions);
        } catch (e) {
            console.error("Erreur de stringification des instructions lors de l'édition.", e);
            form.instructions = '';
        }
    } else {
        form.instructions = instructions;
    }
    // --------------------------------------------------------------------

    form.additional_information = activity.additional_information || '';
    // Assigner les dates comme objets Date pour le PrimeVue Calendar
    form.actual_start_time = activity.actual_start_time ? new Date(activity.actual_start_time) : null;
    form.scheduled_start_time = activity.scheduled_start_time ? new Date(activity.scheduled_start_time) : null;
    form.actual_end_time = activity.actual_end_time ? new Date(activity.actual_end_time) : null;

    form.jobber = activity.jobber || '';
    form.user_id = activity.user_id;
    form.status = activity.status;
    form.parent_activity_id = activity.parent_activity_id;
    form.task_id = activity.task_id;
    form.maintenance_id = activity.maintenance_id;
    form.service_order_cost = activity.service_order_cost || 0;
    form.service_order_description = activity.service_order_description || 'Paiement des pièces détachées et autres';

    // Les données sont déjà parsées (grâce à currentActivities) mais nous prenons les valeurs brutes ou parsées si l'activité vient des props
    form.spare_parts_used = parseJson(rawActivity?.spare_parts_used || activity.spare_parts_used);
    form.spare_parts_returned = parseJson(rawActivity?.spare_parts_returned || activity.spare_parts_returned);

    const answers = {};
    // Utiliser les réponses déjà parsées ou les parser si nécessaire
    const instructionAnswers = parseJson(rawActivity?.instruction_answers || activity.instruction_answers);
    const activityInstructions = activity.task?.instructions || instructions || [];

    if (instructionAnswers && Array.isArray(instructionAnswers)) {
        instructionAnswers.forEach(answer => {
            const instructionId = answer.task_instruction_id ?? answer.activity_instruction_id;
            const instruction = activityInstructions.find(i => i.id === instructionId);
            answers[instructionId] = instruction?.type === 'boolean'
                ? String(answer.value)
                : answer.value;
        });
    }
    form.instruction_answers = answers;

    activityDialogVisible.value = true;
};

// FONCTION OPTIMISÉE : Préparer la création d'une sous-activité
const createSubActivity = (parentActivity) => {
    isCreatingSubActivity.value = true;
    selectedActivity.value = parentActivity;
    console.log("Sous Activity");
    console.log(parentActivity);
    form.reset(); // 1. Réinitialiser tout

    // Champs de contexte hérités
    form.parent_activity_id = parentActivity.id;
    form.task_id = parentActivity.task_id;
    form.maintenance_id = parentActivity.maintenance_id;

    // Champs de contexte copiés
    form.jobber = parentActivity.jobber || null;
    form.user_id = parentActivity.user_id || null;
    form.service_order_description = parentActivity.service_order_description || 'Paiement des pièces détachées et autres'; // Default value

    // Instructions (avec gestion de la conversion en string)
    if (parentActivity.instructions && typeof parentActivity.instructions !== 'string') {
        try {
            form.instructions = JSON.stringify(parentActivity.instructions);
        } catch (e) {
            form.instructions = '';
        }
    } else {
        form.instructions = parentActivity.instructions || '';
    }

    // Pièces de rechange et réponses aux instructions (copiées)
    form.spare_parts_used = parseJson(parentActivity.spare_parts_used);
    form.spare_parts_returned = parseJson(parentActivity.spare_parts_returned);

    // Reste du formulaire (valeurs propres à la nouvelle sous-activité)
    form.status = 'scheduled'; // Default status for new activities
    form.problem_resolution_description = `Sous-activité pour ${parentActivity.task?.title || parentActivity.maintenance?.title || 'Activité'} : `;
    form.proposals = '';
    form.additional_information = ''; // Empty for new sub-activity
    form.service_order_cost = 0;
    form.actual_start_time = null;
    form.scheduled_start_time = parentActivity.scheduled_start_time ? new Date(parentActivity.scheduled_start_time) : null;
    form.actual_start_time = parentActivity.actual_start_time ? new Date(parentActivity.actual_start_time) : null;
    form.actual_end_time = parentActivity.actual_end_time ? new Date(parentActivity.actual_end_time) : null;
    form.actual_end_time = null;

    // Copie des réponses aux instructions (logique complexe de l'héritage)
    const answers = {}; // Initialize answers as an empty object
    const instructionAnswers = parseJson(parentActivity.instruction_answers); // Parse parent's instruction answers
    // Determine the source of instructions, prioritizing task instructions, then activity instructions
    const instructions = parentActivity.task?.instructions || parseJson(parentActivity.instructions) || [];
    if (instructionAnswers && Array.isArray(instructionAnswers)) {
        instructionAnswers.forEach(answer => {
            const instructionId = answer.task_instruction_id ?? answer.activity_instruction_id;
            const instruction = instructions.find(i => i.id === instructionId);
            answers[instructionId] = instruction?.type === 'boolean'
                ? String(answer.value)
                : answer.value;
        });
    }
    form.instruction_answers = answers;
    console.log("CCCCCC");
    console.log(form);

    activityDialogVisible.value = true;
};

// Fonction unifiée pour Créer ou Sauvegarder
const saveActivity = () => {
    // Étape 1: Préparation des données finales
    form.service_order_cost = serviceOrderCost.value;

    // Convertir les objets Date en ISO string formaté pour MySQL
    const tempActualStartTime = form.actual_start_time;
    const tempScheduledStartTime = form.scheduled_start_time;
    const tempActualEndTime = form.actual_end_time;

    form.actual_start_time = tempActualStartTime ? new Date(tempActualStartTime).toISOString().slice(0, 19).replace('T', ' ') : null;
    form.scheduled_start_time = tempScheduledStartTime ? new Date(tempScheduledStartTime).toISOString().slice(0, 19).replace('T', ' ') : null;
    form.actual_end_time = tempActualEndTime ? new Date(tempActualEndTime).toISOString().slice(0, 19).replace('T', ' ') : null;

    // Déterminer la route et la méthode
    const method = isCreatingSubActivity.value ? 'post' : 'put';
    const routeName = isCreatingSubActivity.value ? 'activities.store' : 'activities.update';
    const routeParams = isCreatingSubActivity.value ? {} : form.id;
    const successMessage = isCreatingSubActivity.value ? 'Sous-activité créée avec succès.' : 'Activité mise à jour avec succès.';
    console.log("XXXXXXXXXXXXXXXXXXXX2545");
    console.log(form);
    const handler = {
        onSuccess: () => {
            hideDialog();
            toast.add({ severity: 'success', summary: 'Succès', detail: successMessage, life: 3000 });
        },
        onError: (errors) => {
            console.error(errors);
            // Revertir les dates en objets Date si l'on veut que le formulaire reste ouvert après erreur
            form.actual_start_time = tempActualStartTime;
            form.scheduled_start_time = tempScheduledStartTime;
            form.actual_end_time = tempActualEndTime;

            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l\'enregistrement. Voir la console pour plus de détails.', life: 3000 });
        }
    };

    // Étape 2: Soumission via Inertia
    if (method === 'post') {
        form.post(route(routeName), handler);
    } else {
        form.put(route(routeName, routeParams), handler);
    }
};

// Fonctions pour la gestion des pièces de rechange (inchangées)
const openSparePartDialog = (type, part = null, index = -1) => {
    const isEditing = index > -1 && part;
    sparePartData.value = {
        ids: isEditing ? [part.id] : [],
        quantity: part ? part.quantity : 1,
        index: index,
        type: type
    };
    sparePartDialogVisible.value = true;
};
const saveSparePart = () => {
    const { ids, quantity, index, type } = sparePartData.value;
    const selectedIds = index > -1 ? (ids.length > 0 ? [ids[0]] : []) : ids;

    if (!selectedIds || selectedIds.length === 0 || quantity < 1) {
        toast.add({ severity: 'warn', summary: 'Données invalides', detail: 'Veuillez sélectionner une ou plusieurs pièces et une quantité valide.', life: 3000 });
        return;
    }

    const targetArray = type === 'used' ? form.spare_parts_used : form.spare_parts_returned;

    if (index > -1) {
        // Mode édition
        targetArray[index] = { id: selectedIds[0], quantity };
    } else {
        // Mode ajout
        selectedIds.forEach(partId => {
            const exists = targetArray.some(p => p.id === partId);
            if (!exists) {
                targetArray.push({ id: partId, quantity });
            } else {
                toast.add({ severity: 'info', summary: 'Avertissement', detail: `La pièce (ID: ${partId}) est déjà dans la liste.`, life: 3000 });
            }
        });
    }
    sparePartDialogVisible.value = false;
};

const sparePartOptions = computed(() => {
    return props.spareParts.map(part => ({
        label: `${part.reference} (${part.name}) - Stock: ${part.stock}`,
        value: part.id
    }));
});


const removeSparePartUsed = (index) => {
   form.spare_parts_used = form.spare_parts_used.filter((_, i) => i !== index);
};

const removeSparePartReturned = (index) => {
   form.spare_parts_returned = form.spare_parts_returned.filter((_, i) => i !== index);
};
</script>

<template>
    <AppLayout title="Mes Activités">
        <Head title="Mes Activités" />

        <div class="grid">
            <div class="col-12">
                <Card>
                    <template #title>
                        <h2 class="text-3xl font-bold text-primary-600 mb-2">
                            <i class="pi pi-list-check mr-2"></i> Chronologie de mes activités
                        </h2>
                    </template>

                    <template #subtitle>
                        <div class="flex flex-column md:flex-row md:justify-content-between md:items-center">
                            <span class="mb-2 md:mb-0">Aperçu de l'évolution de vos tâches et interventions.</span>

                            <div class="flex flex-column md:flex-row items-center gap-3">
                                <MultiSelect
                                    v-model="displayFields"
                                    :options="availableDisplayOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Colonnes à afficher dans le tableau"
                                    class="w-full md:w-20rem"
                                    display="chip"
                                    :maxSelectedLabels="2"
                                />

                                <SelectButton v-model="viewMode" :options="viewOptions" optionValue="value"
                                    dataKey="value" aria-labelledby="basic">
                                    <template #option="slotProps">
                                        <i :class="slotProps.option.icon"></i>
                                    </template>
                                </SelectButton>
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <div v-if="currentActivities && currentActivities.length > 0">

                            <div v-if="viewMode === 'timeline'">
                                <Timeline :value="currentActivities" align="left" class="timeline-left p-4">

                                    <template #marker="slotProps">
                                        <Avatar
                                            :icon="getIconForActivity(slotProps.item.status)"
                                            :style="{ backgroundColor: getColorForActivity(slotProps.item.status), color: '#ffffff' }"
                                            shape="circle"
                                            class="z-10 shadow-lg"
                                            size="large"
                                        />
                                    </template>

                                    <template #opposite="slotProps">
                                        <div class="p-0 text-sm font-medium text-400 mt-1 flex align-items-center">
                                            <i class="pi pi-clock mr-1"></i>
                                            <span>
                                                {{ slotProps.item.actual_start_time ? new Date(slotProps.item.actual_start_time).toLocaleString('fr-FR') : (slotProps.item.scheduled_start_time ? new Date(slotProps.item.scheduled_start_time).toLocaleString('fr-FR') : 'Date non définie') }}
                                            </span>
                                        </div>
                                    </template>

                                    <template #content="slotProps">
                                        <Card class="mt-0 surface-card shadow-4 border-round-lg">
                                            <template #title>
                                                <div class="text-xl font-bold text-700 flex align-items-center justify-content-between">
                                                    <span>
                                                        {{ slotProps.item.task?.title || slotProps.item.maintenance?.title || 'Activité Sans Titre' }}
                                                        <span class="text-base font-normal text-500 ml-2">#WorkOrderID: {{ slotProps.item.task?.id|| slotProps.item.maintenance?.id }}</span>
                                                    </span>
                                                </div>
                                            </template>

                                            <template #subtitle>
                                                <div class="mt-2">
                                                    <Tag :value="getStatusLabel(slotProps.item.status)" :severity="getStatusSeverity(slotProps.item.status)" class="text-lg font-bold" />
                                                </div>
                                            </template>

                                            <template #content>
                                                <div class="mt-2 text-600 line-height-3">

                                                    <p v-if="slotProps.item.problem_resolution_description" class="mb-2">
                                                        <strong>Problème/Résolution:</strong> {{ slotProps.item.problem_resolution_description }}
                                                    </p>

                                                    <p v-if="slotProps.item.proposals" class="mb-2">
                                                        <strong>Propositions:</strong> {{ slotProps.item.proposals }}
                                                    </p>

                                                    <p v-if="slotProps.item.additional_information" class="mb-2">
                                                        <strong>Informations Additionnelles:</strong> {{ slotProps.item.additional_information }}
                                                    </p>

                                                    <p v-if="slotProps.item.jobber" class="mt-3">
                                                        <strong>Intervenant:</strong> {{ slotProps.item.jobber }}
                                                    </p>

                                                    <div v-if="slotProps.item.instruction_answers.length > 0" class="mt-3 p-3 bg-bluegray-50 border-round-md border-left-3 border-blue-500">
                                                        <h5 class="font-bold text-blue-800 mb-2">Réponses aux Instructions :</h5>
                                                        <ul class="list-disc ml-4">
                                                            <li v-for="(answer, ansIndex) in formatInstructionAnswer(slotProps.item)" :key="ansIndex" class="mb-1">
                                                                <strong class="text-700">{{ answer.label }}:</strong> {{ answer.value }}
                                                            </li>
                                                        </ul>
                                                    </div>

                                                    <div v-if="displayFields.includes('spare_parts_used') && slotProps.item.spare_parts_used.length > 0" class="mt-3">
                                                        <h5 class="font-bold text-700 mb-1">Pièces utilisées:</h5>
                                                        <ul class="list-disc ml-4">
                                                            <li v-for="(part, index) in slotProps.item.spare_parts_used" :key="index">
                                                                {{ part.quantity }} x {{ getSparePartReference(part.id) }} ({{ part.price }} XOF)
                                                            </li>
                                                        </ul>
                                                    </div>

                                                    <div v-if="displayFields.includes('spare_parts_returned') && slotProps.item.spare_parts_returned.length > 0" class="mt-3">
                                                        <h5 class="font-bold text-700 mb-1">Pièces retournées:</h5>
                                                        <ul class="list-disc ml-4">
                                                            <li v-for="(part, index) in slotProps.item.spare_parts_returned" :key="index">
                                                                {{ part.quantity }} x {{ getSparePartReference(part.id) }}
                                                            </li>
                                                        </ul>
                                                    </div>

                                                    <p v-if="displayFields.includes('actual_end_time') && slotProps.item.actual_end_time" class="mt-3 text-sm text-700">
                                                        <i class="pi pi-check-circle mr-1 text-green-600"></i>
                                                        <strong>Heure de fin réelle:</strong> {{ new Date(slotProps.item.actual_end_time).toLocaleString('fr-FR') }}
                                                    </p>

                                                    <div class="mt-4 pt-3 border-top-2 border-gray-200">
                                                        <h4 class="font-semibold text-gray-700 mb-2">Détails de la Tâche Associée</h4>
                                                        <p class="mb-1"><strong>Priorité:</strong> <Tag :value="slotProps.item.task?.priority || slotProps.item.maintenance?.priority" /></p>
                                                        <p v-if="slotProps.item.task?.description" class="text-sm"><strong>Description:</strong> {{ slotProps.item.task?.description || slotProps.item.maintenance?.description }}</p>
                                                    </div>

                                                </div>

                                                <div class="flex justify-content-end mt-4">
                                                    <Button
                                                        icon="pi pi-plus-circle"
                                                        label="Créer sous-activité"
                                                        class="p-button-text p-button-sm p-button-secondary mr-2"
                                                        @click="createSubActivity(slotProps.item)"
                                                    />
                                                    <Button icon="pi pi-pencil" label="Compléter" class="p-button-text p-button-info p-button-sm" @click="editActivity(slotProps.item)" />
                                                </div>
                                            </template>
                                        </Card>
                                    </template>

                                </Timeline>
                            </div>

                            <div v-else-if="viewMode === 'table'">
                                <DataTable :value="currentActivities" responsiveLayout="scroll" dataKey="id" class="p-datatable-sm shadow-2 border-round-lg">
                                    <Column field="task.title" header="Titre de la Tâche" :sortable="true" class="font-semibold">
                                        <template #body="slotProps">
                                            {{ slotProps.data.task?.title || slotProps.data.maintenance?.title || 'N/A' }}
                                        </template>
                                    </Column>
                                    <Column field="status" header="Statut" :sortable="true">
                                        <template #body="slotProps">
                                            <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" />
                                        </template>
                                    </Column>
                                    <Column field="actual_start_time" header="Début Réel" :sortable="true">
                                        <template #body="slotProps">
                                            {{ slotProps.data.actual_start_time ? new Date(slotProps.data.actual_start_time).toLocaleString('fr-FR') : 'N/A' }}
                                        </template>
                                    </Column>
                                    <Column field="task.priority" header="Priorité" :sortable="true">
                                        <template #body="slotProps">
                                            <Tag :value="slotProps.data.task?.priority || slotProps.data.maintenance?.priority" :severity="getPrioritySeverity(slotProps.data.task?.priority || slotProps.data.maintenance?.priority)" />
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('actual_end_time')" header="Fin Réelle" :sortable="true">
                                        <template #body="slotProps">
                                            {{ slotProps.data.actual_end_time ? new Date(slotProps.data.actual_end_time).toLocaleString('fr-FR') : 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('proposals')" header="Propositions" :sortable="false">
                                        <template #body="slotProps">
                                            <span v-tooltip.top="slotProps.data.proposals" class="max-w-10rem truncate block">{{ slotProps.data.proposals || 'N/A' }}</span>
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('jobber')" field="jobber" header="Intervenant" :sortable="true"></Column>

                                    <Column v-if="displayFields.includes('additional_information')" header="Info Add." :sortable="false">
                                        <template #body="slotProps">
                                            <span v-tooltip.top="slotProps.data.additional_information" class="max-w-10rem truncate block">{{ slotProps.data.additional_information || 'N/A' }}</span>
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('spare_parts_used')" header="Pièces Utilisées" :sortable="false">
                                        <template #body="slotProps">
                                            <div v-if="slotProps.data.spare_parts_used.length > 0">
                                                <Tag
                                                    :value="`${slotProps.data.spare_parts_used.length} Pièce(s)`"
                                                    severity="contrast"
                                                    v-tooltip.top="slotProps.data.spare_parts_used.map(p => `${p.quantity} x ${getSparePartReference(p.id)}`).join(', ')"
                                                />
                                            </div>
                                            <span v-else>Non</span>
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('spare_parts_returned')" header="Pièces Retournées" :sortable="false">
                                        <template #body="slotProps">
                                            <div v-if="slotProps.data.spare_parts_returned.length > 0">
                                                <Tag
                                                    :value="`${slotProps.data.spare_parts_returned.length} Pièce(s)`"
                                                    severity="warning"
                                                    v-tooltip.top="slotProps.data.spare_parts_returned.map(p => `${p.quantity} x ${getSparePartReference(p.id)}`).join(', ')"
                                                />
                                            </div>
                                            <span v-else>Non</span>
                                        </template>
                                    </Column>

                                    <Column header="Instructions/Actions" class="text-right">
                                        <template #body="slotProps">
                                            <div class="flex justify-content-end align-items-center">
                                                <div v-if="slotProps.data.instruction_answers.length > 0" class="mr-2">
                                                    <Tag
                                                        :value="`${slotProps.data.instruction_answers.length} Rép.`"
                                                        severity="info"
                                                        v-tooltip.top="formatInstructionAnswer(slotProps.data).map(a => `${a.label}: ${a.value}`).join(' | ')"
                                                    />
                                                </div>
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="p-button-rounded p-button-info p-button-sm"
                                                    @click="editActivity(slotProps.data)"
                                                    v-tooltip.top="'Compléter/Modifier l\'Activité'"
                                                />
                                            </div>
                                        </template>
                                    </Column>

                                </DataTable>
                            </div>

                        </div>

                        <div v-else class="text-center p-5 surface-50 border-round-md shadow-2">
                            <i class="pi pi-calendar-times text-5xl text-400 mb-3"></i>
                            <p class="text-xl text-700">Aucune activité à afficher pour le moment.</p>
                            <p class="text-600">Revenez plus tard ou ajoutez une nouvelle tâche.</p>
                        </div>

                        <Dialog v-model:visible="activityDialogVisible" modal :header="isCreatingSubActivity ? 'Créer une Sous-Activité' : 'Compléter ou Modifier l\'Activité'" :style="{ width: '45rem' }" class="p-fluid">
                            <div class="p-grid p-col-12 p-nogutter">
                                <div class="field p-col-12" v-if="isCreatingSubActivity">
                                    <Tag icon="pi pi-link" severity="secondary" :value="`Liée à l'activité #${selectedActivity.id}`" class="mb-3" />
                                </div>
                                <div class="field p-col-12">
                                    <label for="status" class="font-semibold">Statut de l'activité</label>
                                    <Dropdown id="status" class="w-full" v-model="form.status" :options="['scheduled', 'in_progress', 'completed', 'completed_with_issues', 'suspended', 'canceled', 'awaiting_resources', 'to_be_reviewed_later']" :optionLabel="getStatusLabel" placeholder="Changer le statut" />
                                    <small class="p-error">{{ form.errors.status }}</small>
                                </div>
                                <div class="field p-col-12">
                                    <label for="description" class="font-semibold">Description du problème et résolution</label>
                                    <Textarea id="description" class="w-full" v-model="form.problem_resolution_description" rows="4" />
                                    <small class="p-error">{{ form.errors.problem_resolution_description }}</small>
                                </div>
                                <div class="field p-col-12">
                                    <label for="proposals" class="font-semibold">Propositions / Recommandations</label>
                                    <Textarea id="proposals" class="w-full" v-model="form.proposals" rows="3" />
                                    <small class="p-error">{{ form.errors.proposals }}</small>
                                </div>
                                <div class="field p-col-12 hidden">
                                    <label for="instructions" class="font-semibold">Instructions laissées</label>
                                    <Textarea id="instructions" class="w-full" v-model="form.instructions" rows="3" />
                                    <small class="p-error">{{ form.errors.instructions }}</small>
                                </div>
                                <div class="field p-col-12">
                                    <label for="additional_information" class="font-semibold">Informations additionnelles</label>
                                    <Textarea id="additional_information" class="w-full" v-model="form.additional_information" rows="3" />
                                    <small class="p-error">{{ form.errors.additional_information }}</small>
                                </div>
                                <div class="p-grid p-col-12 flex flex-wrap">
                                    <div class="field p-col-6 w-full md:w-6/12">
                                        <label for="actual_start_time" class="font-semibold">Heure de début réelle</label>
                                        <Calendar id="actual_start_time" class="w-full" v-model="form.actual_start_time" showTime dateFormat="dd/mm/yy" showIcon />
                                        <small class="p-error">{{ form.errors.actual_start_time }}</small>
                                    </div>
                                    <div class="field p-col-6 w-full md:w-6/12">
                                        <label for="scheduled_start_time" class="font-semibold">Heure de début planifiée</label>
                                        <Calendar id="scheduled_start_time" class="w-full" v-model="form.scheduled_start_time" showTime dateFormat="dd/mm/yy" showIcon />
                                        <small class="p-error">{{ form.errors.scheduled_start_time }}</small>
                                    </div>
                                    <div class="field p-col-6 w-full md:w-6/12">
                                        <label for="actual_end_time" class="font-semibold">Heure de fin réelle</label>
                                        <Calendar id="actual_end_time" class="w-full" v-model="form.actual_end_time" showTime dateFormat="dd/mm/yy" showIcon />
                                        <small class="p-error">{{ form.errors.actual_end_time }}</small>
                                    </div>
                                </div>
                                <div class="p-grid p-col-12 flex flex-wrap">
                                    <div class="field p-col-6 w-full md:w-6/12">
                                        <label for="jobber" class="font-semibold">Intervenant</label>
                                        <InputText id="jobber" class="w-full" v-model="form.jobber" />
                                        <small class="p-error">{{ form.errors.jobber }}</small>
                                    </div>
                                    <div class="field p-col-6 w-full md:w-6/12">
                                        <label for="user_id" class="font-semibold">Utilisateur</label>
                                        <Dropdown id="user_id" class="w-full" v-model="form.user_id"
                                                    :options="props.users" optionLabel="name" optionValue="id"
                                                    placeholder="Sélectionner un utilisateur" filter />
                                        <small class="p-error">{{ form.errors.user_id }}</small>
                                    </div>
                                </div>
                            </div>

                            <Divider />
                            <h4 class="font-bold mb-3">Réponses aux Instructions 📝</h4>

                            <div v-if="selectedActivity?.task?.instructions?.length > 0">
                                <div v-for="instruction in selectedActivity.task.instructions" :key="instruction.id" class="field mb-4 p-3 border rounded-lg bg-gray-50">
                                    <label :for="`instruction-${instruction.id}`" class="font-semibold block mb-2">
                                        {{ instruction.label }}
                                        <span v-if="instruction.is_required" class="text-red-500 ml-1">*</span>
                                    </label>

                                    <InputText v-if="instruction.type === 'text'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" />
                                    <InputNumber v-else-if="instruction.type === 'number'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" :useGrouping="false" />
                                    <Calendar v-else-if="instruction.type === 'date'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" showIcon dateFormat="dd/mm/yy" />
                                    <Dropdown v-else-if="instruction.type === 'boolean'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" :options="[{label: 'Oui', value: '1'}, {label: 'Non', value: '0'}]" optionLabel="label" optionValue="value" placeholder="Sélectionner" class="w-full" />
                                    <Textarea v-else-if="instruction.type === 'textarea'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" rows="3" />

                                    <div v-else class="text-gray-500 text-sm">
                                        Le type d'instruction '{{ instruction.type }}' n'est pas encore supporté pour la saisie.
                                    </div>

                                    <small class="p-error">{{ form.errors[`instruction_answers.${instruction.id}`] }}</small>
                                </div>
                            </div>

                            <div v-else-if="selectedActivity?.maintenance_id">
                                <div v-for="instruction in selectedActivity.maintenance.instructions" :key="instruction.id" class="field mb-4 p-3 border rounded-lg bg-gray-50">
                                    <label :for="`instruction-${instruction.id}`" class="font-semibold block mb-2">
                                        {{ instruction.label }}
                                        <span v-if="instruction.is_required" class="text-red-500 ml-1">*</span>
                                    </label>

                                    <InputText v-if="instruction.type === 'text'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" />
                                    <InputNumber v-else-if="instruction.type === 'number'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" :useGrouping="false" />
                                    <Calendar v-else-if="instruction.type === 'date'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" showIcon dateFormat="dd/mm/yy" />
                                    <Dropdown v-else-if="instruction.type === 'boolean'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" :options="[{label: 'Oui', value: '1'}, {label: 'Non', value: '0'}]" optionLabel="label" optionValue="value" placeholder="Sélectionner" class="w-full" />
                                    <Textarea v-else-if="instruction.type === 'textarea'" :id="`instruction-${instruction.id}`" v-model="form.instruction_answers[instruction.id]" class="w-full" rows="3" />

                                    <div v-else class="text-gray-500 text-sm">
                                        Le type d'instruction '{{ instruction.type }}' n'est pas encore supporté pour la saisie.
                                    </div>

                                    <small class="p-error">{{ form.errors[`instruction_answers.${instruction.id}`] }}</small>
                                </div>
                            </div>

                            <div v-else>
                                <p class="text-gray-500">Aucune instruction spécifique pour cette tâche.</p>
                            </div>

                            <Divider />
                            <h4 class="font-bold mb-3">Pièces de rechange utilisées 🛠️ <Tag :value="`${serviceOrderCost.toFixed(0)} XOF`" severity="secondary" /></h4>

                            <div v-if="form.spare_parts_used.length > 0">
                                <div v-for="(part, index) in form.spare_parts_used" :key="`used-${index}`" class="flex align-items-center mb-2 p-2 border-1 border-round surface-hover relative">
                                    <span class="flex-grow-1 font-semibold text-700">
                                        **{{ part.quantity }} x {{ getSparePartReference(part.id) }}**
                                    </span>
                                    <div class="flex-shrink-0">
                                        <Button
                                            icon="pi pi-pencil"
                                            class="p-button-info p-button-text p-button-rounded mr-1"
                                            @click="openSparePartDialog('used', part, index)"
                                            v-tooltip.top="'Modifier la pièce'"
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            class="p-button-danger p-button-text p-button-rounded"
                                            @click="removeSparePartUsed(index)"
                                            v-tooltip.top="'Supprimer la pièce'"
                                        />
                                    </div>
                                    <small class="p-error absolute bottom-0 left-0">{{ form.errors[`spare_parts_used.${index}.id`] || form.errors[`spare_parts_used.${index}.quantity`] }}</small>
                                </div>
                            </div>
                            <div v-else>
                                <p class="text-gray-500">Aucune pièce utilisée enregistrée.</p>
                            </div>

                            <Button
                                label="Ajouter une pièce utilisée"
                                icon="pi pi-plus"
                                class="p-button-text p-button-sm mt-2"
                                @click="openSparePartDialog('used')"
                            />

                            <Divider />
                            <h4 class="font-bold mb-3">Pièces de rechange retournées ♻️</h4>

                            <div v-if="form.spare_parts_returned.length > 0">
                                <div v-for="(part, index) in form.spare_parts_returned" :key="`returned-${index}`" class="flex align-items-center mb-2 p-2 border-1 border-round surface-hover relative">
                                    <span class="flex-grow-1 font-semibold text-700">
                                        **{{ part.quantity }} x {{ getSparePartReference(part.id) }}**
                                    </span>
                                    <div class="flex-shrink-0">
                                        <Button
                                            icon="pi pi-pencil"
                                            class="p-button-info p-button-text p-button-rounded mr-1"
                                            @click="openSparePartDialog('returned', part, index)"
                                            v-tooltip.top="'Modifier la pièce'"
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            class="p-button-danger p-button-text p-button-rounded"
                                            @click="removeSparePartReturned(index)"
                                            v-tooltip.top="'Supprimer la pièce'"
                                        />
                                    </div>
                                    <small class="p-error absolute bottom-0 left-0">{{ form.errors[`spare_parts_returned.${index}.id`] || form.errors[`spare_parts_returned.${index}.quantity`] }}</small>
                                </div>
                            </div>
                            <div v-else>
                                <p class="text-gray-500">Aucune pièce retournée enregistrée.</p>
                            </div>
                            <Button
                                label="Ajouter une pièce retournée"
                                icon="pi pi-plus"
                                class="p-button-text p-button-sm mt-2"
                                @click="openSparePartDialog('returned')"
                            />

                            <template #footer>
                                <Button label="Annuler" icon="pi pi-times" @click="hideDialog" class="p-button-text p-button-secondary" />
                                <Button
                                    :label="isCreatingSubActivity ? 'Créer la Sous-activité' : 'Sauvegarder'"
                                    icon="pi pi-check"
                                    @click="saveActivity"
                                    :loading="form.processing"
                                    class="p-button-info"
                                />
                            </template>
                        </Dialog>

                        <Dialog v-model:visible="sparePartDialogVisible" modal :header="sparePartData.index === -1 ? 'Ajouter une ou plusieurs pièces' : 'Modifier une pièce'" :style="{ width: '30rem' }" class="p-fluid">
                            <div class="field">
                                <label for="spare-part-id" class="font-semibold">Pièce(s) de rechange</label>

                                <MultiSelect
                                    v-if="sparePartData.index === -1"
                                    id="spare-part-id"
                                    v-model="sparePartData.ids"
                                    :options="sparePartOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner une ou plusieurs pièces"
                                    class="w-full"
                                    filter
                                    display="chip"
                                />

                                <Dropdown
                                    v-else
                                    id="spare-part-id-single"
                                    v-model="sparePartData.ids[0]"
                                    :options="sparePartOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner une pièce"
                                    class="w-full"
                                    filter
                                />
                            </div>
                            <div class="field">
                                <label for="spare-part-quantity" class="font-semibold">Quantité</label>
                                <InputNumber
                                    id="spare-part-quantity"
                                    v-model="sparePartData.quantity"
                                    placeholder="Quantité"
                                    :min="1"
                                    :max="99999"
                                    class="w-full"
                                    :useGrouping="false"
                                />
                            </div>
                            <template #footer>
                                <Button label="Annuler" icon="pi pi-times" @click="sparePartDialogVisible = false" class="p-button-text" />
                                <Button label="Sauvegarder" icon="pi pi-check" @click="saveSparePart" class="p-button-primary" />
                            </template>
                        </Dialog>

                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
<style scoped>
/* Styles personnalisés pour la Timeline si nécessaire */
.timeline-left :deep(.p-timeline-event-opposite) {
    flex: 0;
    padding: 0 1rem 0 0;
}
.timeline-left :deep(.p-timeline-event-content) {
    padding: 0 0 1rem 1rem;
    flex: 1;
}

/* Utilitaires pour le DataTable */
.max-w-10rem {
    max-width: 10rem;
}
.truncate {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

/* Styles pour le grid (si Tailwind n'est pas configuré) */
.grid-cols-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}
.grid-cols-12 {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 0.5rem;
}
.col-span-10 {
    grid-column: span 10 / span 10;
}
.col-span-2 {
    grid-column: span 2 / span 2;
}
.col-span-12 {
    grid-column: span 12 / span 12;
}
</style>
