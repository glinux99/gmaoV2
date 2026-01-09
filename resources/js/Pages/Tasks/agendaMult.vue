<script setup>
import { ref, computed, watch } from 'vue';
import { router, Head, useForm } from '@inertiajs/vue3';

// --- Importations FULLCALENDAR ---
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';

// --- Importations PRIME VUE (inchangées) ---
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import TreeSelect from 'primevue/treeselect';
import Toolbar from 'primevue/toolbar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useConfirm } from 'primevue/useconfirm';


// --- PROPRIÉTÉS & ÉTAT LOCAL (inchangées) ---
const props = defineProps({
    events: Array,
    maintenances: Object,
    filters: Object,
    users: Array,
    teams: Object,
    equipmentTree: Object,
    regions: Array,
});

const toast = useToast();
const confirm = useConfirm();

// État local pour le filtre de statut et la recherche
const selectedStatus = ref(props.filters?.status || '');
const search = ref(props.filters?.search || '');

// État du formulaire et du dialogue
const maintenanceDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);

// États pour le dialogue des détails d'événement
const showDialog = ref(false);
const dialogEvent = ref(null);
const possibleStatuses = ref(['Planifiée', 'En cours', 'En attente', 'Terminée', 'Annulée', 'En retard']);

// --- LOGIQUE DU FORMULAIRE (inchangée) ---
const form = useForm({
    id: null,
    title: '',
    description: '',
    assignable_type: null,
    assignable_id: null,
    type: 'Préventive',
    status: 'Planifiée',
    priority: 'Moyenne',
    scheduled_start_date: null,
    scheduled_end_date: null,
    cost: null,
    recurrence_type: null,
    estimated_duration: null,
    related_equipments: {},
    region_id: null,
    recurrence_interval: null,
    recurrence_days: [],
    recurrence_day_of_month: null,
    recurrence_month_interval: null,
    recurrence_month: null,
    reminder_days: null,
    custom_recurrence_config: null,
});

// --- Options et Computed properties (inchangées) ---
const maintenanceTypes = ref(['Préventive', 'Corrective']);
const maintenanceStatuses = ref(['Planifiée', 'En cours', 'En attente', 'Terminée', 'Annulée', 'En retard']);
const maintenancePriorities = ref(['Basse', 'Moyenne', 'Haute', 'Urgente']);
const assignableTypes = ref([
    { label: 'Technicien', value: 'App\\Models\\User' },
    { label: 'Équipe', value: 'App\\Models\\Team' },
]);
const recurrenceTypes = ref([
    { label: 'Aucune', value: null }, { label: 'Quotidienne', value: 'daily' }, { label: 'Hebdomadaire', value: 'weekly' },
    { label: 'Mensuelle', value: 'monthly' }, { label: 'Trimestrielle', value: 'quarterly' }, { label: 'Semestrielle', value: 'biannual' },
    { label: 'Annuelle', value: 'annual' }, { label: 'Personnalisée', value: 'custom' },
]);
const daysOfWeek = ref([
    { label: 'Lundi', value: 'mon' }, { label: 'Mardi', value: 'tue' }, { label: 'Mercredi', value: 'wed' },
    { label: 'Jeudi', value: 'thu' }, { label: 'Vendredi', value: 'fri' }, { label: 'Samedi', value: 'sat' },
    { label: 'Dimanche', value: 'sun' },
]);
const months = ref([
    { label: 'Janvier', value: 1 }, { label: 'Février', value: 2 }, { label: 'Mars', value: 3 },
    { label: 'Avril', value: 4 }, { label: 'Mai', value: 5 }, { label: 'Juin', value: 6 },
    { label: 'Juillet', value: 7 }, { label: 'Août', value: 8 }, { label: 'Septembre', value: 9 },
    { label: 'Octobre', value: 10 }, { label: 'Novembre', value: 11 }, { label: 'Décembre', value: 12 },
]);

const assignables = computed(() => {
    if (form.assignable_type === 'App\\Models\\User') {
        return props.users;
    }
    if (form.assignable_type === 'App\\Models\\Team') {
        return props.teams.data || props.teams;
    }
    return [];
});
const dialogTitle = computed(() => editing.value ? 'Modifier la Maintenance' : 'Créer une nouvelle Maintenance');
const transformedEquipmentTree = computed(() => {
    if (!props.equipmentTree || typeof props.equipmentTree !== 'object') {
        return [];
    }
    const transformNode = (node) => {
        if (!node) return null;
        return {
            key: String(node.id),
            label: node.designation || node.label,
            icon: 'pi pi-fw pi-cog',
            children: node.children ? Object.values(node.children).map(transformNode).filter(n => n) : []
        };
    };
    return Object.values(props.equipmentTree).map(transformNode).filter(n => n);
});

