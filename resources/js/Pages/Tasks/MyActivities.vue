<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, useForm, router } from '@inertiajs/vue3';
// Layout
import AppLayout from '@/sakai/layout/AppLayout.vue';
// PrimeVue Components
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import DataTable from 'primevue/datatable';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import SelectButton from 'primevue/selectbutton';
import Calendar from 'primevue/calendar';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Tooltip from 'primevue/tooltip';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import OverlayPanel from 'primevue/overlaypanel';
import { useSpareParts } from '@/composables/useSpareParts';
// --- COMPOSABLES ---


// --- PROPS ---
const props = defineProps({
    activities: Object,
    spareParts: Array,
    users: Array,
    equipments: Array,
    teams: Array,
    tasks: Array,
    regions: Array,
    zones: Array,
    filters: Object,
});

const { t, tm, rt } = useI18n();
const toast = useToast();

// --- GESTION DES MODALES ET DE L'ACTION ---
const activityDialogVisible = ref(false);
const dt = ref();
const op = ref();
const isCreatingSubActivity = ref(false);
const expandedRows = ref([]);

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

// --- FORMULAIRE INERTIA ---
const form = useForm({
    id: null,
    title: '', // Champ ajouté pour le titre de l'activité
    problem_resolution_description: '',
    proposals: '',
    instructions: [], // Sera un tableau d'objets
    additional_information: '',
    status: '',
    actual_start_time: null,
    actual_end_time: null,
    jobber: '',
    spare_parts_used: [],
    spare_parts_returned: [],
    user_id: null,
    service_order_cost: 0,
    service_order_description: t('myActivities.defaults.sparePartPayment'),
    instruction_answers: {},
    parent_id: null,
    assignable_type: null,
    assignable_id: null,
    region_id: null,
    zone_id: null,
    task_id: null,
    maintenance_id: null,
    equipment_ids: [], // Champ pour les équipements associés
    stock_movements: [], // Nouveau système de gestion de stock
});

// --- GESTION RÉACTIVE DU STOCK LOCAL ---
// On crée une copie locale et réactive des pièces détachées.
// C'est cette copie que nous allons manipuler pour simuler les mises à jour en temps réel.
const localSpareParts = ref([]);

// On initialise notre copie locale avec les données reçues du serveur.
onMounted(() => {
    localSpareParts.value = JSON.parse(JSON.stringify(props.spareParts || []));
});

// Si jamais les props changent (ex: après une sauvegarde et rechargement), on met à jour notre copie.
watch(() => props.spareParts, (newParts) => {
    localSpareParts.value = JSON.parse(JSON.stringify(newParts || []));
}, { deep: true });

// --- COMPOSABLE SPARE PARTS ---
// On passe maintenant notre état local `localSpareParts` au composable.
const {
    sparePartDialogVisible,
    sparePartData,
    serviceOrderCost,
    getSparePartReference,
    openSparePartDialog,
    saveSparePart, // Cette fonction va être modifiée pour mettre à jour `localSpareParts`
    removeSparePart, // Idem pour celle-ci
} = useSpareParts(form, localSpareParts); // On utilise la copie locale réactive

const sparePartOptions = computed(() => {
    // La liste déroulante se base maintenant sur notre copie locale `localSpareParts`.
    const regionId = form.region_id;
    if (!regionId) return [];

    // Formater pour le dropdown en ajoutant la quantité en stock
    return (localSpareParts.value || [])
        .map(part => {
            const stockInRegion = part.stocks_by_region?.[regionId] || 0;
            return {
                label: `${part.reference} (${t('sparePartMovements.stockLabel')}: ${stockInRegion})`,
                value: part.id, // On utilise l'ID de référence
            };
        })
        .filter(part => part.label.includes(`${t('sparePartMovements.stockLabel')}:`) && !part.label.includes(`${t('sparePartMovements.stockLabel')}: 0`)); // Optionnel: ne montrer que les pièces en stock
});


// --- FILTRES & COLONNES DATATABLE ---
const lazyParams = ref({
    first: props.activities.from - 1,
    rows: props.activities.per_page,
    sortField: 'created_at',
    sortOrder: -1,
    filters: {
        'global': { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
        'status': { value: props.filters?.status || null, matchMode: FilterMatchMode.EQUALS },
        'team_id': { value: props.filters?.team_id || null, matchMode: FilterMatchMode.EQUALS },
        'title': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    }
});

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData();
};

const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData();
};


