<script setup>
/**
 * ==========================================================================================
 * COMPOSANT : CRM Contacts Manager (Vue 3 + Composition API + PrimeVue)
 * DESCRIPTION : Interface avancée de gestion de carnet d'adresses et de leads commerciaux.
 * FONCTIONNALITÉS : Grille/Liste, Formulaire multi-étapes, Sidebar profil détaillée,
 *                   Timeline d'activités, Modale d'email, Lead Scoring, Export/Import.
 * ==========================================================================================
 */

import { ref, computed, watch, onMounted } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIMEVUE COMPONENTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import ConfirmDialog from 'primevue/confirmdialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import FileUpload from 'primevue/fileupload';
import InputSwitch from 'primevue/inputswitch';
import Tooltip from 'primevue/tooltip';
import Toolbar from 'primevue/toolbar';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';
import SelectButton from 'primevue/selectbutton';
import DataView from 'primevue/dataview';
import Chart from 'primevue/chart';
import Skeleton from 'primevue/skeleton';
import Divider from 'primevue/divider';
import Menu from 'primevue/menu';
import Rating from 'primevue/rating';
import Timeline from 'primevue/timeline';
import Editor from 'primevue/editor';
import Chip from 'primevue/chip';

// --- SERVICES ---
const toast = useToast();
const confirm = useConfirm();

// --- PROPS DEPUIS INERTIA ---
const props = defineProps({
    contacts: { type: [Array, Object], default: () => [] },
    companies: { type: [Array, Object], default: () => [] }, // Sociétés liées
    tags: { type: [Array, Object], default: () => [] },      // Étiquettes (VIP, Prospect...)
    sources: { type: [Array, Object], default: () => [] }    // Origines (Web, Event...)
});

// --- ÉTATS & COMPUTEDS (DATA) ---
const contactsList = computed(() => props.contacts?.data ?? props.contacts ?? []);
const companiesList = computed(() => props.companies?.data ?? props.companies ?? []);
const tagsList = computed(() => props.tags?.data ?? props.tags ?? []);
const sourcesList = computed(() => props.sources?.data ?? props.sources ?? []);

// Statistiques KPI
const totalContacts = computed(() => contactsList.value.length);
const activeLeads = computed(() => contactsList.value.filter(c => c.status === 'lead').length);
const hotLeads = computed(() => contactsList.value.filter(c => c.lead_score >= 4).length);
const conversionRate = computed(() => {
    const clients = contactsList.value.filter(c => c.status === 'client').length;
    if (totalContacts.value === 0) return 0;
    return Math.round((clients / totalContacts.value) * 100);
});

// --- ÉTATS UI (INTERFACE) ---
const viewMode = ref('list');
const viewModeOptions = ref([{ icon: 'pi pi-bars', value: 'list' }, { icon: 'pi pi-th-large', value: 'grid' }]);
const isDataLoading = ref(false);
const activeMainTab = ref(0);

// Visibilité des modales
const contactDialog = ref(false);
const viewContactSidebar = ref(false);
const emailDialog = ref(false);
const isEditingContact = ref(false);
const submitting = ref(false);

const expandedContactRows = ref({});
const selectedContacts = ref(null);

// Formulaire Email
const emailForm = ref({
    to: '',
    subject: '',
    body: ''
});

// Menu d'export
const exportMenu = ref();
const exportMenuItems = ref([
    { label: 'Exporter CSV (Standard)', icon: 'pi pi-file-excel', command: () => exportCSV() },
    { label: 'Exporter JSON (API)', icon: 'pi pi-code', command: () => exportJSON() },
    { separator: true },
    { label: 'Importer Contacts', icon: 'pi pi-upload', command: () => toast.add({ severity: 'info', summary: 'Info', detail: 'Fonctionnalité en cours de développement.' }) }
]);
const toggleExportMenu = (event) => exportMenu.value.toggle(event);

// --- DONNÉES STATIQUES ---
const contactStatuses = [
    { label: 'Prospect Froid', value: 'cold', severity: 'secondary' },
    { label: 'Prospect Chaud (Lead)', value: 'lead', severity: 'warn' },
    { label: 'En Négociation', value: 'negotiation', severity: 'info' },
    { label: 'Client Actif', value: 'client', severity: 'success' },
    { label: 'Perdu / Inactif', value: 'lost', severity: 'danger' }
];

const contactPreferences = [
    { label: 'Email', value: 'email' },
    { label: 'Téléphone', value: 'phone' },
    { label: 'WhatsApp', value: 'whatsapp' },
    { label: 'LinkedIn', value: 'linkedin' }
];

// --- FILTRES ---
const filtersContacts = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    'company.id': { value: null, matchMode: FilterMatchMode.EQUALS },
    lead_score: { value: null, matchMode: FilterMatchMode.GREATER_THAN_OR_EQUAL_TO }
});

// --- MODÈLES & FORMULAIRES ---
const defaultContact = {
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    mobile: '',
    job_title: '',
    company_id: null,
    status: 'cold',
    lead_score: 0,
    source_id: null,
    linkedin_profile: '',
    twitter_profile: '',
    website: '',
    address: '',
    city: '',
    country: '',
    zip_code: '',
    notes: '',
    contact_preference: 'email',
    opt_in: true,
    tags: [], // Relation belongsToMany
    avatar_url: null,
    avatar_file: null,
    remove_avatar: false
};

const currentContact = ref({ ...defaultContact });
const formErrors = ref({});
const mockTimeline = ref([]); // Historique simulé pour la sidebar

/**
 * ====================================================================
 * LOGIQUE DE VALIDATION (FRONT-END)
 * ====================================================================
 */
const validateContactForm = () => {
    formErrors.value = {};
    let isValid = true;

    if (!currentContact.value.first_name?.trim()) {
        formErrors.value.first_name = "Le prénom est requis.";
        isValid = false;
    }
    if (!currentContact.value.last_name?.trim()) {
        formErrors.value.last_name = "Le nom est requis.";
        isValid = false;
    }
    if (currentContact.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(currentContact.value.email)) {
        formErrors.value.email = "Format d'email invalide.";
        isValid = false;
    }
    if (currentContact.value.phone && !/^[\d\s\+\-\(\)]+$/.test(currentContact.value.phone)) {
        formErrors.value.phone = "Format de numéro invalide.";
        isValid = false;
    }

    return isValid;
};

