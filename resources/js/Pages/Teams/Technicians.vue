<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { useI18n } from 'vue-i18n';

// --- COMPOSANTS & LAYOUT ---
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

// PrimeVue Components
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';

import FileUpload from 'primevue/fileupload'; // NOUVELLE IMPORTATION

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const props = defineProps({
    technicians: Object,
    regions: Array,
    filters: Object,
    queryParams: Object,
    // --- NOUVEAU: Propriété pour les erreurs d'importation ---
    import_errors: Array,
});

// --- ÉTAT DU COMPOSANT ---
const selectedTechs = ref([]);
const fileInput = ref(null);
const loading = ref(false);
const isModalOpen = ref(false);
const importDialog = ref(false);
const opColumns = ref();
const visibleColumns = ref(['name', 'fonction', 'region', 'numero']);
// --- FILTRES (Structure robuste du premier code) ---


const filters = ref({
    global: { value: props.filters?.global?.value || null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    fonction: { value: null, matchMode: FilterMatchMode.IN },
    'region.designation': { value: null, matchMode: FilterMatchMode.IN },
    email: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    numero: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },
});

// 2. Lazy Params
const lazyParams = ref({
    first: props.technicians.current_page ? (props.technicians.current_page - 1) * props.technicians.per_page : 0,
    rows: props.technicians.per_page || 10,
    sortField: props.queryParams?.sortField || 'created_at',
    sortOrder: props.queryParams?.sortOrder === 'desc' ? -1 : 1,
    filters: filters.value
});