const resetFilters = () => {
    filters.value = {
        'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'status': { value: null, matchMode: FilterMatchMode.EQUALS },
        'team_id': { value: null, matchMode: FilterMatchMode.EQUALS },
        'title': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
    applyFilters();
};

const loadLazyData = () => {
    router.get(route('activities.index'), {
        page: (lazyParams.value.first / lazyParams.value.rows) + 1,
        rows: lazyParams.value.rows,
        sortField: lazyParams.value.sortField,
        sortOrder: lazyParams.value.sortOrder,
        search: lazyParams.value.filters.global.value,
        status: lazyParams.value.filters.status.value,
        team_id: lazyParams.value.filters.team_id.value,
    }, { preserveState: true, replace: true });
};

watch(() => lazyParams.value.filters, () => {
    loadLazyData();
});

const allColumns = computed(() => [
    { field: 'title', header: t('myActivities.table.task') },
    { field: 'status', header: t('myActivities.table.status') },
    { field: 'actual_start_time', header: t('myActivities.table.start_time') },
    { field: 'actual_end_time', header: t('myActivities.table.end_time') },
    { field: 'jobber', header: t('myActivities.table.technician') },
    { field: 'region.designation', header: t('myActivities.table.region') },
    { field: 'zone.title', header: t('myActivities.table.zone') },
]);
const visibleColumns = ref(['title', 'status', 'actual_start_time', 'jobber', 'region.designation']);

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

// --- LOGIQUE D'AFFICHAGE ET STYLE ---

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
    return (props.activities.data || []).map(activity => {
        // 1. Parsing des champs JSON
        const sparePartsUsed = parseJson(activity.spare_parts_used);
        const sparePartsReturned = parseJson(activity.spare_parts_returned);
        const instructionAnswers = activity.instruction_answers || [];

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

// --- NOUVELLE LOGIQUE POUR LA VUE HIÉRARCHIQUE ---
const parentActivities = computed(() => {
    return currentActivities.value.filter(activity => !activity.parent_id);
});

const getSubActivities = (parentId) => {
    return currentActivities.value.filter(activity => activity.parent_id === parentId);
};

// --- STATISTIQUES ---
const stats = computed(() => {
    const data = currentActivities.value;
    return {
        total: props.activities.total,
        in_progress: data.filter(a => a.status === 'in_progress').length,
        completed: data.filter(a => a.status === 'completed').length,
        scheduled: data.filter(a => a.status === 'scheduled').length,
    };
});


// --- LOGIQUE DES ACTIONS ---

const openNew = () => {
    isCreatingSubActivity.value = false;
    form.reset();
    form.status = 'scheduled';
    form.actual_start_time = new Date();
    activityDialogVisible.value = true;
};

const hideDialog = () => {
    activityDialogVisible.value = false;
    form.reset();
    isCreatingSubActivity.value = false;
    form.parent_id = null;
};

// Fonction pour l'édition d'une activité existante
const editActivity = (activity) => {
    isCreatingSubActivity.value = false;

    // Remplir le formulaire avec les données existantes
    form.id = activity.id;
    form.title = activity?.title || activity.maintenance?.title || t('myActivities.common.unnamedActivity');
    form.problem_resolution_description = activity.problem_resolution_description || '';
    form.proposals = activity.proposals || '';

    const rawActivity = props.activities.data.find(a => a.id === activity.id);
    form.assignable_type = activity.assignable_type;
    form.assignable_id = activity.assignable_id;

    // Charger les instructions de l'activité (ou de la tâche si aucune n'existe)
    form.instructions = rawActivity.activity_instructions?.length > 0
        ? rawActivity.activity_instructions
        : (rawActivity.task?.instructions || []);

    // Initialiser les réponses aux instructions
    form.instruction_answers = {};

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
    form.region_id = activity.region_id;
    form.zone_id = activity.zone_id;
    form.maintenance_id = activity.maintenance_id;
    form.service_order_cost = activity.service_order_cost || 0;
    form.equipment_ids = activity.equipment?.map(e => e.id) || []; // Charger les IDs des équipements associés
    form.service_order_description = activity.service_order_description || t('myActivities.defaults.sparePartPayment');

    // Ancien système de stock (à migrer si nécessaire ou à ignorer si on repart de zéro)
    form.spare_parts_used = [];
    form.spare_parts_returned = [];

    const answers = {};
    // Utiliser les réponses déjà parsées
    const instructionAnswers = parseJson(rawActivity?.instruction_answers || activity.instruction_answers);
    const activityInstructions = activity.task?.instructions || rawActivity.activity_instructions || [];

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
    form.reset(); // 1. Réinitialiser tout

    // Champs de contexte hérités
    form.title = t('myActivities.subActivityFor', { title: parentActivity?.title || parentActivity.maintenance?.title || t('myActivities.unnamedActivity') });

    form.parent_id = parentActivity.id;
    form.assignable_type = parentActivity.assignable_type;
    form.assignable_id = parentActivity.assignable_id;
    form.task_id = parentActivity.task_id;
    form.maintenance_id = parentActivity.maintenance_id;
    form.region_id = parentActivity.region_id; // La région est héritée
    form.zone_id = null; // La zone est à choisir

    // Champs de contexte copiés
    form.jobber = parentActivity.jobber || null;
    form.user_id = parentActivity.user_id || null;
    form.service_order_description = parentActivity.service_order_description || t('myActivities.defaults.sparePartPayment'); // Default value

    // Instructions héritées de la tâche parente
    form.instructions = parentActivity.task?.instructions || parseJson(parentActivity.instructions) || [];

    // Le stock n'est pas hérité, une sous-activité a ses propres mouvements
    form.stock_movements = [];

    form.equipment_ids = parentActivity.equipment?.map(e => e.id) || []; // Hériter aussi des équipements
    // Reste du formulaire (valeurs propres à la nouvelle sous-activité)
    form.status = 'scheduled'; // Default status for new activities
    form.problem_resolution_description = ''; // La description est maintenant dans le titre
    form.proposals = '';
    form.additional_information = ''; // Empty for new sub-activity
    form.service_order_cost = 0;
    form.actual_start_time = null;
    form.actual_start_time = new Date(); // Commence maintenant par défaut
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

    activityDialogVisible.value = true;
};

// Fonction unifiée pour Créer ou Sauvegarder
const saveActivity = () => {
    // Étape 1: Préparation des données finales
    form.service_order_cost = serviceOrderCost.value;

    // Convertir les objets Date en ISO string formaté pour MySQL
    const dataToSend = {
        ...form.data(),
        actual_start_time: form.actual_start_time ? new Date(form.actual_start_time).toISOString().slice(0, 19).replace('T', ' ') : null,
        actual_end_time: form.actual_end_time ? new Date(form.actual_end_time).toISOString().slice(0, 19).replace('T', ' ') : null,
    };

    // Déterminer la route et la méthode
    const method = form.id ? 'put' : 'post';
    const routeName = !form.id ? 'activities.store' : 'activities.update';
    const routeParams = isCreatingSubActivity.value ? {} : form.id;
    const successMessage = isCreatingSubActivity.value ? t('myActivities.toast.subActivityCreated') : t('myActivities.toast.activityUpdated');

    const handler = {
        onSuccess: () => {
            hideDialog();
            toast.add({ severity: 'success', summary: t('myActivities.toast.success'), detail: successMessage, life: 3000 });
        },
        onError: (errors) => {
            console.error(errors);

            toast.add({ severity: 'error', summary: t('myActivities.toast.error'), detail: t('myActivities.toast.saveError'), life: 3000 });
        }
    };

    // Étape 2: Soumission via Inertia
    if (method === 'post') {
        form.transform(() => dataToSend).post(route(routeName), handler);
    } else {
        form.transform(() => dataToSend).put(route(routeName, routeParams), handler);
    }
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
    form.instructions.push({ ...newInstruction.value, id: tempId });

    // Réinitialiser le petit formulaire d'ajout
    newInstruction.value = { label: '', type: 'text', is_required: false };

    toast.add({ severity: 'success', summary: t('myActivities.toast.added'), detail: t('myActivities.toast.instructionAdded'), life: 2000 });
};
// --- CONFIGURATION DES INSTRUCTIONS DYNAMIQUES ---




const getAvailableInstructions = computed(() => {
    return form.instructions || [];
});

// --- GESTION DES PIÈCES RETOURNÉES ---

const removeInstruction = (index, instructionId) => {
    // 1. Supprimer l'élément à l'index donné
    form.instructions.splice(index, 1);

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

// Formateurs de date pour l'ergonomie
const formatDateTime = (date) => date ? new Date(date).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : '-';
</script>
<script>
const exportCSV = () => {
    dt.value.exportCSV();
};
</script>

<template>
    <AppLayout :title="t('myActivities.title')">
        <Head :title="t('myActivities.title')" />
        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-briefcase text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                            {{ t('myActivities.title') }}
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">{{ t('myActivities.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button :label="t('myActivities.addNew')" icon="pi pi-plus" class="shadow-lg shadow-primary-200" @click="openNew" />
                </div>
            </div>

            <!-- Section des statistiques -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-briefcase text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('myActivities.stats.total') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center"><i class="pi pi-spin pi-spinner text-2xl text-sky-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.in_progress }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('myActivities.stats.in_progress') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center"><i class="pi pi-check-circle text-2xl text-green-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.completed }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('myActivities.stats.completed') }}</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 flex items-center justify-center"><i class="pi pi-calendar text-2xl text-amber-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.scheduled }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('myActivities.stats.scheduled') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable ref="dt" :value="parentActivities" dataKey="id" v-model:expandedRows="expandedRows"
                    lazy paginator :totalRecords="activities.total"
                    :rows="activities.per_page"
                    @page="onPage($event)" @sort="onSort($event)"
                    v-model:filters="lazyParams.filters" filterDisplay="menu"
                    :globalFilterFields="['title', 'jobber', 'status']"
                    class="p-datatable-sm quantum-table" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" responsiveLayout="scroll"
                    :currentPageReportTemplate="t('myActivities.table.report')">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="lazyParams.filters['global'].value" :placeholder="t('myActivities.table.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <Dropdown v-model="lazyParams.filters['team_id'].value"
                                :options="props.teams" optionLabel="name" optionValue="id"
                                :placeholder="t('myActivities.table.filterByTeam')"
                                showClear class="w-full md:w-60 !rounded-2xl !border-slate-200 !bg-slate-50/50 focus:!bg-white"
                            />
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="resetFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" v-tooltip.bottom="t('common.exportCSV')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="t('common.selectColumns')" />
                            </div>
                        </div>
                    </template>

                    <Column :expander="true" headerStyle="width: 3rem" />

                    <Column v-for="col in allColumns.filter(c => visibleColumns.includes(c.field))" :key="col.field" :field="col.field" :header="col.header" sortable :filterField="col.field">
                        <template #body="{ data, field }">
                            <span v-if="field === 'title'" class="font-bold text-slate-800 tracking-tight">
                                {{ data?.title ||  t('myActivities.common.unnamedActivity') }}
                            </span>
                            <Tag v-else-if="field === 'status'" :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" class="uppercase text-[9px] px-2" />
                            <span v-else-if="field.includes('time')" class="text-slate-600 text-sm font-mono">{{ formatDateTime(data[field]) }}</span>
                            <!-- Logique pour la colonne Technicien/Jobber -->
                            <div v-else-if="field === 'jobber'" class="flex items-center gap-2">
                                <i v-if="data.assignable_type === 'App\\Models\\Team'" class="pi pi-users text-slate-500"></i>
                                <i v-else-if="data.assignable_type === 'App\\Models\\User'" class="pi pi-user text-slate-500"></i>
                                <span class="text-slate-700 text-sm">{{ data.assignable?.name || data.jobber || 'N/A' }}</span>
                            </div> <!-- N/A est une abréviation internationale, pas besoin de traduction -->
                            <span v-else-if="field === 'region.designation'" class="text-slate-600 text-sm">{{ data.region?.designation }}</span>
                            <span v-else-if="field === 'zone.title'" class="text-slate-600 text-sm">{{ data.zone?.title }}</span>
                            <span v-else>{{ data[field] }}</span>
                        </template>

                        <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'status'">
                            <Dropdown v-model="lazyParams.filters['status'].value" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('myActivities.table.filterByStatus')" class="p-column-filter" showClear />
                        </template>
                        <template #filter="{ filterModel, filterCallback }" v-if="col.field === 'title'">
                            <InputText v-model="filterModel.constraints[0].value" type="text" @input="filterCallback()" class="p-column-filter" :placeholder="t('myActivities.table.filterByTask')" />
                        </template>
                    </Column>

                    <Column headerStyle="width: 5rem; text-align: right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-plus" class="p-button-rounded p-button-text p-button-secondary" @click="createSubActivity(data)" v-tooltip.left="t('myActivities.common.createSubActivity')" v-if="!data.parent_id" />
                                <Button icon="pi pi-arrow-right" class="p-button-rounded p-button-text p-button-secondary" @click="editActivity(data)" v-tooltip.left="t('myActivities.common.completeActivityTooltip')" />
                            </div>
                        </template>
                    </Column>

                    <template #expansion="{ data }">
                        <div class="bg-slate-100/50 px-12 py-4 border-t border-b border-slate-200" v-if="getSubActivities(data.id).length > 0">
                            <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">{{ t('myActivities.common.associatedSubActivities') }}</h4>
                            <div class="space-y-2">
                                <div v-for="subActivity in getSubActivities(data.id)" :key="subActivity.id"
                                     class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200 shadow-sm hover:border-primary-200 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <Tag :value="getStatusLabel(subActivity.status)" :severity="getStatusSeverity(subActivity.status)" class="uppercase text-[9px] px-2" />
                                        <div class="flex flex-col">
                                            <span class="font-bold text-sm text-slate-700">{{ subActivity.task?.title || subActivity.maintenance?.title }}</span>
                                            <span class="text-xs text-slate-500">{{ subActivity.assignable?.name || subActivity.jobber }}</span>
                                        </div>
                                    </div>
                                    <Button icon="pi pi-arrow-right" class="p-button-rounded p-button-text p-button-secondary" @click="editActivity(subActivity)" v-tooltip.left="t('myActivities.common.completeActivityTooltip')" />
                                </div>
                            </div>
                        </div>
                    </template>

                </DataTable>
            </div>

            <OverlayPanel ref="op" class="p-4">
                <h4 class="text-sm font-black text-slate-800 mb-4">{{ t('common.customize_columns') }}</h4>
                <MultiSelect v-model="visibleColumns" :options="allColumns" optionLabel="header" optionValue="field"
                    :placeholder="t('common.selectColumns')" display="chip" class="w-full max-w-xs" />
            </OverlayPanel>

