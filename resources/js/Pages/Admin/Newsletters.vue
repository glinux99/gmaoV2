<template>
    <AppLayout>
        <Head title="Newsletters - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-violet-900/50 to-purple-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-violet-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-purple-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Newsletters" severity="contrast" class="bg-violet-500/20 text-violet-300 border border-violet-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Newsletters</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez vos abonnés, campagnes, et suivez l'envoi d'emails.</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <!-- Onglets de navigation -->
                    <div class="border-b border-slate-200 bg-white">
                        <div class="flex overflow-x-auto">
                            <button v-for="tab in tabs" :key="tab.value"
                                @click="activeTab = tab.value"
                                class="px-6 py-4 font-medium text-sm transition-colors border-b-2 flex-shrink-0"
                                :class="activeTab === tab.value
                                    ? 'border-violet-600 text-violet-700 bg-violet-50/50'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'">
                                <i :class="tab.icon" class="mr-2"></i> {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <!-- CONTENU DES ONGLETS -->
                    <div class="p-4 lg:p-6">
                        <!-- ===================== ABONNÉS ===================== -->
                        <div v-if="activeTab === 'subscribers'">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-2 w-full sm:w-80">
                                    <InputText v-model="subscriberSearch" placeholder="Rechercher un abonné..." class="w-full rounded-xl bg-white border-slate-200" @keyup.enter="searchSubscribers" />
                                    <Button icon="pi pi-search" class="p-button-rounded p-button-text" @click="searchSubscribers" v-tooltip.bottom="'Rechercher'" />
                                </div>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-upload" label="Importer CSV" class="p-button-outlined" @click="openImportDialog" />
                                    <Button icon="pi pi-plus" label="Ajouter un abonné" class="bg-violet-500 hover:bg-violet-600 border-none shadow-lg shadow-violet-500/30 text-white font-bold" @click="openNewSubscriber" />
                                </div>
                            </div>

                            <DataTable :value="subscribersList" v-model:selection="selectedSubscribers" dataKey="id"
                                :paginator="true" :rows="10"
                                responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>
                                <template #empty>
                                    <div class="flex flex-col items-center justify-center p-12 text-center">
                                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="pi pi-users text-3xl text-slate-400"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800">Aucun abonné</h3>
                                        <p class="text-slate-500 max-w-sm mb-4">Commencez par ajouter des abonnés.</p>
                                        <Button label="Ajouter un abonné" class="p-button-outlined" @click="openNewSubscriber" />
                                    </div>
                                </template>
                                <Column field="email" header="Email" sortable style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <span class="font-bold text-slate-800">{{ data.email }}</span>
                                    </template>
                                </Column>
                                <Column field="name" header="Nom" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span>{{ data.name || '-' }}</span>
                                    </template>
                                </Column>
                                <Column field="subscribed_at" header="Inscription" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ formatDate(data.subscribed_at) }}</span>
                                    </template>
                                </Column>
                                <Column field="is_active" header="Statut" sortable style="min-width: 8rem; text-align: center;">
                                    <template #body="{ data }">
                                        <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Actif' : 'Inactif'" />
                                    </template>
                                </Column>
                                <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editSubscriber(data)" v-tooltip.top="'Modifier'" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteSubscriber(data)" v-tooltip.top="'Supprimer'" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- ===================== CAMPAGNES ===================== -->
                        <div v-if="activeTab === 'campaigns'">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-2 w-full sm:w-80">
                                    <InputText v-model="campaignSearch" placeholder="Rechercher une campagne..." class="w-full rounded-xl bg-white border-slate-200" @keyup.enter="searchCampaigns" />
                                    <Button icon="pi pi-search" class="p-button-rounded p-button-text" @click="searchCampaigns" v-tooltip.bottom="'Rechercher'" />
                                </div>
                                <Button icon="pi pi-plus" label="Nouvelle campagne" class="bg-violet-500 hover:bg-violet-600 border-none shadow-lg shadow-violet-500/30 text-white font-bold" @click="openNewCampaign" />
                            </div>

                            <DataTable :value="campaignsList" v-model:selection="selectedCampaigns" dataKey="id"
                                :paginator="true" :rows="10"
                                responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>
                                <template #empty>
                                    <div class="flex flex-col items-center justify-center p-12 text-center">
                                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="pi pi-envelope text-3xl text-slate-400"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800">Aucune campagne</h3>
                                        <p class="text-slate-500 max-w-sm mb-4">Créez votre première campagne email.</p>
                                        <Button label="Nouvelle campagne" class="p-button-outlined" @click="openNewCampaign" />
                                    </div>
                                </template>
                                <Column field="subject" header="Sujet" sortable style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <span class="font-bold text-slate-800">{{ data.subject }}</span>
                                    </template>
                                </Column>
                                <Column field="status" header="Statut" sortable style="min-width: 8rem; text-align: center;">
                                    <template #body="{ data }">
                                        <Tag :severity="statusSeverity(data.status)" :value="statusLabel(data.status)" />
                                    </template>
                                </Column>
                                <Column field="recipient_count" header="Destinataires" sortable style="min-width: 8rem; text-align: center;">
                                    <template #body="{ data }">
                                        <Badge :value="data.recipient_count || 0" severity="info" class="bg-indigo-100 text-indigo-800" />
                                    </template>
                                </Column>
                                <Column field="scheduled_at" header="Programmé" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span v-if="data.scheduled_at" class="text-sm">{{ formatDate(data.scheduled_at) }}</span>
                                        <span v-else class="text-slate-400">Immédiat</span>
                                    </template>
                                </Column>
                                <Column field="sent_at" header="Envoyé le" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span v-if="data.sent_at" class="text-sm">{{ formatDate(data.sent_at) }}</span>
                                        <span v-else class="text-slate-400">-</span>
                                    </template>
                                </Column>
                                <Column :exportable="false" style="min-width: 12rem; text-align: right;">
                                    <template #body="{ data }">
                                        <div class="flex justify-end gap-1">
                                            <Button v-if="canSendCampaign(data)" icon="pi pi-send" class="p-button-rounded p-button-text p-button-success" @click="sendCampaign(data)" v-tooltip.top="'Envoyer'" />
                                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" @click="editCampaign(data)" v-tooltip.top="'Modifier'" :disabled="data.status === 'sent'" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteCampaign(data)" v-tooltip.top="'Supprimer'" :disabled="data.status === 'sending'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- ===================== EMAILS ENVOYÉS ===================== -->
                        <div v-if="activeTab === 'sent'">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-2 w-full sm:w-80">
                                    <InputText v-model="sentSearch" placeholder="Rechercher un email..." class="w-full rounded-xl bg-white border-slate-200" @keyup.enter="searchSentEmails" />
                                    <Button icon="pi pi-search" class="p-button-rounded p-button-text" @click="searchSentEmails" v-tooltip.bottom="'Rechercher'" />
                                </div>
                                <Button icon="pi pi-refresh" label="Actualiser" class="p-button-outlined" @click="refreshSentEmails" />
                            </div>

                            <DataTable :value="sentEmailsList" v-model:selection="selectedSentEmails" dataKey="id"
                                :paginator="true" :rows="10"
                                responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>
                                <template #empty>
                                    <div class="flex flex-col items-center justify-center p-12 text-center">
                                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="pi pi-inbox text-3xl text-slate-400"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800">Aucun email envoyé</h3>
                                        <p class="text-slate-500 max-w-sm mb-4">L'historique des envois apparaîtra ici.</p>
                                    </div>
                                </template>
                                <Column field="recipient_email" header="Destinataire" sortable style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <span class="font-medium">{{ data.recipient_email }}</span>
                                    </template>
                                </Column>
                                <Column field="subject" header="Sujet" sortable style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <span class="font-bold text-slate-800">{{ data.subject }}</span>
                                    </template>
                                </Column>
                                <Column field="sent_at" header="Date d'envoi" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ formatDate(data.sent_at) }}</span>
                                    </template>
                                </Column>
                                <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                    <template #body="{ data }">
                                        <div class="flex justify-end gap-1">
                                            <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" @click="viewSentEmail(data)" v-tooltip.top="'Détails'" />
                                            <Button icon="pi pi-replay" class="p-button-rounded p-button-text p-button-success" @click="replyToEmail(data)" v-tooltip.top="'Répondre'" />
                                            <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-info" @click="forwardEmail(data)" v-tooltip.top="'Réexpédier'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== MODALE ABONNÉ ===================== -->
        <Dialog v-model:visible="subscriberDialog" :style="{ width: '500px' }" header="Abonné" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center text-violet-600"><i class="pi pi-user"></i></div>
                    <span class="font-bold text-xl text-slate-800">{{ subscriberEditing ? 'Modifier l\'abonné' : 'Nouvel abonné' }}</span>
                </div>
            </template>
            <div class="space-y-5 pt-2">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Email <span class="text-red-500">*</span></label>
                    <InputText v-model="subscriberForm.email" autofocus :class="{ 'p-invalid': subscriberSubmitted && !subscriberForm.email }" class="w-full rounded-xl" placeholder="exemple@mail.com" />
                    <small v-if="subscriberSubmitted && !subscriberForm.email" class="p-error">L'email est requis.</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Nom</label>
                    <InputText v-model="subscriberForm.name" class="w-full rounded-xl" placeholder="Prénom Nom" />
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">Actif</label>
                    <ToggleButton v-model="subscriberForm.is_active" onLabel="Oui" offLabel="Non" class="w-20" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="subscriberDialog = false" />
                <Button label="Enregistrer" icon="pi pi-check" class="bg-violet-500 border-none hover:bg-violet-600" @click="saveSubscriber" :loading="subscriberSaving" />
            </template>
        </Dialog>

        <!-- ===================== MODALE IMPORT CSV ===================== -->
        <Dialog v-model:visible="importDialog" :style="{ width: '400px' }" header="Importer des abonnés" :modal="true">
            <div class="space-y-4">
                <input type="file" accept=".csv" @change="onCsvSelected" ref="csvInput" class="hidden" />
                <Button label="Choisir un fichier CSV" icon="pi pi-file" class="p-button-outlined w-full" @click="$refs.csvInput.click()" />
                <p class="text-sm text-slate-500">Le fichier doit contenir une colonne "email" et optionnellement "name".</p>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="importDialog = false" />
                <Button label="Importer" icon="pi pi-upload" @click="importCsv" :loading="importing" />
            </template>
        </Dialog>

        <!-- ===================== MODALE CAMPAGNE ===================== -->
        <Dialog v-model:visible="campaignDialog" :style="{ width: '800px' }" header="Campagne" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center text-violet-600"><i class="pi pi-envelope"></i></div>
                    <span class="font-bold text-xl text-slate-800">{{ campaignEditing ? 'Modifier la campagne' : 'Nouvelle campagne' }}</span>
                </div>
            </template>
            <div class="space-y-5 pt-2">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Sujet <span class="text-red-500">*</span></label>
                    <InputText v-model="campaignForm.subject" class="w-full rounded-xl" placeholder="Sujet de l'email" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Destinataires</label>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="campaignForm.recipient_mode" inputId="mode_all" value="all" />
                            <label for="mode_all">Tous les abonnés actifs</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="campaignForm.recipient_mode" inputId="mode_custom" value="custom" />
                            <label for="mode_custom">Sélection personnalisée</label>
                        </div>
                    </div>
                    <div v-if="campaignForm.recipient_mode === 'custom'">
                        <PickList v-model="selectedRecipients" listStyle="height:200px" dataKey="id" class="mt-2">
                            <template #sourceheader>Disponibles</template>
                            <template #targetheader>Sélectionnés</template>
                            <template #item="slotProps">
                                <div class="flex items-center gap-2">
                                    <span>{{ slotProps.item.email }}</span>
                                </div>
                            </template>
                        </PickList>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Contenu HTML</label>
                    <Editor v-model="campaignForm.content" editorStyle="height: 300px" />
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex-1 flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Programmer l'envoi</label>
                        <Calendar v-model="campaignForm.scheduled_at" showTime hourFormat="24" class="w-full rounded-xl" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="campaignDialog = false" />
                <Button label="Sauvegarder" icon="pi pi-check" class="bg-violet-500 border-none hover:bg-violet-600" @click="saveCampaign" :loading="campaignSaving" />
            </template>
        </Dialog>

        <!-- ===================== MODALE DÉTAIL EMAIL ENVOYÉ ===================== -->
        <Dialog v-model:visible="emailDetailDialog" :style="{ width: '700px' }" header="Détail de l'email" :modal="true">
            <div v-if="selectedEmail" class="space-y-4">
                <div><strong>De :</strong> {{ selectedEmail.from_email || config?.email || settings?.email || 'newsletter@aprojed.org' }}</div>
                <div><strong>À :</strong> {{ selectedEmail.recipient_email }}</div>
                <div><strong>Sujet :</strong> {{ selectedEmail.subject }}</div>
                <div><strong>Date :</strong> {{ formatDate(selectedEmail.sent_at) }}</div>
                <div class="border p-4 rounded-xl bg-white mt-4" v-html="selectedEmail.body"></div>
            </div>
            <template #footer>
                <Button label="Fermer" icon="pi pi-times" class="p-button-text" @click="emailDetailDialog = false" />
                <Button label="Répondre" icon="pi pi-replay" class="p-button-success" @click="replyToEmail(selectedEmail); emailDetailDialog = false" />
                <Button label="Réexpédier" icon="pi pi-copy" class="p-button-info" @click="forwardEmail(selectedEmail); emailDetailDialog = false" />
            </template>
        </Dialog>

        <!-- ===================== MODALE RÉPONSE / RÉEXPÉDITION ===================== -->
        <Dialog v-model:visible="replyDialog" :style="{ width: '700px' }" header="Répondre" :modal="true">
            <div class="space-y-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">À</label>
                    <InputText v-model="replyForm.to_email" class="w-full rounded-xl" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Sujet</label>
                    <InputText v-model="replyForm.subject" class="w-full rounded-xl" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Message</label>
                    <Editor v-model="replyForm.body" editorStyle="height: 250px" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="replyDialog = false" />
                <Button label="Envoyer" icon="pi pi-send" @click="sendReply" :loading="replySending" />
            </template>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';

