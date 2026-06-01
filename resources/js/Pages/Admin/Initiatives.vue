<template>
    <AppLayout>
        <Head title="Initiatives - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-rose-900/50 to-indigo-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-rose-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-indigo-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Initiatives" severity="danger" class="bg-rose-500/20 text-rose-300 border border-rose-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Initiatives</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez vos initiatives, leurs visuels et leur ordre d'affichage.</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <!-- Barre d'outils -->
                    <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative flex items-center">
                            <InputText
                                v-model="search"
                                placeholder="Rechercher une initiative..."
                                class="w-full rounded-xl bg-white border-slate-200"
                                @keyup.enter="performSearch"
                            />
                            <Button icon="pi pi-search text-slate-400 absolute right-3 z-10" class="p-button-rounded p-button-text" @click="performSearch" />
                        </span>
                        <Button icon="pi pi-plus" label="Nouvelle initiative" class="bg-rose-500 hover:bg-rose-600 border-none shadow-lg shadow-rose-500/30 text-white font-bold w-full sm:w-auto px-6" @click="openNewInitiative" />
                    </div>

                    <!-- Tableau -->
                    <DataTable :value="initiativesList" v-model:selection="selectedInitiatives" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-16 text-center">
                                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
                                    <i class="pi pi-compass text-4xl text-slate-300"></i>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-2">Aucune initiative</h3>
                                <p class="text-slate-500 max-w-md mb-6">Commencez par créer votre première initiative pour mettre en valeur vos actions et projets.</p>
                                <Button label="Créer une initiative" icon="pi pi-plus" class="p-button-outlined p-button-lg rounded-xl" @click="openNewInitiative" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />
                        <Column field="order" header="Ordre" sortable style="min-width: 5rem">
                            <template #body="{ data }">
                                <Badge :value="data.order" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                            </template>
                        </Column>
                        <Column field="icon" header="Icône" style="min-width: 6rem; text-align: center;">
                            <template #body="{ data }">
                                <div class="flex items-center justify-center">
                                    <span class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-sm" :style="{ backgroundColor: getColorValue(data.color) }">
                                        <i :class="['pi', data.icon]" class="text-lg"></i>
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column field="title" header="Titre" sortable style="min-width: 14rem">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-800 text-base">{{ data.title }}</span>
                            </template>
                        </Column>
                        <Column field="summary" header="Résumé" style="min-width: 18rem">
                            <template #body="{ data }">
                                <span class="text-slate-500 text-sm line-clamp-2 pr-4">{{ data.summary }}</span>
                            </template>
                        </Column>
                        <Column field="is_active" header="Statut" sortable style="min-width: 8rem; text-align: center;">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Publié' : 'Brouillon'" class="px-3 py-1 rounded-full text-xs font-bold tracking-wide" />
                            </template>
                        </Column>
                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2 hover:bg-blue-50" @click="editInitiative(data)" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger hover:bg-red-50" @click="confirmDeleteInitiative(data)" v-tooltip.top="'Supprimer'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODALE INITIATIVE REVISITÉE -->
        <Dialog v-model:visible="initiativeDialog" :style="{ width: '1000px', maxWidth: '95vw' }" :modal="true" class="custom-dialog" :closable="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-rose-100 to-rose-50 border border-rose-100 rounded-xl flex items-center justify-center text-rose-600 shadow-sm">
                            <i class="pi pi-star-fill text-xl"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-slate-800 leading-tight">{{ isEditing ? 'Modification de l\'initiative' : 'Création d\'une initiative' }}</h2>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Configurez les informations et l'apparence visuelle.</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100 text-slate-500" @click="initiativeDialog = false" />
                </div>
            </template>

            <!-- GRILLE DE MISE EN PAGE : 2 COLONNES -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-4">

                <!-- COLONNE GAUCHE : Informations générales (7 colonnes sur 12) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 space-y-5">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="pi pi-align-left text-slate-400"></i> Informations textuelles
                        </h3>

                        <!-- Titre -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Titre de l'initiative <span class="text-rose-500">*</span></label>
                            <InputText v-model="form.title" autofocus :class="{ 'border-rose-500': submitted && !form.title }" class="w-full rounded-xl bg-white shadow-sm p-3" placeholder="Ex: Programme de Santé Maternelle" />
                            <small v-if="submitted && !form.title" class="text-rose-500 font-medium">Le titre est requis.</small>
                        </div>

                        <!-- Résumé -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Résumé (Aperçu rapide) <span class="text-rose-500">*</span></label>
                            <Textarea v-model="form.summary" rows="2" class="w-full rounded-xl bg-white shadow-sm p-3 resize-none" placeholder="Un paragraphe court accrocheur..." :class="{ 'border-rose-500': submitted && !form.summary }" />
                            <small v-if="submitted && !form.summary" class="text-rose-500 font-medium">Le résumé est requis.</small>
                        </div>

                        <!-- Description -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Description détaillée</label>
                            <Textarea v-model="form.description" rows="5" class="w-full rounded-xl bg-white shadow-sm p-3" placeholder="Expliquez en détail le but et les actions de cette initiative..." />
                        </div>
                    </div>

                    <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100 flex items-center justify-between">
                        <div>
                            <label class="text-sm font-bold text-slate-800 block mb-1">Visibilité de l'initiative</label>
                            <p class="text-xs text-slate-500">Si désactivé, l'initiative ne sera pas visible par le public.</p>
                        </div>
                        <ToggleButton v-model="form.is_active" onLabel="Publiée" offLabel="Brouillon"
                            onIcon="pi pi-eye" offIcon="pi pi-eye-slash"
                            class="w-32 rounded-xl shadow-sm"
                            :class="form.is_active ? 'p-button-success' : 'p-button-secondary'" />
                    </div>
                </div>

                <!-- COLONNE DROITE : Visuels et Métriques (5 colonnes sur 12) -->
                <div class="lg:col-span-5 space-y-6">

                    <!-- Section Sélecteur d'icône & Couleur -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <i class="pi pi-palette text-slate-400"></i> Identité visuelle
                            </h3>
                            <!-- Preview de l'icône sélectionnée -->
                            <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100 shadow-inner">
                                <span class="text-xs font-bold text-slate-500">Rendu :</span>
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white transition-colors duration-300" :style="{ backgroundColor: getColorValue(form.color) }">
                                    <i :class="['pi', form.icon || 'pi-question']" class="text-sm"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Couleur -->
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Thème couleur <span class="text-rose-500">*</span></label>
                            <Select v-model="form.color" :options="colorOptions" optionLabel="name" optionValue="value" placeholder="Choisir une couleur" class="w-full rounded-xl shadow-sm" :class="{ 'border-rose-500': submitted && !form.color }">
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <span class="w-5 h-5 rounded-md shadow-inner" :style="{ backgroundColor: getColorValue(slotProps.value) }"></span>
                                        <span class="font-medium">{{ colorOptions.find(c => c.value === slotProps.value)?.name }}</span>
                                    </div>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-3">
                                        <span class="w-5 h-5 rounded-md shadow-inner" :style="{ backgroundColor: getColorValue(slotProps.option.value) }"></span>
                                        <span class="font-medium">{{ slotProps.option.name }}</span>
                                    </div>
                                </template>
                            </Select>
                            <small v-if="submitted && !form.color" class="text-rose-500 font-medium">La couleur est requise.</small>
                        </div>

                        <!-- Sélecteur d'icône avancé -->
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Choix de l'icône <span class="text-rose-500">*</span></label>
                            <div class="border rounded-xl flex flex-col overflow-hidden shadow-sm" :class="{ 'border-rose-500': submitted && !form.icon, 'border-slate-200': !(submitted && !form.icon) }">
                                <!-- Barre de recherche icône -->
                                <div class="bg-slate-50 p-2 border-b border-slate-200">
                                    <span class="p-input-icon-left w-full">
                                        <i class="pi pi-search text-slate-400 text-sm ml-1" />
                                        <InputText v-model="iconSearchQuery" placeholder="Chercher une icône (ex: heart)..." class="w-full p-inputtext-sm rounded-lg border-none bg-white shadow-sm pl-8" />
                                    </span>
                                </div>
                                <!-- Grille d'icônes -->
                                <div class="p-2 h-48 overflow-y-auto bg-white custom-scrollbar grid grid-cols-6 gap-1">
                                    <button type="button" v-for="icon in filteredIcons" :key="icon"
                                        @click="form.icon = icon"
                                        class="aspect-square flex items-center justify-center rounded-lg transition-all duration-200 hover:scale-110"
                                        :class="form.icon === icon ? 'bg-slate-800 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'">
                                        <i :class="['pi', icon]" class="text-lg"></i>
                                    </button>
                                    <div v-if="filteredIcons.length === 0" class="col-span-6 text-center py-8 text-slate-400 text-sm">
                                        Aucune icône trouvée.
                                    </div>
                                </div>
                            </div>
                            <small v-if="submitted && !form.icon" class="text-rose-500 font-medium">L'icône est requise.</small>
                        </div>
                    </div>

                    <!-- Image (upload) -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-3">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="pi pi-image text-slate-400"></i> Image d'illustration
                        </h3>
                        <div class="flex items-center gap-4">
                            <input type="file" accept="image/*" @change="onImageSelected" ref="fileInput" class="hidden" />
                            <div class="flex-1 border-2 border-dashed border-slate-200 rounded-xl p-4 text-center cursor-pointer hover:bg-slate-50 hover:border-rose-300 transition-colors" @click="$refs.fileInput.click()">
                                <i class="pi pi-upload text-2xl text-slate-400 mb-2"></i>
                                <p class="text-sm font-medium text-slate-700">Cliquez pour uploader</p>
                                <p class="text-xs text-slate-400 mt-1">JPG, PNG, WebP (Max 2 Mo)</p>
                            </div>
                        </div>

                        <!-- Aperçu de l'image -->
                        <div v-if="imagePreview || (form.existing_image_url && !form.delete_image)" class="relative mt-2 rounded-xl border border-slate-200 p-2 bg-slate-50">
                            <img :src="imagePreview || form.existing_image_url" class="h-32 w-full object-cover rounded-lg shadow-sm" />
                            <button type="button" @click="removeImage" class="absolute -top-2 -right-2 w-8 h-8 bg-white text-rose-500 rounded-full border border-slate-200 shadow-lg flex items-center justify-center hover:bg-rose-50 transition-colors" v-tooltip.top="'Supprimer l\'image'">
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                            <div class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] px-2 py-1 rounded-md backdrop-blur-sm">
                                {{ imagePreview ? 'Nouvelle image' : 'Image actuelle' }}
                            </div>
                        </div>
                    </div>

                    <!-- Métriques (JSON dynamique) -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-3">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <i class="pi pi-chart-bar text-slate-400"></i> Chiffres Clés
                            </h3>
                            <Button icon="pi pi-plus" size="small" class="p-button-rounded p-button-success p-button-sm w-8 h-8" @click="addMetric" v-tooltip.top="'Ajouter une métrique'" />
                        </div>

                        <div v-if="form.metrics.length === 0" class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <p class="text-sm text-slate-500">Aucune métrique configurée.</p>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
                            <div v-for="(metric, index) in form.metrics" :key="index" class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-100 shadow-sm group">
                                <div class="flex-1 grid grid-cols-2 gap-2">
                                    <InputText v-model="metric.label" placeholder="Libellé (ex: Bénéficiaires)" class="w-full p-inputtext-sm rounded-lg" />
                                    <InputText v-model="metric.value" placeholder="Valeur (ex: 50 000+)" class="w-full p-inputtext-sm rounded-lg font-bold text-indigo-600" />
                                </div>
                                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-danger w-8 h-8 opacity-50 group-hover:opacity-100 transition-opacity" @click="removeMetric(index)" />
                            </div>
                        </div>
                    </div>

                    <!-- Ordre (Optionnel caché visuellement sauf si besoin spécifique) -->
                    <div class="hidden">
                        <InputNumber v-model="form.order" :min="0" />
                    </div>

                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between border-t border-slate-100 pt-5 mt-2 bg-white">
                    <span class="text-xs text-slate-400 font-medium"><span class="text-rose-500">*</span> Champs obligatoires</span>
                    <div class="flex gap-3">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary font-bold" @click="initiativeDialog = false" />
                        <Button label="Enregistrer l'initiative" icon="pi pi-check" class="bg-rose-500 border-none hover:bg-rose-600 shadow-lg shadow-rose-500/30 font-bold px-6 py-3 rounded-xl text-white" @click="saveInitiative" :loading="saving" />
                    </div>
                </div>
            </template>
        </Dialog>

        <ConfirmDialog>
            <template #message="slotProps">
                <div class="flex items-center w-full gap-4 border-b border-slate-100 pb-4 mb-4">
                    <i :class="slotProps.message.icon" class="text-4xl text-rose-500"></i>
                    <p class="text-slate-700 font-medium m-0">{{ slotProps.message.message }}</p>
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