<Dialog
    v-model:visible="activityDialogVisible"
    modal :closable="false"
    class="quantum-dialog w-full max-w-7xl"
    :pt="{
        mask: { style: 'backdrop-filter: blur(4px); background: rgba(15, 23, 42, 0.4)' },
        content: { class: 'rounded-xl border-none shadow-2xl bg-white overflow-hidden' }
    }"
>
    <template #header>
        <div class="w-full flex justify-between items-center bg-slate-900 px-6 py-4 rounded-xl">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i :class="[isCreatingSubActivity ? 'pi pi-plus' : 'pi pi-shield', 'text-white text-lg']"></i>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-white leading-none">
                        {{ isCreatingSubActivity ? t('myActivities.dialog.subActivityInitialization') : t('myActivities.dialog.technicalInterventionReport') }}
                    </h2>
                    <span class="text-[10px] text-slate-400 font-medium mt-1 block uppercase tracking-tighter italic">{{ t('myActivities.dialog.gmaoAdminConsole') }}</span>
                </div>
            </div>
            <Button icon="pi pi-times" text severity="secondary" @click="hideDialog" class="!text-white opacity-50 hover:opacity-100 transition-transform hover:rotate-90" />
        </div>
    </template>

    <div class="grid grid-cols-12 gap-0 border-b border-slate-100">

        <div class="col-span-12 lg:col-span-3 p-6 bg-slate-50/50 border-r border-slate-100 space-y-6">
            <div class="space-y-4">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">{{ t('myActivities.dialog.missionSettings') }}</h3>

                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-bold text-slate-600 ml-1 uppercase tracking-tighter">{{ t('myActivities.dialog.assignationType') }}</label>
                        <SelectButton
                            v-model="form.assignable_type"
                            :options="[{label: t('myActivities.dialog.assignationTypes.user'), value: 'App\\Models\\User'}, {label: t('myActivities.dialog.assignationTypes.team'), value: 'App\\Models\\Team'}]"
                            optionLabel="label" optionValue="value"
                            class="quantum-sb-small"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5" v-if="form.assignable_type === 'App\\Models\\User'">
                        <label class="text-[10px] font-bold text-slate-600 ml-1 italic">
                            {{ t('myActivities.dialog.responsibleTechnician') }}
                        </label>
                        <Dropdown v-model="form.assignable_id" :options="props.users" optionLabel="name" optionValue="id" filter class="w-full !rounded-xl !border-slate-200 shadow-sm" />
                    </div>

                    <div class="flex flex-col gap-1.5" v-if="form.assignable_type === 'App\\Models\\Team'">
                        <label class="text-[10px] font-bold text-slate-600 ml-1 italic">
                            {{ t('myActivities.dialog.responsibleTeam') }}
                        </label>
                        <Dropdown v-model="form.assignable_id" :options="props.teams" optionLabel="name" optionValue="id" filter class="w-full !rounded-xl !border-slate-200 shadow-sm" />
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2">
                        <label class="text-[10px] font-bold text-slate-600 ml-1 italic">{{ t('myActivities.dialog.currentStatus') }}</label>
                        <Dropdown v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full !rounded-xl !border-slate-200 shadow-sm" />
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2">
                        <label class="text-[10px] font-bold text-slate-600 ml-1 italic">{{ t('myActivities.dialog.region') }}</label>
                        <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter
                                  :disabled="isCreatingSubActivity || form.parent_id"
                                  class="w-full !rounded-xl !border-slate-200 shadow-sm" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-600 ml-1 italic">{{ t('myActivities.dialog.zone') }}</label>
                        <Dropdown v-model="form.zone_id" :options="props.zones" optionLabel="title" optionValue="id" filter
                                  :disabled="!form.region_id"
                                  class="w-full !rounded-xl !border-slate-200 shadow-sm" />
                    </div>
                </div>
            </div>

            <div class="space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('myActivities.dialog.timestamps') }}</h3>
                <div class="grid gap-3">
                    <div class="flex flex-col gap-1">
                        <span class="text-[9px] font-bold text-blue-600 uppercase">{{ t('myActivities.dialog.start') }}</span>
                        <Calendar v-model="form.actual_start_time" showTime hourFormat="24" class="quantum-calendar-simple" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[9px] font-bold text-orange-600 uppercase">{{ t('myActivities.dialog.end') }}</span>
                        <Calendar v-model="form.actual_end_time" showTime hourFormat="24" class="quantum-calendar-simple" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-5 p-6 space-y-6 max-h-[72vh] overflow-y-auto custom-scrollbar bg-white">
            <div class="space-y-2">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="pi pi-tag text-blue-500"></i> {{ t('myActivities.dialog.activityTitle') }}
                </h3>
                <InputText v-model="form.title" class="w-full !p-3 !rounded-xl !bg-slate-50 !border-slate-200 !text-sm focus:!bg-white focus:!ring-1 focus:!ring-blue-200 shadow-inner" :placeholder="t('myActivities.dialog.activityTitle')" />
            </div>

            <div class="space-y-2">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="pi pi-align-left text-blue-500"></i> {{ t('myActivities.dialog.resolutionTitle') }}
                </h3>
                <Textarea v-model="form.problem_resolution_description" rows="3"
                    class="w-full !p-3 !rounded-xl !bg-slate-50 !border-slate-200 !text-sm focus:!bg-white focus:!ring-1 focus:!ring-blue-200 shadow-inner"
                    :placeholder="t('myActivities.dialog.resolutionPlaceholder')" autoResize />
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-[9px] font-black text-slate-600 uppercase tracking-wider ml-1">
                    <i class="pi pi-lightbulb text-amber-500"></i> {{ t('myActivities.dialog.recommendationsLabel') }}
                </label>
                <Textarea v-model="form.proposals" rows="2"
                    class="w-full !p-3 !text-xs !rounded-xl !bg-white !border-slate-200 focus:!border-blue-400 shadow-sm"
                    :placeholder="t('myActivities.dialog.clientRecommendationsPlaceholder')" />
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-dashed border-slate-300 space-y-2">
                <label class="flex items-center justify-between text-[9px] font-black text-slate-500 uppercase tracking-wider">
                    <span class="flex items-center gap-2 italic"><i class="pi pi-lock text-[10px]"></i> {{ t('myActivities.dialog.internalNotes') }}</span>
                    <Tag :value="t('myActivities.dialog.adminOnly')" severity="secondary" class="!text-[7px] !px-1.5 !rounded-md" />
                </label>
                <Textarea v-model="form.additional_information" rows="2"
                    class="w-full !p-2 !text-xs !rounded-lg !bg-white !border-none shadow-inner focus:!ring-1 focus:!ring-slate-300"
                    :placeholder="t('myActivities.dialog.internalNotesPlaceholder')" />
            </div>

            <div class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex justify-between items-center bg-slate-100/50 p-2 rounded-xl border border-slate-200/60">
                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest px-2 italic">{{ t('myActivities.dialog.checklistTitle') }}</h3>
                    <div class="flex gap-2">
                        <InputText v-model="newInstruction.label" :placeholder="t('myActivities.dialog.addInstructionPlaceholder')" class="!border-none !bg-white !text-[10px] !w-32 !h-7 shadow-sm !rounded-md" />
                        <Dropdown v-model="newInstruction.type" :options="instructionTypes" optionLabel="label" optionValue="value" class="!border-none !bg-white !rounded-md !text-[9px] !h-7 shadow-sm" />
                        <Button icon="pi pi-plus" severity="info" size="small" @click="addNewInstructionToForm" class="!h-7 !w-7 !rounded-md" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2">
                    <div v-for="(instruction, index) in getAvailableInstructions" :key="instruction.id"
                         class="flex items-center gap-4 p-3 bg-white border border-slate-100 rounded-xl group transition-all hover:border-blue-200">
                        <div class="flex-grow flex items-center justify-between gap-4">
                            <span class="text-[10px] font-bold text-slate-600 truncate">{{ instruction.label }}</span>
                            <div class="flex justify-end min-w-[120px]">
                                <SelectButton v-if="instruction.type === 'boolean'" v-model="form.instruction_answers[instruction.id]"
                                             :options="[{label:'OUI', value:'1'}, {label:'NON', value:'0'}]" optionLabel="label" optionValue="value" class="quantum-sb-mini" />
                                <InputText v-else v-model="form.instruction_answers[instruction.id]" class="w-full !p-1.5 !bg-slate-50 !border-none !text-xs !rounded-lg" />
                            </div>
                        </div>
                        <Button icon="pi pi-trash" text severity="danger" size="small" @click="removeInstruction(index, instruction.id)" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 p-6 bg-slate-50/50 space-y-6">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i class="pi pi-box text-blue-500"></i> {{ t('myActivities.dialog.usedStock') }}
                    </span>
                    <Button icon="pi pi-plus" rounded severity="secondary" size="small" @click="openSparePartDialog('used')" class="!h-7 !w-7" />
                </div>
                <div class="space-y-1.5 max-h-32 overflow-y-auto custom-scrollbar">
                    <div v-for="(part, idx) in form.spare_parts_used" :key="idx" class="flex items-center gap-2 p-2 bg-blue-50/50 rounded-lg text-[10px] border border-blue-100 group">
                        <b class="text-blue-700">x{{ part.quantity }}</b>
                        <span class="flex-grow truncate text-slate-600 font-medium">{{ getSparePartReference(part.id) }}</span>
                        <i class="pi pi-trash text-red-400 cursor-pointer opacity-0 group-hover:opacity-100" @click="removeSparePart('used', idx)"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i class="pi pi-refresh text-emerald-500"></i> {{ t('myActivities.dialog.returnedStock') }}
                    </span>
                    <Button icon="pi pi-plus" rounded severity="secondary" size="small" @click="openSparePartDialog('returned')" class="!h-7 !w-7"/>
                </div>
                <div class="space-y-1.5 max-h-32 overflow-y-auto custom-scrollbar">
                    <div v-for="(part, idx) in form.spare_parts_returned" :key="idx" class="flex items-center gap-2 p-2 bg-emerald-50/50 rounded-lg text-[10px] border border-emerald-100 group">
                        <b class="text-emerald-700">x{{ part.quantity }}</b>
                        <span class="flex-grow truncate text-slate-600 font-medium">{{ getSparePartReference(part.id) }}</span>
                        <i class="pi pi-trash text-red-400 cursor-pointer opacity-0 group-hover:opacity-100" @click="removeSparePart('returned', idx)"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i class="pi pi-cog text-purple-500"></i> {{ t('myActivities.dialog.associatedEquipments') }}
                    </span>
                </div>
                <div class="space-y-1.5">
                    <MultiSelect v-model="form.equipment_ids" :options="props.equipments" optionLabel="designation" optionValue="id"
                                 filter display="chip" :placeholder="t('myActivities.dialog.selectEquipmentsPlaceholder')"
                                 class="w-full !rounded-xl"
                    />
                </div>
            </div>

            <div class="p-5 bg-slate-900 rounded-xl text-white shadow-lg border border-white/5 relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-500/10 rounded-full blur-xl"></div>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-2 italic">{{ t('myActivities.dialog.htValuation') }}</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-blue-400 tabular-nums">{{ serviceOrderCost.toLocaleString() }}</span>
                    <span class="text-[10px] font-bold opacity-40 uppercase ml-1">USD</span>
                </div>
                <InputText v-model="form.service_order_description" :placeholder="t('myActivities.dialog.billingNotesPlaceholder')"
                    class="w-full !mt-4 !bg-white/10 !border-none !text-[11px] !text-white !rounded-lg focus:!ring-1 focus:!ring-blue-500/50" />
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-between items-center w-full px-6 py-4 bg-white border-t border-slate-50">
            <Button :label="t('myActivities.dialog.cancelButton')" text severity="secondary" @click="hideDialog" class="!text-[10px] !font-black !uppercase !tracking-widest" />
            <Button :label="isCreatingSubActivity ? t('myActivities.dialog.createSubActivityButton') : t('myActivities.dialog.publishReportButton')"
                    icon="pi pi-check-circle" severity="info" @click="saveActivity" :loading="form.processing"
                    class="!px-10 !h-12 !rounded-xl !font-black !uppercase !text-xs shadow-md transition-all active:scale-95 shadow-blue-500/20" />
        </div>
    </template>
