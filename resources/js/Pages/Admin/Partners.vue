<template>
    <AppLayout>
        <Head title="Partenaires - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12 font-sans">
            <!-- ================================================================== -->
            <!-- HEADER HERO : Thème Emerald / Teal pour le module Partenaires      -->
            <!-- ================================================================== -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden shadow-2xl">
                <!-- Dégradés et effets de lumière de fond -->
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/60 to-teal-900/60 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-[30rem] h-[30rem] bg-emerald-500 rounded-full blur-[120px] opacity-30 pointer-events-none animate-pulse"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-[25rem] h-[25rem] bg-teal-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6 mt-4">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <Badge value="Module Partenaires" class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[11px] font-bold tracking-widest px-3 py-1" />
                            <span class="text-emerald-400/60 text-sm flex items-center gap-1"><i class="pi pi-check-circle"></i> En ligne</span>
                        </div>
                        <h1 class="text-4xl lg:text-6xl font-black text-white tracking-tight">Partenaires</h1>
                        <p class="text-slate-300 mt-3 text-lg max-w-2xl font-light leading-relaxed">
                            Gérez vos partenaires commerciaux et institutionnels. Uploadez leurs logos, ajoutez leurs liens et définissez leur ordre d'affichage public via un simple glisser‑déposer.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================================================================== -->
            <!-- CONTENU PRINCIPAL : Tableau et Filtres                             -->
            <!-- ================================================================== -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-16 relative z-20">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">

                    <!-- BARRE D'OUTILS (Recherche & Action) -->
                    <div class="p-5 lg:p-8 bg-slate-50/80 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 backdrop-blur-sm">
                        <div class="w-full sm:w-80 relative flex items-center group">
                            <i class="pi pi-search text-slate-400 absolute left-4 z-10 transition-colors group-focus-within:text-emerald-500 text-lg" />
                            <InputText
                                v-model="search"
                                placeholder="Rechercher un partenaire par nom..."
                                class="w-full rounded-2xl bg-white border-slate-200 pl-12 pr-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all font-medium text-slate-700"
                                @keyup.enter="performSearch"
                            />
                            <button v-if="search" @click="clearSearch" class="absolute right-4 z-10 text-slate-400 hover:text-slate-600">
                                <i class="pi pi-times-circle text-lg"></i>
                            </button>
                        </div>

                        <Button
                            icon="pi pi-plus"
                            label="Nouveau partenaire"
                            class="bg-emerald-500 hover:bg-emerald-600 border-none shadow-lg shadow-emerald-500/30 text-white font-bold w-full sm:w-auto px-6 py-3 rounded-2xl transition-transform hover:-translate-y-0.5"
                            @click="openNewPartner"
                        />
                    </div>

                    <!-- TABLEAU DES DONNÉES -->
                    <DataTable :value="partnersList" v-model:selection="selectedPartners" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-20 text-center bg-slate-50/50">
                                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-sm relative">
                                    <div class="absolute inset-0 bg-emerald-500/10 rounded-full animate-ping opacity-20"></div>
                                    <i class="pi pi-users text-4xl text-emerald-400"></i>
                                </div>
                                <h3 class="text-xl font-black text-slate-800 mb-2">Aucun partenaire enregistré</h3>
                                <p class="text-slate-500 max-w-md mb-8 leading-relaxed">Démarrez en ajoutant votre premier partenaire. Il apparaîtra immédiatement dans la section dédiée de votre site public.</p>
                                <Button label="Créer mon premier partenaire" icon="pi pi-plus" class="bg-slate-900 border-none hover:bg-slate-800 text-white px-6 py-3 rounded-xl shadow-lg" @click="openNewPartner" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 4rem" :reorderableColumn="false" />

                        <Column field="order" header="Ordre" sortable style="min-width: 6rem">
                            <template #body="{ data }">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200">
                                    <span class="text-slate-700 font-bold text-sm">{{ data.order }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="logo" header="Logo" style="min-width: 8rem">
                            <template #body="{ data }">

                                <div class="w-16 h-16 rounded-xl border border-slate-100 bg-white shadow-sm flex items-center justify-center p-2 relative group overflow-hidden">
                                    <img v-if="data.logo_url" :src="`${data.logo_url}`" class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-110" alt="Logo" />
                                    <i v-else class="pi pi-image text-slate-300 text-xl"></i>
                                </div>
                            </template>
                        </Column>

                        <Column field="name" header="Identité du Partenaire" sortable style="min-width: 16rem">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800 text-base mb-1">{{ data.name }}</span>
                                    <span class="text-slate-500 text-xs line-clamp-1">{{ data.description || 'Aucune description fournie' }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="website" header="Site web" style="min-width: 14rem">
                            <template #body="{ data }">
                                <a v-if="data.website" :href="data.website" target="_blank" class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border border-emerald-100 hover:border-emerald-200">
                                    <i class="pi pi-external-link text-xs"></i>
                                    {{ data.website.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
                                </a>
                                <span v-else class="text-slate-400 text-sm italic bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100 inline-block">Non renseigné</span>
                            </template>
                        </Column>

                        <Column field="is_active" header="Visibilité" sortable style="min-width: 10rem; text-align: center;">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? 'Public' : 'Masqué'" class="px-4 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase border" :class="data.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200'" />
                            </template>
                        </Column>

                        <Column :exportable="false" style="min-width: 12rem; text-align: right;">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info bg-blue-50 text-blue-600 hover:bg-blue-100 w-10 h-10 transition-colors" @click="editPartner(data)" v-tooltip.top="'Modifier ce partenaire'" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger bg-red-50 text-red-600 hover:bg-red-100 w-10 h-10 transition-colors" @click="confirmDeletePartner(data)" v-tooltip.top="'Supprimer ce partenaire'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- ================================================================== -->
        <!-- MODALE (DIALOG) : CRÉATION ET ÉDITION                              -->
        <!-- ================================================================== -->
        <Dialog v-model:visible="partnerDialog" :style="{ width: '1000px', maxWidth: '95vw' }" :modal="true" class="custom-dialog" :closable="false">

            <!-- HEADER MODALE -->
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-center text-emerald-600 shadow-sm relative overflow-hidden">
                            <div class="absolute inset-0 bg-white/40"></div>
                            <i class="pi pi-briefcase text-2xl relative z-10"></i>
                        </div>
                        <div>
                            <h2 class="font-black text-2xl text-slate-800 tracking-tight">{{ isEditing ? 'Modifier le partenaire' : 'Nouveau partenaire' }}</h2>
                            <p class="text-sm text-slate-500 font-medium mt-0.5">{{ isEditing ? 'Mettez à jour les informations et le logo' : 'Remplissez les informations pour ajouter un partenaire' }}</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100 text-slate-500 w-10 h-10 transition-colors" @click="partnerDialog = false" />
                </div>
            </template>

            <!-- CONTENU MODALE : GRILLE 12 COLONNES -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-6">

                <!-- COLONNE GAUCHE (7/12) : Textes et Liens -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-slate-50/60 p-6 rounded-3xl border border-slate-100 shadow-sm space-y-6">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-2">
                            <i class="pi pi-align-left text-slate-300"></i> Informations générales
                        </h3>

                        <!-- Nom du partenaire -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                Nom de l'organisation <span class="text-emerald-500 font-black text-lg">*</span>
                            </label>
                            <InputText
                                v-model="form.name"
                                autofocus
                                :class="{ 'border-red-400 focus:border-red-500 focus:ring-red-500 bg-red-50/30': submitted && !form.name }"
                                class="w-full rounded-2xl bg-white shadow-sm p-4 text-base transition-colors"
                                placeholder="Ex: Microsoft, Unicef, Acme Corp..."
                            />
                            <small v-if="submitted && !form.name" class="text-red-500 font-bold flex items-center gap-1 mt-1"><i class="pi pi-exclamation-circle text-xs"></i> Le nom est obligatoire.</small>
                        </div>

                        <!-- Site web -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Site Web officiel</label>
                            <span class="p-input-icon-left w-full relative">
                                <i class="pi pi-globe text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" />
                                <InputText
                                    v-model="form.website"
                                    class="w-full rounded-2xl bg-white shadow-sm p-4 pl-12 text-base transition-colors"
                                    placeholder="https://www.exemple.com"
                                />
                            </span>
                        </div>

                        <!-- Description -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700 flex justify-between items-end">
                                <span>Description de la collaboration</span>
                                <span class="text-xs text-slate-400 font-normal">Optionnel</span>
                            </label>
                            <Textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full rounded-2xl bg-white shadow-sm p-4 resize-none transition-colors"
                                placeholder="Expliquez brièvement le rôle de ce partenaire, le type de collaboration, etc."
                            />
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE (5/12) : Logo et Statut -->
                <div class="lg:col-span-5 space-y-6">

                    <!-- Section Visibilité -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between group hover:border-emerald-200 transition-colors">
                        <div>
                            <label class="text-sm font-black text-slate-800 block mb-1">Affichage Public</label>
                            <p class="text-xs text-slate-500 leading-tight pr-4">Activez pour afficher ce partenaire sur le site public.</p>
                        </div>
                        <ToggleButton
                            v-model="form.is_active"
                            onLabel="En Ligne"
                            offLabel="Masqué"
                            onIcon="pi pi-eye"
                            offIcon="pi pi-eye-slash"
                            class="w-36 rounded-xl shadow-sm transition-all"
                            :class="form.is_active ? 'p-button-success bg-emerald-500 border-emerald-500' : 'p-button-secondary bg-slate-200 text-slate-600 border-slate-200'"
                        />
                    </div>

                    <!-- Section Logo Dropzone -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <i class="pi pi-image text-slate-300"></i> Identité visuelle (Logo)
                        </h3>

                        <!-- Zone de Drop / Click -->
                        <div
                            class="relative flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-2xl transition-all duration-300 group overflow-hidden"
                            :class="[
                                (logoPreview || (form.existing_logo_url && !form.delete_logo)) ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-300 bg-slate-50 hover:bg-slate-100 hover:border-emerald-400 cursor-pointer'
                            ]"
                            @click="!logoPreview && !(form.existing_logo_url && !form.delete_logo) ? $refs.fileInput.click() : null"
                        >
                            <input type="file" accept="image/*" @change="onLogoSelected" ref="fileInput" class="hidden" />

                            <!-- État 1 : Vide -->
                            <div v-if="!logoPreview && !(form.existing_logo_url && !form.delete_logo)" class="flex flex-col items-center justify-center p-6 text-center">
                                <div class="w-14 h-14 bg-white rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                                    <i class="pi pi-cloud-upload text-2xl text-emerald-500"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Cliquez pour importer un logo</p>
                                <p class="text-xs text-slate-400 mt-1">PNG, JPG ou WebP (Max 2 Mo)</p>
                                <p class="text-[10px] text-slate-400 mt-2 bg-white px-2 py-1 rounded border border-slate-100">Transparence recommandée</p>
                            </div>

                            <!-- État 2 : Image chargée (Prévisualisation) -->
                            <div v-else class="relative w-full h-full p-4 flex items-center justify-center">
                                <!-- Grille de transparence en fond -->
                                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 10px 10px;"></div>

                                <img :src="logoPreview || form.existing_logo_url" class="max-h-full max-w-full object-contain relative z-10 drop-shadow-md" />

                                <!-- Boutons d'action sur l'image -->
                                <div class="absolute top-3 right-3 z-20 flex gap-2">
                                    <button type="button" @click="$refs.fileInput.click()" class="w-9 h-9 bg-white text-blue-500 rounded-full border border-slate-200 shadow-lg flex items-center justify-center hover:bg-blue-50 transition-colors" v-tooltip.top="'Changer l\'image'">
                                        <i class="pi pi-sync text-sm font-bold"></i>
                                    </button>
                                    <button type="button" @click="removeLogo" class="w-9 h-9 bg-white text-red-500 rounded-full border border-slate-200 shadow-lg flex items-center justify-center hover:bg-red-50 transition-colors" v-tooltip.top="'Supprimer l\'image'">
                                        <i class="pi pi-trash text-sm font-bold"></i>
                                    </button>
                                </div>

                                <!-- Indicateur de source -->
                                <div class="absolute bottom-3 left-3 z-20">
                                    <span class="bg-slate-900/80 text-white text-[10px] font-bold px-2 py-1 rounded-md backdrop-blur-md shadow-sm border border-slate-700">
                                        {{ logoPreview ? 'Nouvelle image' : 'Image actuelle' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER MODALE -->
            <template #footer>
                <div class="flex items-center justify-between border-t border-slate-100 pt-6 mt-4 bg-white w-full">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1">
                        <span class="text-emerald-500 text-lg font-black">*</span> Champs obligatoires
                    </span>
                    <div class="flex gap-3">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-100 px-6 py-3 rounded-xl transition-colors" @click="partnerDialog = false" />
                        <Button label="Enregistrer le partenaire" icon="pi pi-check" class="bg-emerald-500 border-none hover:bg-emerald-600 shadow-lg shadow-emerald-500/30 font-bold px-8 py-3 rounded-xl text-white transition-transform hover:-translate-y-0.5" @click="savePartner" :loading="saving" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- MODALE DE CONFIRMATION GLOBALE (Suppression) -->
        <ConfirmDialog>
            <template #message="slotProps">
                <div class="flex flex-col items-center w-full gap-4 pt-4 pb-2 text-center">
                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-2">
                        <i :class="slotProps.message.icon" class="text-3xl"></i>
                    </div>
                    <p class="text-slate-800 font-bold text-lg m-0">{{ slotProps.message.header }}</p>
                    <p class="text-slate-500 text-sm m-0">{{ slotProps.message.message }}</p>
                </div>
            </template>
        </ConfirmDialog>

    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';

// Imports des composants PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';

// Initialisations globales
const toast = useToast();
const confirm = useConfirm();
const page = usePage();

// Définition des Props envoyées par le contrôleur Laravel/Inertia
const props = defineProps({
    partners: [Array, Object],
    filters: Object,
});

// -------------------------------------------------------------
// GESTION DES DONNÉES & RECHERCHE
// -------------------------------------------------------------
const search = ref(props.filters?.search || '');
const partnersList = computed(() => props.partners?.data ?? props.partners ?? []);
const selectedPartners = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Lancer la recherche globale via Inertia
const performSearch = () => {
    router.get(route('partners.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Vider le champ de recherche
const clearSearch = () => {
    search.value = '';
    performSearch();
};

// -------------------------------------------------------------
// ÉTATS DE LA MODALE & FORMULAIRE
// -------------------------------------------------------------
const partnerDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

const defaultPartner = {
    id: null,
    name: '',
    logo: null,
    existing_logo_url: null,
    website: '',
    description: '',
    is_active: true,
    delete_logo: false
};

const form = reactive({ ...defaultPartner });

// -------------------------------------------------------------
// GESTION DU LOGO (UPLOAD ET PREVIEW)
// -------------------------------------------------------------
const logoPreview = ref(null);
const logoFileName = ref('');
const fileInput = ref(null);

const onLogoSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validation basique côté client (Format et Taille)
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (!validTypes.includes(file.type)) {
        toast.add({ severity: 'error', summary: 'Format invalide', detail: 'Seules les images (JPG, PNG, WebP, SVG) sont acceptées.', life: 5000 });
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    const maxSizeInMB = 2;
    if (file.size > maxSizeInMB * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Fichier trop lourd', detail: `La taille maximale autorisée est de ${maxSizeInMB} Mo.`, life: 5000 });
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    form.logo = file;
    logoFileName.value = file.name;

    // Génération de l'aperçu Base64
    const reader = new FileReader();
    reader.onload = (e) => {
        logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    logoFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';

    // Marquer la suppression pour le backend si on est en édition
    if (isEditing.value && form.existing_logo_url) {
        form.delete_logo = true;
    }
};

// -------------------------------------------------------------
// ACTIONS DU CRUD
// -------------------------------------------------------------

// Ouvrir modale de Création
const openNewPartner = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultPartner)));
    submitted.value = false;
    isEditing.value = false;
    logoPreview.value = null;
    logoFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    partnerDialog.value = true;
};

// Ouvrir modale d'Édition
const editPartner = (partner) => {
    Object.assign(form, {
        id: partner.id,
        name: partner.name,
        logo: null,
        existing_logo_url: partner.logo_url,
        website: partner.website,
        description: partner.description || '',
        is_active: partner.is_active,
        delete_logo: false
    });
    logoPreview.value = null;
    logoFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    isEditing.value = true;
    partnerDialog.value = true;
};

// Enregistrer les données (POST/PUT via FormData)
const savePartner = () => {
    submitted.value = true;

    // Validation stricte
    if (!form.name?.trim()) {
        toast.add({ severity: 'warn', summary: 'Attention', detail: 'Le champ Nom est obligatoire.', life: 3000 });
        return;
    }

    saving.value = true;

    // Construction du payload Multipart
    const formData = new FormData();
    formData.append('name', form.name.trim());
    formData.append('website', form.website ? form.website.trim() : '');
    formData.append('description', form.description ? form.description.trim() : '');
    formData.append('is_active', form.is_active ? 1 : 0);

    if (form.logo instanceof File) {
        formData.append('logo', form.logo);
    }

    const requestOptions = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Opération réussie', detail: isEditing.value ? 'Le partenaire a été mis à jour.' : 'Le nouveau partenaire a été créé.', life: 4000 });
            partnerDialog.value = false;
            saving.value = false;
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat()[0] || 'Une erreur inattendue s\'est produite.';
            toast.add({ severity: 'error', summary: 'Erreur d\'enregistrement', detail: errorMsg, life: 5000 });
            saving.value = false;
        }
    };

    if (isEditing.value) {
        formData.append('_method', 'PUT'); // Fake PUT method for Laravel multipart form data
        if (form.delete_logo) {
            formData.append('delete_logo', '1');
        }
        router.post(route('partners.update', form.id), formData, requestOptions);
    } else {
        router.post(route('partners.store'), formData, requestOptions);
    }
};

// Confirmer et exécuter la suppression
const confirmDeletePartner = (partner) => {
    confirm.require({
        header: 'Supprimer ce partenaire ?',
        message: `Toutes les informations et le logo liés à "${partner.name}" seront définitivement effacés. Cette action est irréversible.`,
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger px-6 py-2 rounded-xl bg-red-500 hover:bg-red-600 border-none shadow-md',
        rejectClass: 'p-button-text p-button-secondary',
        acceptLabel: 'Oui, supprimer',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('partners.destroy', partner.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Le partenaire a été retiré de la base de données.', life: 4000 }),
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de supprimer ce partenaire pour le moment.' })
            });
        }
    });
};

// Gestion du Drag & Drop pour le réordonnancement (Order)
const onRowReorder = (event) => {
    const newOrderIds = event.value.map(p => p.id);

    // Requête XHR silencieuse pour mettre à jour l'ordre
    router.post(route('partners.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'info', summary: 'Nouvel ordre enregistré', detail: 'L\'affichage public a été mis à jour.', life: 2500 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le réordonnancement a échoué. Veuillez rafraîchir la page.' })
    });
};

// Écouteur global pour les messages flash de Laravel (Session)
watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) toast.add({ severity: 'success', summary: 'Succès', detail: newVal, life: 4000 });
});
</script>