// Composants PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    initiatives: [Array, Object],
    filters: Object,
});

const search = ref(props.filters?.search || '');
const initiativesList = computed(() => props.initiatives?.data ?? props.initiatives ?? []);
const selectedInitiatives = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// États modale
const initiativeDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

// -------------------------------------------------------------
// GESTION DES COULEURS
// -------------------------------------------------------------
const colorOptions = [
    { name: 'Rose', value: 'rose' },
    { name: 'Indigo', value: 'indigo' },
    { name: 'Émeraude', value: 'emerald' },
    { name: 'Bleu', value: 'blue' },
    { name: 'Violet', value: 'violet' },
    { name: 'Orange', value: 'orange' },
    { name: 'Cyan', value: 'cyan' },
    { name: 'Gris', value: 'slate' },
    { name: 'Rouge', value: 'red' },
    { name: 'Jaune', value: 'yellow' }
];

const getColorValue = (colorName) => {
    const map = {
        rose: '#f43f5e', indigo: '#6366f1', emerald: '#10b981', blue: '#3b82f6',
        violet: '#8b5cf6', orange: '#f97316', cyan: '#06b6d4', slate: '#64748b',
        red: '#ef4444', yellow: '#eab308'
    };
    return map[colorName] || '#64748b';
};

// -------------------------------------------------------------
// GESTION DES ICÔNES (Liste exhaustive PrimeIcons)
// -------------------------------------------------------------
const iconSearchQuery = ref('');