// PrimeVue components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';
import PickList from 'primevue/picklist';
import Editor from 'primevue/editor';
import Calendar from 'primevue/calendar';
import RadioButton from 'primevue/radiobutton';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    subscribers: [Array, Object],
    campaigns: [Array, Object],
    sentEmails: [Array, Object],
    subscribersAll: Array,
    currentTab: String,
    search: String,
    filters: Object,
    settings: Object,
});

// Active tab
const tabs = [
    { label: 'Abonnés', value: 'subscribers', icon: 'pi pi-users' },
    { label: 'Campagnes', value: 'campaigns', icon: 'pi pi-envelope' },
    { label: 'Envoyés', value: 'sent', icon: 'pi pi-inbox' }
];
const activeTab = ref(props.currentTab || 'subscribers');

// ===================== ABONNÉS =====================
const subscribersList = computed(() => props.subscribers?.data ?? props.subscribers ?? []);
const selectedSubscribers = ref([]);
const subscriberSearch = ref(props.search || '');

const subscriberDialog = ref(false);
const subscriberEditing = ref(false);
const subscriberSubmitted = ref(false);
const subscriberSaving = ref(false);
const subscriberForm = reactive({
    id: null,
    email: '',
    name: '',
    is_active: true
});

const searchSubscribers = () => {
    router.get(route('newsletters.index'), { tab: 'subscribers', search: subscriberSearch.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const openNewSubscriber = () => {
    Object.assign(subscriberForm, { id: null, email: '', name: '', is_active: true });
    subscriberEditing.value = false;
    subscriberSubmitted.value = false;
    subscriberDialog.value = true;
};

const editSubscriber = (sub) => {
    Object.assign(subscriberForm, {
        id: sub.id,
        email: sub.email,
        name: sub.name || '',
        is_active: sub.is_active
    });
    subscriberEditing.value = true;
    subscriberDialog.value = true;
};

const saveSubscriber = () => {
    subscriberSubmitted.value = true;
    if (!subscriberForm.email.trim()) return;

    subscriberSaving.value = true;
    if (subscriberEditing.value) {
        router.put(route('subscribers.update', subscriberForm.id), subscriberForm, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Abonné modifié.', life: 3000 });
                subscriberDialog.value = false;
                subscriberSaving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                subscriberSaving.value = false;
            }
        });
    } else {
        router.post(route('subscribers.store'), subscriberForm, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Abonné ajouté.', life: 3000 });
                subscriberDialog.value = false;
                subscriberSaving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                subscriberSaving.value = false;
            }
        });
    }
};

