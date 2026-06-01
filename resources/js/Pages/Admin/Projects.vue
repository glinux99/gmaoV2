<template>
    <AppLayout>
        <Head title="Projets - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-900/50 to-orange-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-amber-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-orange-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Projets" severity="warning" class="bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Projets</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez vos projets et leur ordre d'affichage (glisser‑déposer).</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <!-- Barre d'outils -->
                    <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 w-full sm:w-80">
                            <InputText v-model="search" placeholder="Rechercher un projet..." class="w-full rounded-xl bg-white border-slate-200" @keyup.enter="performSearch" />
                            <Button icon="pi pi-search" class="p-button-rounded p-button-text" @click="performSearch" v-tooltip.bottom="'Rechercher'" />
                        </div>
                        <Button icon="pi pi-plus" label="Nouveau projet" class="bg-amber-500 hover:bg-amber-600 border-none shadow-lg shadow-amber-500/30 text-white font-bold w-full sm:w-auto" @click="openNewProject" />
                    </div>

                    <!-- Tableau avec réorganisation -->
                    <DataTable :value="projectsList" v-model:selection="selectedProjects" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="pi pi-briefcase text-3xl text-slate-400"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800">Aucun projet</h3>
                                <p class="text-slate-500 max-w-sm mb-4">Vous n'avez pas encore enregistré de projet.</p>
                                <Button label="Créer un projet" class="p-button-outlined" @click="openNewProject" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />
                        <Column field="order" header="Ordre" sortable style="min-width: 5rem">
                            <template #body="{ data }">
                                <Badge :value="data.order" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                            </template>
                        </Column>
                        <Column field="image" header="Image" style="min-width: 6rem">
                          <template #body="{ data }">
                                <div class="flex items-center">
                                    <img
                                        v-if="data.image"
                                        :src="`/media/${data.image}`"
                                        alt="Aperçu du média"
                                        class="h-10 w-10 object-cover rounded-lg border border-slate-200"
                                        @error="(e) => e.target.src = '/media/placeholder.png'"
                                    />

                                    <span v-else class="text-slate-400 text-xs italic">
                                        Aucune image
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column field="title" header="Titre" sortable style="min-width: 14rem">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-800">{{ data.title }}</span>
                            </template>
                            <template #rowtogglericon="slotProps"></template>
                        </Column>
                        <Column field="category" header="Catégorie" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <Tag :value="getCategoryLabel(data.category)" severity="info" />
                            </template>
                        </Column>
                        <Column field="status" header="Statut" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <Tag :severity="statusSeverity(data.status)" :value="statusLabel(data.status)" />
                            </template>
                        </Column>
                        <Column field="start_date" header="Début" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="text-sm">{{ formatDate(data.start_date) }}</span>
                            </template>
                        </Column>
                        <Column field="end_date" header="Fin" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="text-sm">{{ formatDate(data.end_date) }}</span>
                            </template>
                        </Column>
                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editProject(data)" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteProject(data)" v-tooltip.top="'Supprimer'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODALE PROJET -->
        <Dialog v-model:visible="projectDialog" :style="{ width: '700px' }" header="Projet" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">
                        <i class="pi pi-briefcase"></i>
                    </div>
                    <span class="font-bold text-xl text-slate-800">{{ isEditing ? 'Modifier le projet' : 'Nouveau projet' }}</span>
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <!-- Titre -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Titre <span class="text-red-500">*</span></label>
                    <InputText v-model="form.title" autofocus :class="{ 'p-invalid': submitted && !form.title }" class="w-full rounded-xl" placeholder="Ex: Application Mobile" />
                    <small v-if="submitted && !form.title" class="p-error">Le titre est requis.</small>
                </div>

                <!-- Slug (auto-généré) -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Slug (identifiant unique)</label>
                    <InputText v-model="form.slug" class="w-full rounded-xl font-mono text-sm" placeholder="ex: application-mobile" />
                    <small class="text-slate-400">Laissez vide pour auto-génération à partir du titre.</small>
                </div>

                <!-- Catégorie (depuis BDD) -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Catégorie <span class="text-red-500">*</span></label>
                    <Select v-model="form.category_id" :options="categoriesList" optionLabel="name" optionValue="id" placeholder="Choisir une catégorie" class="w-full rounded-xl" :class="{ 'p-invalid': submitted && !form.category_id }" />
                    <small v-if="submitted && !form.category_id" class="p-error">La catégorie est requise.</small>
                </div>

                <!-- Description -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Description</label>
                    <Textarea v-model="form.description" rows="4" class="w-full rounded-xl" placeholder="Description du projet..." />
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Date de début</label>
                        <Calendar v-model="form.start_date" showIcon dateFormat="dd/mm/yy" class="w-full rounded-xl" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Date de fin</label>
                        <Calendar v-model="form.end_date" showIcon dateFormat="dd/mm/yy" class="w-full rounded-xl" />
                    </div>
                </div>

                <!-- Statut -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Statut</label>
                    <Select v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" placeholder="Statut" class="w-full rounded-xl" />
                </div>

                <!-- Image (upload manuel) -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Image</label>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-4">
                            <input type="file" accept="image/*" @change="onImageSelected" ref="fileInput" class="hidden" />
                            <Button type="button" label="Choisir une image" icon="pi pi-upload" class="p-button-outlined" @click="$refs.fileInput.click()" />
                            <span v-if="imagePreview" class="text-sm text-slate-600">{{ imageFileName || 'Fichier sélectionné' }}</span>
                            <Button v-if="form.image || imagePreview" type="button" icon="pi pi-times" class="p-button-rounded p-button-text p-button-danger" @click="removeImage" v-tooltip.top="'Supprimer l’image'" />
                        </div>
                        <div v-if="imagePreview" class="mt-2">
                            <img :src="imagePreview" class="h-24 w-auto object-contain border rounded-lg p-1 bg-white" />
                        </div>
                        <div v-else-if="form.existing_image_url && !imagePreview" class="mt-2">
                            <img :src="form.existing_image_url" class="h-24 w-auto object-contain border rounded-lg p-1 bg-white" />
                            <small class="text-slate-400 text-xs">Image actuelle</small>
                        </div>
                        <small class="text-slate-400 text-xs">Formats : JPG, PNG, GIF, WebP (max 2 Mo)</small>
                    </div>
                </div>

                <!-- Visible -->
                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">Projet visible</label>
                    <ToggleButton v-model="form.is_active" onLabel="Oui" offLabel="Non" class="w-20" />
                </div>

                <!-- Ordre -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Ordre d'affichage</label>
                    <InputNumber v-model="form.order" class="w-full rounded-xl" :min="0" showButtons />
                    <small class="text-slate-400 text-xs">L'ordre est prioritairement géré par glisser-déposer dans le tableau.</small>
                </div>
            </div>

            <template #footer>
                <div class="flex gap-2 justify-end border-t border-slate-100 pt-4 mt-4">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="projectDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-amber-500 border-none hover:bg-amber-600" @click="saveProject" :loading="saving" />
                </div>
            </template>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
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
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';
import Select from 'primevue/select';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