// Dictionnaire très complet des icônes PrimeVue (v3/v4)
const primeIconsList = [
    'align-center', 'align-justify', 'align-left', 'align-right', 'amazon', 'android', 'angle-double-down', 'angle-double-left', 'angle-double-right', 'angle-double-up', 'angle-down', 'angle-left', 'angle-right', 'angle-up', 'apple', 'arrow-circle-down', 'arrow-circle-left', 'arrow-circle-right', 'arrow-circle-up', 'arrow-down', 'arrow-down-left', 'arrow-down-right', 'arrow-left', 'arrow-right', 'arrow-up', 'arrow-up-left', 'arrow-up-right', 'asterisk', 'at', 'backward', 'ban', 'bars', 'bell', 'bitcoin', 'bolt', 'book', 'bookmark', 'bookmark-fill', 'box', 'briefcase', 'building', 'calculator', 'calendar', 'calendar-minus', 'calendar-plus', 'calendar-times', 'camera', 'car', 'caret-down', 'caret-left', 'caret-right', 'caret-up', 'cart-plus', 'chart-bar', 'chart-line', 'chart-pie', 'check', 'check-circle', 'check-square', 'chevron-circle-down', 'chevron-circle-left', 'chevron-circle-right', 'chevron-circle-up', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up', 'circle', 'circle-fill', 'clipboard', 'clock', 'clone', 'cloud', 'cloud-download', 'cloud-upload', 'code', 'cog', 'comment', 'comments', 'compass', 'copy', 'credit-card', 'database', 'desktop', 'directions', 'directions-alt', 'discord', 'desktop', 'download', 'eject', 'ellipsis-h', 'ellipsis-v', 'envelope', 'euro', 'exclamation-circle', 'exclamation-triangle', 'external-link', 'eye', 'eye-slash', 'facebook', 'fast-backward', 'fast-forward', 'file', 'file-excel', 'file-pdf', 'file-word', 'filter', 'filter-slash', 'flag', 'flag-fill', 'folder', 'folder-open', 'forward', 'github', 'globe', 'google', 'graduation-cap', 'hashtag', 'heart', 'heart-fill', 'history', 'home', 'id-card', 'image', 'images', 'inbox', 'info', 'info-circle', 'instagram', 'key', 'language', 'lightbulb', 'link', 'linkedin', 'list', 'lock', 'lock-open', 'map', 'map-marker', 'megaphone', 'microphone', 'minus', 'minus-circle', 'mobile', 'money-bill', 'moon', 'palette', 'paperclip', 'pause', 'paypal', 'pencil', 'percentage', 'phone', 'play', 'plus', 'plus-circle', 'pound', 'power-off', 'prime', 'print', 'qrcode', 'question', 'question-circle', 'reddit', 'refresh', 'replay', 'reply', 'save', 'search', 'search-minus', 'search-plus', 'send', 'server', 'share-alt', 'shield', 'shopping-bag', 'shopping-cart', 'sign-in', 'sign-out', 'sitemap', 'slack', 'sliders-h', 'sliders-v', 'sort', 'sort-alpha-down', 'sort-alpha-down-alt', 'sort-alpha-up', 'sort-alpha-up-alt', 'sort-amount-down', 'sort-amount-down-alt', 'sort-amount-up', 'sort-amount-up-alt', 'sort-down', 'sort-numeric-down', 'sort-numeric-down-alt', 'sort-numeric-up', 'sort-numeric-up-alt', 'sort-up', 'spinner', 'star', 'star-fill', 'step-backward', 'step-forward', 'stop', 'stop-circle', 'sun', 'sync', 'table', 'tablet', 'tag', 'tags', 'telegram', 'th-large', 'thumbs-down', 'thumbs-up', 'ticket', 'times', 'times-circle', 'trash', 'tree', 'trophy', 'truck', 'twitter', 'undo', 'unlock', 'upload', 'user', 'user-edit', 'user-minus', 'user-plus', 'users', 'video', 'vimeo', 'volume-down', 'volume-off', 'volume-up', 'wallet', 'whatsapp', 'wifi', 'window-maximize', 'window-minimize', 'wrench', 'youtube'
].map(i => `pi-${i}`); // Ajout du préfixe pi- pour correspondre à PrimeVue

