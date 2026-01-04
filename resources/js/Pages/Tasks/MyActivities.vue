<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
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

const { t, tm, rt } = useI18n();
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
// ... à l'intérieur de <script setup> ...


/**
 * SOLUTION : Utiliser tm() (translation message) au lieu de t()
 * tm() récupère l'objet brut du fichier JSON.
 * rt() (render token) permet de s'assurer que le texte est bien rendu si c'est un lien.
 */

// 1. Pour les Dropdowns (Status, Types, etc.)
const statusOptions = computed(() => {
    const rawObject = tm('myActivities.statusOptions');
    return Object.entries(rawObject).map(([value, label]) => ({
        label: rt(label),
        value: value
    }));
});

const instructionTypes = computed(() => {
    const rawObject = tm('myActivities.instructionTypes');
    return Object.entries(rawObject).map(([value, label]) => ({
        label: rt(label),
        value: value
    }));
});

const availableDisplayOptions = computed(() => {
    const rawObject = tm('myActivities.displayOptions');
    return Object.entries(rawObject).map(([value, label]) => ({
        label: rt(label),
        value: value
    }));
});

// 2. Pour afficher un libellé simple à partir d'une valeur (ex: 'scheduled')
// const getStatusLabel = (status) => {
//     if (!status) return '';
//     // On va chercher dans "myActivities.status" (pas statusOptions)
//     return t(`myActivities.status.${status}`);
// };

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
    service_order_description: t('myActivities.defaults.sparePartPayment'),
    instruction_answers: {},
    parent_id: null,
    task_id: null,
    maintenance_id: null,
});


// --- UTILITIES ---

const parseJson = (data) => {
    if (typeof data === 'string' && data.length > 0) {
        try {
            return JSON.parse(data);
        } catch (e) {
            console.warn(t('myActivities.toast.jsonParseError'), e);
            return [];
        }
    }
    return Array.isArray(data) ? data : [];
};