// Props reçues du contrôleur
const props = defineProps({
    projects: [Array, Object],
    categories: [Array, Object],   // Liste des catégories (depuis BDD)
    filters: Object,
});

// Sécurisation
const projectsList = computed(() => props.projects?.data ?? props.projects ?? []);
const categoriesList = computed(() => props.categories?.data ?? props.categories ?? []);

// Recherche serveur
const search = ref(props.filters?.search || '');
const performSearch = () => {
    router.get(route('projects.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Filtres locaux pour DataTable (recherche client)
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// Statuts (fixes, mais peuvent venir de BDD si besoin)
const statuses = [
    { label: 'En cours', value: 'in_progress' },
    { label: 'Terminé', value: 'completed' },
    { label: 'En pause', value: 'paused' },
    { label: 'Annulé', value: 'cancelled' }
];

const statusSeverity = (status) => {
    switch (status) {
        case 'in_progress': return 'info';
        case 'completed': return 'success';
        case 'paused': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

const statusLabel = (status) => {
    return statuses.find(s => s.value === status)?.label || status;
};

// Récupérer le nom de la catégorie depuis son ID
const getCategoryLabel = (categoryId) => {
    const cat = categoriesList.value.find(c => c.id == categoryId);
    return cat ? cat.name : categoryId;
};

// Générer un slug à partir d'une chaîne
const generateSlug = (text) => {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
};

// Formulaire
const defaultProject = {
    id: null,
    title: '',
    slug: '',
    category_id: null,
    description: '',
    start_date: null,
    end_date: null,
    status: 'in_progress',
    image: null,
    existing_image_url: null,
    is_active: true,
    order: 0,
    delete_image: false
};

const form = reactive({ ...defaultProject });

// Gestion image
const imagePreview = ref(null);
const imageFileName = ref('');
const fileInput = ref(null);

const onImageSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        toast.add({ severity: 'error', summary: 'Format invalide', detail: 'Veuillez sélectionner une image.' });
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Fichier trop volumineux', detail: 'Taille max : 20 Mo.' });
        return;
    }
    form.image = file;
    imageFileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target.result; };
    reader.readAsDataURL(file);
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    imageFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    if (isEditing.value && form.existing_image_url) {
        form.delete_image = true;
    }
};

// Auto-slug
watch(() => form.title, (newTitle) => {
    if (!isEditing.value && newTitle && !form.slug) {
        form.slug = generateSlug(newTitle);
    }
});

// Modale
const projectDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);
const selectedProjects = ref([]);

const openNewProject = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultProject)));
    submitted.value = false;
    isEditing.value = false;
    imagePreview.value = null;
    imageFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    projectDialog.value = true;
};

