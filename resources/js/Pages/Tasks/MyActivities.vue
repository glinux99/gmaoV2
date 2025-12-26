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
const formatDay = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatTime = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
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
<Dialog
    v-model:visible="equipmentDialog"
    modal
    :header="dialogTitle"
    class="p-fluid"
    :style="{ width: '50rem' }"
    :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
>
    <div class="grid">
        <div class="col-12 field">
            <label for="parent_id" class="font-bold text-900">{{ t('equipments.dialog.parentLabel') }}</label>
            <Dropdown
                id="parent_id"
                v-model="form.parent_id"
                :options="parentEquipments"
                optionLabel="designation"
                optionValue="id"
                :placeholder="t('equipments.dialog.parentPlaceholder')"
                showClear
                filter
            />
            <small class="p-error">{{ form.errors.parent_id }}</small>
        </div>

        <div v-if="!isChild" class="col-12 md:col-6 field">
            <label for="tag" class="font-bold text-900">{{ t('equipments.dialog.tag') }}</label>
            <InputText id="tag" v-model.trim="form.tag" :class="{ 'p-invalid': form.errors.tag }" />
            <small class="p-error">{{ form.errors.tag }}</small>
        </div>

        <div :class="isChild ? 'col-12' : 'col-12 md:col-6'" class="field">
            <label for="designation" class="font-bold text-900">{{ t('equipments.dialog.designation') }}</label>
            <InputText id="designation" v-model.trim="form.designation" :class="{ 'p-invalid': form.errors.designation }" />
            <small class="p-error">{{ form.errors.designation }}</small>
        </div>

        <div class="col-12 md:col-6 field">
            <label for="equipment_type_id" class="font-bold text-900">{{ t('equipments.dialog.type') }}</label>
            <div class="flex gap-2">
                <Dropdown
                    id="equipment_type_id"
                    v-model="form.equipment_type_id"
                    :options="equipmentTypes"
                    optionLabel="name"
                    optionValue="id"
                    class="flex-grow-1"
                    :placeholder="t('equipments.dialog.typePlaceholder')"
                    :class="{ 'p-invalid': form.errors.equipment_type_id }"
                />
                <Button icon="pi pi-plus" severity="secondary" @click="openNewEquipmentType" :title="t('equipments.dialog.addType')" />
            </div>
            <small class="p-error">{{ form.errors.equipment_type_id }}</small>
        </div>

        <div class="col-12 md:col-6 field">
            <label for="region_id" class="font-bold text-900">{{ t('equipments.dialog.region') }}</label>
            <Dropdown id="region_id" v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" :placeholder="t('equipments.dialog.regionPlaceholder')" />
            <small class="p-error">{{ form.errors.region_id }}</small>
        </div>

        <div class="col-12 md:col-4 field">
            <label for="brand" class="font-bold text-900">{{ t('equipments.dialog.brand') }}</label>
            <InputText id="brand" v-model.trim="form.brand" :class="{ 'p-invalid': form.errors.brand }" />
            <small class="p-error">{{ form.errors.brand }}</small>
        </div>

        <div class="col-12 md:col-4 field">
            <label for="model" class="font-bold text-900">{{ t('equipments.dialog.model') }}</label>
            <InputText id="model" v-model.trim="form.model" :class="{ 'p-invalid': form.errors.model }" />
            <small class="p-error">{{ form.errors.model }}</small>
        </div>

        <div v-if="showPuissance" class="col-12 md:col-4 field">
            <label for="puissance" class="font-bold text-900">{{ t('equipments.dialog.power') }}</label>
            <InputNumber id="puissance" v-model="form.puissance" suffix=" kW" :min="0" :class="{ 'p-invalid': form.errors.puissance }" />
            <small class="p-error">{{ form.errors.puissance }}</small>
        </div>

        <div class="col-12 md:col-4 field">
            <label for="serial_number" class="font-bold text-900">{{ t('equipments.dialog.serialNumber') }}</label>
            <InputText id="serial_number" v-model.trim="form.serial_number" :class="{ 'p-invalid': form.errors.serial_number }" />
            <small class="p-error">{{ form.errors.serial_number }}</small>
        </div>

        <div class="col-12 md:col-4 field">
            <label for="price" class="font-bold text-900">{{ t('equipments.dialog.price') }}</label>
            <InputNumber id="price" v-model="form.price" mode="currency" currency="EUR" locale="fr-FR" :class="{ 'p-invalid': form.errors.price }" />
            <small class="p-error">{{ form.errors.price }}</small>
        </div>

        <div class="col-12 md:col-4 field">
            <label for="status" class="font-bold text-900">{{ t('equipments.dialog.status') }}</label>
            <Dropdown id="status" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" :class="{ 'p-invalid': form.errors.status }" />
            <small class="p-error">{{ form.errors.status }}</small>
        </div>

        <div v-if="isStockStatus" class="col-12 field">
            <label for="quantity" class="font-bold text-900">{{ t('equipments.dialog.stockQuantity') }}</label>
            <InputNumber id="quantity" v-model="form.quantity" :min="0" :class="{ 'p-invalid': form.errors.quantity }" />
            <small class="p-error">{{ form.errors.quantity }}</small>
        </div>

        <div class="col-12 md:col-6 field">
            <label for="purchase_date" class="font-bold text-900">{{ t('equipments.dialog.purchaseDate') }}</label>
            <Calendar id="purchase_date" v-model="form.purchase_date" dateFormat="dd/mm/yy" showIcon :class="{ 'p-invalid': form.errors.purchase_date }" />
            <small class="p-error">{{ form.errors.purchase_date }}</small>
        </div>

        <div class="col-12 md:col-6 field">
            <label for="warranty_end_date" class="font-bold text-900">{{ t('equipments.dialog.warrantyEnd') }}</label>
            <Calendar id="warranty_end_date" v-model="form.warranty_end_date" dateFormat="dd/mm/yy" showIcon :class="{ 'p-invalid': form.errors.warranty_end_date }" />
            <small class="p-error">{{ form.errors.warranty_end_date }}</small>
        </div>

        <div class="col-12 field">
            <label for="location" class="font-bold text-900">{{ t('equipments.dialog.location') }}</label>
            <InputText id="location" v-model.trim="form.location" :class="{ 'p-invalid': form.errors.location }" />
            <small class="p-error">{{ form.errors.location }}</small>
        </div>

        <div class="col-12">
            <Divider align="left" type="solid">
                <span class="font-bold">{{ t('equipments.dialog.characteristics') }}</span>
            </Divider>

            <div class="field mb-4">
                <label for="label_id" class="font-bold text-900">{{ t('equipments.dialog.importCharacteristics') }}</label>
                <Dropdown id="label_id" v-model="form.label_id" :options="labels" optionLabel="designation" optionValue="id" showClear filter />
            </div>

            <div v-for="(char, index) in form.characteristics" :key="index" class="flex flex-column md:flex-row gap-2 mb-3 align-items-start border-bottom-1 surface-border pb-3">
                <div class="flex-grow-1">
                    <InputText v-model="char.name" :placeholder="t('equipments.dialog.characteristicNamePlaceholder')" class="w-full" :class="{ 'p-invalid': form.errors[`characteristics.${index}.name`] }" />
                    <small class="p-error" v-if="form.errors[`characteristics.${index}.name`]">{{ form.errors[`characteristics.${index}.name`] }}</small>
                </div>

                <div class="w-full md:w-12rem">
                    <Dropdown v-model="char.type" :options="characteristicTypes" optionLabel="label" optionValue="value" class="w-full" />
                </div>

                <div class="flex-grow-2 w-full">
                    <InputText v-if="char.type === 'text'" v-model="char.value" class="w-full" />
                    <InputNumber v-else-if="char.type === 'number'" v-model="char.value" class="w-full" />
                    <Calendar v-else-if="char.type === 'date'" v-model="char.value" dateFormat="dd/mm/yy" class="w-full" />
                    <InputSwitch v-else-if="char.type === 'boolean'" v-model="char.value" />

                    <div v-else-if="char.type === 'file'" class="flex flex-column gap-2">
                        <input type="file" @change="char.value = $event.target.files[0]" class="p-inputtext p-2 border-1 surface-border border-round" />
                        <a v-if="typeof char.value === 'string'" :href="`/storage/${char.value}`" target="_blank" class="text-primary font-medium">
                            <i class="pi pi-download mr-1"></i> {{ char.value.split('/').pop() }}
                        </a>
                    </div>
                </div>

                <Button icon="pi pi-trash" severity="danger" text rounded @click="removeCharacteristic(index)" :disabled="form.characteristics.length === 1" />
            </div>

            <Button :label="t('equipments.dialog.addCharacteristic')" icon="pi pi-plus" text @click="addCharacteristic" :disabled="isChild" class="mt-2" />
        </div>
    </div>

    <template #footer>
        <Button :label="t('equipments.dialog.cancel')" icon="pi pi-times" text @click="hideDialog" />
        <Button :label="t('equipments.dialog.save')" icon="pi pi-check" @click="saveEquipment" :loading="form.processing" />
    </template>