</Dialog>
<Dialog v-model:visible="sparePartDialogVisible" modal :closable="false"
        class="quantum-dialog w-full max-w-lg shadow-2xl"
        :pt="{ mask: { style: 'backdrop-filter: blur(8px)' }, content: { class: 'p-0 rounded-2xl overflow-hidden' } }">

    <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center">
                <i class="pi pi-box text-blue-400 text-lg"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800">
                    {{ sparePartData.type === 'used' ? t('myActivities.dialog.sparePartModalUsedTitle') : t('myActivities.dialog.sparePartModalReturnedTitle') }}
                </h2>
                <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">
                    {{ form.title || 'Nouvelle activité' }}
                </p>
            </div>
        </div>
        <Button icon="pi pi-times" text rounded severity="secondary" @click="sparePartDialogVisible = false" />
    </div>

    <div class="p-6 space-y-6 bg-white">
            <div class="field">
                <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">{{ t('myActivities.dialog.referencesLabel') }}</label>
                <MultiSelect v-if="sparePartData.index === -1" v-model="sparePartData.ids" :options="sparePartOptions" optionLabel="label" optionValue="value" filter display="chip" :placeholder="t('myActivities.dialog.searchPartPlaceholder')" class="w-full !rounded-xl" />
                <Dropdown v-else v-model="sparePartData.ids[0]" :options="sparePartOptions" optionLabel="label" optionValue="value" filter class="w-full !rounded-xl" />
            </div>

            <div class="field">
                <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">{{ t('myActivities.dialog.unitQuantityLabel') }}</label>
                <InputNumber v-model="sparePartData.quantity" showButtons :min="1" buttonLayout="horizontal" class="w-full" />
            </div>
        </div>

    <div class="p-4 bg-slate-100/60 flex justify-end gap-3 border-t border-slate-200">
        <Button :label="t('myActivities.dialog.closeButton')" severity="secondary" @click="sparePartDialogVisible = false" class="font-bold text-xs" />
        <Button :label="t('myActivities.dialog.validateButton')" severity="info" @click="saveSparePart" class="font-bold text-xs rounded-lg shadow-lg shadow-blue-100" />
    </div>