// --- LOGIQUE DU CALENDRIER ET MÉTHODES ---

// Constantes pour les heures de la plage d'affichage
const WORK_START_TIME = '07:00:00';
const WORK_END_TIME = '16:00:00';

const getEventColor = (type) => {
    switch (type) {
        case 'activity': return '#3498db';
        case 'maintenance': return '#e74c3c';
        case 'task': return '#2ecc71';
        default: return '#95a5a6';
    }
};

/**
 * Fonction utilitaire pour extraire la partie Date (YYYY-MM-DD) ou l'Heure (HH:MM:SS) d'une chaîne.
 * @param {string} dateStr - Chaîne de date.
 * @param {string} part - 'date' ou 'time'.
 * @param {string} defaultTime - Heure par défaut si l'heure est manquante.
 */
const extractDateTimePart = (dateStr, part, defaultTime = '00:00:00') => {
    if (!dateStr) return null;

    const normalizedStr = dateStr.replace('T', ' ');
    const parts = normalizedStr.split(' ');

    if (part === 'date') {
        return parts[0];
    } else if (part === 'time') {
        // Garantit que l'on renvoie toujours une heure complète si elle est présente
        const timePart = parts.length > 1 ? parts[1].split('.')[0] : defaultTime;
        // Si l'heure est H:MM:SS, la met en HH:MM:SS (gestion des formats bruts)
        const timeParts = timePart.split(':');
        if (timeParts.length === 3 && timeParts[0].length === 1) {
             return `0${timeParts[0]}:${timeParts[1]}:${timeParts[2]}`;
        }
        return timePart;
    }
    return null;
};

/**
 * Compare deux chaînes d'heure au format HH:MM:SS et retourne la plus tardive.
 * @param {string} time1 - Heure 1 (ex: '15:00:00').
 * @param {string} time2 - Heure 2 (ex: '16:00:00').
 * @returns {string} L'heure la plus tardive.
 */
const compareAndGetLatestTime = (time1, time2) => {
    if (time1 > time2) {
        return time1;
    }
    return time2;
};

/**
 * Génère un tableau de dates (YYYY-MM-DD) entre une date de début et de fin.
 * @param {string} startDateStr - YYYY-MM-DD
 * @param {string} endDateStr - YYYY-MM-DD
 * @returns {Array<string>} Tableau de dates.
 */