const fonctionOptions = [
    // --- DIRECTION ET ENCADREMENT (1-7) ---
    { label: 'Directeur d’Exploitation', value: 'Directeur d’Exploitation' },
    { label: 'Directeur Technique', value: 'Directeur Technique' },
    { label: 'Responsable HSE (Hygiène Sécurité Environnement)', value: 'Responsable HSE' },
    { label: 'Superviseur de District / Zone', value: 'Superviseur de District' },
    { label: 'Coordonnateur de Projets Électrification', value: 'Coordonnateur Projets' },
    { label: 'Chef de Département Réseau', value: 'Chef Réseau' },
    { label: 'Responsable Administratif et Financier', value: 'RAF' },

    // --- PRODUCTION HYDROÉLECTRIQUE (8-16) ---
    { label: 'Chef de Centrale Hydroélectrique', value: 'Chef de Centrale' },
    { label: 'Opérateur de Salle de Commande', value: 'Opérateur Salle Commande' },
    { label: 'Technicien de Maintenance Turbine', value: 'Technicien Turbine' },
    { label: 'Électricien de Centrale', value: 'Électricien Centrale' },
    { label: 'Mécanicien de Maintenance Hydraulique', value: 'Mécanicien Hydraulique' },
    { label: 'Barragiste / Gardien de Prise d’Eau', value: 'Barragiste' },
    { label: 'Technicien Hydrologue', value: 'Hydrologue' },
    { label: 'Agent de Maintenance Vannes et Grilles', value: 'Agent Vannes' },
    { label: 'Opérateur de Dégrilleur', value: 'Opérateur Dégrilleur' },

    // --- TRANSPORT ET HAUTE TENSION (17-24) ---
    { label: 'Resp. Exploitation Réseau', value: 'Resp. Exploitation Réseau' },
    { label: 'Resp. Exploitation Réseau Adj.', value: 'Resp. Exploitation Réseau Adj.' },
    { label: 'Superviseur sous-station', value: 'Superviseur sous-station' },
    { label: 'Superviseur Maintenance', value: 'Superviseur Maintenance' },
    { label: 'Superviseur Intervention', value: 'Superviseur Intervention' },
    { label: 'Superviseur raccordement', value: 'Superviseur raccordement' },
    { label: 'Administrateur', value: 'Administrateur' },



    { label: 'Ingénieur Poste de Transformation', value: 'Ingénieur Poste' },
    { label: 'Technicien de Maintenance Haute Tension (HT)', value: 'Technicien HT' },
    { label: 'Monteur de Lignes Haute Tension', value: 'Monteur Lignes HT' },
    { label: 'Technicien Protection et Contrôle-Commande', value: 'Technicien Protection' },
    { label: 'Topographe de Lignes', value: 'Topographe' },
    { label: 'Chef de Chantier HT', value: 'Chef Chantier HT' },
    { label: 'Monteur de Pylônes', value: 'Monteur Pylônes' },
    { label: 'Technicien Postes et Télécoms (SCADA)', value: 'Technicien SCADA' },

    // --- DISTRIBUTION ET BASSE TENSION (25-33) ---
    { label: 'Superviseur de Distribution BT', value: 'Superviseur BT' },
    { label: 'Technicien Chef d’Équipe Réseau', value: 'Chef Équipe Réseau' },
    { label: 'Électricien de Réseau Basse Tension', value: 'Électricien BT' },
    { label: 'Technicien de Maintenance Transformateurs', value: 'Technicien Transfo' },
    { label: 'Technicien de Dépannage Urgence 24/7', value: 'Technicien Dépannage' },
    { label: 'Installateur de Kits Domestiques', value: 'Installateur Kits' },
    { label: 'Vérificateur de Conformité Électrique', value: 'Vérificateur Conformité' },
    { label: 'Chef d’Équipe Éclairage Public', value: 'Chef Éclairage Public' },
    { label: 'Technicien de Relève (Compteurs)', value: 'Technicien de Relève' },

    // --- SERVICE COMMERCIAL ET METERING (34-40) ---
    { label: 'Contrôleur de Raccordement Client', value: 'Contrôleur Raccordement' },
    { label: 'Technicien Poseur de Compteurs Cashpower', value: 'Poseur Compteur' },
    { label: 'Agent de Lutte contre la Fraude Électrique', value: 'Agent Fraude' },
    { label: 'Responsable d’Agence Commerciale', value: 'Chef Agence' },
    { label: 'Agent de Guichet / Facturation', value: 'Agent Guichet' },
    { label: 'Technicien Laboratoire de Compteurs', value: 'Technicien Labo' },
    { label: 'Commercial de Terrain (Prospection)', value: 'Commercial Terrain' },

    // --- TACHERONS ET GÉNIE CIVIL (41-50) ---
    { label: 'Tâcheron - Creuseur de Tranchées', value: 'Tâcheron Creuseur' },
    { label: 'Tâcheron - Poseur de Poteaux', value: 'Tâcheron Poteaux' },
    { label: 'Tâcheron - Éclateur de Roches', value: 'Tâcheron Roches' },
    { label: 'Tâcheron - Maçon de Socles et Caniveaux', value: 'Tâcheron Maçon' },
    { label: 'Tâcheron - Élagueur (Entretien Sous Lignes)', value: 'Tâcheron Élagueur' },
    { label: 'Tâcheron - Transporteur de Matériel (Portage)', value: 'Tâcheron Portage' },
    { label: 'Tâcheron - Aide Électricien (Tirage Câbles)', value: 'Tâcheron Tirage' },
    { label: 'Technicien Journalier (Main d’œuvre)', value: 'Journalier' },
    { label: 'Chef de Brigade Tâcherons', value: 'Chef Brigade' },
    { label: 'Ferrailleur de Génie Civil', value: 'Ferrailleur' },

    // --- LOGISTIQUE, MÉCANIQUE ET SUPPORT (51-60) ---
    { label: 'Mécanicien Engins Lourds', value: 'Mécanicien Engins' },
    { label: 'Chauffeur Poids Lourd / Grue', value: 'Chauffeur Grue' },
    { label: 'Chauffeur de Liaison 4x4', value: 'Chauffeur Liaison' },
    { label: 'Magasinier Matériel Électrique', value: 'Magasinier' },
    { label: 'Soudeur - Chaudronnier', value: 'Soudeur' },
    { label: 'Responsable de Flotte Automobile', value: 'Chef Garage' },
    { label: 'Agent de Sécurité de Site (Sentinelle)', value: 'Agent Sécurité' },
    { label: 'Commis d’Entrepôt', value: 'Commis Entrepôt' },
    { label: 'Informaticien Système & Réseau', value: 'IT' },
    { label: 'Agent de Liaison Administrative', value: 'Liaison Admin' }
];