const confirmDeleteSubscriber = (sub) => {
    confirm.require({
        message: `Supprimer définitivement ${sub.email} ?`,
        header: 'Confirmation',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('subscribers.destroy', sub.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Abonné supprimé.', life: 3000 }),
            });
        }
    });
};

// Import CSV
const importDialog = ref(false);
const csvInput = ref(null);
const csvFile = ref(null);
const importing = ref(false);

const openImportDialog = () => {
    csvFile.value = null;
    if (csvInput.value) csvInput.value.value = '';
    importDialog.value = true;
};

const onCsvSelected = (event) => {
    csvFile.value = event.target.files[0];
};

const importCsv = () => {
    if (!csvFile.value) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Aucun fichier sélectionné.' });
        return;
    }
    importing.value = true;
    const formData = new FormData();
    formData.append('csv', csvFile.value);
    router.post(route('subscribers.import'), formData, {
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Import terminé', detail: 'Abonnés importés.', life: 3000 });
            importDialog.value = false;
            importing.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'import.' });
            importing.value = false;
        }
    });
};

// ===================== CAMPAGNES =====================
const campaignsList = computed(() => props.campaigns?.data ?? props.campaigns ?? []);
const selectedCampaigns = ref([]);
const campaignSearch = ref('');
const campaignDialog = ref(false);
const campaignEditing = ref(false);
const campaignSaving = ref(false);
const campaignForm = reactive({
    id: null,
    subject: '',
    content: '',
    recipient_mode: 'all',
    scheduled_at: null,
});
const selectedRecipients = ref([[], []]);
const subscriberPicklist = ref(props.subscribersAll || []);

