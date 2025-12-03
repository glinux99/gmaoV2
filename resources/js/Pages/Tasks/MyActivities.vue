<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Timeline from 'primevue/timeline';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    activities: Object
});

// Utilise directement les données du contrôleur.
const currentActivities = computed(() => props.activities.data);

// Fonctions de style (inchangées)
const getIconForActivity = (status) => {
    switch (status) {
        case 'Planifiée':
            return 'pi pi-calendar';
        case 'En cours':
            return 'pi pi-spin pi-spinner';
        case 'Terminée':
            return 'pi pi-check-circle';
        case 'Annulée':
            return 'pi pi-times-circle';
        case 'En retard':
            return 'pi pi-exclamation-triangle';
        default:
            return 'pi pi-info-circle';
    }
};

const getColorForActivity = (status) => {
    switch (status) {
        case 'Planifiée':
            return '#60A5FA';
        case 'En cours':
            return '#FBBF24';
        case 'Terminée':
            return '#34D399';
        case 'Annulée':
            return '#9CA3AF';
        case 'En retard':
            return '#F87171';
        default:
            return '#A78BFA';
    }
};

const getStatusSeverity = (status) => {
    const severities = {
        'Planifiée': 'info',
        'En cours': 'warning',
        'Terminée': 'success',
        'Annulée': 'secondary',
        'En retard': 'danger',
        'En attente': 'contrast'
    };
    return severities[status] || null;
};

const editActivity = (activityId) => {
    router.get(route('activities.edit', activityId));
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
                            <i class="pi pi-list mr-2"></i> Chronologie de mes activités
                        </h2>

                    </template>
                    <template #subtitle>
                        Voici un aperçu de l'évolution de vos tâches et interventions.
                    </template>

                    <template #content>
                        <div v-if="currentActivities && currentActivities.length > 0">

                            <Timeline :value="currentActivities" align="left" class="timeline-left ">

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
                                    <div class="p-0 text-sm font-medium text-400 mt-1">
                                        <i class="pi pi-clock mr-1"></i>
                                        {{ new Date(slotProps.item.actual_start_time).toLocaleString('fr-FR') }}
                                    </div>
                                </template>

                                <template #content="slotProps">
                                    <Card class="mt-0 surface-card shadow-2">
                                        <template #title>
                                            <div class="text-xl font-semibold text-700">
                                                {{ slotProps.item.task.title || 'Activité Sans Titre' }}
                                                <span class="text-base text-500 ml-2">#WorkOrderID: {{ slotProps.item.task.id }}</span>
                                            </div>
                                        </template>

                                        <template #subtitle>
                                            <div class="mt-2">
                                                <Tag :value="slotProps.item.status" :severity="getStatusSeverity(slotProps.item.status)" class="text-lg font-bold" />
                                            </div>
                                        </template>

                                        <template #content>
                                            <div class="mt-2 text-600 line-height-3">
                                                <p v-if="slotProps.item.problem_resolution_description">
                                                    <strong>Description du problème et résolution:</strong> {{ slotProps.item.problem_resolution_description }}
                                                </p>
                                                <p v-if="slotProps.item.proposals">
                                                    <strong>Propositions:</strong> {{ slotProps.item.proposals }}
                                                </p>
                                                <p v-if="slotProps.item.instructions">
                                                    <strong>Instructions:</strong> {{ slotProps.item.instructions }}
                                                </p>
                                                <p v-if="slotProps.item.additional_information">
                                                    <strong>Informations additionnelles:</strong> {{ slotProps.item.additional_information }}
                                                </p>
                                                <p v-if="slotProps.item.spare_parts_used">
                                                    <strong>Pièces de rechange utilisées:</strong> {{ slotProps.item.spare_parts_used }}
                                                </p>
                                                <p v-if="slotProps.item.spare_parts_returned">
                                                    <strong>Pièces de rechange retournées:</strong> {{ slotProps.item.spare_parts_returned }}
                                                </p>
                                                <p v-if="slotProps.item.jobber">
                                                    <strong>Jobber:</strong> {{ slotProps.item.jobber }}
                                                </p>
                                            </div>

                                            <div class="flex justify-content-end mt-4">
                                                <Button icon="pi pi-pencil" label="Modifier" class="p-button-text p-button-sm" @click="editActivity(slotProps.item.id)" />
                                            </div>

                                        </template>
                                    </Card>
                                </template>

                            </Timeline>
                        </div>

                        <div v-else class="text-center p-5 surface-50 border-round-md">
                            <i class="pi pi-calendar-times text-5xl text-400 mb-3"></i>
                            <p class="text-xl text-700">Aucune activité à afficher pour le moment.</p>
                            <p class="text-600">Revenez plus tard ou ajoutez une nouvelle tâche.</p>
                        </div>

                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Force la disposition en ligne pour l'alignement à gauche */
.timeline-left .p-timeline-event {
    flex-direction: row;
}

/* ---------------------------------------------------- */
/* CORRECTION POUR 3/4 DE LARGEUR DU CONTENU (CONTENT)  */
/* ---------------------------------------------------- */

/* Cible l'espace pour la date (à gauche du marqueur) */
.timeline-left .p-timeline-event-opposite {
    /* 25% de la largeur pour l'espace de la date (pour laisser 75% au contenu) */
    flex-basis: 25%;
    flex-grow: 0; /* Ne grandit pas */
    flex-shrink: 0; /* Ne rétrécit pas */
    padding: 0 1rem 0 0;
    text-align: right; /* Aligne le texte près de la ligne */
}

/* Cible le contenu de la carte (à droite du marqueur) */
.timeline-left .p-timeline-event-content {
    /* 75% de la largeur pour le contenu */
    flex-basis: 75%;
    flex-grow: 1; /* Permet de prendre l'espace si l'opposite est plus petit */
    flex-shrink: 1;
    padding-left: 1rem;
    padding-right: 0;
}

/* ---------------------------------------------------- */
/* Autres ajustements */
/* ---------------------------------------------------- */

.p-card {
    border: 1px solid var(--surface-border);
}

/* Assure que la toute première Card est bien alignée au début de la div sans marge supérieure */
.p-timeline-event:first-child .p-timeline-event-content .p-card {
    margin-top: 0 !important;
}
</style>