</Dialog>
                      <Dialog v-model:visible="sparePartDialogVisible" modal
        :header="sparePartData.index === -1 ? 'Ajouter des Ressources' : 'Modifier la Ressource'"
        class="quantum-dialog w-full max-w-lg"
        :pt="{
            mask: { style: 'backdrop-filter: blur(4px)' },
            header: { class: 'p-6 bg-slate-50 border-b border-slate-100' }
        }">

    <div class="p-6 space-y-6">
        <div class="p-4 bg-indigo-50 border-2 border-indigo-100 rounded-2xl flex items-center gap-4">
            <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-indigo-200">
                <i class="pi pi-search text-white"></i>
            </div>
            <div class="flex-1">
                <label class="text-[10px] font-black uppercase text-indigo-400 tracking-widest block mb-1">Catalogue Pièces</label>

                <MultiSelect v-if="sparePartData.index === -1" v-model="sparePartData.ids" :options="sparePartOptions"
                             optionLabel="label" optionValue="value" placeholder="Rechercher une référence..."
                             filter display="chip" class="w-full border-none bg-transparent shadow-none" />

                <Dropdown v-else v-model="sparePartData.ids[0]" :options="sparePartOptions"
                          optionLabel="label" optionValue="value" filter
                          class="w-full border-none bg-transparent shadow-none" />
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-1 text-center">Quantité Requis</label>
            <div class="flex items-center justify-center gap-4">
                <Button icon="pi pi-minus" class="p-button-rounded p-button-outlined p-button-secondary border-slate-200"
                        @click="sparePartData.quantity > 1 ? sparePartData.quantity-- : null" />

                <InputNumber v-model="sparePartData.quantity" class="text-center w-32" :min="1"
                             inputClass="text-center text-2xl font-black text-slate-800 border-none shadow-none" />

                <Button icon="pi pi-plus" class="p-button-rounded p-button-outlined p-button-secondary border-slate-200"
                        @click="sparePartData.quantity++" />
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex gap-2 p-2">
            <Button label="Annuler" class="p-button-text p-button-secondary flex-1" @click="sparePartDialogVisible = false" />
            <Button label="Valider l'Ajout" icon="pi pi-check" class="p-button-indigo flex-1 h-12 rounded-xl shadow-lg shadow-indigo-100" @click="saveSparePart" />
        </div>
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