const canSendCampaign = (campaign) => campaign.status === 'draft' || campaign.status === 'scheduled';
const statusSeverity = (status) => {
    switch (status) {
        case 'draft': return 'secondary';
        case 'scheduled': return 'warning';
        case 'sending': return 'info';
        case 'sent': return 'success';
        default: return 'secondary';
    }
};
const statusLabel = (status) => {
    switch (status) {
        case 'draft': return 'Brouillon';
        case 'scheduled': return 'Programmée';
        case 'sending': return 'En cours';
        case 'sent': return 'Envoyée';
        default: return status;
    }
};

const searchCampaigns = () => {
    router.get(route('newsletters.index'), { tab: 'campaigns', search: campaignSearch.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const openNewCampaign = () => {
    Object.assign(campaignForm, {
        id: null,
        subject: '',
        content: '',
        recipient_mode: 'all',
        scheduled_at: null,
    });
    selectedRecipients.value = [[...subscriberPicklist.value], []];
    campaignEditing.value = false;
    campaignDialog.value = true;
};

const editCampaign = (campaign) => {
    Object.assign(campaignForm, {
        id: campaign.id,
        subject: campaign.subject,
        content: campaign.content || '',
        recipient_mode: campaign.recipient_mode || 'all',
        scheduled_at: campaign.scheduled_at ? new Date(campaign.scheduled_at) : null,
    });
    // Initialiser PickList avec les destinataires existants
    if (campaign.recipients && campaign.recipients.length) {
        const target = campaign.recipients;
        const source = subscriberPicklist.value.filter(s => !target.find(t => t.id === s.id));
        selectedRecipients.value = [source, target];
    } else {
        selectedRecipients.value = [[...subscriberPicklist.value], []];
    }
    campaignEditing.value = true;
    campaignDialog.value = true;
};

const saveCampaign = () => {
    if (!campaignForm.subject.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le sujet est requis.' });
        return;
    }
    campaignSaving.value = true;
    const payload = {
        subject: campaignForm.subject,
        content: campaignForm.content,
        recipient_mode: campaignForm.recipient_mode,
        scheduled_at: campaignForm.scheduled_at ? campaignForm.scheduled_at.toISOString() : null,
        recipients: campaignForm.recipient_mode === 'custom' ? selectedRecipients.value[1].map(r => r.id) : [],
    };

    if (campaignEditing.value) {
        router.put(route('campaigns.update', campaignForm.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Campagne mise à jour.', life: 3000 });
                campaignDialog.value = false;
                campaignSaving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                campaignSaving.value = false;
            }
        });
    } else {
        router.post(route('campaigns.store'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Campagne créée.', life: 3000 });
                campaignDialog.value = false;
                campaignSaving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                campaignSaving.value = false;
            }
        });
    }
};

const sendCampaign = (campaign) => {
    confirm.require({
        message: `Lancer l'envoi de la campagne "${campaign.subject}" ?`,
        header: 'Confirmation d\'envoi',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(route('campaigns.send', campaign.id), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Envoyée', detail: 'Campagne envoyée.', life: 3000 }),
            });
        }
    });
};