const getDatesInRange = (startDateStr, endDateStr) => {
    const dates = [];
    let currentDate = new Date(startDateStr);
    const endDate = new Date(endDateStr);

    while (currentDate <= endDate) {
        dates.push(currentDate.toISOString().slice(0, 10)); // YYYY-MM-DD
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return dates;
};

/**
 * Fonction utilitaire pour vérifier si un événement s'étend sur plusieurs jours.
 */
const isMultiDay = (startStr, endStr) => {
    if (!startStr || !endStr) return false;

    const startDatePart = extractDateTimePart(startStr, 'date');
    const endDatePart = extractDateTimePart(endStr, 'date');

    return startDatePart !== endDatePart;
};


/**
 * Événements filtrés et formatés pour FullCalendar, avec logique de fractionnement.
 */
const calendarEvents = computed(() => {
    const sourceEvents = props.events.length > 0 ? props.events : (props.maintenances?.data || []);
    const segmentedEvents = [];

    sourceEvents
        .filter(event => selectedStatus.value === '' || event.status === selectedStatus.value)
        .forEach(event => {

            const originalStart = event.scheduled_start_date || event.start_date;
            const originalEnd = event.scheduled_end_date || event.end_date;

            if (!originalStart || !originalEnd) {
                console.warn("Événement ignoré car date de début ou de fin manquante:", event);
                return;
            }

            const isMultidateEvent = isMultiDay(originalStart, originalEnd);
            const commonEventProps = {
                extendedProps: event,
                title: event.title || event.label,
                color: getEventColor(event.type),
                allDay: false,
            };

            if (!isMultidateEvent) {
                // CAS 1: Événement monodate
                const allDay = !originalStart.includes(' ') && !originalStart.includes('T');

                segmentedEvents.push({
                    ...commonEventProps,
                    id: event.id,
                    start: originalStart,
                    end: originalEnd,
                    allDay: allDay,
                });
            } else {
                // CAS 2: Événement multidate - Logique de fractionnement

                const startDateOnly = extractDateTimePart(originalStart, 'date');
                const endDateOnly = extractDateTimePart(originalEnd, 'date');
                // Heures réelles, avec 07:00:00 ou 16:00:00 comme défaut si l'heure est manquante
                const startTime = extractDateTimePart(originalStart, 'time', WORK_START_TIME);
                const endTime = extractDateTimePart(originalEnd, 'time', WORK_END_TIME);

                const datesRange = getDatesInRange(startDateOnly, endDateOnly);

                datesRange.forEach((date, index) => {
                    let segmentStart;
                    let segmentEnd;
                    let segmentId = `${event.id}-${date}`;
                    let segmentAllDay = false; // Par défaut, les segments ne sont pas allDay

                    if (index === 0) {
                        // Premier jour: Heure réelle de début -> Heure de fin de la plage de travail (16:00:00)
                        segmentStart = `${date} ${startTime}`;
                        segmentEnd = `${date} ${WORK_END_TIME}`;
                    } else if (index === datesRange.length - 1) {
                        // Dernier jour: Heure de début de la plage de travail (07:00:00) -> Heure réelle de fin
                        segmentStart = `${date} ${WORK_START_TIME}`;
                        segmentEnd = `${date} ${endTime}`;
                    } else {
                        // Jours intermédiaires: 07:00:00 -> 16:00:00
                        segmentStart = `${date} ${WORK_START_TIME}`;
                        segmentEnd = `${date} ${WORK_END_TIME}`;
                    }

                    // Si le segment couvre toute la journée de travail (7h-16h) et que l'heure de fin réelle est après 16h,
                    // ou si l'heure de début réelle est avant 7h, on peut considérer le segment comme "allDay" pour cette journée.
                    // Cependant, pour la visualisation, on veut garder les heures de travail.
                    segmentedEvents.push({
                        ...commonEventProps,
                        id: segmentId,
                        start: segmentStart,
                        end: segmentEnd,
                        realId: event.id, // ID du parent pour le clic/édition
                        allDay: segmentAllDay,
                    });
                });
            }
        });

    return segmentedEvents;
});


/**
 * Fonction utilitaire pour extraire la date/heure au format requis pour le backend (Laravel).
 */
const formatFullCalendarDate = (dateStr) => {
    if (!dateStr) return null;
    return dateStr.split('+')[0].split('.')[0].trim().replace('T', ' ');
};

/**
 * Gère la mise à jour de la date par glisser-déposer (drop) ou redimensionnement (resize).
 */
const handleEventDrop = (info) => {
    const { event: calendarEvent } = info;

    // Si l'événement est un segment généré, on bloque le déplacement/redimensionnement.
    if (calendarEvent.extendedProps.realId) {
        info.revert();
        toast.add({ severity: 'warn', summary: 'Attention',
                    detail: 'Veuillez modifier la date d\'un événement multidate via le formulaire (clic sur l\'événement).', life: 5000 });
        return;
    }

    const newStartDateTime = formatFullCalendarDate(calendarEvent.startStr);
    const newEndDateTime = formatFullCalendarDate(calendarEvent.endStr);

    const eventId = calendarEvent.id;
    const eventType = calendarEvent.extendedProps.type;
    let routeName;
    let routeParams;
    let updateData = {};
    let dateStartKey;
    let dateEndKey;

    if (eventType === 'maintenance') {
        routeName = 'maintenances.update';
        routeParams = { maintenance: eventId };
        dateStartKey = 'scheduled_start_date';
        dateEndKey = 'scheduled_end_date';
    } else if (eventType === 'task') {
        routeName = 'tasks.update';
        routeParams = { task: eventId };
        dateStartKey = 'planned_start_date';
        dateEndKey = 'planned_end_date';
    } else if (eventType === 'activity') {
        routeName = 'activities.update';
        routeParams = { activity: eventId };
        dateStartKey = 'actual_start_time';
        dateEndKey = 'actual_end_time';
    } else {
        info.revert();
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Type d\'événement inconnu pour la mise à jour.', life: 3000 });
        return;
    }

    if (!route().has(routeName)) {
        info.revert();
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Route de mise à jour non trouvée.', life: 3000 });
        return;
    }

    updateData[dateStartKey] = newStartDateTime;
    updateData[dateEndKey] = newEndDateTime;

    toast.add({ severity: 'info', summary: 'Mise à jour', detail: `Déplacement de l'événement #${eventId} (${eventType})...`, life: 3000 });

    router.put(route(routeName, routeParams), updateData, {
        preserveState: true,
        onError: (errors) => {
            info.revert();
            console.error(`Erreur lors de la mise à jour de l'événement (${eventType}):`, errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: `Impossible de mettre à jour l'événement de type ${eventType}.`, life: 5000 });
        },
        onSuccess: () => {
             toast.add({ severity: 'success', summary: 'Succès', detail: `Événement de type ${eventType} mis à jour.`, life: 3000 });
        }
    });
};

