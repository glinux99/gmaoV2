<script setup>
/**
 * ==========================================================================================
 * COMPOSANT : Organisation, Équipes & Annuaire (Vue 3 + Composition API + PrimeVue)
 * DESCRIPTION : Gestion complète des départements (équipes) et des utilisateurs.
 * AJOUTS : Ordre d'affichage personnalisable (drag & drop) + hiérarchie parent/enfant
 * ==========================================================================================
 */

import { ref, computed, onMounted } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- IMPORTATION COMPOSANTS PRIMEVUE ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
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
import Calendar from 'primevue/calendar';

// --- SERVICES ---
const toast = useToast();
const confirm = useConfirm();

// --- PROPS DEPUIS INERTIA ---
const props = defineProps({
    teams: { type: [Array, Object], default: () => [] },
    users: { type: [Array, Object], default: () => [] }
});

// --- ÉTATS & COMPUTEDS ---
const teamsList = computed(() => props.teams?.data ?? props.teams ?? []);
const usersList = computed(() => props.users?.data ?? props.users ?? []);

// Statistiques globales
const totalUsers = computed(() => usersList.value.length);
const totalTeams = computed(() => teamsList.value.length);
const activeUsers = computed(() => usersList.value.filter(u => u.is_active).length);
const avgHourlyRate = computed(() => {
    const validRates = usersList.value.filter(u => u.hourly_rate > 0);
    if (!validRates.length) return 0;
    const sum = validRates.reduce((acc, u) => acc + parseFloat(u.hourly_rate), 0);
    return (sum / validRates.length).toFixed(2);
});

// --- ÉTATS D'INTERFACE (UI) ---
const viewMode = ref('list');
const viewModeOptions = ref([{ icon: 'pi pi-bars', value: 'list' }, { icon: 'pi pi-th-large', value: 'grid' }]);
const isDataLoading = ref(false);
const activeMainTab = ref(0);

const teamDialog = ref(false);
const userDialog = ref(false);
const viewUserSidebar = ref(false);
const isEditingTeam = ref(false);
const isEditingUser = ref(false);

const expandedTeamRows = ref({});
const selectedUsers = ref(null);

// Menu d'export rapide
const exportMenu = ref();
const exportMenuItems = ref([
    { label: 'Exporter CSV', icon: 'pi pi-file-excel', command: () => exportUsersCSV() },
    { label: 'Exporter JSON', icon: 'pi pi-file', command: () => exportUsersJSON() }
]);
const toggleExportMenu = (event) => exportMenu.value.toggle(event);

// --- LISTES DE RÉFÉRENCES ---
const tailwindColors = [
    { name: 'Gris (Slate)', value: 'slate' }, { name: 'Rouge (Red)', value: 'red' },
    { name: 'Orange', value: 'orange' }, { name: 'Ambre (Amber)', value: 'amber' },
    { name: 'Émeraude (Emerald)', value: 'emerald' }, { name: 'Sarcelle (Teal)', value: 'teal' },
    { name: 'Bleu (Blue)', value: 'blue' }, { name: 'Indigo', value: 'indigo' },
    { name: 'Violet', value: 'violet' }, { name: 'Rose', value: 'rose' }
];

const contractTypes = ['CDI', 'CDD', 'Alternance', 'Stage', 'Freelance', 'Intérim'];

// --- FILTRES ---
const filtersTeams = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] }
});

const filtersUsers = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'team.id': { value: null, matchMode: FilterMatchMode.EQUALS },
    contract_type: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- MODÈLES ---
const defaultTeam = {
    id: null,
    name: '',
    description: '',
    color: 'indigo',
    parent_id: null
};

const defaultUser = {
    id: null,
    team_id: null,
    name: '',
    last_name: '',
    email: '',
    password: '',
    provider_name: null,
    provider_id: null,
    hourly_rate: null,
    position: '',
    phone: '',
    contract_type: 'CDI',
    hiring_date: null,
    linkedin_url: '',
    bio: '',
    is_active: true,
    avatar: null,
    avatar_url: null,
    avatar_file: null
};

const currentTeam = ref({ ...defaultTeam });
const currentUser = ref({ ...defaultUser });
const formErrors = ref({});

// ====================================================================
// VALIDATION
// ====================================================================
const validateUserForm = () => {
    formErrors.value = {};
    let isValid = true;

    if (!currentUser.value.name?.trim()) {
        formErrors.value.name = "Le prénom/nom est requis.";
        isValid = false;
    }
    if (currentUser.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(currentUser.value.email)) {
        formErrors.value.email = "Format d'email invalide.";
        isValid = false;
    }
    if (currentUser.value.phone && !/^[\d\s\+\-\(\)]+$/.test(currentUser.value.phone)) {
        formErrors.value.phone = "Format de numéro invalide.";
        isValid = false;
    }
    return isValid;
};

const validateTeamForm = () => {
    formErrors.value = {};
    let isValid = true;

    if (!currentTeam.value.name?.trim()) {
        formErrors.value.name = "Le nom de l'équipe est requis.";
        isValid = false;
    }
    if (!currentTeam.value.color) {
        formErrors.value.color = "Une couleur doit être sélectionnée.";
        isValid = false;
    }
    return isValid;
};

// ====================================================================
// GESTION DES UTILISATEURS
// ====================================================================
const openNewUser = () => {
    currentUser.value = { ...defaultUser };
    formErrors.value = {};
    if(fileUploadRef.value) fileUploadRef.value.clear();
    isEditingUser.value = false;
    userDialog.value = true;
};

const editUser = (user) => {
    currentUser.value = {
        ...user,
        password: '',
        hiring_date: user.hiring_date ? new Date(user.hiring_date) : null
    };
    formErrors.value = {};
    if(fileUploadRef.value) fileUploadRef.value.clear();
    isEditingUser.value = true;
    userDialog.value = true;
};

const viewUser = (user) => {
    currentUser.value = { ...user };
    viewUserSidebar.value = true;
};

const submitting = ref(false);