</Dialog>
        </div>
    </AppLayout>
</template>
<style scoped>
.quantum-calendar-dark :deep(.p-inputtext) {
    background: rgba(255, 255, 255, 0.1) !important;
    border: none;
    color: white;
    font-size: 0.8rem;
    padding: 0.5rem;
}
.p-button-indigo {
    background: #4f46e5;
    border: none;
}
.p-button-indigo:hover {
    background: #4338ca;
}

.v11-select-sm :deep(.p-button) {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
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
:deep(.p-dropdown), :deep(.p-multiselect), :deep(.p-calendar-w-btn .p-inputtext), :deep(.p-inputnumber-input) {
    border-radius: 0.75rem !important;
}

/* Style spécifique SelectButton Intervenant */
:deep(.quantum-sb-small .p-button) {
    padding: 0.35rem 0.6rem !important;
    font-size: 9px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    border-radius: 0.6rem !important;
}

/* Style spécifique SelectButton Checklist */
:deep(.quantum-sb-mini .p-button) {
    padding: 0.25rem 0.4rem !important;
    font-size: 8px !important;
    font-weight: 900 !important;
    border-radius: 0.4rem !important;
}

/* Chips du MultiSelect */
:deep(.p-multiselect-token) {
    background: #eff6ff !important;
    color: #1e40af !important;
    border-radius: 0.5rem !important;
    font-size: 10px !important;
    font-weight: 700 !important;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
