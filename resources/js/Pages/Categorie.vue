<script setup>
import { ref, computed, watch } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, usePage } from '@inertiajs/vue3';
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
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    categories: [Array, Object], // Peut être un tableau ou un objet de pagination
    tags: [Array, Object],
    errors: Object
});

// --- VARIABLES SÉCURISÉES (Gère la pagination ou les tableaux bruts) ---
const categoriesList = computed(() => props.categories?.data ?? props.categories ?? []);
const tagsList = computed(() => props.tags?.data ?? props.tags ?? []);

// --- ÉTATS DES TABLES ---
const selectedCategories = ref([]);
const selectedTags = ref([]);

const filtersCategories = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const filtersTags = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// --- ÉTATS DES MODALES ---
const categoryDialog = ref(false);
const tagDialog = ref(false);
const isEditingCategory = ref(false);
const isEditingTag = ref(false);
const submitted = ref(false);

// --- MODÈLES DE DONNÉES ---
const defaultCategory = {
    id: null,
    name: '',
    slug: '',
    description: '',
    color: 'indigo'
};

const defaultTag = {
    id: null,
    name: '',
    slug: ''
};

const categoryForm = ref({ ...defaultCategory });
const tagForm = ref({ ...defaultTag });

// --- PALETTE DE COULEURS TAILWIND ---
const tailwindColors = [
    { name: 'Gris (Slate)', value: 'slate', hex: '#64748b' },
    { name: 'Rouge (Red)', value: 'red', hex: '#ef4444' },
    { name: 'Orange', value: 'orange', hex: '#f97316' },
    { name: 'Ambre (Amber)', value: 'amber', hex: '#f59e0b' },
    { name: 'Émeraude (Emerald)', value: 'emerald', hex: '#10b981' },
    { name: 'Cyan', value: 'cyan', hex: '#06b6d4' },
    { name: 'Bleu (Blue)', value: 'blue', hex: '#3b82f6' },
    { name: 'Indigo', value: 'indigo', hex: '#6366f1' },
    { name: 'Violet', value: 'violet', hex: '#8b5cf6' },
    { name: 'Fuchsia', value: 'fuchsia', hex: '#d946ef' },
    { name: 'Rose', value: 'rose', hex: '#f43f5e' }
];

// --- UTILITAIRES ---
const generateSlug = (text) => {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')           // Remplace les espaces par -
        .replace(/[^\w\-]+/g, '')       // Supprime les caractères non alphanumériques
        .replace(/\-\-+/g, '-')         // Remplace les - multiples par un seul
        .replace(/^-+/, '')             // Retire les - au début
        .replace(/-+$/, '');            // Retire les - à la fin
};

// Auto-remplissage du slug
watch(() => categoryForm.value.name, (newVal) => {
    if (!isEditingCategory.value && newVal) categoryForm.value.slug = generateSlug(newVal);
});
watch(() => tagForm.value.name, (newVal) => {
    if (!isEditingTag.value && newVal) tagForm.value.slug = generateSlug(newVal);
});

// ==========================================
// LOGIQUE CATÉGORIES
// ==========================================
const openNewCategory = () => {
    categoryForm.value = { ...defaultCategory };
    submitted.value = false;
    isEditingCategory.value = false;
    categoryDialog.value = true;
};

const editCategory = (cat) => {
    categoryForm.value = { ...cat };
    isEditingCategory.value = true;
    categoryDialog.value = true;
};

const saveCategory = () => {
    submitted.value = true;
    if (!categoryForm.value.name?.trim()) return;

    const method = isEditingCategory.value ? 'put' : 'post';
    const routeName = isEditingCategory.value ? route('categories.update', categoryForm.value.id) : route('categories.store');

    router[method](routeName, categoryForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Catégorie enregistrée.', life: 3000 });
            categoryDialog.value = false;
        }
    });
};

const confirmDeleteCategory = (cat) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la catégorie "${cat.name}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('categories.destroy', cat.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Catégorie supprimée.', life: 3000 })
            });
        }
    });
};

// ==========================================
// LOGIQUE TAGS
// ==========================================
const openNewTag = () => {
    tagForm.value = { ...defaultTag };
    submitted.value = false;
    isEditingTag.value = false;
    tagDialog.value = true;
};

const editTag = (tag) => {
    tagForm.value = { ...tag };
    isEditingTag.value = true;
    tagDialog.value = true;
};

const saveTag = () => {
    submitted.value = true;
    if (!tagForm.value.name?.trim()) return;

    const method = isEditingTag.value ? 'put' : 'post';
    const routeName = isEditingTag.value ? route('tags.update', tagForm.value.id) : route('tags.store');

    router[method](routeName, tagForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Mot-clé enregistré.', life: 3000 });
            tagDialog.value = false;
        }
    });
};