const getSparePartReference = (id) => {
    const part = props.spareParts.find(p => p.id === id);
    return part ? part.reference : t('myActivities.unknownReference');
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
const getStatusLabel = (status) => {
    return statusOptions.value.find(s => s.value === status)?.label || status;
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
        const label = instruction ? instruction.label : t('myActivities.common.instructionNotFound', { id: instructionId });
        let value = answer.value;

        if (instruction && instruction.type === 'boolean') {
            value = value === '1' || value === 1 || value === true ? t('myActivities.boolean.yes') : t('myActivities.boolean.no');
        } else if (instruction && instruction.type === 'date' && value) {
            try {
                value = new Date(value).toLocaleDateString('fr-FR');
            } catch {
                value = t('myActivities.common.invalidDate');
            }
        } else if (value === null || value === undefined || value === '') {
             value = t('myActivities.common.notSet');
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
    form.parent_id = null;
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
    form.parent_id = activity.parent_id;
    form.task_id = activity.task_id;
    form.maintenance_id = activity.maintenance_id;
    form.service_order_cost = activity.service_order_cost || 0;
    form.service_order_description = activity.service_order_description || t('myActivities.defaults.sparePartPayment');

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

    form.parent_id = parentActivity.id;
    form.task_id = parentActivity.task_id;
    form.maintenance_id = parentActivity.maintenance_id;

    // Champs de contexte copiés
    form.jobber = parentActivity.jobber || null;
    form.user_id = parentActivity.user_id || null;
    form.service_order_description = parentActivity.service_order_description || t('myActivities.defaults.sparePartPayment'); // Default value

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
    form.problem_resolution_description = t('myActivities.subActivityFor', { title: parentActivity.task?.title || parentActivity.maintenance?.title || t('myActivities.unnamedActivity') });
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
    const successMessage = isCreatingSubActivity.value ? t('myActivities.toast.subActivityCreated') : t('myActivities.toast.activityUpdated');
    form.instructions =(getAvailableInstructions.value);
    console.log("XXXXXXXXXXXXXXXXXXXX2545");
    console.log({...form, instructions: getAvailableInstructions.value});
    console.log(getAvailableInstructions.value);
    const handler = {
        onSuccess: () => {
            hideDialog();
            toast.add({ severity: 'success', summary: t('myActivities.toast.success'), detail: successMessage, life: 3000 });
        },
        onError: (errors) => {
            console.error(errors);
            // Revertir les dates en objets Date si l'on veut que le formulaire reste ouvert après erreur
            form.actual_start_time = tempActualStartTime;
            form.scheduled_start_time = tempScheduledStartTime;
            form.actual_end_time = tempActualEndTime;

            toast.add({ severity: 'error', summary: t('myActivities.toast.error'), detail: t('myActivities.toast.saveError'), life: 3000 });
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


const sparePartOptions = computed(() => {
    return props.spareParts.map(part => ({
        label: `${part.reference} (${part.name}) - Stock: ${part.stock}`, // This is dynamic, better to leave it as is or construct it with t()
        value: part.id
    }));
});


const removeSparePartUsed = (index) => {
   form.spare_parts_used = form.spare_parts_used.filter((_, i) => i !== index);
};

const removeSparePartReturned = (index) => {
   form.spare_parts_returned = form.spare_parts_returned.filter((_, i) => i !== index);
};
// --- LOGIQUE D'AJOUT D'INSTRUCTIONS DYNAMIQUES ---
const newInstruction = ref({
    label: '',
    type: 'text',
    is_required: false
});


const addNewInstructionToForm = () => {
    if (!newInstruction.value.label) {
        toast.add({ severity: 'warn', summary: t('myActivities.toast.warning'), detail: t('myActivities.toast.instructionLabelRequired'), life: 3000 });
        return;
    }

    // Créer un ID temporaire unique
    const tempId = 'new_' + Date.now();

    // 1. On l'ajoute à la liste des instructions affichées
    const currentInstructions = parseJson(form.instructions) || [];
    const updatedInstructions = [...currentInstructions, { ...newInstruction.value, id: tempId }];

    // 2. On met à jour le champ du formulaire
    form.instructions = JSON.stringify(updatedInstructions);

    // Réinitialiser le petit formulaire d'ajout
    newInstruction.value = { label: '', type: 'text', is_required: false };

    toast.add({ severity: 'success', summary: t('myActivities.toast.added'), detail: t('myActivities.toast.instructionAdded'), life: 2000 });
};
// --- CONFIGURATION DES INSTRUCTIONS DYNAMIQUES ---




const getAvailableInstructions = computed(() => {
    try {
        return typeof form.instructions === 'string'
            ? JSON.parse(form.instructions)
            : (form.instructions || []);
    } catch (e) {
        return [];
    }
});

// --- GESTION DES PIÈCES RETOURNÉES ---


// Modification de saveSparePart pour inclure le retour
const saveSparePart = () => {
    if (sparePartData.value.ids.length === 0) return;

    const target = sparePartData.value.type === 'used'
        ? form.spare_parts_used
        : form.spare_parts_returned;

    sparePartData.value.ids.forEach(id => {
        const exists = target.find(p => p.id === id);
        if (exists) {
            exists.quantity += sparePartData.value.quantity;
        } else {
            target.push({ id: id, quantity: sparePartData.value.quantity });
        }
    });

    sparePartDialogVisible.value = false;
    sparePartData.value = { type: 'used', ids: [], quantity: 1, index: -1 };
};
const removeInstruction = (index, instructionId) => {
    // 1. Récupérer les instructions actuelles
    let currentInstructions = [];
    try {
        currentInstructions = typeof form.instructions === 'string'
            ? JSON.parse(form.instructions)
            : (form.instructions || []);
    } catch (e) {
        currentInstructions = [];
    }

    // 2. Supprimer l'élément à l'index donné
    currentInstructions.splice(index, 1);

    // 3. Mettre à jour le formulaire
    form.instructions = JSON.stringify(currentInstructions);

    // 4. Nettoyer la réponse associée dans instruction_answers pour ne pas envoyer de données inutiles
    if (form.instruction_answers && form.instruction_answers[instructionId]) {
        delete form.instruction_answers[instructionId];
    }

    toast.add({
        severity: 'info',
        summary: t('myActivities.toast.instructionRemoved'),
        detail: t('myActivities.toast.listUpdated'),
        life: 2000
    });
};
const getStatusGradient = (status) => {
    const maps = {
        'completed': 'from-emerald-400 to-cyan-400',
        'in_progress': 'from-blue-400 to-indigo-400',
        'scheduled': 'from-slate-300 to-slate-400',
        'suspended': 'from-orange-400 to-red-400',
        'canceled': 'from-gray-400 to-gray-600'
    };
    return maps[status] || 'from-indigo-400 to-purple-400';
};

const getStatusBgClass = (status) => {
    const maps = {
        'completed': 'bg-emerald-500',
        'in_progress': 'bg-blue-500',
        'scheduled': 'bg-slate-400',
        'suspended': 'bg-orange-500',
        'canceled': 'bg-gray-500'
    };
    return maps[status] || 'bg-indigo-500';
};

// Formateurs de date pour l'ergonomie
const formatTime = (date) => date ? new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
const formatDate = (date) => date ? new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '---';
const formatDateShort = (date) => date ? new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'numeric', year: '2-digit' }) : 'N/A';
</script>

<template>
    <AppLayout :title="t('myActivities.title')">
        <Head :title="t('myActivities.title')" />

        <div class="grid">
            <div class="col-12">
                <Card>
                    <template #title>
                        <h2 class="text-3xl font-bold text-primary-600 mb-2">
                            <i class="pi pi-list-check mr-2"></i> {{ t('myActivities.timelineTitle') }}
                        </h2>
                    </template>

                    <template #subtitle>
                        <div class="flex flex-column md:flex-row md:justify-content-between md:items-center">
                            <span class="mb-2 md:mb-0">{{ t('myActivities.timelineSubtitle') }}</span>

                            <div class="flex flex-column md:flex-row items-center gap-3">
                                <MultiSelect
                                    v-model="displayFields"
                                    :options="availableDisplayOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    :placeholder="t('myActivities.columnsPlaceholder')"
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
                                <Timeline :value="currentActivities" align="left" class="custom-timeline-pro">

            <template #marker="slotProps">
                <div class="flex flex-column align-items-center">
                    <div
                        class="flex align-items-center justify-content-center border-circle shadow-4 z-2 transition-transform duration-300 hover:scale-110"
                        :class="getStatusBgClass(slotProps.item.status)"
                        style="width: 3.5rem; height: 3.5rem; border: 0.3rem solid white;"
                    >
                        <i :class="[getIconForActivity(slotProps.item.status), 'text-white text-xl']"></i>
                    </div>
                    </div>
            </template>

            <template #opposite="slotProps">
                <div class="hidden md:flex flex-column align-items-end mt-3 pr-3">
                    <span class="text-xl font-black text-slate-700 tracking-tighter">
                        {{ formatTime(slotProps.item.actual_start_time) }}
                    </span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                        {{ formatDate(slotProps.item.actual_start_time) }}
                    </span>
                </div>
            </template>

            <template #content="slotProps">
                <div class="relative mb-8 group">
                    <div class="absolute -inset-1 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition duration-500 rounded-2xl blur-lg"
                         :class="getStatusGradient(slotProps.item.status)"></div>

                    <Card class="relative border-none shadow-2 overflow-hidden bg-white/80 backdrop-blur-md rounded-2xl">
                        <template #content>
                            <div class="flex flex-column sm:flex-row justify-content-between align-items-start gap-3 mb-4">
                                <div class="flex-1">
                                    <div class="flex align-items-center gap-2 mb-1">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-bold uppercase">
                                            OT #{{ slotProps.item.task?.id || slotProps.item.maintenance?.id || 'N/A' }}
                                        </span>
                                        <Tag :value="getStatusLabel(slotProps.item.status)"
                                             :severity="getStatusSeverity(slotProps.item.status)"
                                             class="text-xs uppercase font-bold px-3" />
                                    </div>
                                    <h3 class="text-2xl font-bold text-slate-800 m-0 tracking-tight leading-tight">
                                        {{ slotProps.item.task?.title || slotProps.item.maintenance?.title || t('myActivities.common.unnamedActivity') }}
                                    </h3>
                                </div>

                                <div class="flex align-items-center gap-2 bg-red-50 px-3 py-2 rounded-xl border-1 border-red-100"
                                     v-if="slotProps.item.task?.priority === 'high' || slotProps.item.maintenance?.priority === 'high'">
                                    <span class="relative flex h-3 w-3">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                    </span>
                                    <span class="text-red-700 font-black text-xs uppercase">Urgente</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border-1 border-slate-100">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-blue-500"><i class="pi pi-user"></i></div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] uppercase font-bold text-slate-400">Technicien</span>
                                        <span class="text-sm font-semibold text-slate-700">{{ slotProps.item.jobber || 'Non assigné' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border-1 border-slate-100">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-orange-500"><i class="pi pi-map-marker"></i></div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] uppercase font-bold text-slate-400">Localisation</span>
                                        <span class="text-sm font-semibold text-slate-700">{{ slotProps.item.location || 'Site Principal' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border-1 border-slate-100">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-emerald-500"><i class="pi pi-calendar"></i></div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] uppercase font-bold text-slate-400">Fin estimée</span>
                                        <span class="text-sm font-semibold text-slate-700">{{ formatDateShort(slotProps.item.actual_end_time) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="slotProps.item.problem_resolution_description" class="relative p-4 rounded-2xl bg-slate-800 text-white mb-4 overflow-hidden">
                                <i class="pi pi-quote-right absolute right-4 top-4 text-4xl opacity-10"></i>
                                <h4 class="text-xs uppercase tracking-widest font-bold text-slate-400 mb-2">Rapport de résolution</h4>
                                <p class="text-sm line-height-3 m-0 font-medium leading-relaxed">
                                    {{ slotProps.item.problem_resolution_description }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-4 mt-2">
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-box text-slate-400"></i>
                                    <span class="text-sm font-bold text-slate-600">
                                        {{ slotProps.item.spare_parts_used?.length || 0 }} pièces
                                    </span>
                                </div>
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-list text-slate-400"></i>
                                    <span class="text-sm font-bold text-slate-600">
                                        {{ slotProps.item.instruction_answers ? Object.keys(slotProps.item.instruction_answers).length : 0 }} points de contrôle
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 mt-4 pt-4 border-t-1 border-slate-100">
                                <Button label="Détails" icon="pi pi-eye" class="p-button-text p-button-secondary font-bold text-xs" />
                                <Button :label="t('myActivities.common.complete')"
                                        icon="pi pi-pencil"
                                        @click="editActivity(slotProps.item)"
                                        class="p-button-rounded bg-slate-900 border-none px-4 hover:bg-indigo-600 transition-colors duration-300 shadow-lg" />
                            </div>
                        </template>
                    </Card>
                </div>
            </template>
        </Timeline>



                            </div>

                            <div v-else-if="viewMode === 'table'">
                                <DataTable :value="currentActivities" responsiveLayout="scroll" dataKey="id" class="p-datatable-sm shadow-2 border-round-lg">
                                    <Column field="task.title" :header="t('myActivities.common.taskTitle')" :sortable="true" class="font-semibold">
                                        <template #body="slotProps">
                                            {{ slotProps.data.task?.title || slotProps.data.maintenance?.title || t('myActivities.common.notApplicable') }}
                                        </template>
                                    </Column>
                                    <Column field="status" :header="t('myActivities.common.status')" :sortable="true">
                                        <template #body="slotProps">
                                            <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" />
                                        </template>
                                    </Column>
                                    <Column field="actual_start_time" :header="t('myActivities.common.actualStart')" :sortable="true">
                                        <template #body="slotProps">
                                            {{ slotProps.data.actual_start_time ? new Date(slotProps.data.actual_start_time).toLocaleString('fr-FR') : t('myActivities.common.notApplicable') }}
                                        </template>
                                    </Column>
                                    <Column field="task.priority" :header="t('myActivities.common.priority')" :sortable="true">
                                        <template #body="slotProps">
                                            <Tag :value="slotProps.data.task?.priority || slotProps.data.maintenance?.priority" :severity="getPrioritySeverity(slotProps.data.task?.priority || slotProps.data.maintenance?.priority)" />
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('actual_end_time')" :header="t('myActivities.displayOptions.actual_end_time')" :sortable="true">
                                        <template #body="slotProps">
                                            {{ slotProps.data.actual_end_time ? new Date(slotProps.data.actual_end_time).toLocaleString('fr-FR') : t('myActivities.common.notApplicable') }}
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('proposals')" :header="t('myActivities.displayOptions.proposals')" :sortable="false">
                                        <template #body="slotProps">
                                            <span v-tooltip.top="slotProps.data.proposals" class="max-w-10rem truncate block">{{ slotProps.data.proposals || t('myActivities.common.notApplicable') }}</span>
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('jobber')" field="jobber" :header="t('myActivities.displayOptions.jobber')" :sortable="true"></Column>

                                    <Column v-if="displayFields.includes('additional_information')" :header="t('myActivities.displayOptions.additional_information')" :sortable="false">
                                        <template #body="slotProps">
                                            <span v-tooltip.top="slotProps.data.additional_information" class="max-w-10rem truncate block">{{ slotProps.data.additional_information || t('myActivities.common.notApplicable') }}</span>
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('spare_parts_used')" :header="t('myActivities.displayOptions.spare_parts_used')" :sortable="false">
                                        <template #body="slotProps">
                                            <div v-if="slotProps.data.spare_parts_used.length > 0">
                                                <Tag
                                                    :value="t('myActivities.common.usedPartsCount', { count: slotProps.data.spare_parts_used.length })"
                                                    severity="contrast"
                                                    v-tooltip.top="slotProps.data.spare_parts_used.map(p => `${p.quantity} x ${getSparePartReference(p.id)}`).join(', ')"
                                                />
                                            </div>
                                            <span v-else>{{ t('myActivities.boolean.no') }}</span>
                                        </template>
                                    </Column>

                                    <Column v-if="displayFields.includes('spare_parts_returned')" :header="t('myActivities.displayOptions.spare_parts_returned')" :sortable="false">
                                        <template #body="slotProps">
                                            <div v-if="slotProps.data.spare_parts_returned.length > 0">
                                                <Tag
                                                    :value="t('myActivities.common.returnedPartsCount', { count: slotProps.data.spare_parts_returned.length })"
                                                    severity="warning"
                                                    v-tooltip.top="slotProps.data.spare_parts_returned.map(p => `${p.quantity} x ${getSparePartReference(p.id)}`).join(', ')"
                                                />
                                            </div>
                                            <span v-else>{{ t('myActivities.boolean.no') }}</span>
                                        </template>
                                    </Column>

                                    <Column :header="t('myActivities.common.instructionsAndActions')" class="text-right">
                                        <template #body="slotProps">
                                            <div class="flex justify-content-end align-items-center">
                                                <div v-if="slotProps.data.instruction_answers.length > 0" class="mr-2">
                                                    <Tag
                                                        :value="t('myActivities.common.answersCount', { count: slotProps.data.instruction_answers.length })"
                                                        severity="info"
                                                        v-tooltip.top="formatInstructionAnswer(slotProps.data).map(a => `${a.label}: ${a.value}`).join(' | ')"
                                                    />
                                                </div>
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="p-button-rounded p-button-info p-button-sm"
                                                    @click="editActivity(slotProps.data)"
                                                    v-tooltip.top="t('myActivities.common.completeActivityTooltip')"
                                                />
                                            </div>
                                        </template>
                                    </Column>

                                </DataTable>
                            </div>

                        </div>

                        <div v-else class="text-center p-5 surface-50 border-round-md shadow-2">
                            <i class="pi pi-calendar-times text-5xl text-400 mb-3"></i>
                            <p class="text-xl text-700">{{ t('myActivities.noActivitiesTitle') }}</p>
                            <p class="text-600">{{ t('myActivities.noActivitiesSubtitle') }}</p>
                        </div>
<template>
    <Dialog
        v-model:visible="activityDialogVisible"
        modal
        class="quantum-dialog w-full max-w-7xl overflow-hidden"
        :closable="false"
        :pt="{
            mask: { style: 'backdrop-filter: blur(6px)' },
            content: { class: 'p-0 rounded-3xl border-none shadow-2xl' }
        }"
    >
        <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50 rounded-xl">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-blue-500/20 rounded-xl border border-blue-500/30">
                    <i :class="[isCreatingSubActivity ? 'pi pi-plus-circle' : 'pi pi-shield', 'text-blue-400 text-xl']"></i>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">
                        {{ isCreatingSubActivity ? t('myActivities.dialog.subActivityInitialization') : t('myActivities.dialog.technicalInterventionReport') }}
                    </h2>
                    <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">
                        {{ t('myActivities.dialog.gmaoAdminConsole') }}
                    </span>
                </div>
            </div>
            <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="hideDialog" class="text-white hover:bg-white/10" />
        </div>

        <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

                <div class="md:col-span-7 space-y-8">

                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-4 italic">{{ t('myActivities.dialog.resolutionTitle') }}</label>
                        <Textarea v-model="form.problem_resolution_description" rows="6"
                            class="w-full p-4 rounded-2xl border-slate-200 focus:ring-2 focus:ring-blue-500/20 text-sm shadow-inner"
                            :placeholder="t('myActivities.dialog.resolutionPlaceholder')" />

                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="field">
                                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">{{ t('myActivities.dialog.recommendationsLabel') }}</label>
                                <Textarea v-model="form.proposals" rows="3" class="w-full text-xs rounded-xl border-slate-200" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">{{ t('myActivities.dialog.internalNotesLabel') }}</label>
                                <Textarea v-model="form.additional_information" rows="3" class="w-full text-xs rounded-xl border-slate-200" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-2 border-dashed border-slate-200 rounded-[2rem]">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xs font-black uppercase text-indigo-600 tracking-widest flex items-center gap-2">
                                <i class="pi pi-list-check"></i> {{ t('myActivities.dialog.checklistTitle') }}
                            </h3>
                            <Tag :value="t('myActivities.dialog.checklistPoints', { count: getAvailableInstructions.length })" severity="info" rounded />
                        </div>

                        <div class="flex gap-2 mb-6 p-2 bg-indigo-50/50 rounded-xl border border-indigo-100">
                            <InputText v-model="newInstruction.label" placeholder="Nouvelle consigne..." class="flex-grow p-inputtext-sm border-none bg-transparent" />
                            <Dropdown v-model="newInstruction.type" :options="instructionTypes" optionLabel="label" optionValue="value" class="w-36 p-inputtext-sm" />
                            <Button icon="pi pi-plus" severity="indigo" class="p-button-sm rounded-lg" @click="addNewInstructionToForm" />
                        </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div v-for="(instruction, index) in getAvailableInstructions" :key="instruction.id"
         class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:border-red-200 transition-all group relative">

        <button
            @click="removeInstruction(index, instruction.id)"
            class="absolute -top-2 -right-2 h-6 w-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-lg flex items-center justify-center z-10"
            :title="t('myActivities.dialog.removeInstructionTitle')"
        >
            <i class="pi pi-times text-[10px]"></i>
        </button>

        <span class="text-[9px] font-black text-slate-400 uppercase block mb-2 tracking-tighter">
            {{ instruction.label }}
            <Tag v-if="instruction.is_custom" value="Custom" severity="warning" class="scale-75 origin-left ml-2" />
        </span>

        <div class="instruction-input">
            <InputText v-if="instruction.type === 'text'" v-model="form.instruction_answers[instruction.id]" class="w-full p-inputtext-sm border-none bg-slate-50 rounded-lg" />
            <InputNumber v-else-if="instruction.type === 'number'" v-model="form.instruction_answers[instruction.id]" class="w-full p-inputtext-sm border-none bg-slate-50 rounded-lg" />
            <SelectButton v-else-if="instruction.type === 'boolean'" v-model="form.instruction_answers[instruction.id]"
                         :options="[{label:'OUI', value:'1'}, {label:'NON', value:'0'}]" optionLabel="label" optionValue="value" class="v11-select-sm" />
            <Calendar v-else-if="instruction.type === 'date'" v-model="form.instruction_answers[instruction.id]" class="w-full" />
            <Textarea v-else-if="instruction.type === 'textarea'" v-model="form.instruction_answers[instruction.id]" rows="2" class="w-full text-xs border-none bg-slate-50 rounded-lg" />
        </div>
    </div>
</div>
                    </div>
                </div>

                <div class="md:col-span-5 space-y-6">

                    <div class="p-6 bg-slate-900 rounded-[2.5rem] text-white shadow-xl relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl"></div>
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] mb-6 text-blue-300 italic">{{ t('myActivities.dialog.missionConfigTitle') }}</h4>

                        <div class="space-y-4">
                            <div class="field">
                                <label class="text-[8px] font-bold uppercase opacity-50 mb-1 block">{{ t('myActivities.dialog.statusLabel') }}</label>
                                <Dropdown v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value"
                                          class="w-full bg-white/5 border-white/10 text-white rounded-xl text-sm" />
                            </div>
                            <div class="field">
                                <label class="text-[8px] font-bold uppercase opacity-50 mb-1 block">{{ t('myActivities.dialog.assignedTechnicianLabel') }}</label>
                                <Dropdown v-model="form.user_id" :options="props.users" optionLabel="name" optionValue="id"
                                          filter class="w-full bg-white/5 border-white/10 text-white rounded-xl text-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-4 pt-2">
                                <div class="field">
                                    <label class="text-[8px] font-bold uppercase text-blue-400">{{ t('myActivities.dialog.actualStartLabel') }}</label>
                                    <Calendar v-model="form.actual_start_time" showTime hourFormat="24" class="quantum-calendar-dark" />
                                </div>
                                <div class="field">
                                    <label class="text-[8px] font-bold uppercase text-orange-400">{{ t('myActivities.dialog.actualEndLabel') }}</label>
                                    <Calendar v-model="form.actual_end_time" showTime hourFormat="24" class="quantum-calendar-dark" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-6 bg-white border border-slate-200 rounded-[2.5rem] shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-[10px] font-black uppercase text-slate-500 tracking-widest">📦 {{ t('myActivities.dialog.stockOutflowsTitle') }}</h4>
                                <Button icon="pi pi-plus" rounded severity="secondary" size="small" @click="openSparePartDialog('used')" />
                            </div>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <div v-for="(part, idx) in form.spare_parts_used" :key="idx" class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl text-[11px] font-bold group">
                                    <span class="bg-blue-600 text-white px-2 py-0.5 rounded-lg font-black text-[9px]">x{{ part.quantity }}</span>
                                    <span class="flex-grow truncate">{{ getSparePartReference(part.id) }}</span>
                                    <i class="pi pi-trash text-red-400 cursor-pointer opacity-0 group-hover:opacity-100" @click="removeSparePartUsed(idx)"></i>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-emerald-50/50 border border-emerald-100 rounded-[2.5rem] shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-[10px] font-black uppercase text-emerald-600 tracking-widest">🔄 {{ t('myActivities.dialog.storeReturnsTitle') }}</h4>
                                <Button icon="pi pi-plus" rounded severity="success" size="small" @click="openSparePartDialog('returned')" />
                            </div>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <div v-for="(part, idx) in form.spare_parts_returned" :key="idx" class="flex items-center gap-3 p-3 bg-white rounded-2xl text-[11px] font-bold group border border-emerald-100">
                                    <span class="bg-emerald-500 text-white px-2 py-0.5 rounded-lg font-black text-[9px]">x{{ part.quantity }}</span>
                                    <span class="flex-grow truncate">{{ getSparePartReference(part.id) }}</span>
                                    <i class="pi pi-trash text-red-400 cursor-pointer opacity-0 group-hover:opacity-100" @click="removeSparePartReturned(idx)"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-slate-100 rounded-[2.5rem] border border-slate-200">
                        <label class="text-[9px] font-black uppercase text-slate-400 mb-2 block tracking-widest">{{ t('myActivities.dialog.financialImpactLabel') }}</label>
                        <div class="text-4xl font-black text-slate-900 tracking-tighter">
                            {{ serviceOrderCost.toLocaleString() }} <small class="text-xs font-bold opacity-40">XOF</small>
                        </div>
                        <InputText v-model="form.service_order_description" :placeholder="t('myActivities.dialog.billingLabelPlaceholder')" class="mt-4 p-inputtext-sm w-full bg-white/50 border-none" />
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('myActivities.dialog.cancelButton')" icon="pi pi-times" text severity="secondary" @click="hideDialog" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="isCreatingSubActivity ? t('myActivities.dialog.createSubActivityButton') : t('myActivities.dialog.publishReportButton')"
                        icon="pi pi-check-circle" severity="indigo"
                        class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                        @click="saveActivity" :loading="form.processing" />
            </div>
        </template>
    </Dialog>

    <Dialog v-model:visible="sparePartDialogVisible" modal :closable="false"
            class="quantum-dialog w-full max-w-md shadow-2xl"
            :pt="{ mask: { style: 'backdrop-filter: blur(8px)' }, content: { class: 'p-0 rounded-[2rem] overflow-hidden' } }">

        <div class="p-6 bg-slate-900 text-white flex items-center gap-4 rounded-xl">
            <i class="pi pi-box text-blue-400 text-xl"></i>
            <h2 class="text-xs font-black uppercase tracking-widest">
                {{ sparePartData.type === 'used' ? t('myActivities.dialog.sparePartModalUsedTitle') : t('myActivities.dialog.sparePartModalReturnedTitle') }}
            </h2>
        </div>

        <div class="p-8 space-y-6 bg-white">
            <div class="field">
                <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">{{ t('myActivities.dialog.referencesLabel') }}</label>
                <MultiSelect v-if="sparePartData.index === -1" v-model="sparePartData.ids" :options="sparePartOptions" optionLabel="label" optionValue="value" filter display="chip" :placeholder="t('myActivities.dialog.searchPartPlaceholder')" class="w-full" />
                <Dropdown v-else v-model="sparePartData.ids[0]" :options="sparePartOptions" optionLabel="label" optionValue="value" filter class="w-full" />

            </div>

            <div class="field">
                <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">{{ t('myActivities.dialog.unitQuantityLabel') }}</label>
                <InputNumber v-model="sparePartData.quantity" showButtons :min="1" buttonLayout="horizontal" class="w-full" />
            </div>
        </div>

        <div class="p-4 bg-slate-50 flex gap-3 border-t">
            <Button :label="t('myActivities.dialog.closeButton')" text severity="secondary" class="flex-1 font-bold text-xs" @click="sparePartDialogVisible = false" />
            <Button :label="t('myActivities.dialog.validateButton')" severity="indigo" class="flex-1 font-bold text-xs rounded-xl shadow-lg shadow-indigo-100" @click="saveSparePart" />
        </div>
    </Dialog>
</template>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
<style scoped>
    /* Correction de la ligne de timeline pour un look plus fin et moderne */
:deep(.p-timeline-event-connector) {
    width: 2px;
    background-color: #e2e8f0; /* slate-200 */
}

:deep(.p-timeline-event-opposite) {
    flex: 0 0 100px; /* Largeur fixe pour l'heure à gauche */
}

/* Suppression des bordures par défaut de PrimeVue Card */
:deep(.p-card) {
    border-radius: 1.25rem;
}

:deep(.p-card-body) {
    padding: 1.5rem;
}
    .quantum-calendar-dark :deep(.p-inputtext) {
    background: rgba(255, 255, 255, 0.1) !important;
    border: none;
    color: white;
    font-size: 0.8rem;
    padding: 0.5rem;
}
.quantum-selectbutton :deep(.p-button) {
    font-size: 0.75rem;
    font-weight: bold;
}
.p-button-indigo {
    background: #4f46e5;
    border: none;
}
.p-button-indigo:hover {
    background: #4338ca;
}
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