/**
 * Gère le clic sur un événement pour afficher les détails.
 * S'assure d'afficher les détails de l'événement PÈRE si on clique sur un segment.
 */
const handleEventClick = (info) => {
    const realEventId = info.event.extendedProps.realId || info.event.id;

    // Retrouver l'événement parent dans la source de données
    const sourceEvents = props.events.length > 0 ? props.events : (props.maintenances?.data || []);
    const parentEvent = sourceEvents.find(e => e.id == realEventId);

    if (!parentEvent) return;

    dialogEvent.value = {
        title: parentEvent.title || parentEvent.label,
        ...parentEvent,
        // On affiche les dates originales (non segmentées) pour les détails
        start: parentEvent.scheduled_start_date || parentEvent.start_date,
        end: parentEvent.scheduled_end_date || parentEvent.end_date,
        color: info.event.backgroundColor,
        daily_schedule: parentEvent.daily_schedule,
    };
    showDialog.value = true;
};

// Configuration du calendrier
const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek,listYear'
    },
    events: calendarEvents.value,
    locale: frLocale,
    editable: true,
    eventDurationEditable: true,
    selectable: true,
    eventDrop: handleEventDrop,
    eventResize: handleEventDrop,
    eventClick: handleEventClick,

    // CONFIGURATION DES HORAIRES D'AFFICHAGE
    slotMinTime: WORK_START_TIME,
    slotMaxTime: WORK_END_TIME,
});

// --- WATCHERS et autres fonctions utilitaires (inchangées) ---

watch(calendarEvents, (newEvents) => {
    if (calendarOptions.value) {
        calendarOptions.value.events = newEvents;
    }
}, { deep: true });

watch(() => form.assignable_type, (newValue) => { form.assignable_id = null; });
watch(() => form.recurrence_type, (newValue) => {
    form.recurrence_interval = null;
    form.recurrence_days = [];
    form.recurrence_day_of_month = null;
    form.recurrence_month_interval = null;
    form.recurrence_month = null;
    form.reminder_days = null;
    form.custom_recurrence_config = null;
});

const performSearch = () => {
    router.get(route('agenda.index'), { search: search.value, status: selectedStatus.value }, {
        preserveState: true,
        replace: true,
    });
};

const hideDialog = () => {
    maintenanceDialog.value = false;
    submitted.value = false;
};

const openNew = () => {
    form.reset();
    editing.value = false;
    maintenanceDialog.value = true;
};

const saveMaintenance = () => {
    submitted.value = true;
    if (!form.title || Object.keys(form.related_equipments).length === 0 || !form.type) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez remplir les champs obligatoires (Titre, Équipement, Type).', life: 3000 });
        return;
    }

    const equipmentIds = form.related_equipments
        ? Object.keys(form.related_equipments)
            .filter(key => form.related_equipments[key] && form.related_equipments[key].checked)
            .map(key => parseInt(key, 10))
        : [];

    const data = {
        ...form.data(),
        equipment_ids: equipmentIds,
        // Formatage pour Laravel: 'YYYY-MM-DD HH:MM:SS'
        scheduled_start_date: form.scheduled_start_date ? new Date(form.scheduled_start_date).toISOString().slice(0, 19).replace('T', ' ') : null,
        scheduled_end_date: form.scheduled_end_date ? new Date(form.scheduled_end_date).toISOString().slice(0, 19).replace('T', ' ') : null,
    };

    if (!form.assignable_type) {
        data.assignable_id = null;
    }

    const routeName = editing.value ? 'maintenances.update' : 'maintenances.store';
    const method = editing.value ? 'put' : 'post';
    const routeParams = editing.value ? { maintenance: form.id } : {};

    router[method](route(routeName, routeParams), data, {
        onSuccess: () => {
            maintenanceDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: `Maintenance ${editing.value ? 'mise à jour' : 'créée'} avec succès.`, life: 3000 });
            form.reset();
            router.reload({ only: ['events', 'maintenances'] });
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la maintenance", errors);
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la sauvegarde.', life: 3000 });
            form.errors = errors;
        }
    });
};