// Filtrage réactif des icônes
const filteredIcons = computed(() => {
    if (!iconSearchQuery.value) return primeIconsList;
    const query = iconSearchQuery.value.toLowerCase().replace('pi-', '');
    return primeIconsList.filter(icon => icon.includes(query));
});

// -------------------------------------------------------------
// GESTION DU FORMULAIRE ET DONNÉES
// -------------------------------------------------------------
const defaultInitiative = {
    id: null,
    title: '',
    icon: 'pi-star', // Icône par défaut au lieu de vide
    color: 'rose', // Couleur par défaut
    summary: '',
    description: '',
    metrics: [],
    image: null,
    existing_image_url: null,
    is_active: true,
    order: 0,
    delete_image: false
};

const form = reactive({ ...defaultInitiative });

const performSearch = () => {
    router.get(route('initiatives.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Gestion de l'image
const imagePreview = ref(null);
const fileInput = ref(null);

const onImageSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        toast.add({ severity: 'error', summary: 'Format invalide', detail: 'Veuillez sélectionner une image.' });
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Fichier volumineux', detail: 'Taille max : 2 Mo.' });
        return;
    }

    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target.result; };
    reader.readAsDataURL(file);
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
    if (isEditing.value && form.existing_image_url) {
        form.delete_image = true;
    }
};