const editProject = (project) => {
    Object.assign(form, {
        id: project.id,
        title: project.title,
        slug: project.slug || '',
        category_id: project.category_id,
        description: project.description || '',
        start_date: project.start_date ? new Date(project.start_date) : null,
        end_date: project.end_date ? new Date(project.end_date) : null,
        status: project.status,
        image: null,
        existing_image_url: project.image_url || null,
        is_active: project.is_active,
        order: project.order,
        delete_image: false
    });
    imagePreview.value = null;
    imageFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    isEditing.value = true;
    projectDialog.value = true;
};

const saveProject = () => {
    submitted.value = true;
    if (!form.title?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le titre est requis.' });
        return;
    }
    if (!form.category_id) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'La catégorie est requise.' });
        return;
    }

    saving.value = true;
    const formData = new FormData();
    formData.append('title', form.title);
    formData.append('slug', form.slug || generateSlug(form.title));
    formData.append('category_id', form.category_id);
    formData.append('description', form.description || '');
    if (form.start_date) formData.append('start_date', form.start_date.toISOString().split('T')[0]);
    if (form.end_date) formData.append('end_date', form.end_date.toISOString().split('T')[0]);
    formData.append('status', form.status);
    formData.append('is_active', form.is_active ? 1 : 0);
    formData.append('order', form.order);
    if (form.image instanceof File) formData.append('image', form.image);
    if (form.delete_image) formData.append('delete_image', '1');

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        router.post(route('projects.update', form.id), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Projet modifié.', life: 3000 });
                projectDialog.value = false;
                saving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                saving.value = false;
            }
        });
    } else {
        router.post(route('projects.store'), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Projet créé.', life: 3000 });
                projectDialog.value = false;
                saving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                saving.value = false;
            }
        });
    }
};

const confirmDeleteProject = (project) => {
    confirm.require({
        message: `Supprimer le projet "${project.title}" ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('projects.destroy', project.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Projet supprimé.', life: 3000 }),
            });
        }
    });
};

const onRowReorder = (event) => {
    const newOrderIds = event.value.map(p => p.id);
    router.post(route('projects.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Ordre mis à jour', detail: 'L\'ordre des projets a été modifié.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec du réordonnancement.' })
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('fr-FR');
};

// Flash messages depuis Laravel
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
:deep(.p-datatable .p-datatable-tbody > tr > td .p-row-reorder-icon) {
    color: #94a3b8;
    transition: color 0.2s;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover .p-row-reorder-icon) {
    color: #0f172a;
}
</style>