const confirmDeleteTag = (tag) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer le tag "${tag.name}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tags.destroy', tag.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Mot-clé supprimé.', life: 3000 })
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Classification - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">

            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-violet-900/50 to-indigo-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-violet-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-indigo-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Configuration" severity="success" class="bg-violet-500/20 text-violet-300 border border-violet-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Taxonomies</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Structurez votre contenu en gérant vos catégories principales et vos mots-clés (tags).</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">

                    <TabView class="custom-tabview">

                        <!-- ================================================================= -->
                        <!-- ONGLET 1 : CATÉGORIES -->
                        <!-- ================================================================= -->
                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-folder"></i>
                                    <span>Catégories</span>
                                    <Badge :value="categoriesList.length" class="ml-2 bg-indigo-100 text-indigo-700 font-bold" />
                                </div>
                            </template>

                            <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                                               <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative flex items-center">
                                <i class="pi pi-search text-slate-400 absolute right-3 z-10" />
                                <InputText
                                    v-model="filtersCategories['global'].value"
                                    placeholder="Rechercher une categorie..."
                                    class="w-full pl-10 rounded-xl bg-white border-slate-200 hover:border-indigo-300 focus:border-indigo-500"
                                />
                            </span>
                                <Button icon="pi pi-plus" label="Nouvelle Catégorie" class="bg-indigo-500 hover:bg-indigo-600 border-none shadow-lg shadow-indigo-500/30 text-white font-bold w-full sm:w-auto" @click="openNewCategory" />
                            </div>

                            <DataTable :value="categoriesList" v-model:selection="selectedCategories" dataKey="id"
                                :paginator="true" :rows="10" :filters="filtersCategories"
                                responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>

                                <template #empty>
                                    <div class="flex flex-col items-center justify-center p-12 text-center">
                                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4"><i class="pi pi-folder-open text-3xl text-slate-400"></i></div>
                                        <h3 class="text-lg font-bold text-slate-800">Aucune catégorie</h3>
                                        <p class="text-slate-500 max-w-sm mb-4">Vous n'avez pas encore créé de catégorie pour classer vos articles.</p>
                                        <Button label="Créer ma première catégorie" class="p-button-outlined" @click="openNewCategory" />
                                    </div>
                                </template>

                                <Column field="name" header="Nom & Détails" sortable style="min-width: 20rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <div :class="`w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-sm bg-${data.color}-500`">
                                                <i class="pi pi-bookmark text-lg"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 text-base">{{ data.name }}</span>
                                                <span class="text-xs text-slate-500 font-mono">{{ data.slug }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="description" header="Description" style="min-width: 20rem">
                                    <template #body="{ data }">
                                        <span class="text-sm text-slate-600 line-clamp-1">{{ data.description || '-' }}</span>
                                    </template>
                                </Column>

                                <Column field="posts_count" header="Articles" sortable style="min-width: 8rem; text-align: center;">
                                    <template #body="{ data }">
                                        <Badge :value="data.posts_count || 0" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                                    </template>
                                </Column>

                                <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editCategory(data)" v-tooltip.top="'Modifier'" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteCategory(data)" v-tooltip.top="'Supprimer'" />
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>

                        <!-- ================================================================= -->
                        <!-- ONGLET 2 : TAGS -->
                        <!-- ================================================================= -->
                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-tags"></i>
                                    <span>Mots-clés (Tags)</span>
                                    <Badge :value="tagsList.length" class="ml-2 bg-violet-100 text-violet-700 font-bold" />
                                </div>
                            </template>

                            <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative flex items-center">
                                <i class="pi pi-search text-slate-400 absolute right-3 z-10" />
                                <InputText
                                    v-model="filtersTags['global'].value"
                                    placeholder="Rechercher un tag..."
                                    class="w-full pl-10 rounded-xl bg-white border-slate-200 hover:border-indigo-300 focus:border-indigo-500"
                                />
                            </span>
                                <Button icon="pi pi-plus" label="Nouveau Tag" class="bg-violet-500 hover:bg-violet-600 border-none shadow-lg shadow-violet-500/30 text-white font-bold w-full sm:w-auto" @click="openNewTag" />
                            </div>

                            <DataTable :value="tagsList" v-model:selection="selectedTags" dataKey="id"
                                :paginator="true" :rows="10" :filters="filtersTags"
                                responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>

                                <template #empty>
                                    <div class="flex flex-col items-center justify-center p-12 text-center">
                                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4"><i class="pi pi-hashtag text-3xl text-slate-400"></i></div>
                                        <h3 class="text-lg font-bold text-slate-800">Aucun tag</h3>
                                        <Button label="Créer un tag" class="p-button-text mt-2" @click="openNewTag" />
                                    </div>
                                </template>

                                <Column field="name" header="Mot-clé" sortable style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-hashtag text-slate-400"></i>
                                            <span class="font-bold text-slate-800">{{ data.name }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="slug" header="Permalien (Slug)" sortable style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <span class="text-sm font-mono text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ data.slug }}</span>
                                    </template>
                                </Column>

                                <Column field="posts_count" header="Utilisations" sortable style="min-width: 8rem; text-align: center;">
                                    <template #body="{ data }">
                                        <Badge :value="data.posts_count || 0" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                                    </template>
                                </Column>

                                <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editTag(data)" v-tooltip.top="'Modifier'" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteTag(data)" v-tooltip.top="'Supprimer'" />
                                    </template>
                                </Column>
                            </DataTable>
                        </TabPanel>

                    </TabView>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODALE : FORMULAIRE CATÉGORIE -->
        <!-- ========================================================================= -->
        <Dialog v-model:visible="categoryDialog" :style="{ width: '500px' }" header="Catégorie" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600"><i class="pi pi-folder"></i></div>
                    <span class="font-bold text-xl text-slate-800">{{ isEditingCategory ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</span>
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Nom de la catégorie <span class="text-red-500">*</span></label>
                    <InputText v-model="categoryForm.name" autofocus :class="{ 'p-invalid': submitted && !categoryForm.name }" class="w-full rounded-xl" placeholder="Ex: Actualités" />
                    <small v-if="submitted && !categoryForm.name" class="p-error">Le nom est requis.</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Permalien (Slug) <span class="text-red-500">*</span></label>
                    <InputText v-model="categoryForm.slug" class="w-full rounded-xl font-mono text-sm" placeholder="ex: actualites" :class="{ 'p-invalid': submitted && !categoryForm.slug }" />
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Couleur d'identification</label>
                    <Dropdown v-model="categoryForm.color" :options="tailwindColors" optionLabel="name" optionValue="value" class="w-full rounded-xl">
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <span :class="`w-4 h-4 rounded-full bg-${slotProps.value}-500 shadow-sm`"></span>
                                <span>{{ tailwindColors.find(c => c.value === slotProps.value)?.name }}</span>
                            </div>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <span :class="`w-4 h-4 rounded-full bg-${slotProps.option.value}-500 shadow-sm`"></span>
                                <span>{{ slotProps.option.name }}</span>
                            </div>
                        </template>
                    </Dropdown>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Description (Optionnel)</label>
                    <Textarea v-model="categoryForm.description" rows="3" class="w-full rounded-xl" placeholder="Courte description de cette catégorie..." />
                </div>
            </div>

            <template #footer>
                <div class="flex gap-2 justify-end border-t border-slate-100 pt-4 mt-4">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="categoryDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-indigo-500 border-none hover:bg-indigo-600" @click="saveCategory" />
                </div>
            </template>
        </Dialog>

        <!-- ========================================================================= -->
        <!-- MODALE : FORMULAIRE TAG -->
        <!-- ========================================================================= -->
        <Dialog v-model:visible="tagDialog" :style="{ width: '450px' }" header="Mot-clé" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center text-violet-600"><i class="pi pi-hashtag"></i></div>
                    <span class="font-bold text-xl text-slate-800">{{ isEditingTag ? 'Modifier le mot-clé' : 'Nouveau mot-clé' }}</span>
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Nom du mot-clé <span class="text-red-500">*</span></label>
                    <InputText v-model="tagForm.name" autofocus :class="{ 'p-invalid': submitted && !tagForm.name }" class="w-full rounded-xl" placeholder="Ex: Innovation" />
                    <small v-if="submitted && !tagForm.name" class="p-error">Le nom est requis.</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Permalien (Slug) <span class="text-red-500">*</span></label>
                    <InputText v-model="tagForm.slug" class="w-full rounded-xl font-mono text-sm" placeholder="ex: innovation" :class="{ 'p-invalid': submitted && !tagForm.slug }" />
                </div>
            </div>

            <template #footer>
                <div class="flex gap-2 justify-end border-t border-slate-100 pt-4 mt-4">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="tagDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-violet-500 border-none hover:bg-violet-600" @click="saveTag" />
                </div>
            </template>
        </Dialog>

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
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

/* ========================================= */
/* TABVIEW CUSTOMIZATION */
/* ========================================= */
:deep(.custom-tabview .p-tabview-nav) {
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0 1rem;
}
:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-weight: 700;
    font-size: 0.95rem;
    padding: 1.25rem 1.5rem;
    box-shadow: none !important;
    transition: all 0.2s;
}
:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
}
:deep(.custom-tabview .p-tabview-panels) {
    padding: 0;
    background: transparent;
}

/* ========================================= */
/* DIALOG CUSTOMIZATION */
/* ========================================= */
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
</style>