const allColumns = computed(() => [
    { field: 'name', header: t('technicians.fields.name'), default: true },
    { field: 'fonction', header: t('technicians.fields.fonction'), default: true },
    { field: 'region.designation', header: t('technicians.fields.region'), default: true },
    { field: 'numero', header: t('technicians.fields.numero'), default: true },
    { field: 'email', header: t('technicians.fields.email'), default: false },
    { field: 'pointure', header: t('technicians.fields.pointure'), default: false },
    { field: 'teams', header: t('technicians.fields.teams'), default: false },
    { field: 'size', header: t('technicians.fields.size'), default: false },
    { field: 'created_at', header: t('technicians.fields.created_at'), default: false },
    { field: 'updated_at', header: t('technicians.fields.updated_at'), default: false },
]);

// --- STATISTIQUES ---
const stats = computed(() => {
    const data = props.technicians.data || [];
    return {
        total: props.technicians.total,
        supervisors: data.filter(t => t.fonction === 'Superviseur').length,
        chiefs: data.filter(t => t.fonction === 'Technicien Chef').length,
        daily: data.filter(t => t.fonction === 'Technicien Journalier').length,
    };
});


// --- FORMULAIRE ---
const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    fonction: null,
    numero: '',
   region_id: null,
    pointure: '',
    size: '',
    profile_photo: null,
    profile_photo_preview: null,
    status: 'active'
});

// --- NOUVEAU: Formulaire pour l'importation ---
const importForm = useForm({
    file: null,
});

// --- LOGIQUE PHOTO ---
const triggerFileInput = () => fileInput.value.click();

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Image trop lourde (Max 2MB)', life: 3000 });
            return;
        }
        form.profile_photo = file;
        const reader = new FileReader();
        reader.onload = (event) => {
            form.profile_photo_preview = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    form.profile_photo = null;
    form.profile_photo_preview = null;
    if (fileInput.value) fileInput.value.value = null;
};

// --- ACTIONS CRUD ---
const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.profile_photo_preview = null;
    isModalOpen.value = true;
};

const openEdit = (tech) => {
    form.clearErrors();
    form.id = tech.id;
    form.name = tech.name;
    form.email = tech.email;
    form.fonction = tech.fonction;
    form.numero = tech.numero;
    form.region_id = tech.region_id;
    form.pointure = tech.pointure;
    form.size = tech.size;
    form.profile_photo_preview = tech.profile_photo_url;
    isModalOpen.value = true;
};

const submit = () => {
    const isEditing = !!form.id;

    // Pour les mises à jour (édition), Inertia.js a une particularité avec les fichiers.
    // Si un fichier est présent, il faut utiliser une requête POST avec un champ `_method: 'PUT'`.
    // La méthode `form.submit('put', ...)` peut mal gérer le cas où le fichier est `null` en édition.
    // Nous utilisons donc `router.post` pour plus de contrôle.
    if (isEditing) {
        // On utilise router.post pour les mises à jour afin de gérer correctement le FormData
        router.post(route('technicians.update', form.id), {
            _method: 'put', // Indique à Laravel que c'est une requête PUT
            ...form.data(), // Inclut toutes les données du formulaire
        }, {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.add({ severity: 'success', summary: t('common.success'), detail: t('technicians.toast.updateSuccess'), life: 3000 });
            },
            onError: (errors) => {
                form.errors = errors;
                toast.add({ severity: 'error', summary: t('toast.error'), detail: t('toast.genericError'), life: 3000 });
            }
        });
    } else {
        // Pour la création, la méthode standard fonctionne parfaitement.
        form.post(route('technicians.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: t(isEditing ? 'technicians.toast.updateSuccess' : 'technicians.toast.createSuccess'), life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            form.errors = errors;
            toast.add({ severity: 'error', summary: t('toast.error'), detail: t('toast.genericError'), life: 3000 });
        }
        });
    }
};