const saveUser = () => {
    if (!validateUserForm()) {
        toast.add({ severity: 'warn', summary: 'Validation', detail: 'Corrigez les champs en rouge.', life: 4000 });
        return;
    }

    const form = useForm({ ...currentUser.value });

    form.transform((data) => {
        let payload = {
            ...data,
            name: data.name || '',
            last_name: data.last_name || '',
            email: data.email || '',
            is_active: Boolean(data.is_active),
            roles: Array.isArray(data.roles) ? data.roles : [],
            remove_avatar: Boolean(data.remove_avatar),
            hiring_date: data.hiring_date ? new Date(data.hiring_date).toISOString().split('T')[0] : null,
        };

        if (!isEditingUser.value) {
            payload.password = data.password || '';
        } else {
            if (!data.password) delete payload.password;
            payload._method = 'put';
        }

        payload.avatar = data.avatar_file || null;
        return payload;
    });

    const routeName = isEditingUser.value
        ? route('users.update', currentUser.value.id)
        : route('users.store');

    submitting.value = true;

    form.post(routeName, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            userDialog.value = false;
            toast.add({
                severity: 'success',
                summary: 'Succès',
                detail: isEditingUser.value ? 'Utilisateur mis à jour' : 'Utilisateur créé',
                life: 3000
            });
            router.reload({ only: ['users'] });
            resetForm();
        },
        onError: (errors) => {
            formErrors.value = errors;
            toast.add({
                severity: 'error',
                summary: 'Erreur',
                detail: Object.values(errors).flat().join(' | '),
                life: 5000
            });
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
};

const resetForm = () => {
  currentUser.value = { ...defaultUser };
  formErrors.value = {};
  isEditingUser.value = false;
};

const confirmDeleteUser = (user) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer définitivement le profil de ${user.name} ?`,
        header: 'Suppression Irréversible',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('users.destroy', user.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Utilisateur retiré du système.', life: 3000 })
            });
        }
    });
};

const confirmBulkDeleteUsers = () => {
    if (!selectedUsers.value || selectedUsers.value.length === 0) return;

    confirm.require({
        message: `Supprimer les ${selectedUsers.value.length} utilisateurs sélectionnés ?`,
        header: 'Action en masse',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = selectedUsers.value.map(u => u.id);
            router.post(route('users.bulk_destroy'), { ids: ids }, {
                onSuccess: () => {
                    selectedUsers.value = null;
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Opération de suppression réussie.', life: 3000 });
                }
            });
        }
    });
};

// --- GESTION AVATAR ---
const fileUploadRef = ref(null);
const triggerAvatarUpload = () => fileUploadRef.value.$el.querySelector('input[type="file"]')?.click();

const onUploadAvatar = (event) => {
    const file = event.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            toast.add({ severity: 'error', summary: 'Fichier lourd', detail: 'Max 2 Mo autorisés.', life: 4000 });
            fileUploadRef.value.clear();
            return;
        }
        currentUser.value.avatar_file = file;
        const reader = new FileReader();
        reader.onload = (e) => currentUser.value.avatar_url = e.target.result;
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    currentUser.value.avatar_url = null;
    currentUser.value.avatar_file = null;
    currentUser.value.avatar = null;
    if (fileUploadRef.value) fileUploadRef.value.clear();
};

// ====================================================================
// GESTION DES DÉPARTEMENTS (TEAMS) AVEC HIÉRARCHIE
// ====================================================================
const openNewTeam = () => {
    currentTeam.value = { ...defaultTeam };
    formErrors.value = {};
    isEditingTeam.value = false;
    teamDialog.value = true;
};

const editTeam = (team) => {
    currentTeam.value = { ...team };
    formErrors.value = {};
    isEditingTeam.value = true;
    teamDialog.value = true;
};

const saveDepartment = () => {
    if (!validateTeamForm()) return;

    const form = useForm({ ...currentTeam.value });
    const method = isEditingTeam.value ? 'put' : 'post';
    const routeName = isEditingTeam.value ? route('teams.update', currentTeam.value.id) : route('teams.store');

    form.submit(method, routeName, {
        preserveScroll: true,
        onSuccess: () => {
            teamDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipe sauvegardée.', life: 3000 });
            router.reload({ only: ['teams'] });
        },
        onError: (errors) => formErrors.value = errors
    });
};

const confirmDeleteTeam = (team) => {
    confirm.require({
        message: `Supprimer l'équipe "${team.name}" ? Les utilisateurs liés verront leur affectation retirée (null). Les sous-équipes seront réassignées à l'équipe parente.`,
        header: 'Avertissement',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('teams.destroy', team.id))
    });
};

// --- RÉORGANISATION (drag & drop natif PrimeVue) ---
const onTeamReorder = (event) => {
    const newOrderIds = event.value.map(team => team.id);
    router.post(route('teams.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Ordre mis à jour', detail: 'La hiérarchie des départements a été sauvegardée.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le réordonnancement a échoué.' });
        }
    });
};

// ====================================================================
// ANALYTICS & GRAPHIQUES
// ====================================================================
const chartOptions = ref({
    plugins: { legend: { labels: { color: '#475569', font: { family: 'Inter', weight: 'bold' } } } },
    cutout: '65%'
});

const teamDistributionChart = computed(() => {
    const labels = teamsList.value.map(t => t.name);
    const data = teamsList.value.map(t => usersList.value.filter(u => u.team_id === t.id).length);
    const bgColors = teamsList.value.map(t => {
        const map = { slate:'#64748b', red:'#ef4444', orange:'#f97316', amber:'#f59e0b', emerald:'#10b981', teal:'#14b8a6', blue:'#3b82f6', indigo:'#6366f1', violet:'#8b5cf6', rose:'#f43f5e' };
        return map[t.color] || '#cbd5e1';
    });

    const unassignedCount = usersList.value.filter(u => !u.team_id).length;
    if (unassignedCount > 0) {
        labels.push('Sans département');
        data.push(unassignedCount);
        bgColors.push('#94a3b8');
    }

    return {
        labels: labels,
        datasets: [{ data: data, backgroundColor: bgColors, hoverOffset: 4, borderWidth: 0 }]
    };
});

// ====================================================================
// UTILITAIRES & EXPORT
// ====================================================================
const getInitials = (name, lastName) => {
    const first = name ? name.charAt(0) : '';
    const last = lastName ? lastName.charAt(0) : '';
    return (first + last).toUpperCase() || '??';
};

const getFullName = (u) => {
    return `${u.name || ''} ${u.last_name || ''}`.trim();
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(dateStr));
};

const exportUsersCSV = () => {
    let csv = "ID,Prenom,Nom,Email,Poste,Contrat,Telephone,Taux Horaire,Equipe\n";
    usersList.value.forEach(u => {
        const teamStr = u.team ? u.team.name : 'Aucune';
        csv += `"${u.id}","${u.name}","${u.last_name || ''}","${u.email}","${u.position || ''}","${u.contract_type || ''}","${u.phone || ''}","${u.hourly_rate || ''}","${teamStr}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "export_utilisateurs.csv";
    link.click();
};

const exportUsersJSON = () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(usersList.value, null, 2));
    const link = document.createElement('a');
    link.href = dataStr;
    link.download = "export_utilisateurs.json";
    link.click();
};