/**
 * ====================================================================
 * GESTION CRUD CONTACTS
 * ====================================================================
 */
const openNewContact = () => {
    currentContact.value = { ...defaultContact };
    formErrors.value = {};
    if(fileUploadRef.value) fileUploadRef.value.clear();
    isEditingContact.value = false;
    contactDialog.value = true;
};

const editContact = (contact) => {
    currentContact.value = {
        ...contact,
        tags: contact.tags ? contact.tags.map(t => t.id) : [] // Extraction pour MultiSelect
    };
    formErrors.value = {};
    if(fileUploadRef.value) fileUploadRef.value.clear();
    isEditingContact.value = true;
    contactDialog.value = true;
};

const viewContact = (contact) => {
    currentContact.value = { ...contact };
    generateMockTimeline(); // Simulation de l'historique CRM
    viewContactSidebar.value = true;
};

const saveContact = () => {
    if (!validateContactForm()) {
        toast.add({ severity: 'warn', summary: 'Validation', detail: 'Corrigez les champs en rouge.', life: 4000 });
        return;
    }

    const form = useForm({ ...currentContact.value });

    form.transform((data) => {
        let payload = { ...data };

        // Sécurisation des booléens et tableaux
        payload.opt_in = Boolean(data.opt_in);
        payload.tags = Array.isArray(data.tags) ? data.tags : [];
        payload.remove_avatar = Boolean(data.remove_avatar);
        payload.avatar = data.avatar_file || null;

        if (isEditingContact.value) {
            payload._method = 'put'; // Nécessaire pour Laravel avec Multipart/form-data
        }

        return payload;
    });

    const routeName = isEditingContact.value ? route('contacts.update', currentContact.value.id) : route('contacts.store');
    submitting.value = true;

    form.post(routeName, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            contactDialog.value = false;
            toast.add({ severity: 'success', summary: 'Opération réussie', detail: 'Le contact a été sauvegardé dans le carnet.', life: 3000 });
        },
        onError: (errors) => {
            formErrors.value = errors;
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de sauvegarder le contact.', life: 4000 });
        },
        onFinish: () => submitting.value = false
    });
};

const confirmDeleteContact = (contact) => {
    confirm.require({
        message: `Souhaitez-vous vraiment supprimer le contact ${contact.first_name} ${contact.last_name} ? Toutes les notes associées seront perdues.`,
        header: 'Suppression Définitive',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('contacts.destroy', contact.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Contact retiré du CRM.', life: 3000 })
            });
        }
    });
};

const confirmBulkDelete = () => {
    if (!selectedContacts.value || selectedContacts.value.length === 0) return;

    confirm.require({
        message: `Supprimer définitivement les ${selectedContacts.value.length} contacts sélectionnés ?`,
        header: 'Nettoyage en masse',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = selectedContacts.value.map(c => c.id);
            router.post(route('contacts.bulk_destroy'), { ids: ids }, {
                onSuccess: () => {
                    selectedContacts.value = null;
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Les contacts ont été supprimés.', life: 3000 });
                }
            });
        }
    });
};

// --- GESTION DE L'AVATAR ---
const fileUploadRef = ref(null);

const triggerAvatarUpload = () => fileUploadRef.value.$el.querySelector('input[type="file"]')?.click();

const onUploadAvatar = (event) => {
    const file = event.files[0];
    if (file) {
        if (file.size > 3 * 1024 * 1024) {
            toast.add({ severity: 'error', summary: 'Taille dépassée', detail: 'La photo ne doit pas excéder 3 Mo.', life: 4000 });
            fileUploadRef.value.clear();
            return;
        }
        currentContact.value.avatar_file = file;
        const reader = new FileReader();
        reader.onload = (e) => currentContact.value.avatar_url = e.target.result;
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    currentContact.value.avatar_url = null;
    currentContact.value.avatar_file = null;
    currentContact.value.remove_avatar = true;
    if (fileUploadRef.value) fileUploadRef.value.clear();
};

/**
 * ====================================================================
 * FONCTIONNALITÉS CRM & COMMUNICATION
 * ====================================================================
 */
const openEmailComposer = (contact = null) => {
    if (contact && contact.email) {
        emailForm.value.to = contact.email;
    } else if (selectedContacts.value && selectedContacts.value.length > 0) {
        // Envoi groupé (BCC dans la vraie vie)
        emailForm.value.to = selectedContacts.value.map(c => c.email).filter(e => e).join('; ');
    } else {
        emailForm.value.to = '';
    }
    emailForm.value.subject = '';
    emailForm.value.body = '';
    emailDialog.value = true;
};

const sendEmail = () => {
    if (!emailForm.value.to || !emailForm.value.subject) {
        toast.add({ severity: 'warn', summary: 'Champs manquants', detail: 'Renseignez un destinataire et un sujet.', life: 3000 });
        return;
    }
    // Simulation d'envoi d'email via API locale
    setTimeout(() => {
        emailDialog.value = false;
        toast.add({ severity: 'success', summary: 'Email envoyé', detail: 'Votre campagne a été envoyée avec succès.', life: 4000 });
    }, 800);
};

const copyToClipboard = (text) => {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        toast.add({ severity: 'info', summary: 'Copié', detail: 'Texte copié dans le presse-papier.', life: 2000 });
    });
};

const generateMockTimeline = () => {
    mockTimeline.value = [
        { status: 'Contact créé', date: '12/10/2023 10:00', icon: 'pi pi-user-plus', color: '#14b8a6' },
        { status: 'Appel téléphonique (Sortant)', date: '15/10/2023 14:30', icon: 'pi pi-phone', color: '#3b82f6', description: 'Le client semble intéressé par l\'offre Premium. A relancer.' },
        { status: 'Email envoyé', date: '16/10/2023 09:15', icon: 'pi pi-envelope', color: '#6366f1', description: 'Envoi de la plaquette commerciale.' },
        { status: 'Statut mis à jour : Lead Chaud', date: '20/10/2023 11:00', icon: 'pi pi-star-fill', color: '#f59e0b' }
    ];
};