// --- NOUVEAU: Watcher pour les filtres ---
watch(filters, () => {
    lazyParams.value.first = 0; // Reset to first page on filter
    lazyParams.value.filters = filters.value;
    loadLazyData();
}, { deep: true });

// --- SUPPRESSION ---
const deleteTech = (tech) => {
    confirm.require({
        message: `Voulez-vous supprimer définitivement ${tech.name} ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('technicians.destroy', tech.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Technicien retiré', life: 3000 })
            });
        }
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedTechs.value.length} techniciens sélectionnés ?`,
        header: 'Suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = selectedTechs.value.map(t => t.id);
            router.post(route('technicians.bulkDestroy'), { ids }, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} techniciens supprimés.`, life: 3000 });
                    selectedTechs.value = [];
                }
            });
        }
    });
};

const exportData = (type) => {
    window.location.href = route('technicians.export', { type, search: filters.value.global.value });
};
const selectedColumns = ref(allColumns.value.filter(col => col.default));

// --- NOUVEAU: Logique pour l'importation ---
const importTechnicians = (event) => {
    const file = event.files[0];
    if (!file) return;

    // On utilise le router d'Inertia pour envoyer le fichier
    router.post(route('technicians.import'), {
        file: file,
    }, {
        onSuccess: () => {
            importDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Importation des techniciens réussie.', life: 3000 });
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: errors.file || 'Une erreur est survenue lors de l\'envoi du fichier.', life: 5000 });
        }
    });
};


// 3. La fonction de chargement UNIQUE
const loadLazyData = (event = null) => {
    loading.value = true;

    // Si l'event vient de la DataTable (onPage), on met à jour lazyParams
    if (event) {
        lazyParams.value = event;
    }

    const queryParams = {
        page: Math.floor(lazyParams.value.first / lazyParams.value.rows) + 1,
        per_page: lazyParams.value.rows,
        sortField: lazyParams.value.sortField,
        sortOrder: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
        // On envoie les filtres simplifiés pour ne pas surcharger l'URL
        search: filters.value.global.value
    };

    router.get(route('technicians.index'), queryParams, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { loading.value = false; }
    });
};

// 4. Les déclencheurs d'événements
const onPage = (event) => {
    loadLazyData(event);
};

const onSort = (event) => {
    loadLazyData(event);
};

// 5. REMPLACEZ VOTRE WATCH PAR UN DEBOUCE (pour éviter la boucle infinie)
// On surveille uniquement le filtre global (champ recherche)
watch(() => filters.value.global.value, (newValue) => {
    // Reset à la première page lors d'une recherche
    lazyParams.value.first = 0;
    loadLazyData();
});
</script>


<template>
    <AppLayout title="Ultimate Technicians Manager">
        <Head :title="t('technicians.title')" />
        <Toast />
        <ConfirmDialog />

        <div class="p-4 md:p-8 bg-[#F8FAFC] min-h-screen">

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="pi pi-users text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ t('technicians.title') }}</h1>
                        <p class="text-slate-500 text-sm">{{ t('technicians.subtitle') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button icon="pi pi-file-excel" severity="secondary" text class="rounded-full" @click="exportData('excel')" v-tooltip.bottom="'Export Excel'" />
                    <Button icon="pi pi-file-pdf" severity="secondary" text class="rounded-full" @click="exportData('pdf')" v-tooltip.bottom="'Export PDF'" />
                    <Button icon="pi pi-upload" severity="secondary" text class="rounded-full" @click="importDialog = true" v-tooltip.bottom="'Importer'" />
                    <div class="h-8 w-[1px] bg-slate-100 mx-2"></div>
                    <Button :label="t('technicians.actions.add')" icon="pi pi-plus"  raised @click="openCreate" class="rounded-lg font-bold" />
                </div>
            </div>



            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t(`technicians.stats.${key}`) }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i class="pi pi-users text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NOUVEAU: Affichage des erreurs d'importation -->
            <div v-if="import_errors && import_errors.length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <h3 class="font-bold">Erreurs lors de la dernière importation :</h3>
                <ul class="list-disc list-inside mt-2 text-sm">
                    <li v-for="(error, index) in import_errors" :key="index">{{ error }}</li>
                </ul>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">

                <DataTable
                  ref="dt"
    :value="technicians.data"
    v-model:selection="selectedTechs"
    v-model:filters="filters"
    dataKey="id"
    :loading="loading"
    :paginator="true"
    :rows="lazyParams.rows"
    :totalRecords="technicians.total"
    :lazy="true"
    @page="onPage($event)"
    @sort="onSort($event)"
    :first="lazyParams.first"
    :sortField="lazyParams.sortField"
    :sortOrder="lazyParams.sortOrder"

    paginatorPosition="bottom"
    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
    :rowsPerPageOptions="[5,10, 20, 50, 100, 500]"
    currentPageReportTemplate="({first} à {last} sur {totalRecords})"

    class="p-datatable-custom shadow-md rounded-2xl overflow-hidden border border-slate-200"
    filterDisplay="menu"
    :globalFilterFields="['name', 'email', 'fonction', 'numero']"
                >
                    <template #header>
                        <div class="flex flex-wrap justify-between items-center gap-4 p-2 w-full">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('technicians.toolbar.searchPlaceholder')" class="w-full md:w-80 border-none bg-slate-50 rounded-xl" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header"
                                :placeholder="t('common.columns')" display="chip" class="w-full md:w-64 border-none bg-slate-50 rounded-xl" />
                       <Button v-if="selectedTechs.length" :label="t('technicians.actions.deleteSelected')" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected"
                                        class="p-button-sm rounded-xl" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-for="col in selectedColumns" :key="col.field" :field="col.field" :header="col.header" sortable filter>
                         <template #body="{ data, field }">
                            <template v-if="field === 'name'">
                                <div class="flex items-center gap-5 group cursor-pointer" @click="openEdit(data)">
                                    <div class="relative">
                                        <Avatar :image="data.profile_photo_url || null" :label="data.profile_photo_url ? '' : data.name?.charAt(0) || 'U'" shape="circle" size="xlarge"
                                            class="shadow-lg" :class="{'bg-slate-200 text-slate-700': !data.profile_photo_url}" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800">{{ data.name }}</span>
                                        <span class="text-xs text-slate-500">{{ data.email }}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-else-if="field === 'fonction'">
                                <Tag :value="data.fonction" severity="secondary" >
                                  <template #default></template>
                                </Tag>
                            </template>
                            <template v-else-if="field === 'region.designation'">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-map-marker text-gray-400"></i>
                                    <span class="font-semibold text-slate-600">{{ data.region?.designation ?? "" }}</span>
                                </div>
                            </template>
                            <template v-else-if="field === 'numero'">
                                <span class="font-mono text-sm">{{ data.numero }}</span>
                            </template>
                            <template v-else-if="field === 'teams'">
                                <div class="flex flex-wrap gap-1">
                                    <Tag v-for="team in data.teams" :key="team.id" :value="team.name" severity="info" />
                                </div>
                            </template>
                            <template v-else>
                                {{ data[field] }}
                            </template>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" class="hover:bg-blue-50" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteTech(data)" class="hover:bg-red-50" />
                            </div>
                        </template>
                    </Column>

                </DataTable>
            </div>
        </div>


        <Dialog v-model:visible="isModalOpen" modal position="right" :header="false" :closable="false"
            :style="{ width: '60vw' }" class="v11-dialog-ultimate" :draggable="false">
     <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-user-plus text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ form.id ? t('technicians.editTitle') : t('technicians.createTitle') }}</h4>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isModalOpen = false" class="text-white hover:bg-white/10" />
            </div>
            <form @submit.prevent="submit" class="p-4 space-y-8">
                <div class="grid grid-cols-12 gap-10">

                 <div class="col-span-12 md:col-span-4">
        <div class="sticky top-0">
            <div class="relative group bg-white rounded-[2.5rem] p-3 border border-slate-200 shadow-2xl transition-all duration-500 hover:border-primary-300">

               <div class="relative w-full aspect-square overflow-hidden rounded-[2.5rem] bg-slate-100 shadow-2xl border-4 border-white group">

    <div
        class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 group-hover:scale-110"
        :style="{
            backgroundImage: form.profile_photo_preview ? `url(${form.profile_photo_preview})` : 'none'
        }"
    >
        <div v-if="!form.profile_photo_preview" class="w-full h-full flex items-center justify-center">
            <span class="text-[14rem] font-black text-slate-200 uppercase select-none">
                {{ form.name ? form.name[0] : 'VE' }}
            </span>
        </div>
    </div>

    <div
        @click="triggerFileInput"
        class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-all duration-300 cursor-pointer backdrop-blur-[2px] z-10"
    >
        <div class="w-24 h-24 bg-white/95 text-slate-900 rounded-full flex items-center justify-center shadow-2xl scale-75 group-hover:scale-100 transition-transform duration-500 border-8 border-primary-500/10">
            <i class="pi pi-camera text-4xl"></i>
        </div>
    </div>

    <button
        v-if="form.profile_photo_preview"
        type="button"
        @click.stop="removePhoto"
        class="absolute top-5 right-5 w-12 h-12 bg-white/90 text-red-500 rounded-2xl flex items-center justify-center shadow-xl hover:bg-red-500 hover:text-white hover:scale-110 transition-all z-20 border border-white/50 backdrop-blur-md"
        v-tooltip.left="'Supprimer la photo'"
    >
        <i class="pi pi-trash text-xl"></i>
    </button>

</div>

                <input type="file" ref="fileInput" class="hidden" @change="handlePhotoChange" accept="image/*" />

                <div class="py-6 text-center">
                    <h3 class="font-black text-slate-900 text-2xl tracking-tighter leading-none mb-2">
                        {{ form.name || 'Nouveau Profil' }}
                    </h3>
                    <span class="text-[10px] font-black text-primary-600 uppercase tracking-[0.3em] bg-primary-50 px-4 py-1.5 rounded-full">
                        Identité Visuelle
                    </span>
                </div>
            </div>

            <div class="mt-4 p-5 bg-slate-900 rounded-[2rem] shadow-xl border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative flex items-center justify-center">
                            <div class="absolute w-full h-full bg-emerald-50/20 rounded-full animate-ping"></div>
                            <div class="w-3 h-3 bg-emerald-400 rounded-full relative shadow-[0_0_15px_rgba(52,211,153,1)]"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Compte Actif</span>
                    </div>
                    <i class="pi pi-verified text-emerald-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

                    <div class="col-span-12 md:col-span-8 space-y-8">
                        <div>
                            <span class="v11-header-label">Informations Générales</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="v11-label">Nom complet</label>
                                    <InputText id="name" v-model="form.name" class="" placeholder="ex: Jean Dupont" required />
                                    <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="email" class="v11-label">Email Professionnel</label>
                                    <InputText id="email" v-model="form.email" type="email" class="" placeholder="tech@entreprise.com" required />
                                    <small class="p-error" v-if="form.errors.email">{{ form.errors.email }}</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="v11-header-label">Affectation & Rôle</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="fonction" class="v11-label">Fonction</label>
                                    <Dropdown v-model="form.fonction" :options="fonctionOptions" optionLabel="label" optionValue="value" filter showClear
                                        placeholder="Sélectionner un rôle" class="v11-dropdown-ultimate" id="fonction" />
                                    <small class="p-error" v-if="form.errors.fonction">{{ form.errors.fonction }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="region" class="v11-label">Région</label>
                                    <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id"
                                        placeholder="Zone d'intervention" class="v11-dropdown-ultimate" id="region" />
                                    <small class="p-error" v-if="form.errors.region_id">{{ form.errors.region_id }}</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="v11-header-label">Données de Sécurité (EPI)</span>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                <div class="flex flex-col gap-2">
                                    <label for="numero" class="v11-label">Téléphone</label>
                                    <InputText id="numero" v-model="form.numero" class=" !bg-white" />
                                    <small class="p-error" v-if="form.errors.numero">{{ form.errors.numero }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="pointure" class="v11-label">Pointure</label>
                                    <InputText id="pointure" v-model="form.pointure" class=" !bg-white" />
                                    <small class="p-error" v-if="form.errors.pointure">{{ form.errors.pointure }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="size" class="v11-label">Taille (Vêtement)</label>
                                    <InputText id="size" v-model="form.size" class=" !bg-white" />
                                    <small class="p-error" v-if="form.errors.size">{{ form.errors.size }}</small>
                                </div>
                            </div>
                        </div>

                        <div v-if="!form.id" class="p-6 bg-blue-50/30 rounded-3xl border border-blue-100">
                            <span class="v11-header-label !text-blue-700">Sécurité du compte</span>
                            <div class="grid grid-cols-2 gap-6 mt-4">
                                <InputText v-model="form.password" type="password" placeholder="Mot de passe" class=" !bg-white" />
                                <small class="p-error" v-if="form.errors.password">{{ form.errors.password }}</small>
                                <InputText v-model="form.password_confirmation" type="password" placeholder="Confirmer" class=" !bg-white" />
                                <small class="p-error" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</small>
                            </div>
                        </div>
                    </div>
                </div>
 <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="isModalOpen = false" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="form.id ? t('common.save') : t('common.create')"  icon="pi pi-check-circle"
                        class="px-10 h-14 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                        @click="submit" :loading="form.processing" />
            </div>


            </form>
        </Dialog>

        <Dialog v-model:visible="importDialog" modal header="Importer des Techniciens" :style="{ width: '450px' }">
            <div class="flex flex-col gap-4">
                <p class="text-slate-600">
                    Sélectionnez un fichier CSV ou Excel. Assurez-vous que le fichier contient les colonnes :
                    <code class="bg-slate-100 text-slate-800 p-1 rounded">name</code>,
                    <code class="bg-slate-100 text-slate-800 p-1 rounded">email</code>,
                    <code class="bg-slate-100 text-slate-800 p-1 rounded">fonction</code>,
                    <code class="bg-slate-100 text-slate-800 p-1 rounded">region</code>.
                </p>
                <FileUpload
                    name="file"
                    :multiple="false"
                    accept=".xlsx,.xls,.csv,.txt"
                    :maxFileSize="1000000"
                    chooseLabel="Choisir un fichier"
                    :auto="true"
                    customUpload
                    @uploader="importTechnicians"
                >
                    <template #empty>
                        <p>Glissez-déposez un fichier ici pour le téléverser.</p>
                    </template>
                </FileUpload>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style lang="scss">
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-6 px-4 border-b border-slate-100;
    }
    .p-datatable-tbody > tr {
        @apply border-b border-slate-50;
        &:hover { @apply bg-slate-50/50; }
    }
}
/* Scrollbar invisible pour le look moderne */
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