// Gestion des métriques
const addMetric = () => form.metrics.push({ label: '', value: '' });
const removeMetric = (index) => form.metrics.splice(index, 1);

// Ouverture modale Création
const openNewInitiative = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultInitiative)));
    form.metrics = [];
    submitted.value = false;
    isEditing.value = false;
    imagePreview.value = null;
    iconSearchQuery.value = '';
    if (fileInput.value) fileInput.value.value = '';
    initiativeDialog.value = true;
};

// Ouverture modale Édition
const editInitiative = (initiative) => {
    Object.assign(form, {
        id: initiative.id,
        title: initiative.title,
        icon: initiative.icon || 'pi-star',
        color: initiative.color || 'slate',
        summary: initiative.summary,
        description: initiative.description || '',
        metrics: initiative.metrics ? JSON.parse(JSON.stringify(initiative.metrics)) : [],
        image: null,
        existing_image_url: initiative.image ? `/storage/${initiative.image}` : null,
        is_active: initiative.is_active,
        order: initiative.order,
        delete_image: false
    });
    imagePreview.value = null;
    iconSearchQuery.value = '';
    if (fileInput.value) fileInput.value.value = '';
    isEditing.value = true;
    initiativeDialog.value = true;
};

// Sauvegarde API
const saveInitiative = () => {
    submitted.value = true;

    if (!form.title?.trim() || !form.icon?.trim() || !form.color || !form.summary?.trim()) {
        toast.add({ severity: 'error', summary: 'Formulaire incomplet', detail: 'Veuillez remplir les champs obligatoires (en rouge).' });
        return;
    }

    saving.value = true;

    const formData = new FormData();
    formData.append('title', form.title);
    formData.append('icon', form.icon);
    formData.append('color', form.color);
    formData.append('summary', form.summary);
    formData.append('description', form.description || '');
    formData.append('is_active', form.is_active ? 1 : 0);
    formData.append('order', form.order);

    // Nettoyer les métriques vides avant l'envoi
    const cleanMetrics = form.metrics.filter(m => m.label.trim() !== '' || m.value.trim() !== '');
    formData.append('metrics', JSON.stringify(cleanMetrics));

    if (form.image instanceof File) {
        formData.append('image', form.image);
    }

    const requestOptions = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: isEditing.value ? 'Initiative mise à jour avec succès.' : 'Nouvelle initiative créée.', life: 3000 });
            initiativeDialog.value = false;
            saving.value = false;
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            toast.add({ severity: 'error', summary: 'Erreur d\'enregistrement', detail: firstError || 'Une erreur est survenue.' });
            saving.value = false;
        }
    };

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        if (form.delete_image) formData.append('delete_image', '1');
        router.post(route('initiatives.update', form.id), formData, requestOptions);
    } else {
        router.post(route('initiatives.store'), formData, requestOptions);
    }
};