const confirmDeleteCampaign = (campaign) => {
    confirm.require({
        message: `Supprimer la campagne "${campaign.subject}" ?`,
        header: 'Confirmation',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('campaigns.destroy', campaign.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimée', detail: 'Campagne supprimée.', life: 3000 }),
            });
        }
    });
};

// ===================== EMAILS ENVOYÉS =====================
const sentEmailsList = computed(() => props.sentEmails?.data ?? props.sentEmails ?? []);
const selectedSentEmails = ref([]);
const sentSearch = ref('');
const emailDetailDialog = ref(false);
const selectedEmail = ref(null);
const replyDialog = ref(false);
const replySending = ref(false);
const replyForm = reactive({
    to_email: '',
    subject: '',
    body: '',
    original_id: null,
});

const searchSentEmails = () => {
    router.get(route('newsletters.index'), { tab: 'sent', search: sentSearch.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const refreshSentEmails = () => {
    router.get(route('newsletters.index'), { tab: 'sent' }, { preserveState: true, preserveScroll: true });
};

const viewSentEmail = (email) => {
    selectedEmail.value = email;
    emailDetailDialog.value = true;
};

const replyToEmail = (email) => {
    replyForm.to_email = email.recipient_email;
    replyForm.subject = `Re: ${email.subject}`;
    replyForm.body = `<br/><br/>--- Message original ---<br/>${email.body}`;
    replyForm.original_id = email.id;
    replyDialog.value = true;
};

const forwardEmail = (email) => {
    replyForm.to_email = '';
    replyForm.subject = `Fwd: ${email.subject}`;
    replyForm.body = `<br/><br/>--- Message transféré ---<br/>${email.body}`;
    replyForm.original_id = null;
    replyDialog.value = true;
};

const sendReply = () => {
    if (!replyForm.to_email.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Destinataire requis.' });
        return;
    }
    replySending.value = true;
    router.post(route('emails.reply'), replyForm, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Réponse envoyée.', life: 3000 });
            replyDialog.value = false;
            replySending.value = false;
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
            replySending.value = false;
        }
    });
};

// ===================== CHARGEMENT INITIAL =====================
const loadSubscribersAll = async () => {
    if (!props.subscribersAll || props.subscribersAll.length === 0) {
        try {
            const response = await fetch(route('subscribers.all'));
            if (response.ok) {
                const data = await response.json();
                subscriberPicklist.value = data;
            }
        } catch (e) {
            console.error('Erreur lors du chargement des abonnés', e);
        }
    } else {
        subscriberPicklist.value = props.subscribersAll;
    }
};

onMounted(() => {
    loadSubscribersAll();
});

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('fr-FR', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

// Flash messages
watch(() => page.props.flash?.success, (val) => {
    if (val) toast.add({ severity: 'success', summary: 'Succès', detail: val, life: 3000 });
});
</script>

<style scoped>
:deep(.custom-table .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem;
    border-bottom: 2px solid #e2e8f0;
    border-top: 1px solid #e2e8f0;
}
:deep(.custom-table .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.custom-dialog .p-dialog-header) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem;
}
:deep(.custom-dialog .p-dialog-content) {
    padding: 1.5rem;
}
:deep(.custom-dialog .p-dialog-footer) {
    padding: 0 1.5rem 1.5rem 1.5rem;
}
:deep(.p-picklist .p-picklist-list) {
    height: 200px;
}
</style>