/**
 * ====================================================================
 * ANALYTICS & GRAPHIQUES (CHART.JS)
 * ====================================================================
 */
const chartOptions = ref({
    plugins: { legend: { labels: { color: '#475569', font: { family: 'Inter', weight: 'bold' } } } },
    cutout: '65%'
});

const statusDistributionChart = computed(() => {
    const statuses = contactStatuses.map(s => s.label);
    const data = contactStatuses.map(s => contactsList.value.filter(c => c.status === s.value).length);
    const bgColors = ['#94a3b8', '#f59e0b', '#3b82f6', '#10b981', '#f43f5e'];

    return {
        labels: statuses,
        datasets: [{ data: data, backgroundColor: bgColors, hoverOffset: 4, borderWidth: 0 }]
    };
});

/**
 * ====================================================================
 * UTILITAIRES & EXPORT
 * ====================================================================
 */
const getInitials = (first, last) => {
    const f = first ? first.charAt(0) : '';
    const l = last ? last.charAt(0) : '';
    return (f + l).toUpperCase() || 'CX';
};

const getFullName = (c) => `${c.first_name || ''} ${c.last_name || ''}`.trim();

const getStatusBadge = (statusValue) => {
    const status = contactStatuses.find(s => s.value === statusValue);
    return status || { label: 'Inconnu', severity: 'secondary' };
};