// Suppression API
const confirmDeleteInitiative = (initiative) => {
    confirm.require({
        message: `La suppression de l'initiative "${initiative.title}" est définitive. Continuer ?`,
        header: 'Suppression de l\'initiative',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Oui, supprimer',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('initiatives.destroy', initiative.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimée', detail: 'Initiative supprimée du système.', life: 3000 }),
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Suppression impossible.' })
            });
        }
    });
};

// Réordonnancement Drag & Drop
const onRowReorder = (event) => {
    const newOrderIds = event.value.map(p => p.id);
    router.post(route('initiatives.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'info', summary: 'Ordre sauvegardé', life: 2000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le réordonnancement a échoué.' })
    });
};

// Écoute des messages flash du backend Laravel
watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) toast.add({ severity: 'success', summary: 'Succès', detail: newVal, life: 3000 });
});
</script>

<style scoped>
/* --------- STYLE DATATABLE --------- */
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
    background-color: #f8fafc !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.p-datatable .p-datatable-tbody > tr > td .p-row-reorder-icon) {
    color: #cbd5e1;
    transition: color 0.2s, transform 0.2s;
    cursor: grab;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover .p-row-reorder-icon) {
    color: #64748b;
}
:deep(.p-datatable .p-datatable-tbody > tr .p-row-reorder-icon:active) {
    cursor: grabbing;
    transform: scale(1.1);
}

/* --------- STYLE MODALE DIALOG --------- */
:deep(.custom-dialog .p-dialog-header) {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem 2rem;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
}
:deep(.custom-dialog .p-dialog-content) {
    background: #ffffff;
    padding: 0 2rem 1rem 2rem;
}
:deep(.custom-dialog .p-dialog-footer) {
    background: #ffffff;
    padding: 0 2rem 1.5rem 2rem;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

/* --------- UTILITAIRES & SCROLLBAR --------- */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Scrollbar personnalisée élégante pour la grille d'icônes et métriques */
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
</style>
