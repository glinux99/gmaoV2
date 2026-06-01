<template>
    <AppLayout>
        <Head title="Sliders Héro - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/50 to-teal-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-emerald-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-teal-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Sliders" severity="success" class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Sliders Héro</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez les diapositives de la bannière principale (glisser‑déposer pour réordonner).</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">

                          <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative flex items-center">
                                <i class="pi pi-search text-slate-400 absolute right-3 z-10" />
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Rechercher un slide..."
                                    class="w-full pl-10 rounded-xl bg-white border-slate-200 hover:border-indigo-300 focus:border-indigo-500"
                                />
                            </span>
                        <Button icon="pi pi-plus" label="Nouveau slide" class="bg-emerald-500 hover:bg-emerald-600 border-none shadow-lg shadow-emerald-500/30 text-white font-bold w-full sm:w-auto" @click="openNewSlide" />
                    </div>

                    <DataTable :value="slidesList" v-model:selection="selectedSlides" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4"><i class="pi pi-images text-3xl text-slate-400"></i></div>
                                <h3 class="text-lg font-bold text-slate-800">Aucun slide</h3>
                                <p class="text-slate-500 max-w-sm mb-4">Ajoutez votre première diapositive pour la bannière principale.</p>
                                <Button label="Créer un slide" class="p-button-outlined" @click="openNewSlide" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />

                        <Column field="order" header="Ordre" sortable style="min-width: 5rem">
                            <template #body="{ data }">
                                <Badge :value="data.order" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                            </template>
                        </Column>

                        <Column field="image" header="Image" style="min-width: 8rem">
                            <template #body="{ data }">
                                <img v-if="data.image_url" :src="data.image_url" class="w-20 h-12 object-cover rounded shadow-sm" />
                                <span v-else class="text-slate-400 text-xs">Aucune</span>
                            </template>
                        </Column>

                        <Column field="badge" header="Badge" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="text-sm">{{ data.badge || '-' }}</span>
                            </template>
                        </Column>

                        <Column field="title" header="Titre" style="min-width: 18rem">
                            <template #body="{ data }">
                                <div class="text-sm">
                                    <span class="font-semibold">{{ data.title_pre }}</span>
                                    <span class="text-emerald-600 font-semibold">{{ data.title_highlight }}</span>
                                    <span>{{ data.title_post }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="is_active" header="Statut" sortable style="min-width: 8rem; text-align: center;">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Actif' : 'Inactif'" />
                            </template>
                        </Column>

                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editSlide(data)" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteSlide(data)" v-tooltip.top="'Supprimer'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODALE SLIDE AVEC UPLOAD IMAGE -->
        <Dialog v-model:visible="slideDialog" :style="{ width: '700px' }" header="Diapositive" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600"><i class="pi pi-image"></i></div>
                    <span class="font-bold text-xl text-slate-800">{{ isEditing ? 'Modifier le slide' : 'Nouveau slide' }}</span>
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <!-- Upload image -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Image *</label>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-4">
    <input type="file" accept="image/*" @change="onImageSelected" ref="fileInput" class="hidden" />
    <Button type="button" label="Choisir une image" icon="pi pi-upload" class="p-button-outlined" @click="$refs.fileInput.click()" />
    <span v-if="imagePreview" class="text-sm text-slate-600">{{ imageFileName || 'Fichier sélectionné' }}</span>
    <Button type="button" icon="pi pi-times" class="p-button-rounded p-button-text p-button-danger" @click="removeImage" v-tooltip.top="'Supprimer l’image'" />
</div>
                        <div v-if="imagePreview" class="mt-2">
                            <img :src="imagePreview" class="h-32 w-auto object-cover rounded-lg border shadow-sm" />
                        </div>
                        <div v-else-if="form.existing_image_url && !imagePreview" class="mt-2">
                            <img :src="form.existing_image_url" class="h-32 w-auto object-cover rounded-lg border shadow-sm" />
                            <small class="text-slate-400 text-xs">Image actuelle</small>
                        </div>
                        <small class="text-slate-400 text-xs">Formats : JPG, PNG, GIF, WebP (max 2 Mo)</small>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Badge (optionnel)</label>
                    <InputText v-model="form.badge" class="w-full rounded-xl" placeholder="Ex: APROJED EN ACTION" />
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold">Titre (avant)</label>
                        <InputText v-model="form.title_pre" class="w-full" placeholder="Ex: Bienvenue chez" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold">Surbrillance</label>
                        <InputText v-model="form.title_highlight" class="w-full" placeholder="APROJED" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold">Titre (après)</label>
                        <InputText v-model="form.title_post" class="w-full" placeholder="Ex: le leader" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Description</label>
                    <Textarea v-model="form.description" rows="3" class="w-full rounded-xl" placeholder="Description du slide..." />
                </div>

                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">Slide actif</label>
                    <ToggleButton v-model="form.is_active" onLabel="Oui" offLabel="Non" class="w-20" />
                </div>
            </div>

            <template #footer>
                <div class="flex gap-2 justify-end border-t border-slate-100 pt-4 mt-4">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="slideDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-emerald-500 border-none hover:bg-emerald-600" @click="saveSlide" :loading="saving" />
                </div>
            </template>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
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
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    slides: [Array, Object]
});