const exportCSV = () => {
    let csv = "ID,Prenom,Nom,Email,Telephone,Societe,Poste,Statut,Score\n";
    contactsList.value.forEach(c => {
        const company = c.company ? c.company.name : '';
        csv += `"${c.id}","${c.first_name}","${c.last_name}","${c.email}","${c.phone}","${company}","${c.job_title}","${c.status}","${c.lead_score}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "crm_contacts.csv";
    link.click();
};

const exportJSON = () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(contactsList.value, null, 2));
    const link = document.createElement('a');
    link.href = dataStr;
    link.download = "crm_contacts_api.json";
    link.click();
};

onMounted(() => {
    isDataLoading.value = true;
    setTimeout(() => isDataLoading.value = false, 700);
});
</script>

<template>
    <AppLayout>
        <Head title="Contacts & Leads CRM - Enterprise Hub" />

        <div class="min-h-screen bg-[#f8fafc] pb-24 font-sans">

            <!-- ========================================================== -->
            <!-- 1. HERO HEADER SECTION -->
            <!-- ========================================================== -->
            <div class="bg-slate-900 pt-10 pb-32 px-4 lg:px-8 relative overflow-hidden shadow-xl">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PHBhdGggZD0iTTAgMGg0MHY0MEgweiIgZmlsbD0ibm9uZSIvPjxwYXRoIGQ9Ik0wIDIwaDQwTTIwIDB2NDAiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9zdmc+')] opacity-10"></div>
                <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-blue-500/20 rounded-full blur-[140px] pointer-events-none"></div>
                <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-indigo-600/30 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-4">
                            <Badge value="Module CRM" class="bg-blue-500/20 text-blue-300 border border-blue-500/30 font-mono text-[11px] tracking-widest px-3 py-1 shadow-sm backdrop-blur-md" />
                            <Badge value="Ventes & Marketing" class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-mono text-[11px] tracking-widest px-3 py-1 shadow-sm backdrop-blur-md" />
                        </div>
                        <h1 class="text-4xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                            Gestion des <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">Contacts</span>
                        </h1>
                        <p class="text-slate-400 mt-4 text-lg max-w-2xl font-light leading-relaxed">
                            Centralisez votre carnet d'adresses professionnel, qualifiez vos prospects et suivez vos interactions commerciales en temps réel.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 bg-slate-800/60 backdrop-blur-xl border border-slate-700/50 p-2.5 rounded-2xl shadow-2xl">
                        <Button icon="pi pi-envelope" label="Campagne Mail" class="p-button-text text-white hover:bg-slate-700 rounded-xl font-bold" @click="openEmailComposer()" />
                        <div class="w-px h-6 bg-slate-600"></div>
                        <Button icon="pi pi-cog" class="p-button-rounded p-button-text text-slate-300 hover:text-white" v-tooltip.top="'Paramètres CRM'" />
                        <Button icon="pi pi-cloud-download" label="Export / Import" class="bg-blue-600 hover:bg-blue-500 border-none rounded-xl text-white font-bold shadow-lg shadow-blue-500/20 ml-2" @click="toggleExportMenu" aria-haspopup="true" aria-controls="export_menu" />
                        <Menu ref="exportMenu" id="export_menu" :model="exportMenuItems" :popup="true" class="rounded-xl shadow-2xl border-slate-100" />
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- 2. KPI CARDS (STATISTIQUES CRM) -->
            <!-- ========================================================== -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-20 relative z-20 mb-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">

                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center text-2xl shadow-inner border border-slate-200 group-hover:scale-110 transition-transform"><i class="pi pi-users"></i></div>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Total Contacts</p>
                            <h3 class="text-4xl font-black text-slate-800">{{ totalContacts }}</h3>
                        </div>
                    </div>

                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shadow-inner border border-amber-200 group-hover:scale-110 transition-transform"><i class="pi pi-bolt"></i></div>
                            <Tag value="Opportunités" severity="warning" class="bg-amber-100 text-amber-700 font-bold border border-amber-200" />
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Leads Actifs</p>
                            <h3 class="text-4xl font-black text-slate-800">{{ activeLeads }}</h3>
                        </div>
                    </div>

                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shadow-inner border border-rose-200 group-hover:scale-110 transition-transform"><i class="pi pi-star-fill"></i></div>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Prospects VIP (Score 4+)</p>
                            <h3 class="text-4xl font-black text-slate-800">{{ hotLeads }}</h3>
                        </div>
                    </div>

                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-inner border border-emerald-200 group-hover:scale-110 transition-transform"><i class="pi pi-chart-line"></i></div>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Taux de Conversion</p>
                            <h3 class="text-4xl font-black text-slate-800">{{ conversionRate }} <span class="text-xl text-slate-400 font-bold">%</span></h3>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ========================================================== -->
            <!-- 3. MAIN WORKSPACE (TABVIEW) -->
            <!-- ========================================================== -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 relative z-20">
                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-2xl shadow-slate-200/40 overflow-hidden">

                    <TabView v-model:activeIndex="activeMainTab" class="custom-main-tabview">

                        <!-- ONGLET 1 : ANNUAIRE / TABLEAU DES CONTACTS -->
                        <TabPanel>
                            <template #header><div class="flex items-center gap-3 px-3 py-2"><i class="pi pi-address-book text-lg"></i><span class="font-bold text-base">Carnet d'adresses</span><Badge :value="totalContacts" severity="info" class="bg-slate-100 text-slate-700 font-black" /></div></template>
                            <Toolbar class="bg-slate-50/50 border-0 border-b border-slate-100 p-5">
                                <template #start>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <!-- Recherche -->
                                        <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative flex items-center">
                                            <i class="pi pi-search text-slate-400 absolute right-3 z-10" />
                                            <InputText v-model="filtersContacts['global'].value" placeholder="Rechercher (Nom, Email, Entreprise)..." class="w-full rounded-2xl border-slate-200 shadow-sm focus:border-blue-400" />
                                        </span>
                                        <!-- Filtre Statut -->
                                        <Dropdown v-model="filtersContacts['status'].value" :options="contactStatuses" optionLabel="label" optionValue="value" placeholder="Tous les statuts" :showClear="true" class="w-full sm:w-56 rounded-2xl border-slate-200 shadow-sm" />
                                        <!-- Bulk Actions -->
                                        <Button v-if="selectedContacts?.length > 0" icon="pi pi-envelope" :label="`Mailer (${selectedContacts.length})`" class="bg-slate-800 hover:bg-slate-900 text-white rounded-2xl shadow-sm px-4 font-bold border-none" @click="openEmailComposer()" />
                                        <Button v-if="selectedContacts?.length > 0" icon="pi pi-trash" class="p-button-danger rounded-2xl shadow-sm px-3" @click="confirmBulkDelete" v-tooltip="'Supprimer la sélection'" />
                                    </div>
                                </template>
                                <template #end>
                                    <div class="flex items-center gap-4">
                                        <SelectButton v-model="viewMode" :options="viewModeOptions" optionLabel="value" dataKey="value" class="hidden md:flex bg-white rounded-xl shadow-sm p-1 border border-slate-200">
                                            <template #option="slotProps"><i :class="slotProps.option.icon" class="text-slate-600 px-3 py-1"></i></template>
                                        </SelectButton>
                                        <div class="h-8 w-px bg-slate-200 hidden md:block"></div>
                                        <Button icon="pi pi-user-plus" label="Nouveau Contact" class="bg-blue-600 hover:bg-blue-700 border-none shadow-lg shadow-blue-500/30 text-white font-bold rounded-2xl px-6" @click="openNewContact" />
                                    </div>
                                </template>
                            </Toolbar>

                            <!-- Chargement Squelette -->
                            <div v-if="isDataLoading" class="p-8">
                                <div v-for="i in 5" :key="i" class="flex items-center gap-6 mb-6"><Skeleton shape="circle" size="3.5rem"></Skeleton><div class="flex-1 space-y-3"><Skeleton width="30%"></Skeleton><Skeleton width="50%"></Skeleton></div></div>
                            </div>

                            <!-- DATAVIEW -->
                            <DataView v-else :value="contactsList" :layout="viewMode" :paginator="true" :rows="12" class="border-none">

                                <!-- ================= VUE LISTE (DATATABLE) ================= -->
                                <template #list>
                                    <DataTable
                                        v-model:selection="selectedContacts"
                                        :value="contactsList"
                                        :filters="filtersContacts"
                                        dataKey="id"
                                        class="custom-table"
                                        responsiveLayout="scroll"
                                        :rowHover="true"
                                        stripedRows
                                    >
                                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                                        <Column field="name" header="Identité & Société" sortable style="min-width: 25rem">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-4">
                                                    <Avatar v-if="data.avatar_url" :image="data.avatar_url" size="xlarge" shape="circle" class="border border-slate-200 shadow-sm w-12 h-12 flex-shrink-0" />
                                                    <Avatar v-else :label="getInitials(data.first_name, data.last_name)" size="xlarge" shape="circle" class="bg-gradient-to-br from-blue-50 to-indigo-100 text-blue-700 font-black border border-blue-200 shadow-sm w-12 h-12 text-lg flex-shrink-0" />

                                                    <div class="flex flex-col">
                                                        <span class="font-extrabold text-slate-800 text-base cursor-pointer hover:text-blue-600 transition-colors" @click="viewContact(data)">{{ getFullName(data) }}</span>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md"><i class="pi pi-building text-[10px] mr-1"></i>{{ data.company?.name || 'Indépendant' }}</span>
                                                            <span class="text-xs font-medium text-slate-400">{{ data.job_title || 'Poste inconnu' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="email" header="Coordonnées" style="min-width: 15rem">
                                            <template #body="{ data }">
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:text-blue-600" @click="copyToClipboard(data.email)" v-tooltip="'Copier'"><i class="pi pi-envelope text-slate-400 text-xs"></i> {{ data.email || '-' }}</span>
                                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:text-blue-600" @click="copyToClipboard(data.phone)" v-tooltip="'Copier'"><i class="pi pi-phone text-slate-400 text-xs"></i> {{ data.phone || data.mobile || '-' }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="status" header="Statut du Lead" sortable style="min-width: 12rem">
                                            <template #body="{ data }">
                                                <Tag :value="getStatusBadge(data.status).label" :severity="getStatusBadge(data.status).severity" class="font-bold border px-3 py-1 text-[11px] uppercase tracking-wider" />
                                            </template>
                                        </Column>

                                        <Column field="lead_score" header="Score Qualité" sortable style="min-width: 10rem">
                                            <template #body="{ data }">
                                                <Rating :modelValue="data.lead_score" :readonly="true" :cancel="false" class="custom-rating" />
                                            </template>
                                        </Column>

                                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                            <template #body="{ data }">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="viewContact(data)" v-tooltip.top="'Fiche CRM'" />
                                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info hover:bg-blue-50" @click="editContact(data)" v-tooltip.top="'Modifier'" />
                                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger hover:bg-red-50" @click="confirmDeleteContact(data)" v-tooltip.top="'Supprimer'" />
                                                </div>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </template>

                                <!-- ================= VUE GRILLE (CARTES CRM) ================= -->
                                <template #grid>
                                    <div class="p-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 bg-slate-50/50">
                                        <div v-for="contact in contactsList" :key="contact.id" class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-lg shadow-slate-200/30 flex flex-col relative group hover:-translate-y-2 hover:shadow-2xl hover:border-blue-200 transition-all duration-300 cursor-pointer" @click="viewContact(contact)">

                                            <!-- En-tête de carte -->
                                            <div class="flex justify-between items-start w-full mb-4">
                                                <Tag :value="getStatusBadge(contact.status).label" :severity="getStatusBadge(contact.status).severity" class="text-[10px] font-bold uppercase tracking-widest" />
                                                <div @click.stop>
                                                    <Button icon="pi pi-ellipsis-h" class="p-button-rounded p-button-text p-button-sm text-slate-400" @click="(e) => { currentContact.value=contact; toggleExportMenu(e) }" />
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-center text-center">
                                                <Avatar v-if="contact.avatar_url" :image="contact.avatar_url" size="xlarge" shape="circle" class="w-20 h-20 shadow-md mb-4 border-4 border-white" />
                                                <Avatar v-else :label="getInitials(contact.first_name, contact.last_name)" size="xlarge" shape="circle" class="w-20 h-20 shadow-md mb-4 border-4 border-white bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 font-black text-2xl" />

                                                <h4 class="font-black text-lg text-slate-800 leading-tight mb-1">{{ getFullName(contact) }}</h4>
                                                <p class="text-xs font-bold text-blue-600 mb-2">{{ contact.job_title || 'Non défini' }}</p>
                                                <p class="text-xs font-medium text-slate-500 mb-4 bg-slate-50 px-3 py-1 rounded-full"><i class="pi pi-building mr-1"></i> {{ contact.company?.name || 'Indépendant' }}</p>

                                                <Rating :modelValue="contact.lead_score" :readonly="true" :cancel="false" class="custom-rating text-sm mb-4" />
                                            </div>

                                            <Divider class="my-0 w-full opacity-50" />

                                            <!-- Pied de carte -->
                                            <div class="w-full flex justify-between items-center pt-4">
                                                <div class="flex gap-1">
                                                    <Button icon="pi pi-envelope" class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8 bg-slate-50 hover:bg-slate-100" v-tooltip.bottom="contact.email" @click.stop="openEmailComposer(contact)" />
                                                    <Button v-if="contact.phone || contact.mobile" icon="pi pi-phone" class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8 bg-slate-50 hover:bg-slate-100" v-tooltip.bottom="contact.phone || contact.mobile" />
                                                </div>
                                                <Button label="Voir fiche" class="p-button-text p-button-sm text-blue-600 font-bold hover:bg-blue-50" @click.stop="viewContact(contact)" />
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </DataView>
                        </TabPanel>

                        <!-- ONGLET 2 : ANALYSE & PIPELINE -->
                        <TabPanel>
                            <template #header><div class="flex items-center gap-3 px-3 py-2"><i class="pi pi-chart-pie text-lg"></i><span class="font-bold text-base">Pipeline & Analytics</span></div></template>

                            <div class="p-8 bg-slate-50/50 min-h-[500px]">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <!-- Chart Statut -->
                                    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/30 flex flex-col">
                                        <h3 class="font-black text-slate-800 text-xl mb-2 text-center">Répartition du Pipeline (Statuts)</h3>
                                        <p class="text-sm text-slate-500 text-center mb-8">Visualisation de l'état d'avancement des prospects</p>
                                        <div class="flex justify-center flex-1 items-center">
                                            <Chart type="doughnut" :data="statusDistributionChart" :options="chartOptions" class="w-full max-w-[25rem]" />
                                        </div>
                                    </div>

                                    <!-- Liste Derniers Ajouts -->
                                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/30">
                                        <h3 class="font-black text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="pi pi-clock text-blue-500"></i> Derniers Contacts Ajoutés</h3>
                                        <ul class="space-y-3">
                                            <li v-for="c in [...contactsList].reverse().slice(0, 5)" :key="'latest'+c.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-200 transition-colors cursor-pointer" @click="viewContact(c)">
                                                <div class="flex items-center gap-4">
                                                    <Avatar :image="c.avatar_url" :label="!c.avatar_url ? getInitials(c.first_name, c.last_name) : null" shape="circle" class="w-10 h-10 bg-white text-slate-700 font-bold shadow-sm" />
                                                    <div>
                                                        <p class="font-bold text-slate-800 text-sm">{{ getFullName(c) }}</p>
                                                        <p class="text-xs text-slate-500">{{ c.company?.name || 'Indépendant' }}</p>
                                                    </div>
                                                </div>
                                                <Tag :value="getStatusBadge(c.status).label" :severity="getStatusBadge(c.status).severity" class="text-[10px]" />
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- MODALES ET SIDEBARS CRM -->
        <!-- ========================================================== -->

        <!-- 1. SIDEBAR CRM (VUE DÉTAILLÉE 360°) -->
        <Sidebar v-model:visible="viewContactSidebar" position="right" class="w-full md:w-[40rem] lg:w-[45rem] custom-sidebar">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400">Fiche Contact #{{ currentContact.id }}</span>
                    <div class="flex gap-2 mr-4">
                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" @click="editContact(currentContact); viewContactSidebar=false" v-tooltip="'Modifier'" />
                        <Button icon="pi pi-envelope" class="p-button-rounded p-button-text p-button-success" @click="openEmailComposer(currentContact)" v-tooltip="'Envoyer un email'" />
                    </div>
                </div>
            </template>

            <div v-if="currentContact.id" class="flex flex-col h-full overflow-y-auto bg-slate-50/50">

                <!-- HEADER PROFIL -->
                <div class="bg-white p-8 border-b border-slate-200 shadow-sm flex flex-col sm:flex-row items-center gap-6">
                    <Avatar v-if="currentContact.avatar_url" :image="currentContact.avatar_url" size="xlarge" shape="circle" class="w-28 h-28 shadow-lg border-4 border-white" />
                    <Avatar v-else :label="getInitials(currentContact.first_name, currentContact.last_name)" size="xlarge" shape="circle" class="w-28 h-28 shadow-lg border-4 border-white bg-gradient-to-br from-blue-50 to-indigo-100 text-blue-700 font-black text-4xl" />

                    <div class="flex-1 text-center sm:text-left">
                        <h2 class="text-3xl font-black text-slate-800">{{ getFullName(currentContact) }}</h2>
                        <p class="text-lg font-bold text-slate-500 mt-1">{{ currentContact.job_title || 'Poste non renseigné' }} <span v-if="currentContact.company">chez <span class="text-blue-600">{{ currentContact.company.name }}</span></span></p>

                        <div class="flex items-center justify-center sm:justify-start gap-4 mt-4">
                            <Tag :value="getStatusBadge(currentContact.status).label" :severity="getStatusBadge(currentContact.status).severity" class="font-bold border" />
                            <Rating :modelValue="currentContact.lead_score" :readonly="true" :cancel="false" class="custom-rating" />
                        </div>
                    </div>
                </div>

                <!-- ONGLETS DE LA FICHE -->
                <TabView class="custom-sidebar-tabview flex-1">

                    <!-- INFO DE BASE -->
                    <TabPanel header="Coordonnées & Infos">
                        <div class="p-6 space-y-6">
                            <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2"><i class="pi pi-id-card"></i> Contact Direct</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500"><i class="pi pi-envelope"></i></div>
                                        <div><p class="text-[10px] uppercase font-bold text-slate-400">Email Professionnel</p>
                                        <p class="text-sm font-bold text-slate-800 cursor-pointer hover:text-blue-600" @click="copyToClipboard(currentContact.email)">{{ currentContact.email || '-' }}</p></div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500"><i class="pi pi-phone"></i></div>
                                        <div><p class="text-[10px] uppercase font-bold text-slate-400">Téléphone Direct</p>
                                        <p class="text-sm font-bold text-slate-800 cursor-pointer hover:text-blue-600" @click="copyToClipboard(currentContact.phone)">{{ currentContact.phone || '-' }}</p></div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500"><i class="pi pi-mobile"></i></div>
                                        <div><p class="text-[10px] uppercase font-bold text-slate-400">Mobile</p>
                                        <p class="text-sm font-bold text-slate-800 cursor-pointer hover:text-blue-600" @click="copyToClipboard(currentContact.mobile)">{{ currentContact.mobile || '-' }}</p></div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500"><i class="pi pi-linkedin"></i></div>
                                        <div><p class="text-[10px] uppercase font-bold text-blue-400">LinkedIn</p>
                                        <a v-if="currentContact.linkedin_profile" :href="currentContact.linkedin_profile" target="_blank" class="text-sm font-bold text-blue-700 hover:underline">Voir le profil</a>
                                        <p v-else class="text-sm font-bold text-slate-400">-</p></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2"><i class="pi pi-map-marker"></i> Localisation</h4>
                                <p class="text-sm font-medium text-slate-700 leading-relaxed">
                                    {{ currentContact.address || 'Adresse inconnue' }}<br/>
                                    {{ currentContact.zip_code }} {{ currentContact.city }}<br/>
                                    <strong>{{ currentContact.country }}</strong>
                                </p>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TIMELINE & ACTIVITÉS -->
                    <TabPanel header="Historique & Activités">
                        <div class="p-6">
                            <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm mb-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2"><i class="pi pi-history"></i> Timeline CRM</h4>
                                    <Button label="Ajouter une note" icon="pi pi-plus" class="p-button-sm p-button-outlined p-button-secondary rounded-xl" />
                                </div>

                                <Timeline :value="mockTimeline" class="custom-timeline">
                                    <template #marker="slotProps">
                                        <span class="flex items-center justify-center text-white rounded-full w-8 h-8 shadow-sm" :style="{ backgroundColor: slotProps.item.color }">
                                            <i :class="slotProps.item.icon" class="text-sm"></i>
                                        </span>
                                    </template>
                                    <template #content="slotProps">
                                        <div class="mb-6">
                                            <span class="text-xs font-bold text-slate-400 block mb-1">{{ slotProps.item.date }}</span>
                                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                                <h5 class="text-sm font-black text-slate-800 mb-1">{{ slotProps.item.status }}</h5>
                                                <p v-if="slotProps.item.description" class="text-sm text-slate-600">{{ slotProps.item.description }}</p>
                                            </div>
                                        </div>
                                    </template>
                                </Timeline>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- NOTES COMPLÈTES -->
                    <TabPanel header="Notes Internes">
                        <div class="p-6">
                            <div class="bg-yellow-50/50 rounded-3xl border border-yellow-200 p-6 shadow-sm h-full min-h-[300px]">
                                <h4 class="text-xs font-black uppercase tracking-widest text-yellow-600 mb-4 flex items-center gap-2"><i class="pi pi-pen-to-square"></i> Bloc-notes Privé</h4>
                                <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ currentContact.notes || 'Aucune note enregistrée sur ce contact.' }}</p>
                            </div>
                        </div>
                    </TabPanel>

                </TabView>
            </div>
        </Sidebar>

        <!-- 2. MODALE DE FORMULAIRE (MULTI-ÉTAPES) -->
        <Dialog v-model:visible="contactDialog" :style="{ width: '900px' }" :modal="true" class="custom-dialog" :closable="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 border border-blue-100 shadow-inner"><i class="pi pi-user-plus text-2xl"></i></div>
                        <div>
                            <h2 class="font-black text-2xl text-slate-800">{{ isEditingContact ? 'Modifier le Contact' : 'Nouveau Contact' }}</h2>
                            <p class="text-xs text-slate-500 font-medium">Saisie des informations de la fiche CRM.</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100" @click="contactDialog = false" />
                </div>
            </template>

            <div class="p-2 -mx-4 -mb-4">
                <TabView class="custom-modal-tabview">

                    <!-- ETAPE 1 : IDENTITÉ -->
                    <TabPanel header="Identité & Base">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-6 px-4">
                            <!-- Avatar -->
                            <div class="lg:col-span-4 flex flex-col items-center border-r border-slate-100 pr-6">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest w-full text-center mb-6">Photo / Logo</label>
                                <div v-if="currentContact.avatar_url" class="relative group w-40 h-40 rounded-[2rem] overflow-hidden border-4 border-white shadow-xl mb-4">
                                    <img :src="currentContact.avatar_url" class="w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-3 backdrop-blur-sm">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-info" @click="triggerAvatarUpload" v-tooltip="'Modifier'" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="removeAvatar" v-tooltip="'Supprimer'" />
                                    </div>
                                </div>
                                <div v-else class="w-40 h-40 rounded-[2rem] border-2 border-dashed border-slate-300 bg-slate-50 flex flex-col items-center justify-center hover:bg-blue-50 hover:border-blue-400 hover:text-blue-600 cursor-pointer transition-all duration-300 mb-4 group text-slate-400" @click="triggerAvatarUpload">
                                    <i class="pi pi-camera text-4xl mb-3 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-xs font-bold uppercase tracking-widest">Image</span>
                                </div>
                                <FileUpload ref="fileUploadRef" mode="basic" :auto="false" accept="image/*" @select="onUploadAvatar" class="hidden" />
                            </div>

                            <!-- Champs -->
                            <div class="lg:col-span-8 space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Prénom <span class="text-red-500">*</span></label>
                                        <InputText v-model="currentContact.first_name" :class="['w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3', {'p-invalid': formErrors.first_name}]" />
                                        <small v-if="formErrors.first_name" class="text-red-500 font-bold">{{ formErrors.first_name }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Nom <span class="text-red-500">*</span></label>
                                        <InputText v-model="currentContact.last_name" :class="['w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3', {'p-invalid': formErrors.last_name}]" />
                                        <small v-if="formErrors.last_name" class="text-red-500 font-bold">{{ formErrors.last_name }}</small>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Email</label>
                                        <InputGroup class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 focus-within:bg-white">
                                            <InputGroupAddon class="bg-transparent border-0 px-4"><i class="pi pi-envelope text-slate-400"></i></InputGroupAddon>
                                            <InputText v-model="currentContact.email" class="border-0 bg-transparent w-full focus:ring-0 shadow-none py-3" :class="{'p-invalid': formErrors.email}" />
                                        </InputGroup>
                                        <small v-if="formErrors.email" class="text-red-500 font-bold">{{ formErrors.email }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Téléphone Fixe</label>
                                        <InputGroup class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 focus-within:bg-white">
                                            <InputGroupAddon class="bg-transparent border-0 px-4"><i class="pi pi-phone text-slate-400"></i></InputGroupAddon>
                                            <InputText v-model="currentContact.phone" class="border-0 bg-transparent w-full py-3" />
                                        </InputGroup>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- ETAPE 2 : PRO & CRM -->
                    <TabPanel header="Entreprise & Qualification">
                        <div class="space-y-8 pt-6 px-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Société (Compte)</label>
                                    <Dropdown v-model="currentContact.company_id" :options="companiesList" optionLabel="name" optionValue="id" placeholder="Rechercher ou sélectionner..." :filter="true" class="w-full rounded-2xl border-slate-200" :showClear="true" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Intitulé du poste</label>
                                    <InputText v-model="currentContact.job_title" placeholder="Ex: Directeur Achat" class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3 px-4" />
                                </div>
                            </div>

                            <div class="bg-blue-50/50 border border-blue-100 rounded-3xl p-6">
                                <h3 class="font-black text-blue-900 mb-4 flex items-center gap-2"><i class="pi pi-filter"></i> Qualification du Lead</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Statut CRM</label>
                                        <Dropdown v-model="currentContact.status" :options="contactStatuses" optionLabel="label" optionValue="value" class="w-full rounded-2xl border-slate-200 bg-white" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Score de potentiel (1 à 5)</label>
                                        <div class="bg-white p-3 rounded-2xl border border-slate-200 flex items-center h-[50px]">
                                            <Rating v-model="currentContact.lead_score" :cancel="true" class="custom-rating" />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 mt-6">
                                    <label class="text-sm font-bold text-slate-700">Étiquettes (Tags)</label>
                                    <MultiSelect v-model="currentContact.tags" :options="tagsList" optionLabel="name" optionValue="id" placeholder="Ajouter des tags..." :filter="true" class="w-full rounded-2xl border-slate-200 bg-white" display="chip" />
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- ETAPE 3 : AUTRES -->
                    <TabPanel header="Adresse & Notes">
                        <div class="space-y-6 pt-6 px-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="col-span-4 flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Adresse postale</label>
                                    <InputText v-model="currentContact.address" class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3" />
                                </div>
                                <div class="col-span-2 md:col-span-1 flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Code postal</label>
                                    <InputText v-model="currentContact.zip_code" class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3" />
                                </div>
                                <div class="col-span-2 md:col-span-1 flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Ville</label>
                                    <InputText v-model="currentContact.city" class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3" />
                                </div>
                                <div class="col-span-4 md:col-span-2 flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Pays</label>
                                    <InputText v-model="currentContact.country" class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 mt-4">
                                <label class="text-sm font-bold text-slate-700">Notes Internes</label>
                                <Textarea v-model="currentContact.notes" rows="4" class="w-full rounded-2xl border-slate-200 bg-slate-50 p-4" placeholder="Saisissez des informations contextuelles..." />
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full bg-slate-50 -mx-6 -mb-6 p-6 border-t border-slate-200 rounded-b-[2rem]">
                    <span class="text-xs text-slate-400 font-bold tracking-widest uppercase"><span class="text-red-500">*</span> Obligatoire</span>
                    <div class="flex gap-3">
                        <Button label="Annuler" class="p-button-text text-slate-600 font-bold hover:bg-slate-200 rounded-2xl px-6 py-3" @click="contactDialog = false" />
                        <Button label="Sauvegarder le contact" icon="pi pi-check" :loading="submitting" class="bg-blue-600 border-none hover:bg-blue-700 shadow-xl shadow-blue-500/40 font-bold rounded-2xl px-8 py-3" @click="saveContact" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- 3. MODALE D'ENVOI D'EMAIL (WYSIWYG) -->
        <Dialog v-model:visible="emailDialog" :style="{ width: '800px' }" header="Composer un message" :modal="true" class="custom-dialog">
            <div class="space-y-4 pt-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Destinataire(s)</label>
                    <InputText v-model="emailForm.to" class="w-full rounded-xl border-slate-200 bg-slate-50" readonly />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Sujet de l'email</label>
                    <InputText v-model="emailForm.subject" placeholder="Saisissez l'objet du mail..." class="w-full rounded-xl border-slate-200" />
                </div>
                <div class="flex flex-col gap-2 mt-4">
                    <label class="text-sm font-bold text-slate-700">Message</label>
                    <Editor v-model="emailForm.body" editorStyle="height: 250px" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" class="p-button-text text-slate-600 font-bold" @click="emailDialog = false" />
                <Button label="Envoyer le message" icon="pi pi-send" class="bg-slate-800 border-none hover:bg-slate-900 shadow-lg font-bold rounded-xl px-6" @click="sendEmail" />
            </template>
        </Dialog>

        <!-- CONFIRMATION DIALOG GLOBALE -->
        <ConfirmDialog :style="{ width: '450px' }" class="custom-confirm-dialog" :breakpoints="{'960px': '75vw', '640px': '90vw'}">
            <template #message="slotProps">
                <div class="flex flex-col items-center w-full gap-4 pb-4">
                    <div class="w-20 h-20 bg-red-50 rounded-[2rem] flex items-center justify-center text-red-500 text-4xl mb-2 shadow-inner border border-red-100"><i :class="slotProps.message.icon"></i></div>
                    <p class="text-center text-slate-800 font-bold text-lg leading-snug">{{ slotProps.message.message }}</p>
                </div>
            </template>
        </ConfirmDialog>

    </AppLayout>
</template>

<style scoped>
/* ==========================================================================
   STYLES SPECIFIQUES (THEME ENTERPRISE / CLEAN UI)
   ========================================================================== */

/* --- DATATABLE --- */
:deep(.custom-table) { font-family: 'Inter', system-ui, sans-serif; }
:deep(.custom-table .p-datatable-header) { background: transparent; border: none; padding: 0; }
:deep(.custom-table .p-datatable-thead > tr > th) {
    background: #f8fafc; color: #64748b; font-size: 0.70rem; font-weight: 900;
    letter-spacing: 0.05em; text-transform: uppercase; padding: 1.25rem 1.5rem;
    border-bottom: 2px solid #e2e8f0; border-top: none;
}
:deep(.custom-table .p-datatable-tbody > tr) { transition: background-color 0.2s; }
:deep(.custom-table .p-datatable-tbody > tr > td) { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
:deep(.custom-table .p-datatable-tbody > tr:hover) { background-color: #f8fafc; }
:deep(.custom-table .p-paginator) { background: transparent; border-top: 1px solid #e2e8f0; padding: 1.5rem; }

/* --- MAIN TABVIEW --- */
:deep(.custom-main-tabview .p-tabview-nav) { border-bottom: 1px solid #f1f5f9; background: #ffffff; padding: 0 1.5rem; border-radius: 2rem 2rem 0 0; }
:deep(.custom-main-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent; border: none; border-bottom: 3px solid transparent;
    color: #64748b; font-weight: 700; padding: 1.5rem 1.5rem; transition: all 0.3s ease;
}
:deep(.custom-main-tabview .p-tabview-nav li:not(.p-highlight):hover .p-tabview-nav-link) { color: #334155; border-bottom-color: #cbd5e1; }
:deep(.custom-main-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) { color: #2563eb; border-bottom-color: #2563eb; }

/* --- DIALOGS (MODALS) --- */
:deep(.custom-dialog) { border-radius: 2rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); border: 1px solid #e2e8f0; }
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1.5rem 2rem; background: #ffffff; }
:deep(.custom-dialog .p-dialog-content) { padding: 0 2rem 1.5rem 2rem; background: #ffffff; }

/* --- MODAL TABVIEW --- */
:deep(.custom-modal-tabview .p-tabview-nav) { padding: 0; border-bottom: 2px solid #f8fafc; }
:deep(.custom-modal-tabview .p-tabview-nav li .p-tabview-nav-link) { padding: 1rem 1.5rem; font-size: 0.85rem; }

/* --- SIDEBAR --- */
:deep(.custom-sidebar) { border-top-left-radius: 2rem; border-bottom-left-radius: 2rem; background: #f8fafc; }
:deep(.custom-sidebar .p-sidebar-header) { padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
:deep(.custom-sidebar-tabview .p-tabview-nav) { background: #ffffff; padding: 0 2rem; border-bottom: 1px solid #f1f5f9; }

/* --- RATINGS --- */
:deep(.custom-rating .p-rating-item.p-rating-item-active .p-rating-icon) { color: #f59e0b; }

/* --- FOCUS RINGS --- */
:deep(.p-inputtext:focus), :deep(.p-dropdown:focus), :deep(.p-dropdown.p-focus), :deep(.p-multiselect.p-focus) {
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #bfdbfe; /* Ring blue */
    border-color: #3b82f6;
}
</style>
