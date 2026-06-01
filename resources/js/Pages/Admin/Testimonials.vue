<template>
    <AppLayout>
        <Head title="Témoignages - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12 font-sans">
            <!-- ================================================================== -->
            <!-- HEADER HERO : Thème Amber / Orange                                 -->
            <!-- ================================================================== -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-900/60 to-orange-900/60 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-[30rem] h-[30rem] bg-amber-500 rounded-full blur-[120px] opacity-30 pointer-events-none animate-pulse"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-[25rem] h-[25rem] bg-orange-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6 mt-4">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <Badge value="Module Témoignages" class="bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono text-[11px] font-bold tracking-widest px-3 py-1" />
                            <span class="text-amber-400/60 text-sm flex items-center gap-1"><i class="pi pi-star-fill"></i> Social Proof</span>
                        </div>
                        <h1 class="text-4xl lg:text-6xl font-black text-white tracking-tight">Témoignages</h1>
                        <p class="text-slate-300 mt-3 text-lg max-w-2xl font-light leading-relaxed">
                            Mettez en valeur les retours de vos clients. Gérez les avis, les avatars et définissez l'ordre d'affichage (glisser‑déposer) pour maximiser votre preuve sociale.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================================================================== -->
            <!-- CONTENU PRINCIPAL : Tableau et Barre d'outils                      -->
            <!-- ================================================================== -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-16 relative z-20">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">

                    <!-- BARRE D'OUTILS -->
                    <div class="p-5 lg:p-8 bg-slate-50/80 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 backdrop-blur-sm">
                        <div class="w-full sm:w-96 relative flex items-center group">
                            <i class="pi pi-search text-slate-400 absolute left-4 z-10 transition-colors group-focus-within:text-amber-500 text-lg" />
                            <InputText
                                v-model="search"
                                placeholder="Rechercher par nom ou entreprise..."
                                class="w-full rounded-2xl bg-white border-slate-200 pl-12 pr-10 py-3 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-all font-medium text-slate-700"
                                @keyup.enter="performSearch"
                            />
                            <button v-if="search" @click="clearSearch" class="absolute right-4 z-10 text-slate-400 hover:text-slate-600 transition-colors">
                                <i class="pi pi-times-circle text-lg"></i>
                            </button>
                        </div>

                        <Button
                            icon="pi pi-plus"
                            label="Nouveau témoignage"
                            class="bg-amber-500 hover:bg-amber-600 border-none shadow-lg shadow-amber-500/30 text-white font-bold w-full sm:w-auto px-6 py-3 rounded-2xl transition-transform hover:-translate-y-0.5"
                            @click="openNewTestimonial"
                        />
                    </div>

                    <!-- TABLEAU -->
                    <DataTable :value="testimonialsList" v-model:selection="selectedTestimonials" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-20 text-center bg-slate-50/50">
                                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-sm relative">
                                    <div class="absolute inset-0 bg-amber-500/10 rounded-full animate-ping opacity-20"></div>
                                    <i class="pi pi-star text-4xl text-amber-400"></i>
                                </div>
                                <h3 class="text-xl font-black text-slate-800 mb-2">Aucun témoignage</h3>
                                <p class="text-slate-500 max-w-md mb-8 leading-relaxed">Ajoutez les retours d'expérience de vos meilleurs clients pour renforcer la confiance de vos visiteurs.</p>
                                <Button label="Ajouter mon premier avis" icon="pi pi-plus" class="bg-slate-900 border-none hover:bg-slate-800 text-white px-6 py-3 rounded-xl shadow-lg" @click="openNewTestimonial" />
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

                        <Column field="name" header="Auteur" sortable style="min-width: 18rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full border border-slate-200 bg-white shadow-sm flex-shrink-0 overflow-hidden">
                                        <!-- Utilisation sécurisée de l'avatar et du nom -->
                                        <img v-if="data.avatar_url || data.avatar" :src="data.avatar_url || `/storage/${data.avatar}`" class="w-full h-full object-cover" alt="Avatar" />
                                        <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-lg">
                                            {{ getInitials(data) }}
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 text-base">{{ data.name || data.author || 'Anonyme' }}</span>
                                        <span class="text-slate-500 text-xs">
                                            {{ data.position || data.role || 'Client' }} <span v-if="data.company" class="text-amber-600 font-medium">@ {{ data.company }}</span>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="content" header="Message" style="min-width: 20rem">
                            <template #body="{ data }">
                                <span class="text-slate-600 text-sm line-clamp-2 italic pr-4">"{{ data.content }}"</span>
                            </template>
                        </Column>

                        <!-- Sécurité si "rating" n'existe pas dans la BD -->
                        <Column field="rating" header="Note" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <Rating :modelValue="data.rating || 5" readonly :cancel="false" class="text-amber-400 gap-1" />
                            </template>
                        </Column>

                        <Column field="is_active" header="Visibilité" sortable style="min-width: 10rem; text-align: center;">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? 'En Ligne' : 'Masqué'" class="px-4 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase border" :class="data.is_active ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200'" />
                            </template>
                        </Column>

                        <Column :exportable="false" style="min-width: 12rem; text-align: right;">
                            <template #body="{ data }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info bg-blue-50 text-blue-600 hover:bg-blue-100 w-10 h-10 transition-colors" @click="editTestimonial(data)" v-tooltip.top="'Modifier cet avis'" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger bg-red-50 text-red-600 hover:bg-red-100 w-10 h-10 transition-colors" @click="confirmDeleteTestimonial(data)" v-tooltip.top="'Supprimer cet avis'" />
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
        <Dialog v-model:visible="testimonialDialog" :style="{ width: '1000px', maxWidth: '95vw' }" :modal="true" class="custom-dialog" :closable="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-100 to-amber-50 border border-amber-200 rounded-2xl flex items-center justify-center text-amber-600 shadow-sm relative overflow-hidden">
                            <div class="absolute inset-0 bg-white/40"></div>
                            <i class="pi pi-star-fill text-2xl relative z-10"></i>
                        </div>
                        <div>
                            <h2 class="font-black text-2xl text-slate-800 tracking-tight">{{ isEditing ? 'Modifier le témoignage' : 'Nouvel avis client' }}</h2>
                            <p class="text-sm text-slate-500 font-medium mt-0.5">{{ isEditing ? 'Mettez à jour les mots de votre client.' : 'Transcrivez l\'avis de votre client.' }}</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100 text-slate-500 w-10 h-10 transition-colors" @click="testimonialDialog = false" />
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-6">
                <!-- COLONNE GAUCHE (7/12) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-slate-50/60 p-6 rounded-3xl border border-slate-100 shadow-sm space-y-5">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-2">
                            <i class="pi pi-id-card text-slate-300"></i> Identité du client
                        </h3>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Nom complet <span class="text-amber-500 font-black text-lg">*</span></label>
                            <InputText v-model="form.name" autofocus :class="{ 'border-red-400 bg-red-50/30': submitted && !form.name }" class="w-full rounded-2xl bg-white shadow-sm p-4 text-base" placeholder="Ex: Marie Dupont" />
                            <small v-if="submitted && !form.name" class="text-red-500 font-bold mt-1"><i class="pi pi-exclamation-circle"></i> Requis.</small>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-bold text-slate-700">Fonction / Rôle</label>
                                <InputText v-model="form.position" class="w-full rounded-2xl bg-white shadow-sm p-3.5 text-base" placeholder="Ex: Directrice Marketing" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-bold text-slate-700">Entreprise</label>
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-building text-slate-400" />
                                    <InputText v-model="form.company" class="w-full rounded-2xl bg-white shadow-sm p-3.5 pl-10 text-base" placeholder="Ex: Acme Corp" />
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50/60 p-6 rounded-3xl border border-slate-100 shadow-sm space-y-4">
                        <div class="flex justify-between items-end">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <i class="pi pi-comment text-slate-300"></i> Le Message
                            </h3>
                            <span class="text-xs font-bold px-2 py-1 rounded-md" :class="form.content.length > 500 ? 'text-red-500 bg-red-50' : 'text-slate-400 bg-slate-200/50'">{{ form.content.length }} car.</span>
                        </div>

                        <div class="flex flex-col gap-2 relative">
                            <i class="pi pi-quote-left absolute top-4 left-4 text-slate-200 text-2xl pointer-events-none"></i>
                            <Textarea
                                v-model="form.content"
                                rows="5"
                                :class="{ 'border-red-400 bg-red-50/30': submitted && !form.content }"
                                class="w-full rounded-2xl bg-white shadow-sm p-4 pt-12 text-base italic resize-none"
                                placeholder="Collez le retour du client ici..."
                            />
                            <small v-if="submitted && !form.content" class="text-red-500 font-bold mt-1"><i class="pi pi-exclamation-circle"></i> Le message est obligatoire.</small>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE (5/12) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 p-6 rounded-3xl border border-amber-100 shadow-sm text-center">
                        <label class="text-sm font-black text-slate-800 block mb-3 uppercase tracking-wider">Note attribuée <span class="text-amber-500">*</span></label>
                        <div class="flex flex-col items-center gap-3">
                            <Rating v-model="form.rating" :cancel="false" class="text-3xl text-amber-500 gap-2" />
                            <div class="bg-white px-4 py-1.5 rounded-full shadow-sm text-amber-600 font-black text-sm border border-amber-100">
                                {{ form.rating }} / 5 Étoiles
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center text-center group">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 w-full mb-4">
                            <i class="pi pi-camera text-slate-300"></i> Photo de profil
                        </h3>

                        <input type="file" accept="image/*" @change="onAvatarSelected" ref="fileInput" class="hidden" />

                        <div
                            class="relative w-32 h-32 rounded-full border-4 border-dashed transition-all duration-300 cursor-pointer flex items-center justify-center overflow-hidden"
                            :class="(avatarPreview || (form.existing_avatar_url && !form.delete_avatar)) ? 'border-amber-200 bg-amber-50/30' : 'border-slate-200 hover:border-amber-400 hover:bg-slate-50'"
                            @click="!avatarPreview && !(form.existing_avatar_url && !form.delete_avatar) ? $refs.fileInput.click() : null"
                        >
                            <div v-if="!avatarPreview && !(form.existing_avatar_url && !form.delete_avatar)" class="flex flex-col items-center">
                                <i class="pi pi-user-plus text-3xl text-slate-300 mb-1 group-hover:text-amber-400 transition-colors"></i>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Ajouter</span>
                            </div>

                            <div v-else class="w-full h-full relative group/avatar">
                                <img :src="avatarPreview || form.existing_avatar_url" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex items-center justify-center gap-2 backdrop-blur-sm">
                                    <button type="button" @click.stop="$refs.fileInput.click()" class="w-8 h-8 bg-white/20 hover:bg-white/40 text-white rounded-full flex items-center justify-center" v-tooltip.top="'Changer'">
                                        <i class="pi pi-sync text-sm"></i>
                                    </button>
                                    <button type="button" @click.stop="removeAvatar" class="w-8 h-8 bg-red-500/80 hover:bg-red-600 text-white rounded-full flex items-center justify-center" v-tooltip.top="'Supprimer'">
                                        <i class="pi pi-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between group hover:border-amber-200 transition-colors">
                        <div>
                            <label class="text-sm font-black text-slate-800 block mb-1">Affichage Public</label>
                            <p class="text-[11px] text-slate-500 leading-tight pr-2">Visible sur le site web.</p>
                        </div>
                        <ToggleButton
                            v-model="form.is_active"
                            onLabel="En Ligne"
                            offLabel="Masqué"
                            onIcon="pi pi-eye"
                            offIcon="pi pi-eye-slash"
                            class="w-32 rounded-xl shadow-sm transition-all text-sm font-bold"
                            :class="form.is_active ? 'p-button-success bg-amber-500 border-amber-500' : 'p-button-secondary bg-slate-100 text-slate-600 border-slate-200'"
                        />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between border-t border-slate-100 pt-6 mt-4 bg-white w-full">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1">
                        <span class="text-amber-500 text-lg font-black">*</span> Champs obligatoires
                    </span>
                    <div class="flex gap-3">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-100 px-6 py-3 rounded-xl transition-colors" @click="testimonialDialog = false" />
                        <Button label="Enregistrer l'avis" icon="pi pi-check" class="bg-amber-500 border-none hover:bg-amber-600 shadow-lg shadow-amber-500/30 font-bold px-8 py-3 rounded-xl text-white transition-transform hover:-translate-y-0.5" @click="saveTestimonial" :loading="saving" />
                    </div>
                </div>
            </template>
        </Dialog>

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
import { ref, reactive, watch } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';
import Rating from 'primevue/rating';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    testimonials: [Array, Object],
    filters: Object,
});