<style scoped>
/* ==========================================================================
   STYLES DU TABLEAU (DATATABLE PRIMEVUE)
   ========================================================================== */
:deep(.custom-table) {
    font-family: inherit;
}
:deep(.custom-table .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
    border-top: 1px solid #e2e8f0;
}
:deep(.custom-table .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.custom-table .p-datatable-tbody > tr:hover) {
    background-color: #f1f5f9 !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

/* Icône Drag and Drop (Poignée) */
:deep(.p-datatable .p-datatable-tbody > tr > td .p-row-reorder-icon) {
    color: #cbd5e1;
    font-size: 1.25rem;
    transition: all 0.2s;
    cursor: grab;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover .p-row-reorder-icon) {
    color: #94a3b8;
}
:deep(.p-datatable .p-datatable-tbody > tr .p-row-reorder-icon:active) {
    cursor: grabbing;
    transform: scale(1.1);
    color: #10b981; /* Emerald 500 sur le grab actif */
}

/* ==========================================================================
   STYLES DE LA MODALE (DIALOG PRIMEVUE)
   ========================================================================== */
:deep(.custom-dialog .p-dialog-header) {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem 2rem;
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
}
:deep(.custom-dialog .p-dialog-content) {
    background: #ffffff;
    padding: 0 2rem 1.5rem 2rem;
}
:deep(.custom-dialog .p-dialog-footer) {
    background: #ffffff;
    padding: 0 2rem 1.5rem 2rem;
    border-bottom-left-radius: 1.5rem;
    border-bottom-right-radius: 1.5rem;
}

/* ==========================================================================
   MODIFICATEURS ET UTILITAIRES CSS
   ========================================================================== */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Scrollbar minimaliste pour la modale si le contenu déborde */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Animations génériques */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.dialog-enter-active {
    animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
