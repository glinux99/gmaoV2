<script setup>
import { ref, computed } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIMEVUE COMPONENTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import ConfirmDialog from 'primevue/confirmdialog';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import Tooltip from 'primevue/tooltip';
import Badge from 'primevue/badge';

const toast = useToast();
const confirm = useConfirm();

const props = defineProps({
    files: [Array, Object], // Liste des fichiers (paginée ou brute)
    stats: Object, // Ex: { total_size: '1.2 GB', images_count: 145, docs_count: 32 }
});

// --- ÉTATS ---
const viewMode = ref('grid'); // 'grid' ou 'list'
const uploadDialog = ref(false);
const fileDetailsSidebar = ref(false);
const selectedFile = ref(null);
const selectedFilesList = ref([]); // Pour la suppression groupée dans la DataTable

const filesList = computed(() => props.files?.data ?? props.files ?? []);

// --- FILTRES ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const fileTypes = [
    { label: 'Images (JPG, PNG...)', value: 'image', icon: 'pi pi-image', color: 'blue' },
    { label: 'Documents (PDF, Word...)', value: 'document', icon: 'pi pi-file-pdf', color: 'rose' },
    { label: 'Vidéos', value: 'video', icon: 'pi pi-video', color: 'violet' },
    { label: 'Archives (ZIP, RAR)', value: 'archive', icon: 'pi pi-box', color: 'amber' }
];

// --- HELPERS (Utilitaires) ---
const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const getFileMeta = (mimeType) => {
    if (!mimeType) return { icon: 'pi pi-file', color: 'slate', type: 'Fichier inconnu' };

    if (mimeType.startsWith('image/')) return { icon: 'pi pi-image', color: 'blue', type: 'Image' };
    if (mimeType.startsWith('video/')) return { icon: 'pi pi-video', color: 'violet', type: 'Vidéo' };
    if (mimeType.includes('pdf')) return { icon: 'pi pi-file-pdf', color: 'rose', type: 'PDF' };
    if (mimeType.includes('word') || mimeType.includes('document')) return { icon: 'pi pi-file-word', color: 'indigo', type: 'Document Word' };
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return { icon: 'pi pi-file-excel', color: 'emerald', type: 'Tableur Excel' };
    if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('tar')) return { icon: 'pi pi-box', color: 'amber', type: 'Archive' };

    return { icon: 'pi pi-file', color: 'slate', type: 'Document' };
};

const isImage = (mimeType) => mimeType?.startsWith('image/');

// --- ACTIONS ---
const openFileDetails = (file) => {
    selectedFile.value = file;
    fileDetailsSidebar.value = true;
};

const copyUrl = (url) => {
    navigator.clipboard.writeText(url);
    toast.add({ severity: 'info', summary: 'Lien copié', detail: 'Le lien du fichier est dans le presse-papier.', life: 3000 });
};

const downloadFile = (file) => {
    // Redirection vers la route de téléchargement ou ouverture dans un nouvel onglet
    window.open(file.url, '_blank');
};