// -------------------------------------------------------------
// CORRECTION 1 : FIN DE LA BOUCLE INFINIE DU DATATABLE
// En copiant les données dans une 'ref' isolée, PrimeVue peut les modifier
// sans redéclencher les propriétés en lecture seule d'Inertia.
// -------------------------------------------------------------
const testimonialsList = ref([]);
watch(() => props.testimonials, (newVal) => {
    const rawData = newVal?.data ?? newVal ?? [];
    testimonialsList.value = JSON.parse(JSON.stringify(rawData)); // Clonage profond
}, { immediate: true, deep: true });

const selectedTestimonials = ref([]);
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });
const search = ref(props.filters?.search || '');

const performSearch = () => {
    router.get(route('testimonials.index'), { search: search.value }, {
        preserveState: true, preserveScroll: true, replace: true,
    });
};

const clearSearch = () => {
    search.value = '';
    performSearch();
};

// -------------------------------------------------------------
// CORRECTION 2 : FONCTION SÉCURISÉE POUR LES INITIALES
// Évite l'erreur 'charAt of null' si data.name et data.author sont vides
// -------------------------------------------------------------
const getInitials = (data) => {
    const name = data.name || data.author || '?';
    return name.charAt(0).toUpperCase();
};

// -------------------------------------------------------------
// ÉTATS ET FORMULAIRE
// Correspondance parfaite avec ce qu'attend votre Contrôleur Laravel
// -------------------------------------------------------------
const testimonialDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