const formatDateTime = (date) => {
    if (!date) return 'N/A';
    const d = date instanceof Date ? date : new Date(date);
    return d.toLocaleString('fr-FR', { dateStyle: 'full', timeStyle: 'short' });
};

const getStatusSeverity = (status) => {
    const severities = { 'Planifiée': 'info', 'En cours': 'warning', 'Terminée': 'success', 'Annulée': 'secondary', 'En retard': 'danger', 'En attente': 'contrast' };
    return severities[status] || null;
};
const getPrioritySeverity = (priority) => {
    const severities = { 'Basse': 'info', 'Moyenne': 'success', 'Haute': 'warning', 'Urgente': 'danger' };
    return severities[priority] || null;
};
</script>

<template>
    <AppLayout title="Gestion des Maintenances">
        <Head title="Agenda de Maintenance" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📅 Agenda de Maintenance</h2>
        </template>

        <div class="grid">
            <div class="col-12">
                <Toast />
                <ConfirmDialog></ConfirmDialog>

                <Toolbar class="mb-4">
                    <template #start>
                        <Button label="Nouvelle Maintenance" icon="pi pi-plus" class="mr-2" @click="openNew" />
                    </template>
                    <template #end>
                        <div class="flex items-center gap-2">
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="search" placeholder="Rechercher..." @input="performSearch" />
                            </IconField>

                            <Dropdown
                                v-model="selectedStatus"
                                :options="possibleStatuses"
                                placeholder="Filtrer par statut"
                                showClear
                                @change="performSearch"
                                class="w-full md:w-auto"
                            />
                        </div>
                    </template>
                </Toolbar>

                <div class="bg-white p-6 rounded-lg shadow-xl w-full">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>
        </div>

        <Dialog
            :header="dialogEvent ? 'Détails: ' + dialogEvent.title : 'Détails de l\'événement'"
            v-model:visible="showDialog"
            modal
            :style="{ width: '400px' }"
        >
            <div v-if="dialogEvent" class="p-fluid">
                <div class="field mb-4">
                    <p class="mb-2"><strong>Type :</strong> {{ dialogEvent.type || 'N/A' }}</p>
                    <p class="mb-2"><strong>Statut :</strong> <Tag :value="dialogEvent.status" :severity="getStatusSeverity(dialogEvent.status)" /></p>
                    <p class="mb-4"><strong>Priorité :</strong> <Tag :value="dialogEvent.priority" :severity="getPrioritySeverity(dialogEvent.priority)" /></p>
                </div>

                <hr class="my-4">

                <div v-if="dialogEvent.daily_schedule" class="field mb-4 p-3 border-round bg-yellow-100 border-1 border-yellow-300">
                    <p class="font-bold text-yellow-800">Règle de charge quotidienne :</p>
                    <p class="text-yellow-800">
                        L'événement est planifié de **{{ dialogEvent.daily_schedule }}** pour **chaque jour** de la période ({{ dialogEvent.daily_schedule.includes(' - ') ? 'soit ' + (
                            (parseInt(dialogEvent.daily_schedule.split(' - ')[1]?.split(':')[0]) || 0) - (parseInt(dialogEvent.daily_schedule.split(' - ')[0]?.split(':')[0]) || 0)
                        ) + ' heures de travail par jour.' : '' }}).
                    </p>
                </div>
                <hr v-if="dialogEvent.daily_schedule" class="my-4">


                <div class="field mb-2">
                    <p>
                        <strong>Début de la période :</strong>
                        {{ formatDateTime(dialogEvent.start) }}
                    </p>
                </div>
                <div class="field mb-4">
                    <p>
                        <strong>Fin de la période :</strong>
                        {{ formatDateTime(dialogEvent.end) }}
                    </p>
                </div>
                <div class="field mb-4">
                    <p>
                        <strong>Durée Totale Estimée:</strong>
                        {{ dialogEvent.estimated_duration ? (dialogEvent.estimated_duration + ' minutes') : 'N/A' }}
                    </p>
                </div>
            </div>

            <template #footer>
                <Button label="Fermer" icon="pi pi-times" @click="showDialog = false" class="p-button-text"/>
            </template>
        </Dialog>

        <Dialog v-model:visible="maintenanceDialog" modal :header="dialogTitle" :style="{ width: '65rem' }">
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
                        <label for="assignable_id" class="font-semibold">Assigner à (Nom)</label>
                        <Dropdown id="assignable_id" class="w-full" v-model="form.assignable_id"
                            :options="assignables" optionLabel="name" optionValue="id"
                            placeholder="Sélectionner une personne/équipe" :disabled="!form.assignable_type"
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
                        <label for="type" class="font-semibold">Type de Maintenance</label>
                        <Dropdown id="type" class="w-full" v-model="form.type" :options="maintenanceTypes"
                            placeholder="Sélectionner un type" />
                        <small class="p-error">{{ form.errors.type }}</small>
                    </div>
                    <div class="field">
                        <label for="status" class="font-semibold">Statut</label>
                        <Dropdown id="status" class="w-full" v-model="form.status"
                            :options="maintenanceStatuses" placeholder="Sélectionner un statut" />
                        <small class="p-error">{{ form.errors.status }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="field">
                        <label for="priority" class="font-semibold">Priorité</label>
                        <Dropdown id="priority" class="w-full" v-model="form.priority"
                            :options="maintenancePriorities" placeholder="Sélectionner une priorité" />
                        <small class="p-error">{{ form.errors.priority }}</small>
                    </div>
                    <div class="field">
                        <label for="estimated_duration" class="font-semibold">Durée Estimée
                            (minutes)</label>
                        <InputNumber id="estimated_duration" class="w-full"
                            v-model="form.estimated_duration" :min="0" />
                        <small class="p-error">{{ form.errors.estimated_duration }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="field">
                        <label for="scheduled_start_date" class="font-semibold">
                            {{ form.status === 'Planifiée' ? 'Date de début planifiée' : 'Date de début' }}</label>
                        <Calendar id="scheduled_start_date" class="w-full"
                            v-model="form.scheduled_start_date" showTime dateFormat="dd/mm/yy" showIcon />
                        <small class="p-error">{{ form.errors.scheduled_start_date }}</small>
                    </div>
                    <div class="field">
                        <label for="scheduled_end_date" class="font-semibold">
                            {{ form.status === 'Planifiée'? 'Date de fin planifiée' : 'Date de fin' }}</label>
                        <Calendar id="scheduled_end_date" class="w-full" v-model="form.scheduled_end_date"
                            showTime dateFormat="dd/mm/yy" showIcon />
                        <small class="p-error">{{ form.errors.scheduled_end_date }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="field">
                        <label for="cost" class="font-semibold">Coût / Budget</label>
                        <InputNumber id="cost" class="w-full" v-model="form.cost" mode="currency"
                            currency="USD" locale="fr-FR" :min="0" />
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
                        <label for="scheduled_start_date_recurrence" class="font-semibold">Date de début de
                            récurrence</label>
                        <Calendar id="scheduled_start_date_recurrence" class="w-full"
                            v-model="form.scheduled_start_date" dateFormat="dd/mm/yy" showIcon />
                        <small class="p-error">{{ form.errors.scheduled_start_date }}</small>
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

                <div v-if="form.recurrence_type === 'custom'" class="field">
                    <label for="custom_recurrence_config" class="font-semibold">Configuration personnalisée
                        de la
                        récurrence</label>
                    <Textarea id="custom_recurrence_config" class="w-full"
                        v-model="form.custom_recurrence_config" rows="3"
                        placeholder="Ex: 'every 2 weeks on Monday and Friday'" />
                </div>
            </div>

            <template #footer>
                <Button label="Annuler" icon="pi pi-times" @click="hideDialog" class="p-button-text" />
                <Button label="Sauvegarder" icon="pi pi-check" @click="saveMaintenance"
                    :loading="form.processing" />
            </template>
        </Dialog>
    </AppLayout>
</template>