const slidesList = computed(() => props.slides?.data ?? props.slides ?? []);
const selectedSlides = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// États modale
const slideDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

// Formulaire avec gestion du fichier
const defaultSlide = {
    id: null,
    image: null,                // fichier uploadé
    existing_image_url: null,   // pour prévisualisation en édition
    badge: '',
    title_pre: '',
    title_highlight: '',
    title_post: '',
    description: '',
    is_active: true
};
const form = reactive({ ...defaultSlide });

const imagePreview = ref(null);
const imageFileName = ref('');
const fileInput = ref(null);

// Gestion sélection fichier
const onImageSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        toast.add({ severity: 'error', summary: 'Format invalide', detail: 'Veuillez sélectionner une image.' });
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Fichier trop volumineux', detail: 'Taille max : 2 Mo.' });
        return;
    }
    form.image = file;
    imageFileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
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

// Ouvrir modale création
const openNewSlide = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultSlide)));
    submitted.value = false;
    isEditing.value = false;
    imagePreview.value = null;
    imageFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    slideDialog.value = true;
};

// Ouvrir modale édition
const editSlide = (slide) => {
    Object.assign(form, {
        id: slide.id,
        image: null,
        existing_image_url: slide.image_url,
        badge: slide.badge || '',
        title_pre: slide.title_pre || '',
        title_highlight: slide.title_highlight || '',
        title_post: slide.title_post || '',
        description: slide.description || '',
        is_active: slide.is_active,
        delete_image: false
    });
    imagePreview.value = null;
    imageFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    isEditing.value = true;
    slideDialog.value = true;
};

// Sauvegarde avec FormData
const saveSlide = () => {
    submitted.value = true;
    if (!form.image && !form.existing_image_url && !isEditing.value) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'L\'image est obligatoire.' });
        return;
    }
    saving.value = true;

    const formData = new FormData();
    formData.append('badge', form.badge || '');
    formData.append('title_pre', form.title_pre || '');
    formData.append('title_highlight', form.title_highlight || '');
    formData.append('title_post', form.title_post || '');
    formData.append('description', form.description || '');
    formData.append('is_active', form.is_active ? 1 : 0);

    if (form.image instanceof File) {
        formData.append('image', form.image);
    }

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        if (form.delete_image) {
            formData.append('delete_image', '1');
        }
        router.post(route('hero-slides.update', form.id), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Slide modifié.', life: 3000 });
                slideDialog.value = false;
                saving.value = false;
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(errors).flat()[0] });
                saving.value = false;
            }
        });
    } else {
        router.post(route('hero-slides.store'), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Slide ajouté.', life: 3000 });
                slideDialog.value = false;
                saving.value = false;
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(errors).flat()[0] });
                saving.value = false;
            }
        });
    }
};

// Suppression
const confirmDeleteSlide = (slide) => {
    confirm.require({
        message: `Supprimer définitivement ce slide ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('hero-slides.destroy', slide.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Slide supprimé', life: 3000 }),
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Suppression impossible' })
            });
        }
    });
};

// Réordonnancement (drag & drop natif DataTable)
const onRowReorder = (event) => {
    const newOrderIds = event.value.map(s => s.id);
    router.post(route('hero-slides.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Ordre mis à jour', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le réordonnancement a échoué.' })
    });
};
</script>

<style scoped>
/* Reprise des styles identiques aux autres modules */
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