const defaultTestimonial = {
    id: null,
    name: '',
    position: '',
    company: '',
    content: '',
    rating: 5,
    avatar: null,
    existing_avatar_url: null,
    is_active: true,
    order: 0,
    delete_avatar: false
};

const form = reactive({ ...defaultTestimonial });

// -------------------------------------------------------------
// GESTION AVATAR
// -------------------------------------------------------------
const avatarPreview = ref(null);
const fileInput = ref(null);

const onAvatarSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        toast.add({ severity: 'error', summary: 'Format non supporté', detail: 'Veuillez uploader un fichier JPG, PNG ou WebP.', life: 5000 });
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Fichier trop lourd', detail: 'La taille maximale est de 2 Mo.', life: 5000 });
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    form.avatar = file;
    const reader = new FileReader();
    reader.onload = (e) => { avatarPreview.value = e.target.result; };
    reader.readAsDataURL(file);
};

const removeAvatar = () => {
    form.avatar = null;
    avatarPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
    if (isEditing.value && form.existing_avatar_url) {
        form.delete_avatar = true;
    }
};

// -------------------------------------------------------------
// ACTIONS DU CRUD
// -------------------------------------------------------------
const openNewTestimonial = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultTestimonial)));
    submitted.value = false;
    isEditing.value = false;
    avatarPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
    testimonialDialog.value = true;
};

