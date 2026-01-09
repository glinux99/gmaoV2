<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield'; // Ajouté
import InputIcon from 'primevue/inputicon';   // Ajouté
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps({
    logins: Object,
});

const confirm = useConfirm();
const toast = useToast();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Fonctions utilitaires conservées
const getDeviceIcon = (isMobile, isTablet, isDesktop) => {
    if (isMobile) return 'pi pi-mobile';
    if (isTablet) return 'pi pi-tablet';
    if (isDesktop) return 'pi pi-desktop';
    return 'pi pi-globe';
};

const getDeviceType = (isMobile, isTablet, isDesktop) => {
    if (isMobile) return 'Mobile';
    if (isTablet) return 'Tablette';
    if (isDesktop) return 'Ordinateur';
    return 'Inconnu';
};

const formatDate = (value) => {
    return new Date(value).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined) return 'N/A';
    if (seconds < 60) return `${seconds} sec`;
    const minutes = Math.floor(seconds / 60);
    return `${minutes} min`;
};

const logoutSession = (session) => {
    confirm.require({
        message: 'Êtes-vous sûr de vouloir fermer cette session ? L\'utilisateur sera déconnecté.',
        header: 'Confirmer la déconnexion',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Fermer la session',
        rejectLabel: 'Annuler',
        accept: () => {
            router.post(route('sessions.logout', session.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'La session a été fermée.', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de fermer la session.', life: 3000 });
                }
            });
        }
    });
};

const isSessionActive = (session) => {
    return session.login_successful && !session.session_duration;
};
</script>

<template>
    <Toast />
    <ConfirmDialog />
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-end gap-4">
            <div class="text-center md:text-left">
                <h3 class="text-2xl font-black text-slate-800">Historique des connexions</h3>
                <p class="text-slate-500">Voici la liste des 10 dernières connexions à votre compte.</p>
            </div>
        </div>

        <DataTable
            :value="logins.data"
            v-model:filters="filters"
            dataKey="id"
            responsiveLayout="scroll"
            paginator
            :rows="10"
            :globalFilterFields="['ip_address', 'agent.browser', 'agent.platform']"
            class="p-datatable-sm border border-slate-200 rounded-2xl overflow-hidden shadow-sm"
        >
            <template #header>
                <div class="flex justify-end p-2">
                    <IconField iconPosition="left" class="w-full md:w-80">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText
                            v-model="filters['global'].value"
                            placeholder="Rechercher une IP, un navigateur..."
                            class="w-full h-11 rounded-2xl border-slate-200 bg-slate-50/50 focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-all duration-200"
                        />
                    </IconField>
                </div>
            </template>

            <template #empty>
                <div class="p-8 text-center text-slate-500">Aucune connexion trouvée.</div>
            </template>

            <Column field="login_at" header="Date" :sortable="true" style="min-width: 12rem">
                <template #body="{ data }">
                    <span class="font-medium text-slate-700">{{ formatDate(data.login_at) }}</span>
                </template>
            </Column>

            <Column field="session_duration" header="Durée" style="min-width: 10rem">
                <template #body="{ data }">
                    <span v-if="data.session_duration" class="text-slate-600 font-semibold">
                        {{ formatDuration(data.session_duration) }}
                    </span>
                    <Tag v-else-if="data.login_successful" severity="success" value="En cours" class="rounded-lg px-3"></Tag>
                    <span v-else class="text-slate-400">N/A</span>
                </template>
            </Column>

            <Column field="ip_address" header="Adresse IP" style="min-width: 10rem">
                <template #body="{ data }">
                    <code class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs border border-slate-200">
                        {{ data.ip_address }}
                    </code>
                </template>
            </Column>

            <Column header="Appareil" style="min-width: 12rem">
                <template #body="{ data }">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                            <i :class="getDeviceIcon(data.agent.is_mobile, data.agent.is_tablet, data.agent.is_desktop)"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-700">{{ getDeviceType(data.agent.is_mobile, data.agent.is_tablet, data.agent.is_desktop) }}</span>
                            <span class="text-xs text-slate-500">{{ data.agent.platform }}</span>
                        </div>
                    </div>
                </template>
            </Column>

            <Column header="Navigateur" style="min-width: 12rem">
                <template #body="{ data }">
                    <span class="text-sm text-slate-600">
                        {{ data.agent.browser }} <small class="text-slate-400">v{{ data.agent.browser_version }}</small>
                    </span>
                </template>
            </Column>

            <Column field="login_successful" header="Statut" :sortable="true" style="min-width: 8rem">
                <template #body="{ data }">
                    <Tag
                        :severity="data.login_successful ? 'success' : 'danger'"
                        :value="data.login_successful ? 'Réussie' : 'Échouée'"
                        class="rounded-full px-3"
                    />
                </template>
            </Column>

            <Column header="Actions" style="min-width: 8rem; text-align: center;">
                <template #body="{ data }">
                    <Button
                        v-if="isSessionActive(data)"
                        icon="pi pi-power-off"
                        severity="danger"
                        text rounded
                        @click="logoutSession(data)"
                        v-tooltip.top="'Fermer la session'"/>
                </template>
            </Column>
        </DataTable>

        <div class="p-4 bg-amber-50 text-amber-800 rounded-2xl text-sm border border-amber-200 flex gap-3 items-start">
            <i class="pi pi-exclamation-triangle mt-0.5"></i>
            <p><strong>Information :</strong> Si vous remarquez une activité suspecte ou une connexion que vous ne reconnaissez pas, nous vous recommandons de changer immédiatement votre mot de passe dans l'onglet <strong>"Sécurité"</strong>.</p>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 0;
}

:deep(.p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem;
}

:deep(.p-datatable-tbody > tr) {
    transition: background-color 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background-color: #f1f5f9 !important;
}
</style>