const confirmDelete = (file) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer le fichier "${file.name}" ? Cette action est irréversible.`,
        header: 'Suppression du fichier',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('media.destroy', file.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Le fichier a été supprimé.', life: 3000 });
                    fileDetailsSidebar.value = false;
                }
            });
        }
    });
};

const onUploadComplete = (event) => {
    uploadDialog.value = false;
    toast.add({ severity: 'success', summary: 'Upload réussi', detail: 'Les fichiers ont été ajoutés à la bibliothèque.', life: 3000 });
    router.reload({ only: ['files', 'stats'] }); // Rafraîchit les données via Inertia
};
</script>

<template>
    <AppLayout>
        <Head title="Médiathèque - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">

            <!-- ========================================== -->
            <!-- HEADER HERO -->
            <!-- ========================================== -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/50 to-cyan-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-cyan-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Stockage Cloud" severity="info" class="bg-blue-500/20 text-blue-300 border border-blue-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Médiathèque</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez vos images, documents et archives. Importez des fichiers pour les utiliser dans vos publications.</p>
                    </div>
                    <div class="flex gap-3">
                        <Button icon="pi pi-cloud-upload" label="Importer des fichiers" class="bg-blue-500 hover:bg-blue-600 border-none shadow-lg shadow-blue-500/30 text-white font-bold px-6" @click="uploadDialog = true" />
                    </div>
                </div>
            </div>

            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20 space-y-6">

                <!-- ========================================== -->
                <!-- MINI STATS CARDS -->
                <!-- ========================================== -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-blue-500/30"><i class="pi pi-database"></i></div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Espace utilisé</p>
                            <h3 class="text-2xl font-black text-slate-800">{{ stats?.total_size || '0 MB' }}</h3>
                            <ProgressBar :value="45" :showValue="false" class="h-1.5 mt-2 bg-slate-100" />
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-emerald-500/30"><i class="pi pi-images"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Images & Médias</p>
                            <h3 class="text-3xl font-black text-slate-800">{{ stats?.images_count || 0 }} <span class="text-sm font-medium text-slate-400">fichiers</span></h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-400 to-rose-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-rose-500/30"><i class="pi pi-file-pdf"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Documents PDF / Word</p>
                            <h3 class="text-3xl font-black text-slate-800">{{ stats?.docs_count || 0 }} <span class="text-sm font-medium text-slate-400">fichiers</span></h3>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- MAIN WORKSPACE -->
                <!-- ========================================== -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden min-h-[600px] flex flex-col">

                    <!-- TOOLBAR -->
                    <div class="p-4 lg:p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-50/50">
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative">
                                <i class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher un fichier..." class="w-full rounded-xl bg-white border-slate-200 hover:border-blue-300 focus:border-blue-500" />
                            </span>

                            <Dropdown v-model="filters['type'].value" :options="fileTypes" optionLabel="label" optionValue="value" placeholder="Tous les types" :showClear="true" class="w-full sm:w-56 rounded-xl bg-white border-slate-200">
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <i :class="[slotProps.option.icon, `text-${slotProps.option.color}-500`]"></i>
                                        <span>{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="flex items-center gap-3 self-end lg:self-auto">
                            <div class="bg-slate-100/80 border border-slate-200 rounded-xl p-1 flex shadow-sm">
                                <button @click="viewMode = 'grid'" :class="['px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 flex items-center gap-2', viewMode === 'grid' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-800']"><i class="pi pi-th-large"></i> <span class="hidden sm:inline">Grille</span></button>
                                <button @click="viewMode = 'list'" :class="['px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 flex items-center gap-2', viewMode === 'list' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-800']"><i class="pi pi-list"></i> <span class="hidden sm:inline">Liste</span></button>
                            </div>
                        </div>
                    </div>

                    <!-- EMPTY STATE GLOBAL -->
                    <div v-if="filesList.length === 0" class="flex-1 flex flex-col items-center justify-center p-12 text-center">
                        <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-6 border-2 border-dashed border-slate-200">
                            <i class="pi pi-cloud-upload text-5xl text-slate-300"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-2">Votre médiathèque est vide</h3>
                        <p class="text-slate-500 max-w-md mb-6">Importez des images, des PDF ou d'autres documents pour commencer à les organiser et les partager.</p>
                        <Button label="Importer mon premier fichier" icon="pi pi-upload" class="bg-blue-500 border-none hover:bg-blue-600 shadow-lg shadow-blue-500/30" @click="uploadDialog = true" />
                    </div>

                    <!-- CONTENT : GRID VIEW -->
                    <div v-else-if="viewMode === 'grid'" class="p-6 flex-1 bg-slate-50/30 overflow-y-auto">
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 lg:gap-6">

                            <!-- CARTE FICHIER -->
                            <div v-for="file in filesList" :key="file.id" class="group relative bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 cursor-pointer overflow-hidden flex flex-col h-48" @click="openFileDetails(file)">

                                <!-- PREVIEW ZONE -->
                                <div class="flex-1 relative overflow-hidden bg-slate-50 flex items-center justify-center border-b border-slate-100">
                                    <!-- Si c'est une image -->
                                    <img v-if="isImage(file.mime_type)" :src="file.url" :alt="file.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
                                    <!-- Si c'est un document (icône) -->
                                    <div v-else class="flex flex-col items-center justify-center w-full h-full gap-2">
                                        <i :class="[getFileMeta(file.mime_type).icon, `text-${getFileMeta(file.mime_type).color}-400 text-5xl`]"></i>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ file.extension }}</span>
                                    </div>

                                    <!-- Overlay Hover Actions -->
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2 backdrop-blur-[2px]">
                                        <Button icon="pi pi-eye" class="p-button-rounded p-button-info bg-white/20 border-none text-white hover:bg-white/40" v-tooltip.top="'Voir les détails'" />
                                    </div>
                                </div>

                                <!-- INFO ZONE -->
                                <div class="p-3 bg-white">
                                    <h4 class="text-sm font-bold text-slate-800 line-clamp-1 truncate" :title="file.name">{{ file.name }}</h4>
                                    <p class="text-xs text-slate-400 mt-0.5 flex justify-between items-center">
                                        <span>{{ formatSize(file.size) }}</span>
                                        <span class="font-mono text-[10px]">{{ file.extension?.toUpperCase() }}</span>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- CONTENT : LIST VIEW -->
                    <div v-else-if="viewMode === 'list'" class="flex-1">
                        <DataTable :value="filesList" v-model:selection="selectedFilesList" dataKey="id"
                            :paginator="true" :rows="15" :filters="filters"
                            responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>

                            <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                            <Column field="name" header="Fichier" sortable style="min-width: 25rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-4 cursor-pointer group" @click="openFileDetails(data)">
                                        <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-slate-100 flex items-center justify-center border border-slate-200">
                                            <img v-if="isImage(data.mime_type)" :src="data.url" class="w-full h-full object-cover" />
                                            <i v-else :class="[getFileMeta(data.mime_type).icon, `text-${getFileMeta(data.mime_type).color}-500 text-xl`]"></i>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-bold text-slate-800 group-hover:text-blue-600 transition-colors truncate">{{ data.name }}</span>
                                            <span class="text-xs text-slate-500 font-mono mt-0.5">{{ getFileMeta(data.mime_type).type }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="size" header="Taille" sortable style="min-width: 8rem">
                                <template #body="{ data }">
                                    <span class="text-sm font-medium text-slate-600">{{ formatSize(data.size) }}</span>
                                </template>
                            </Column>

                            <Column field="created_at" header="Ajouté le" sortable style="min-width: 12rem">
                                <template #body="{ data }">
                                    <span class="text-sm text-slate-500"><i class="pi pi-clock mr-1 text-xs"></i> {{ formatDate(data.created_at) }}</span>
                                </template>
                            </Column>

                            <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                <template #body="{ data }">
                                    <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary mr-1" @click="openFileDetails(data)" v-tooltip.top="'Détails'" />
                                    <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-info mr-1" @click="downloadFile(data)" v-tooltip.top="'Télécharger'" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDelete(data)" v-tooltip.top="'Supprimer'" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODALE : UPLOAD DE FICHIERS -->
        <!-- ========================================================================= -->
        <Dialog v-model:visible="uploadDialog" modal header="Importer des fichiers" :style="{ width: '600px' }" class="custom-dialog">
            <p class="text-slate-500 mb-6 text-sm">Glissez-déposez vos fichiers ici ou cliquez pour parcourir votre ordinateur. Taille max : 10MB par fichier.</p>

            <FileUpload
                name="files[]"
                :url="route('media.store')"
                @upload="onUploadComplete"
                :multiple="true"
                accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip"
                :maxFileSize="10000000"
                chooseLabel="Parcourir"
                uploadLabel="Envoyer"
                cancelLabel="Annuler"
                class="custom-fileupload"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center p-12 text-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 mt-4">
                        <i class="pi pi-cloud-upload text-4xl text-slate-400 mb-4"></i>
                        <p class="m-0 text-slate-500 font-medium">Glissez et déposez vos fichiers ici.</p>
                    </div>
                </template>
            </FileUpload>
        </Dialog>

        <!-- ========================================================================= -->
        <!-- SIDEBAR : DÉTAILS DU FICHIER -->
        <!-- ========================================================================= -->
        <Sidebar v-model:visible="fileDetailsSidebar" position="right" class="w-full md:w-[450px] custom-sidebar">
            <template #header>
                <div class="flex items-center gap-2 font-bold text-slate-800 text-lg">
                    <i class="pi pi-info-circle text-blue-500"></i> Informations du fichier
                </div>
            </template>

            <div v-if="selectedFile" class="flex flex-col h-full pb-8">

                <!-- Preview (Image en grand ou Icône) -->
                <div class="w-full aspect-video bg-slate-100 rounded-2xl mb-6 overflow-hidden flex items-center justify-center border border-slate-200 shadow-inner relative group">
                    <img v-if="isImage(selectedFile.mime_type)" :src="selectedFile.url" class="w-full h-full object-contain bg-slate-800" />
                    <i v-else :class="[getFileMeta(selectedFile.mime_type).icon, `text-${getFileMeta(selectedFile.mime_type).color}-400 text-6xl`]"></i>

                    <div class="absolute bottom-2 right-2 flex gap-2">
                        <Button icon="pi pi-download" class="p-button-rounded p-button-sm shadow-md" v-tooltip.top="'Télécharger'" @click="downloadFile(selectedFile)" />
                        <Button icon="pi pi-external-link" class="p-button-rounded p-button-secondary p-button-sm bg-white text-slate-700 shadow-md border-none hover:bg-slate-50" v-tooltip.top="'Ouvrir dans un onglet'" @click="window.open(selectedFile.url, '_blank')" />
                    </div>
                </div>

                <!-- Nom et type -->
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-800 mb-2 break-all leading-tight">{{ selectedFile.name }}</h2>
                    <Tag :value="getFileMeta(selectedFile.mime_type).type" severity="info" class="bg-slate-100 text-slate-600 font-mono text-[10px] border border-slate-200" />
                </div>

                <!-- Métadonnées -->
                <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 space-y-4 mb-6 flex-1">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Taille</span>
                        <span class="text-sm font-medium text-slate-800">{{ formatSize(selectedFile.size) }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Date d'importation</span>
                        <span class="text-sm font-medium text-slate-800">{{ formatDate(selectedFile.created_at) }}</span>
                    </div>
                    <div v-if="isImage(selectedFile.mime_type) && selectedFile.dimensions" class="flex flex-col">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dimensions</span>
                        <span class="text-sm font-medium text-slate-800">{{ selectedFile.dimensions }} pixels</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Type MIME</span>
                        <span class="text-sm font-mono text-slate-600 bg-white border border-slate-200 px-2 py-1 rounded inline-block w-max mt-1">{{ selectedFile.mime_type }}</span>
                    </div>
                </div>

                <!-- URL Copy -->
                <div class="mb-8">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">URL du fichier</span>
                    <div class="flex items-center">
                        <InputText :value="selectedFile.url" readonly class="w-full rounded-r-none rounded-l-xl text-sm bg-slate-50 border-r-0" />
                        <Button icon="pi pi-copy" class="rounded-l-none rounded-r-xl bg-slate-800 border-slate-800 hover:bg-slate-900" @click="copyUrl(selectedFile.url)" v-tooltip.top="'Copier le lien'" />
                    </div>
                </div>

                <!-- Delete Action -->
                <div class="pt-4 border-t border-slate-100 mt-auto">
                    <Button label="Supprimer ce fichier" icon="pi pi-trash" class="w-full p-button-danger p-button-outlined hover:bg-red-50" @click="confirmDelete(selectedFile)" />
                </div>
            </div>
        </Sidebar>

        <ConfirmDialog></ConfirmDialog>
    </AppLayout>
</template>

<style scoped>
/* ========================================= */
/* DATATABLE CUSTOMIZATION */
/* ========================================= */
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
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

/* ========================================= */
/* DIALOG & SIDEBAR CUSTOMIZATION */
/* ========================================= */
:deep(.custom-dialog .p-dialog-header) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem;
}
:deep(.custom-sidebar) {
    border-left: 1px solid #e2e8f0;
    box-shadow: -10px 0 30px rgba(0,0,0,0.05);
}
:deep(.custom-sidebar .p-sidebar-header) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem;
}
:deep(.custom-sidebar .p-sidebar-content) {
    padding: 1.5rem;
}

/* ========================================= */
/* FILEUPLOAD OVERRIDE */
/* ========================================= */
:deep(.custom-fileupload .p-fileupload-buttonbar) {
    background: transparent;
    border: none;
    padding: 0;
    margin-bottom: 1rem;
    display: flex;
    gap: 0.5rem;
}
:deep(.custom-fileupload .p-fileupload-content) {
    background: transparent;
    border: none;
    padding: 0;
}
</style>