const editTestimonial = (testimonial) => {
    Object.assign(form, {
        id: testimonial.id,
        // Fallback robustes : utilise name ou author, position ou role
        name: testimonial.name || testimonial.author || '',
        position: testimonial.position || testimonial.role || '',
        company: testimonial.company || '',
        content: testimonial.content || '',
        rating: testimonial.rating || 5,
        avatar: null,
        existing_avatar_url: testimonial.avatar_url || (testimonial.avatar ? `/storage/${testimonial.avatar}` : null),
        is_active: testimonial.is_active ?? true,
        order: testimonial.order || 0,
        delete_avatar: false
    });
    avatarPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
    isEditing.value = true;
    testimonialDialog.value = true;
};

const saveTestimonial = () => {
    submitted.value = true;

    if (!form.name?.trim() || !form.content?.trim() || !form.rating) {
        toast.add({ severity: 'warn', summary: 'Champs incomplets', detail: 'Veuillez remplir les champs obligatoires.', life: 3000 });
        return;
    }

    saving.value = true;
    const formData = new FormData();
    formData.append('name', form.name.trim());
    formData.append('position', form.position ? form.position.trim() : '');
    formData.append('company', form.company ? form.company.trim() : '');
    formData.append('content', form.content.trim());
    formData.append('rating', form.rating);
    formData.append('is_active', form.is_active ? 1 : 0);
    formData.append('order', form.order || 0);

    if (form.avatar instanceof File) {
        formData.append('avatar', form.avatar);
    }

    const reqOptions = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: isEditing.value ? 'Avis mis à jour.' : 'Témoignage publié.', life: 3000 });
            testimonialDialog.value = false;
            saving.value = false;
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
            saving.value = false;
        }
    };

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        if (form.delete_avatar) formData.append('delete_avatar', '1');
        router.post(route('testimonials.update', form.id), formData, reqOptions);
    } else {
        router.post(route('testimonials.store'), formData, reqOptions);
    }
};

const confirmDeleteTestimonial = (testimonial) => {
    confirm.require({
        header: 'Supprimer ce témoignage ?',
        message: `L'avis de "${testimonial.name || testimonial.author}" sera définitivement effacé du site.`,
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger px-6 py-2 rounded-xl bg-red-500 hover:bg-red-600 border-none',
        rejectClass: 'p-button-text p-button-secondary',
        acceptLabel: 'Oui, supprimer',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('testimonials.destroy', testimonial.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Avis retiré avec succès.' }),
            });
        }
    });
};

const onRowReorder = (event) => {
    const newOrderIds = event.value.map(t => t.id);
    router.post(route('testimonials.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'info', summary: 'Affichage mis à jour', life: 2500 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec du réordonnancement.' })
    });
};

watch(() => page.props.flash?.success, (val) => {
    if (val) toast.add({ severity: 'success', summary: 'Succès', detail: val, life: 3000 });
});
</script>

<style scoped>
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
:deep(.custom-table .p-datatable-tbody > tr:hover) {
    background-color: #f1f5f9 !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
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
    color: #f59e0b;
}
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
:deep(.p-rating .p-rating-item.p-rating-item-active .p-rating-icon) {
    color: #f59e0b;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