// --- LIFECYCLE ---
onMounted(() => {
    isDataLoading.value = true;
    setTimeout(() => isDataLoading.value = false, 600);
});
</script>

<template>
    <AppLayout>
        <Head title="Annuaire & Départements - Enterprise Hub" />

        <div class="min-h-screen bg-[#f8fafc] pb-24 font-sans">

            <!-- HERO HEADER SECTION -->
            <div class="bg-slate-900 pt-10 pb-32 px-4 lg:px-8 relative overflow-hidden shadow-xl">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PHBhdGggZD0iTTAgMGg0MHY0MEgweiIgZmlsbD0ibm9uZSIvPjxwYXRoIGQ9Ik0wIDIwaDQwTTIwIDB2NDAiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9zdmc+')] opacity-10"></div>
                <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-teal-500/20 rounded-full blur-[140px] pointer-events-none"></div>
                <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-indigo-600/30 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-4">
                            <Badge value="Ressources Humaines" class="bg-teal-500/20 text-teal-300 border border-teal-500/30 font-mono text-[11px] tracking-widest px-3 py-1 shadow-sm backdrop-blur-md" />
                            <Badge value="Opérations" class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-mono text-[11px] tracking-widest px-3 py-1 shadow-sm backdrop-blur-md" />
                        </div>
                        <h1 class="text-4xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                            Structure & <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-400 to-emerald-300">Annuaire</span>
                        </h1>
                        <p class="text-slate-400 mt-4 text-lg max-w-2xl font-light leading-relaxed">
                            Supervisez les départements, administrez les profils utilisateurs et pilotez vos effectifs via ce hub centralisé.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 bg-slate-800/60 backdrop-blur-xl border border-slate-700/50 p-2.5 rounded-2xl shadow-2xl">
                        <Button icon="pi pi-chart-bar" label="Dashboard RH" class="p-button-text text-white hover:bg-slate-700 rounded-xl font-bold" @click="activeMainTab = 2" />
                        <div class="w-px h-6 bg-slate-600"></div>
                        <Button icon="pi pi-cloud-download" label="Export Data" class="bg-teal-600 hover:bg-teal-500 border-none rounded-xl text-white font-bold shadow-lg shadow-teal-500/20" @click="toggleExportMenu" aria-haspopup="true" aria-controls="export_menu" />
                        <Menu ref="exportMenu" id="export_menu" :model="exportMenuItems" :popup="true" class="rounded-xl shadow-2xl border-slate-100" />
                    </div>
                </div>
            </div>

            <!-- KPI CARDS -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-20 relative z-20 mb-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    <!-- KPI 1 -->
                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 flex items-center justify-center text-2xl shadow-inner border border-indigo-200/50 group-hover:scale-110 transition-transform"><i class="pi pi-users"></i></div>
                            <Tag :value="activeUsers + ' Actifs'" severity="success" class="bg-emerald-50 text-emerald-700 font-bold border border-emerald-200" />
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Collaborateurs</p>
                            <div class="flex items-baseline gap-2"><h3 class="text-4xl font-black text-slate-800">{{ totalUsers }}</h3></div>
                        </div>
                    </div>
                    <!-- KPI 2 -->
                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-50 to-teal-100 text-teal-600 flex items-center justify-center text-2xl shadow-inner border border-teal-200/50 group-hover:scale-110 transition-transform"><i class="pi pi-sitemap"></i></div>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Départements</p>
                            <div class="flex items-baseline gap-2"><h3 class="text-4xl font-black text-slate-800">{{ totalTeams }}</h3></div>
                        </div>
                    </div>
                    <!-- KPI 3 -->
                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 text-amber-600 flex items-center justify-center text-2xl shadow-inner border border-amber-200/50 group-hover:scale-110 transition-transform"><i class="pi pi-euro"></i></div>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Moyenne Taux Hor.</p>
                            <div class="flex items-baseline gap-2"><h3 class="text-4xl font-black text-slate-800">{{ avgHourlyRate }} <span class="text-xl text-slate-400 font-bold">€</span></h3></div>
                        </div>
                    </div>
                    <!-- KPI 4 -->
                    <div class="bg-white/90 backdrop-blur-2xl rounded-[2rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-50 to-rose-100 text-rose-600 flex items-center justify-center text-2xl shadow-inner border border-rose-200/50 group-hover:scale-110 transition-transform"><i class="pi pi-globe"></i></div>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Auth. Tierce (OAuth)</p>
                            <div class="flex items-baseline gap-2"><h3 class="text-4xl font-black text-slate-800">{{ usersList.filter(u => u.provider_name).length }}</h3><span class="text-sm font-medium text-slate-400">comptes liés</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN WORKSPACE (TABVIEW) -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 relative z-20">
                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-2xl shadow-slate-200/40 overflow-hidden">

                    <TabView v-model:activeIndex="activeMainTab" class="custom-main-tabview">

                        <!-- ONGLET 1 : UTILISATEURS (identique à avant) -->
                        <TabPanel>
                            <template #header><div class="flex items-center gap-3 px-3 py-2"><i class="pi pi-users text-lg"></i><span class="font-bold text-base">Annuaire Personnel</span><Badge :value="totalUsers" severity="info" class="bg-slate-100 text-slate-700 font-black" /></div></template>

                            <Toolbar class="bg-slate-50/50 border-0 border-b border-slate-100 p-5">
                                <template #start>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <span class="p-input-icon-left w-full sm:w-80">
                                            <i class="pi pi-search text-slate-400" />
                                            <InputText v-model="filtersUsers['global'].value" placeholder="Rechercher nom, email, poste..." class="w-full rounded-2xl border-slate-200 shadow-sm focus:border-indigo-400" />
                                        </span>
                                        <Dropdown v-model="filtersUsers['team.id'].value" :options="teamsList" optionLabel="name" optionValue="id" placeholder="Tous les départements" :showClear="true" class="w-full sm:w-60 rounded-2xl border-slate-200 shadow-sm" />
                                        <Button v-if="selectedUsers?.length > 0" icon="pi pi-trash" :label="`Supprimer (${selectedUsers.length})`" severity="danger" class="rounded-2xl shadow-sm px-4 font-bold" @click="confirmBulkDeleteUsers" />
                                    </div>
                                </template>
                                <template #end>
                                    <div class="flex items-center gap-4">
                                        <SelectButton v-model="viewMode" :options="viewModeOptions" optionLabel="value" dataKey="value" class="hidden md:flex bg-white rounded-xl shadow-sm p-1 border border-slate-200">
                                            <template #option="slotProps"><i :class="slotProps.option.icon" class="text-slate-600 px-3 py-1"></i></template>
                                        </SelectButton>
                                        <div class="h-8 w-px bg-slate-200 hidden md:block"></div>
                                        <Button icon="pi pi-user-plus" label="Ajouter Profil" class="bg-indigo-600 hover:bg-indigo-700 border-none shadow-lg shadow-indigo-500/30 text-white font-bold rounded-2xl px-6" @click="openNewUser" />
                                    </div>
                                </template>
                            </Toolbar>

                            <div class="p-0">
                                <div v-if="isDataLoading" class="p-8">
                                    <div v-for="i in 5" :key="i" class="flex items-center gap-6 mb-6"><Skeleton shape="circle" size="3.5rem"></Skeleton><div class="flex-1 space-y-3"><Skeleton width="25%"></Skeleton><Skeleton width="45%"></Skeleton></div></div>
                                </div>

                                <DataView v-else :value="usersList" :layout="viewMode" :paginator="true" :rows="12" class="border-none">
                                    <template #list>
                                        <DataTable
                                            v-model:selection="selectedUsers"
                                            :value="usersList"
                                            :filters="filtersUsers"
                                            dataKey="id"
                                            class="custom-table"
                                            responsiveLayout="scroll"
                                            :rowHover="true"
                                            stripedRows
                                            emptyMessage="Aucun utilisateur ne correspond à vos critères."
                                        >
                                            <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                                            <Column field="name" header="Collaborateur" sortable style="min-width: 22rem">
                                                <template #body="{ data }">
                                                    <div class="flex items-center gap-4">
                                                        <div class="relative">
                                                            <Avatar v-if="data.avatar_url" :image="data.avatar_url" size="xlarge" shape="circle" class="border border-slate-200 shadow-sm w-12 h-12 flex-shrink-0" />
                                                            <Avatar v-else :label="getInitials(data.name, data.last_name)" size="xlarge" shape="circle" class="bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-700 font-black border border-indigo-200 shadow-sm w-12 h-12 text-lg flex-shrink-0" />
                                                            <span :class="['absolute bottom-0 right-0 w-3.5 h-3.5 border-2 border-white rounded-full', data.is_active ? 'bg-emerald-500' : 'bg-rose-500']" v-tooltip="data.is_active ? 'Actif' : 'Inactif'"></span>
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="font-extrabold text-slate-800 text-base cursor-pointer hover:text-indigo-600 transition-colors" @click="viewUser(data)">{{ getFullName(data) }}</span>
                                                            <div class="flex items-center gap-2 mt-0.5">
                                                                <span class="text-xs font-medium text-slate-500"><i class="pi pi-envelope text-[10px] mr-1"></i>{{ data.email || 'Sans email' }}</span>
                                                                <i v-if="data.provider_name" class="pi pi-shield text-emerald-500 text-[10px]" v-tooltip="'Connecté via ' + data.provider_name"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </Column>
                                            <Column field="position" header="Poste & Statut" sortable style="min-width: 15rem">
                                                <template #body="{ data }">
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-slate-700">{{ data.position || 'Non renseigné' }}</span>
                                                        <span class="text-xs font-medium text-slate-400 mt-1">{{ data.contract_type || 'Indéfini' }}</span>
                                                    </div>
                                                </template>
                                            </Column>
                                            <Column field="team.name" header="Département" sortable style="min-width: 12rem">
                                                <template #body="{ data }">
                                                    <div v-if="data.team" class="flex items-center gap-2">
                                                        <span :class="`w-2 h-2 rounded-full shadow-sm bg-${data.team.color}-500`"></span>
                                                        <span class="font-bold text-slate-700 text-sm">{{ data.team.name }}</span>
                                                    </div>
                                                    <span v-else class="text-xs font-bold text-slate-400 italic bg-slate-100 px-2 py-1 rounded-md">Non affecté</span>
                                                </template>
                                            </Column>
                                            <Column field="hourly_rate" header="Taux Hor." sortable style="min-width: 8rem; text-align: right">
                                                <template #body="{ data }">
                                                    <span class="font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded border border-indigo-100">{{ data.hourly_rate ? data.hourly_rate + ' €' : '-' }}</span>
                                                </template>
                                            </Column>
                                            <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                                <template #body="{ data }">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="viewUser(data)" v-tooltip.top="'Profil'" />
                                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info hover:bg-indigo-50" @click="editUser(data)" v-tooltip.top="'Modifier'" />
                                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger hover:bg-red-50" @click="confirmDeleteUser(data)" v-tooltip.top="'Supprimer'" />
                                                    </div>
                                                </template>
                                            </Column>
                                        </DataTable>
                                    </template>
                                    <template #grid>
                                        <div class="p-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 bg-slate-50/50">
                                            <div v-for="user in usersList" :key="user.id" class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-lg shadow-slate-200/30 flex flex-col items-center relative group hover:-translate-y-2 hover:shadow-2xl hover:border-indigo-200 transition-all duration-300 cursor-pointer" @click="viewUser(user)">
                                                <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                                    <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-sm text-slate-400" @click="(e) => { currentUser.value=user; toggleExportMenu(e) }" />
                                                </div>
                                                <div class="absolute top-6 left-6 flex gap-1">
                                                    <div :class="['w-2.5 h-2.5 rounded-full shadow-sm', user.is_active ? 'bg-emerald-500' : 'bg-rose-500']" v-tooltip="user.is_active ? 'Actif' : 'Inactif'"></div>
                                                </div>
                                                <Avatar v-if="user.avatar_url" :image="user.avatar_url" size="xlarge" shape="circle" class="w-24 h-24 shadow-md mb-4 border-4 border-white" />
                                                <Avatar v-else :label="getInitials(user.name, user.last_name)" size="xlarge" shape="circle" class="w-24 h-24 shadow-md mb-4 border-4 border-white bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-700 font-black text-3xl" />
                                                <h4 class="font-black text-lg text-slate-800 text-center leading-tight mb-1">{{ getFullName(user) }}</h4>
                                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3 text-center">{{ user.position || 'Collaborateur' }}</p>
                                                <div v-if="user.team" :class="`text-[10px] font-bold px-3 py-1 rounded-lg bg-${user.team.color}-50 text-${user.team.color}-700 border border-${user.team.color}-100 mb-4`">
                                                    {{ user.team.name }}
                                                </div>
                                                <div v-else class="text-[10px] font-bold px-3 py-1 rounded-lg bg-slate-100 text-slate-500 mb-4">Non affecté</div>
                                                <Divider class="my-2 w-full opacity-50" />
                                                <div class="w-full flex justify-between items-center mt-2 px-2">
                                                    <div class="flex gap-1">
                                                        <Button icon="pi pi-envelope" class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8 bg-slate-50 hover:bg-slate-100" v-tooltip.bottom="user.email" @click.stop="window.location.href=`mailto:${user.email}`" />
                                                        <Button v-if="user.phone" icon="pi pi-phone" class="p-button-rounded p-button-text p-button-secondary !w-8 !h-8 bg-slate-50 hover:bg-slate-100" v-tooltip.bottom="user.phone" />
                                                    </div>
                                                    <Button label="Modifier" class="p-button-text p-button-sm text-indigo-600 font-bold hover:bg-indigo-50" @click.stop="editUser(user)" />
                                                </div>
                                            </div>
                                            <div v-if="usersList.length === 0" class="col-span-full py-20 text-center flex flex-col items-center">
                                                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-4"><i class="pi pi-search text-4xl text-slate-300"></i></div>
                                                <h3 class="text-xl font-black text-slate-700 mb-2">Aucun profil trouvé</h3>
                                                <p class="text-slate-500 font-medium">Vérifiez vos filtres de recherche.</p>
                                            </div>
                                        </div>
                                    </template>
                                </DataView>
                            </div>
                        </TabPanel>

                        <!-- ONGLET 2 : ÉQUIPES / DÉPARTEMENTS AVEC HIÉRARCHIE -->
                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-3 px-3 py-2">
                                    <i class="pi pi-sitemap text-lg"></i>
                                    <span class="font-bold text-base">Départements</span>
                                </div>
                            </template>

                            <Toolbar class="bg-slate-50/50 border-0 border-b border-slate-100 p-5">
                                <template #start>
                                    <span class="p-input-icon-left w-full sm:w-80">
                                        <i class="pi pi-search text-slate-400" />
                                        <InputText v-model="filtersTeams['global'].value" placeholder="Rechercher une équipe..." class="w-full rounded-2xl border-slate-200 shadow-sm focus:border-teal-400" />
                                    </span>
                                </template>
                                <template #end>
                                    <Button icon="pi pi-plus" label="Nouveau Département" class="bg-teal-600 hover:bg-teal-700 border-none shadow-lg shadow-teal-500/30 text-white font-bold rounded-2xl px-6" @click="openNewTeam" />
                                </template>
                            </Toolbar>

                            <DataTable
                                v-model:expandedRows="expandedTeamRows"
                                :value="teamsList"
                                :filters="filtersTeams"
                                dataKey="id"
                                class="custom-table"
                                stripedRows
                                reorderableRows
                                @row-reorder="onTeamReorder"
                            >
                                <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />
                                <Column expander style="width: 3rem" />
                                <Column field="name" header="Département" sortable style="min-width: 20rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-4">
                                            <div :class="`w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-md bg-gradient-to-br from-${data.color}-400 to-${data.color}-600`">
                                                <i class="pi pi-sitemap text-xl"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-extrabold text-slate-800 text-lg">{{ data.name }}</span>
                                                <span v-if="data.parent" class="text-xs text-slate-500">Parent : {{ data.parent.name }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="description" header="Description" style="min-width: 25rem">
                                    <template #body="{ data }">
                                        <span class="text-sm text-slate-600 line-clamp-2 leading-relaxed">{{ data.description || 'Aucune description fournie.' }}</span>
                                    </template>
                                </Column>
                                <Column field="parent.name" header="Parent" sortable style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <span v-if="data.parent" class="text-sm font-medium text-slate-700">{{ data.parent.name }}</span>
                                        <span v-else class="text-xs italic text-slate-400">Aucun parent</span>
                                    </template>
                                </Column>
                                <Column field="members_count" header="Effectif" sortable style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <Badge :value="(usersList.filter(u => u.team_id === data.id)).length + ' membres'" severity="secondary" class="bg-slate-100 text-slate-700 font-bold px-3 py-1" />
                                    </template>
                                </Column>
                                <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                    <template #body="{ data }">
                                        <div class="flex justify-end gap-1">
                                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" @click="editTeam(data)" v-tooltip.top="'Modifier'" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteTeam(data)" v-tooltip.top="'Supprimer'" />
                                        </div>
                                    </template>
                                </Column>

                                <!-- EXPANSION : montre les sous-équipes et les membres -->
                                <template #expansion="{ data }">
                                    <div class="p-8 bg-slate-50 border-y border-slate-100 shadow-inner space-y-8">
                                        <!-- Sous-équipes (enfants) -->
                                        <div>
                                            <h5 class="text-sm font-black text-slate-700 mb-4 flex items-center gap-2 uppercase tracking-widest">
                                                <i class="pi pi-sitemap text-slate-400"></i> Sous-départements
                                            </h5>
                                            <div v-if="teamsList.filter(t => t.parent_id === data.id).length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                                <div v-for="child in teamsList.filter(t => t.parent_id === data.id)" :key="child.id" class="flex items-center gap-4 bg-white border border-slate-200 p-3 rounded-2xl shadow-sm hover:border-slate-300 transition-colors">
                                                    <div :class="`w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-sm bg-${child.color}-500`">
                                                        <i class="pi pi-sitemap text-sm"></i>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-bold text-slate-800">{{ child.name }}</span>
                                                        <span class="text-xs text-slate-500">{{ usersList.filter(u => u.team_id === child.id).length }} membre(s)</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="text-sm text-slate-500 italic p-4 bg-white rounded-2xl border border-slate-100 text-center shadow-sm">
                                                <i class="pi pi-inbox text-2xl text-slate-200 mb-1 block"></i>
                                                Aucun sous-département rattaché.
                                            </div>
                                        </div>

                                        <!-- Membres -->
                                        <div>
                                            <h5 class="text-sm font-black text-slate-700 mb-4 flex items-center gap-2 uppercase tracking-widest">
                                                <i class="pi pi-users text-slate-400"></i> Personnel rattaché
                                            </h5>
                                            <div v-if="usersList.filter(u => u.team_id === data.id).length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                                                <div v-for="u in usersList.filter(u => u.team_id === data.id)" :key="u.id" class="flex items-center gap-4 bg-white border border-slate-200 p-3 rounded-2xl shadow-sm hover:border-slate-300 transition-colors cursor-pointer group" @click="viewUser(u)">
                                                    <Avatar :image="u.avatar_url" :label="!u.avatar_url ? getInitials(u.name, u.last_name) : null" shape="circle" class="w-10 h-10 text-sm bg-slate-100 text-slate-600 font-bold flex-shrink-0 group-hover:scale-110 transition-transform" />
                                                    <div class="flex flex-col overflow-hidden">
                                                        <span class="text-sm font-bold text-slate-800 truncate">{{ getFullName(u) }}</span>
                                                        <span class="text-[10px] uppercase font-bold text-slate-400 truncate">{{ u.position || 'Collaborateur' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="text-sm text-slate-500 italic p-4 bg-white rounded-2xl border border-slate-100 text-center shadow-sm">
                                                <i class="pi pi-inbox text-2xl text-slate-200 mb-1 block"></i>
                                                Aucun collaborateur n'est assigné à ce département.
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>
                        </TabPanel>

                        <!-- ONGLET 3 : ANALYTICS (inchangé) -->
                        <TabPanel>
                            <template #header><div class="flex items-center gap-3 px-3 py-2"><i class="pi pi-chart-pie text-lg"></i><span class="font-bold text-base">Analyse RH</span></div></template>

                            <div class="p-8 bg-slate-50/50 min-h-[500px]">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/30 flex flex-col">
                                        <h3 class="font-black text-slate-800 text-xl mb-2 text-center">Répartition des effectifs</h3>
                                        <p class="text-sm text-slate-500 text-center mb-8">Nombre de collaborateurs par département</p>
                                        <div class="flex justify-center flex-1 items-center">
                                            <Chart type="doughnut" :data="teamDistributionChart" :options="chartOptions" class="w-full max-w-[25rem]" />
                                        </div>
                                    </div>
                                    <div class="space-y-8">
                                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/30">
                                            <h3 class="font-black text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="pi pi-euro text-amber-500"></i> Top Taux Horaires</h3>
                                            <ul class="space-y-3">
                                                <li v-for="(user, idx) in [...usersList].filter(u=>u.hourly_rate).sort((a,b)=>b.hourly_rate - a.hourly_rate).slice(0,4)" :key="'top'+user.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center font-black text-slate-400 text-xs shadow-sm">#{{idx+1}}</div>
                                                        <div>
                                                            <p class="font-bold text-slate-800 text-sm">{{ getFullName(user) }}</p>
                                                            <p class="text-xs text-slate-500">{{ user.position || 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                    <span class="font-mono font-black text-amber-600 bg-amber-50 px-3 py-1 rounded-lg border border-amber-100">{{ user.hourly_rate }} €/h</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>
        </div>

        <!-- MODALE UTILISATEUR (inchangée) -->
        <Dialog v-model:visible="userDialog" :style="{ width: '850px' }" :modal="true" class="custom-dialog" :closable="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 border border-indigo-100 shadow-inner"><i class="pi pi-user-edit text-2xl"></i></div>
                        <div>
                            <h2 class="font-black text-2xl text-slate-800">{{ isEditingUser ? 'Fiche Employé' : 'Nouvel Employé' }}</h2>
                            <p class="text-xs text-slate-500 font-medium">Administration des accès et données RH.</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100" @click="userDialog = false" />
                </div>
            </template>

            <div class="p-2 -mx-4 -mb-4">
                <TabView class="custom-modal-tabview">
                    <TabPanel header="Identité & Accès">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-6 px-4">
                            <div class="lg:col-span-4 flex flex-col items-center border-r border-slate-100 pr-6">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest w-full text-center mb-6">Avatar (Optionnel)</label>
                                <div v-if="currentUser.avatar_url" class="relative group w-40 h-40 rounded-[2rem] overflow-hidden border-4 border-white shadow-xl mb-4">
                                    <img :src="currentUser.avatar_url" class="w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-3 backdrop-blur-sm">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-info" @click="triggerAvatarUpload" v-tooltip="'Modifier'" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="removeAvatar" v-tooltip="'Supprimer'" />
                                    </div>
                                </div>
                                <div v-else class="w-40 h-40 rounded-[2rem] border-2 border-dashed border-slate-300 bg-slate-50 flex flex-col items-center justify-center hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-600 cursor-pointer transition-all duration-300 mb-4 group text-slate-400" @click="triggerAvatarUpload">
                                    <i class="pi pi-camera text-4xl mb-3 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-xs font-bold uppercase tracking-widest">Image</span>
                                </div>
                                <FileUpload ref="fileUploadRef" mode="basic" :auto="false" accept="image/*" @select="onUploadAvatar" class="hidden" />
                                <div v-if="currentUser.provider_name" class="mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-200 w-full text-center">
                                    <i class="pi pi-shield text-emerald-500 mb-2 text-2xl"></i>
                                    <p class="text-xs font-bold text-slate-700">Connecté via OAuth</p>
                                    <p class="text-[10px] text-slate-500 uppercase">{{ currentUser.provider_name }}</p>
                                </div>
                            </div>
                            <div class="lg:col-span-8 space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Prénom (Name) <span class="text-red-500">*</span></label>
                                        <InputText v-model="currentUser.name" :class="['w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white px-4 py-3', {'p-invalid': formErrors.name}]" />
                                        <small v-if="formErrors.name" class="text-red-500 font-bold"><i class="pi pi-exclamation-circle mr-1"></i>{{ formErrors.name }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Nom (Last Name)</label>
                                        <InputText v-model="currentUser.last_name" class="w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white px-4 py-3" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Email Professionnel</label>
                                        <InputGroup class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-500/20">
                                            <InputGroupAddon class="bg-transparent border-0 px-4"><i class="pi pi-envelope text-slate-400"></i></InputGroupAddon>
                                            <InputText v-model="currentUser.email" placeholder="contact@..." class="border-0 bg-transparent w-full focus:ring-0 shadow-none py-3" :class="{'p-invalid': formErrors.email}" />
                                        </InputGroup>
                                        <small v-if="formErrors.email" class="text-red-500 font-bold">{{ formErrors.email }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Mot de passe</label>
                                        <InputText v-model="currentUser.password" type="password" class="w-full rounded-2xl border-slate-200 bg-slate-50 focus:bg-white px-4 py-3" :placeholder="isEditingUser ? 'Laisser vide pour ignorer' : 'Nouveau mot de passe'" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Téléphone</label>
                                        <InputGroup class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-500/20">
                                            <InputGroupAddon class="bg-transparent border-0 px-4"><i class="pi pi-phone text-slate-400"></i></InputGroupAddon>
                                            <InputText v-model="currentUser.phone" placeholder="+33 6..." class="border-0 bg-transparent w-full focus:ring-0 shadow-none py-3" :class="{'p-invalid': formErrors.phone}" />
                                        </InputGroup>
                                    </div>
                                    <div class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-200 mt-7">
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm">Compte Actif</p>
                                            <p class="text-[10px] text-slate-500">Autoriser la connexion</p>
                                        </div>
                                        <InputSwitch v-model="currentUser.is_active" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                    <TabPanel header="Poste & Affectation">
                        <div class="space-y-8 pt-6 px-4">
                            <div class="bg-indigo-50/50 border border-indigo-100 rounded-3xl p-6">
                                <h3 class="font-black text-indigo-900 mb-2 flex items-center gap-2"><i class="pi pi-sitemap"></i> Département (Équipe)</h3>
                                <p class="text-xs text-indigo-700/70 mb-6">Sélectionnez l'équipe principale à laquelle l'employé est rattaché (Relation 1-to-N).</p>
                                <div class="flex flex-col gap-2">
                                    <Dropdown v-model="currentUser.team_id" :options="teamsList" optionLabel="name" optionValue="id" placeholder="Sélectionnez un département..." :filter="true" class="w-full rounded-2xl border-slate-200 bg-white" :showClear="true">
                                        <template #value="sp"><div v-if="sp.value" class="flex gap-2 items-center"><span :class="`w-2 h-2 rounded-full bg-${teamsList.find(t=>t.id===sp.value)?.color}-500`"></span><span class="font-bold">{{ teamsList.find(t=>t.id===sp.value)?.name }}</span></div></template>
                                        <template #option="sp"><div class="flex gap-2 items-center"><span :class="`w-2 h-2 rounded-full bg-${sp.option.color}-500`"></span><span class="font-bold">{{ sp.option.name }}</span></div></template>
                                    </Dropdown>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Intitulé du Poste</label>
                                    <InputText v-model="currentUser.position" placeholder="Ex: Directeur Commercial" class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3 px-4" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Type de Contrat</label>
                                    <Dropdown v-model="currentUser.contract_type" :options="contractTypes" placeholder="Sélectionnez..." class="w-full rounded-2xl border-slate-200" :showClear="true" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Date d'embauche</label>
                                    <Calendar v-model="currentUser.hiring_date" dateFormat="dd/mm/yy" class="w-full" inputClass="rounded-2xl border-slate-200 bg-slate-50 py-3 px-4 w-full" :showIcon="true" placeholder="JJ/MM/AAAA" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Taux Horaire (€/h)</label>
                                    <InputNumber v-model="currentUser.hourly_rate" mode="currency" currency="EUR" locale="fr-FR" placeholder="Ex: 45,00 €" class="w-full" inputClass="rounded-2xl border-slate-200 bg-slate-50 py-3 px-4 w-full" />
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                    <TabPanel header="Notes & Réseaux">
                        <div class="space-y-6 pt-6 px-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-bold text-slate-700">Profil LinkedIn URL</label>
                                <InputGroup class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-500/20">
                                    <InputGroupAddon class="bg-transparent border-0 px-4"><i class="pi pi-linkedin text-blue-600 text-lg"></i></InputGroupAddon>
                                    <InputText v-model="currentUser.linkedin_url" placeholder="https://linkedin.com/in/..." class="border-0 bg-transparent w-full focus:ring-0 shadow-none py-3" />
                                </InputGroup>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-bold text-slate-700">Biographie / Notes RH</label>
                                <Textarea v-model="currentUser.bio" rows="6" class="w-full rounded-2xl border-slate-200 bg-slate-50 p-4 focus:bg-white" placeholder="Parcours, compétences, observations..." />
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full bg-slate-50 -mx-6 -mb-6 p-6 border-t border-slate-200 rounded-b-[2rem]">
                    <span class="text-xs text-slate-400 font-bold tracking-widest uppercase"><span class="text-red-500">*</span> Champs Obligatoires</span>
                    <div class="flex gap-3">
                        <Button label="Annuler" class="p-button-text text-slate-600 font-bold hover:bg-slate-200 rounded-2xl px-6 py-3" @click="userDialog = false" />
                        <Button label="Sauvegarder Profil" icon="pi pi-check" class="bg-indigo-600 border-none hover:bg-indigo-700 shadow-xl shadow-indigo-500/40 font-bold rounded-2xl px-8 py-3" @click="saveUser" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- MODALE ÉQUIPE AVEC CHAMP PARENT -->
        <Dialog v-model:visible="teamDialog" :style="{ width: '600px' }" :modal="true" class="custom-dialog" :closable="false">
            <template #header>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 shadow-inner border border-teal-100"><i class="pi pi-sitemap text-2xl"></i></div>
                    <h2 class="font-black text-2xl text-slate-800">{{ isEditingTeam ? 'Modifier Département' : 'Nouveau Département' }}</h2>
                </div>
            </template>

            <div class="space-y-6 pt-6">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Nom du département <span class="text-red-500">*</span></label>
                    <InputText v-model="currentTeam.name" :class="['w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3', {'p-invalid': formErrors.name}]" placeholder="Ex: Support Technique" />
                    <small v-if="formErrors.name" class="text-red-500 font-bold">{{ formErrors.name }}</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Description des missions</label>
                    <Textarea v-model="currentTeam.description" rows="3" class="w-full rounded-2xl border-slate-200 bg-slate-50 p-4" placeholder="Objectifs et responsabilités..." />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Couleur d'identification <span class="text-red-500">*</span></label>
                    <Dropdown v-model="currentTeam.color" :options="tailwindColors" optionLabel="name" optionValue="value" class="w-full rounded-2xl border-slate-200 py-1 bg-slate-50">
                        <template #value="sp"><div v-if="sp.value" class="flex gap-3 items-center px-2"><span :class="`w-5 h-5 rounded-md shadow-sm bg-${sp.value}-500`"></span><span class="font-bold">{{ tailwindColors.find(c => c.value === sp.value)?.name }}</span></div></template>
                        <template #option="sp"><div class="flex gap-3 items-center"><span :class="`w-5 h-5 rounded-md bg-${sp.option.value}-500`"></span><span class="font-bold">{{ sp.option.name }}</span></div></template>
                    </Dropdown>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Département parent (optionnel)</label>
                    <Dropdown
                        v-model="currentTeam.parent_id"
                        :options="teamsList.filter(t => t.id !== currentTeam.id)"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Aucun parent (département racine)"
                        :showClear="true"
                        class="w-full rounded-2xl border-slate-200"
                    >
                        <template #value="sp">
                            <div v-if="sp.value" class="flex gap-2 items-center">
                                <span :class="`w-2 h-2 rounded-full bg-${teamsList.find(t=>t.id===sp.value)?.color}-500`"></span>
                                <span class="font-bold">{{ teamsList.find(t=>t.id===sp.value)?.name }}</span>
                            </div>
                            <span v-else class="text-slate-500 italic">Aucun parent</span>
                        </template>
                        <template #option="sp">
                            <div class="flex gap-2 items-center">
                                <span :class="`w-2 h-2 rounded-full bg-${sp.option.color}-500`"></span>
                                <span class="font-bold">{{ sp.option.name }}</span>
                            </div>
                        </template>
                    </Dropdown>
                    <small class="text-slate-400 text-xs">Permet de créer une hiérarchie (ex: Direction > Service > Équipe).</small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3 bg-slate-50 -mx-6 -mb-6 p-6 border-t border-slate-200 rounded-b-[2rem]">
                    <Button label="Annuler" class="p-button-text text-slate-600 font-bold hover:bg-slate-200 rounded-2xl px-6 py-3" @click="teamDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-teal-600 border-none hover:bg-teal-700 shadow-xl shadow-teal-500/40 font-bold rounded-2xl px-8 py-3" @click="saveDepartment" />
                </div>
            </template>
        </Dialog>

        <!-- SIDEBAR DÉTAIL UTILISATEUR (inchangé) -->
        <Sidebar v-model:visible="viewUserSidebar" position="right" class="w-full md:w-[30rem] lg:w-[35rem] custom-sidebar">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400">Dossier RH #{{ currentUser.id }}</span>
                </div>
            </template>
            <div v-if="currentUser.id" class="flex flex-col h-full overflow-y-auto pb-10">
                <div class="flex flex-col items-center text-center mt-6 mb-8 px-6">
                    <Avatar v-if="currentUser.avatar_url" :image="currentUser.avatar_url" size="xlarge" shape="circle" class="w-32 h-32 shadow-2xl border-4 border-white mb-4" />
                    <Avatar v-else :label="getInitials(currentUser.name, currentUser.last_name)" size="xlarge" shape="circle" class="w-32 h-32 shadow-2xl border-4 border-white bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 font-black text-4xl mb-4" />
                    <h2 class="text-3xl font-black text-slate-800">{{ getFullName(currentUser) }}</h2>
                    <p class="text-lg font-bold text-indigo-600 mt-1">{{ currentUser.position || 'Poste non défini' }}</p>
                    <div class="mt-4">
                        <Tag v-if="currentUser.team" :value="currentUser.team.name" :class="`bg-${currentUser.team.color}-100 text-${currentUser.team.color}-700 border-${currentUser.team.color}-200 px-3 py-1 text-xs font-bold rounded-lg border`" />
                        <Tag v-else value="Sans département" severity="secondary" />
                    </div>
                </div>
                <div class="px-8 space-y-6">
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-6 shadow-xl shadow-slate-200/40 space-y-4">
                        <div class="flex items-center gap-4 border-b border-slate-50 pb-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-500"><i class="pi pi-envelope text-xl"></i></div>
                            <div><p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Email Principal</p><p class="text-sm font-bold text-slate-800">{{ currentUser.email || '-' }}</p></div>
                        </div>
                        <div class="flex items-center gap-4 border-b border-slate-50 pb-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-500"><i class="pi pi-phone text-xl"></i></div>
                            <div><p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Téléphone</p><p class="text-sm font-bold text-slate-800">{{ currentUser.phone || '-' }}</p></div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-500"><i class="pi pi-file text-xl"></i></div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Contrat & Ancienneté</p>
                                <p class="text-sm font-bold text-slate-800">{{ currentUser.contract_type || 'Indéfini' }} <span v-if="currentUser.hiring_date" class="text-slate-400 font-medium">depuis le {{ formatDate(currentUser.hiring_date) }}</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100/50 rounded-[2rem] border border-indigo-100 p-6 shadow-sm flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-indigo-500"><i class="pi pi-euro text-xl font-bold"></i></div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-indigo-400 tracking-widest">Taux Horaire</p>
                                <p class="text-2xl font-black text-indigo-900">{{ currentUser.hourly_rate || 0 }} <span class="text-sm font-bold text-indigo-600">€/h</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-6 shadow-xl shadow-slate-200/40">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Biographie & Liens</h4>
                        <p class="text-sm text-slate-600 leading-relaxed mb-4">{{ currentUser.bio || 'Aucune biographie fournie pour ce collaborateur.' }}</p>
                        <div v-if="currentUser.linkedin_url" class="flex items-center gap-3 bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                            <i class="pi pi-linkedin text-blue-600 text-xl"></i>
                            <a :href="currentUser.linkedin_url" target="_blank" class="text-sm font-bold text-blue-700 hover:underline">Voir le profil LinkedIn</a>
                        </div>
                        <div v-if="currentUser.provider_name" class="flex items-center gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200 mt-2">
                            <i class="pi pi-key text-slate-400 text-xl"></i>
                            <span class="text-xs font-bold text-slate-500">SSO OAuth : {{ currentUser.provider_name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Sidebar>

        <!-- CONFIRMATION DIALOG -->
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
   STYLES SPECIFIQUES (IDENTIQUES À VOTRE CODE ORIGINAL)
   ========================================================================== */
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

:deep(.custom-main-tabview .p-tabview-nav) { border-bottom: 1px solid #f1f5f9; background: #ffffff; padding: 0 1.5rem; border-radius: 2rem 2rem 0 0; }
:deep(.custom-main-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent; border: none; border-bottom: 3px solid transparent;
    color: #64748b; font-weight: 700; padding: 1.5rem 1.5rem; transition: all 0.3s ease;
}
:deep(.custom-main-tabview .p-tabview-nav li:not(.p-highlight):hover .p-tabview-nav-link) { color: #334155; border-bottom-color: #cbd5e1; }
:deep(.custom-main-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) { color: #4f46e5; border-bottom-color: #4f46e5; }

:deep(.custom-dialog) { border-radius: 2rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); border: 1px solid #e2e8f0; }
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1.5rem 2rem; background: #ffffff; }
:deep(.custom-dialog .p-dialog-content) { padding: 0 2rem 1.5rem 2rem; background: #ffffff; }

:deep(.custom-modal-tabview .p-tabview-nav) { padding: 0; border-bottom: 2px solid #f8fafc; }
:deep(.custom-modal-tabview .p-tabview-nav li .p-tabview-nav-link) { padding: 1rem 1.5rem; font-size: 0.85rem; }

:deep(.custom-sidebar) { border-top-left-radius: 2rem; border-bottom-left-radius: 2rem; background: #f8fafc; }
:deep(.custom-sidebar .p-sidebar-header) { padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; background: transparent; }

:deep(.p-inputtext:focus), :deep(.p-dropdown:focus), :deep(.p-dropdown.p-focus), :deep(.p-calendar:not(.p-calendar-disabled).p-focus > .p-inputtext) {
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #818cf8;
    border-color: #6366f1;
}
</style>
