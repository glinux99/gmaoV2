<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIMEVUE COMPONENTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Editor from 'primevue/editor';
import FileUpload from 'primevue/fileupload';
import InputSwitch from 'primevue/inputswitch';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";
import SplitButton from 'primevue/splitbutton';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Skeleton from 'primevue/skeleton';
import InputNumber from 'primevue/inputnumber';
import Tooltip from 'primevue/tooltip';


const toast = useToast()
const confirm = useConfirm()

const props = defineProps({
  posts: Object,
  categories: Array,
  tags: Array,
  users: Array,
  queryParams: Object,
})

const postsList = computed(() => props.posts?.data ?? [])
const totalRecords = computed(() => props.posts?.total ?? 0)

const dt = ref()
const selectedPosts = ref([])
const viewMode = ref('list')
const isLoading = ref(false)
const postSidebarVisible = ref(false)
const previewDialogVisible = ref(false)
const isEditing = ref(false)
const submitted = ref(false)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  status: { value: null, matchMode: FilterMatchMode.EQUALS },
  'category.name': { value: null, matchMode: FilterMatchMode.EQUALS },
  author: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const statuses = [
  { label: 'Publié', value: 'published', severity: 'success', icon: 'pi pi-check-circle' },
  { label: 'Brouillon', value: 'draft', severity: 'secondary', icon: 'pi pi-file-edit' },
  { label: 'Planifié', value: 'scheduled', severity: 'warning', icon: 'pi pi-calendar-clock' },
  { label: 'Archivé', value: 'archived', severity: 'danger', icon: 'pi pi-box' },
]

const defaultPost = {
  id: null,
  title: '',
  slug: '',
  content: '',
  excerpt: '',
  cover_image: null,
  cover_file: null, // Ajouté pour stocker le fichier physique
  status: 'draft',
  category_id: null,
  tags: [],
  author_id: props.users?.[0]?.id ?? null,
  published_at: new Date(),
  seo_title: '',
  seo_description: '',
  is_featured: false,
  views: 0,
  likes: 0,
}

const currentPost = ref({ ...defaultPost })

onMounted(() => {
  if (props.queryParams?.filters?.global?.value) {
    filters.value.global.value = props.queryParams.filters.global.value
  }
  if (props.queryParams?.filters?.status?.value) {
    filters.value.status.value = props.queryParams.filters.status.value
  }
  if (props.queryParams?.filters?.['category.name']?.value) {
    filters.value['category.name'].value = props.queryParams.filters['category.name'].value
  }
  if (props.queryParams?.filters?.author?.value) {
    filters.value.author.value = props.queryParams.filters.author.value
  }
})

const loadData = (event = {}) => {
  router.get(route('posts.index'), {
    page: event.page ? event.page + 1 : props.posts?.current_page ?? 1,
    rows: event.rows ?? props.posts?.per_page ?? 10,
    sortField: event.sortField ?? props.queryParams?.sortField ?? 'created_at',
    sortOrder: event.sortOrder ?? props.queryParams?.sortOrder ?? 'desc',
    filters: {
      global: filters.value.global,
      status: filters.value.status,
      'category.name': filters.value['category.name'],
      author: filters.value.author,
    },
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

watch(filters, () => {
  loadData()
}, { deep: true })

const initFilters = () => {
  filters.value = {
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    'category.name': { value: null, matchMode: FilterMatchMode.EQUALS },
    author: { value: null, matchMode: FilterMatchMode.CONTAINS },
  }
  loadData()
}

const getStatusSeverity = (status) => {
  const found = statuses.find(s => s.value === status)
  return found ? found.severity : 'secondary'
}

const getStatusLabel = (status) => {
  const found = statuses.find(s => s.value === status)
  return found ? found.label : status
}

const getStatusIcon = (status) => {
  const found = statuses.find(s => s.value === status)
  return found ? found.icon : 'pi pi-info-circle'
}

const formatDate = (value) => {
  if (!value) return ''
  return new Date(value).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const calculateReadingTime = (text) => {
  if (!text) return 1
  const words = text.replace(/<[^>]*>/g, '').split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.ceil(words / 200))
}

const openNew = () => {
  currentPost.value = { ...defaultPost, published_at: new Date() }
  isEditing.value = false
  submitted.value = false
  postSidebarVisible.value = true
}

const editPost = (post) => {
  currentPost.value = {
    ...post,
    category_id: post.category_id ?? post.category?.id ?? null,
    author_id: post.author_id ?? post.author?.id ?? null,
    tags: post.tags?.map(t => t.id) ?? [],
    cover_file: null, // On s'assure de réinitialiser le fichier d'upload
  }
  isEditing.value = true
  postSidebarVisible.value = true
}

const hideDialog = () => {
  postSidebarVisible.value = false
  submitted.value = false
}

// ----------------------------------------------------
// LOGIQUE DE SAUVEGARDE (AVEC GESTION DU FICHIER)
// ----------------------------------------------------
const savePost = () => {
  submitted.value = true
  if (!currentPost.value.title || (!currentPost.value.category && !currentPost.value.category_id)) return

  const url = isEditing.value
    ? route('posts.update', currentPost.value.id)
    : route('posts.store')

  // Création du formulaire Inertia
  const form = useForm({
      ...currentPost.value,
      cover_image: currentPost.value.cover_file || null
  });

  form.transform((data) => {
    const transformedData = {
      ...data,
      category_id: data.category?.id || data.category_id,
      author_id: data.author_id || props.users?.[0]?.id || null,
      published_at: data.published_at ? new Date(data.published_at).toISOString().split('T')[0] : null,
    };

    // Règle d'or Laravel : pour modifier avec un fichier, on fait un POST + _method: 'put'
    if (isEditing.value) {
      transformedData._method = 'put';
    }

    return transformedData;
  }).post(url, { // Note: On utilise TOUJOURS .post() ici à cause du fichier !
    forceFormData: true, // Force le format multipart
    preserveScroll: true,
    onSuccess: () => {
      postSidebarVisible.value = false;
      toast.add({ severity: 'success', summary: 'Succès', detail: `Publication ${isEditing.value ? 'mise à jour' : 'créée'} avec succès.`, life: 3000 });
    },
    onError: (errors) => {
      const errorDetails = Object.values(errors).join(', ');
      toast.add({ severity: 'error', summary: 'Erreur de sauvegarde', detail: errorDetails, life: 5000 });
    }
  });
}

const confirmDeletePost = (post) => {
  confirm.require({
    message: 'Voulez-vous supprimer cet article ?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('posts.destroy', post.id), {
        preserveScroll: true,
        onSuccess: () => {
          toast.add({
            severity: 'success',
            summary: 'Supprimé',
            detail: 'Publication supprimée avec succès.',
            life: 3000,
          })
        },
      })
    },
  })
}

const confirmDeleteSelected = () => {
  const ids = selectedPosts.value.map(p => p.id)
  if (!ids.length) return

  confirm.require({
    message: 'Voulez-vous supprimer les publications sélectionnées ?',
    header: 'Suppression groupée',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('posts.bulkDestroy'), {
        data: { ids },
        preserveScroll: true,
        onSuccess: () => {
          selectedPosts.value = []
        },
      })
    },
  })
}

const duplicatePost = (post) => {
    const form = useForm({ ...post });
    form.submit('post', route('posts.duplicate', post.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Publication dupliquée avec succès.', life: 3000 });
        },
        onError: (errors) => {
            const errorDetails = Object.values(errors).join(', ');
            toast.add({ severity: 'error', summary: 'Erreur de duplication', detail: errorDetails, life: 5000 });
        }
    });
}

// ----------------------------------------------------
// GESTION UPLOAD IMAGE COUVERTURE
// ----------------------------------------------------
const fileUploadRef = ref(null);

const triggerUpload = () => {
    const fileInput = fileUploadRef.value.$el.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.click();
    }
};

const onUploadCover = (event) => {
    const file = event.files[0];

    if (file) {
        // Stockage du fichier physique pour l'envoi au serveur
        currentPost.value.cover_file = file;

        // Génération de l'aperçu local
        const reader = new FileReader();
        reader.onload = (e) => {
            currentPost.value.cover_image = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeCover = () => {
    currentPost.value.cover_image = null;
    currentPost.value.cover_file = null;

    if (fileUploadRef.value) {
        fileUploadRef.value.clear();
    }
};
// A METTRE APRÈS : const currentPost = ref({ ...defaultPost })

// --- ÉTATS DU SUPER ÉDITEUR ---
const isFullscreen = ref(false);

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
    if (isFullscreen.value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
};

// Statistiques en temps réel de l'article (Version sécurisée)
const editorStats = computed(() => {
    // Sécurité : si currentPost n'est pas encore chargé, on renvoie des zéros
    if (!currentPost.value?.content) {
        return { words: 0, chars: 0, readTime: 1 };
    }

    // Supprime les balises HTML pour ne compter que le texte pur
    const plainText = currentPost.value.content.replace(/<[^>]*>?/gm, '').trim();

    const words = plainText ? plainText.split(/\s+/).filter(word => word.length > 0).length : 0;
    const chars = plainText.length;
    const readTime = Math.max(1, Math.ceil(words / 200)); // Moyenne de 200 mots/min

    return { words, chars, readTime };
});
const editorRef = ref(null);

// 2. Fonction de chargement "propre" (Adaptation de votre code)
const forceEditorLoad = () => {
    // Si l'éditeur n'est pas encore monté dans le DOM, on arrête
    if (!editorRef.value || !editorRef.value.quill) return;

    // On récupère le contenu (soit de l'article chargé, soit vide si c'est un nouveau)
    const htmlContent = currentPost.value.content || "";

    // On utilise le convertisseur natif de Quill
    const delta = editorRef.value.quill.clipboard.convert({
        html: htmlContent
    });

    // nextTick s'assure que Vue a fini de peindre l'écran avant d'injecter
    nextTick(() => {
        // 'silent' est très important : ça évite de déclencher une fausse "modification" du texte
        editorRef.value.quill.setContents(delta, 'silent');
    });
};

// 3. On observe l'ouverture de la fenêtre (Sidebar/Modale)
watch(() => postSidebarVisible.value, (isVisible) => {
    // Si la fenêtre s'ouvre (en Création OU en Édition)
    if (isVisible) {
        // On attend que l'animation d'ouverture commence et que l'éditeur soit dans le DOM
        nextTick(() => {
            // Un léger délai garantit que Quill est 100% prêt à recevoir le contenu
            setTimeout(() => {
                forceEditorLoad();
            }, 50);
        });
    }
});
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Publications - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">

            <!-- ========================================== -->
            <!-- HEADER HERO -->
            <!-- ========================================== -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <!-- Décorations de fond premium -->
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-900/50 to-emerald-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-indigo-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-emerald-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module CMS" severity="success" class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Publications</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Rédigez, planifiez et diffusez vos articles, rapports et actualités sur tous vos canaux.</p>
                    </div>
                    <div class="flex gap-3">
                        <Button icon="pi pi-download" label="Exporter CSV" class="p-button-outlined !text-white !border-slate-600 hover:!bg-slate-800" @click="exportCSV" />
                        <Button icon="pi pi-plus" label="Nouvel Article" class="bg-indigo-500 hover:bg-indigo-600 border-none shadow-lg shadow-indigo-500/30 text-white font-bold px-6" @click="openNew" />
                    </div>
                </div>
            </div>

            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20 space-y-6">

                <!-- ========================================== -->
                <!-- MINI STATS CARDS -->
                <!-- ========================================== -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center gap-5 transition-transform hover:-translate-y-1 duration-300">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-emerald-500/30"><i class="pi pi-check-circle"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Articles Publiés</p>
                            <h3 class="text-3xl font-black text-slate-800">{{ isLoading ? '...' : stats?.published ?? '' }}</h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center gap-5 transition-transform hover:-translate-y-1 duration-300">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-blue-500/30"><i class="pi pi-eye"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Vues Cumulées</p>
                            <h3 class="text-3xl font-black text-slate-800">{{ isLoading ? '...' : stats?.totalViews.toLocaleString() ?? 0 }}</h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center gap-5 transition-transform hover:-translate-y-1 duration-300">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-rose-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-orange-500/30"><i class="pi pi-pen-to-square"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">En Rédaction</p>
                            <h3 class="text-3xl font-black text-slate-800">{{ isLoading ? '...' : stats?.drafts ?? 0 }}</h3>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- MAIN WORKSPACE -->
                <!-- ========================================== -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">

                    <!-- TOOLBAR -->
                    <div class="p-4 lg:p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-50/50">
                        <!-- Search & Filters -->
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <span class="p-input-icon-left w-full sm:w-72 lg:w-80 relative flex items-center">
                                <i class="pi pi-search text-slate-400 absolute right-3 z-10" />
                                <InputText
                                    v-model="filters['global'].value"
                                    placeholder="Rechercher par titre, auteur..."
                                    class="w-full pl-10 rounded-xl bg-white border-slate-200 hover:border-indigo-300 focus:border-indigo-500"
                                />
                            </span>
                            <Dropdown v-model="filters['status']" :options="statuses" optionLabel="label" optionValue="value" placeholder="Tous les statuts" :showClear="true" class="w-full sm:w-48 rounded-xl bg-white border-slate-200">
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <i :class="[slotProps.option.icon, `text-${slotProps.option.severity}-500`]"></i>
                                        <span>{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </Dropdown>
                            <Dropdown v-model="filters['category.name']" :options="categories" optionLabel="name" optionValue="name" placeholder="Toutes catégories" :showClear="true" class="w-full sm:w-48 rounded-xl bg-white border-slate-200" />
                        </div>

                        <!-- View Toggle & Bulk Actions -->
                        <div class="flex items-center gap-3 self-end lg:self-auto">
                            <Button v-if="selectedPosts && selectedPosts.length > 0" icon="pi pi-trash" :label="`${selectedPosts.length} sél.`" severity="danger" @click="confirmDeleteSelected" class="p-button-outlined bg-white" />
                            <div class="bg-slate-100/80 border border-slate-200 rounded-xl p-1 flex">
                                <button @click="viewMode = 'list'" :class="['px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 flex items-center gap-2', viewMode === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800']"><i class="pi pi-list"></i> <span class="hidden sm:inline">Liste</span></button>
                                <button @click="viewMode = 'grid'" :class="['px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 flex items-center gap-2', viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800']"><i class="pi pi-th-large"></i> <span class="hidden sm:inline">Grille</span></button>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENT: SKELETON -->
                    <div v-if="isLoading" class="p-6">
                        <DataTable :value="new Array(5)">
                            <Column style="width: 5%"><template #body><Skeleton></Skeleton></template></Column>
                            <Column header="Titre" style="width: 40%"><template #body><div class="flex gap-4 items-center"><Skeleton size="4rem" shape="square" class="rounded-lg"></Skeleton><div class="space-y-2 w-full"><Skeleton width="80%"></Skeleton><Skeleton width="40%"></Skeleton></div></div></template></Column>
                            <Column header="Statut" style="width: 15%"><template #body><Skeleton width="5rem" height="1.5rem" class="rounded-full"></Skeleton></template></Column>
                            <Column header="Date" style="width: 15%"><template #body><Skeleton width="6rem"></Skeleton></template></Column>
                        </DataTable>
                    </div>

                    <!-- CONTENT TRANSITION WRAPPER -->
                    <Transition name="fade" mode="out-in">

                        <!-- DATATABLE (LIST VIEW) -->
                        <div v-if="!isLoading && viewMode === 'list'" key="list">
                            <DataTable ref="dt" :value="postsList" v-model:selection="selectedPosts" dataKey="id"
                                :paginator="true" :rows="10" :filters="filters" filterDisplay="menu"
                                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                                :rowsPerPageOptions="[10, 25, 50]" currentPageReportTemplate="{first} - {last} sur {totalRecords} publications"
                                responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows>

                                <template #empty>
                                    <div class="flex flex-col items-center justify-center p-12 text-center">
                                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="pi pi-search text-4xl text-slate-400"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800 mb-1">Aucune publication trouvée</h3>
                                        <p class="text-slate-500 max-w-sm">Essayez de modifier vos filtres ou de rechercher un autre terme.</p>
                                        <Button label="Réinitialiser les filtres" class="p-button-text mt-4" @click="initFilters" />
                                    </div>
                                </template>

                                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                                <Column field="title" header="Titre & Informations" sortable style="min-width: 30rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-4 py-2">
                                            <div class="relative w-24 h-16 rounded-xl overflow-hidden shrink-0 border border-slate-200 shadow-sm bg-slate-100">
                                                <img v-if="data.cover_image" :src="data.cover_image" :alt="data.title" class="w-full h-full object-cover" loading="lazy" />
                                                <i v-else class="pi pi-image absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-slate-300 text-xl"></i>
                                                <div v-if="data.isFeatured" class="absolute top-1 left-1 bg-amber-500 text-white p-0.5 rounded text-[10px] shadow-sm"><i class="pi pi-star-fill"></i></div>
                                            </div>
                                            <div class="flex flex-col flex-1 min-w-0">
                                                <span class="font-bold text-slate-800 text-base leading-snug hover:text-indigo-600 cursor-pointer transition truncate" @click="editPost(data)" :title="data.title">{{ data.title }}</span>
                                                <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-500">
                                                    <span class="flex items-center gap-1"><span :class="['w-2 h-2 rounded-full', data.category?.color ? `bg-${data.category.color}-500` : 'bg-slate-400']"></span> <span class="font-medium text-slate-700">{{ data.category?.name || 'Non classé' }}</span></span>
                                                    <span>•</span>
                                                    <span class="flex items-center gap-1"><Avatar :label="data.author.name.charAt(0).toUpperCase()" shape="circle" size="small" class="w-4 h-4 text-[8px]" /> {{ data.author.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="status" header="État" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" :icon="getStatusIcon(data.status)" class="px-3 py-1 font-bold tracking-wide uppercase text-[10px] border" :class="`border-${getStatusSeverity(data.status)}-200`" rounded />
                                    </template>
                                </Column>

                                <Column field="views" header="Impact" sortable style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-3 text-sm text-slate-600">
                                                <span class="flex items-center gap-1 w-12" v-tooltip.top="'Vues uniques'"><i class="pi pi-eye text-indigo-400"></i> <span class="font-medium">{{ data.views }}</span></span>
                                                <span class="flex items-center gap-1" v-tooltip.top="'J\'aime'"><i class="pi pi-heart-fill text-rose-400"></i> <span class="font-medium">{{ data.likes }}</span></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template #filterapply="slotProps"></template>
                                </Column>

                                <Column field="publishDate" header="Date" sortable dataType="date" style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span class="text-slate-600 font-medium whitespace-nowrap"><i class="pi pi-calendar mr-2 text-slate-400"></i>{{ formatDate(data.publishDate) }}</span>
                                    </template>
                                </Column>

                                <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                                    <template #body="{ data }">
                                        <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity" style="opacity: 1;">
                                            <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="duplicatePost(data)" v-tooltip.top="'Dupliquer'" />
                                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info hover:bg-blue-50" @click="editPost(data)" v-tooltip.top="'Modifier'" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger hover:bg-red-50" @click="confirmDeletePost(data)" v-tooltip.top="'Supprimer'" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- GRID VIEW -->
                        <div v-else-if="!isLoading && viewMode === 'grid'" key="grid" class="p-6 bg-slate-50/50">
                            <!-- Empty state Grid -->
                            <div v-if="postsList.length === 0" class="flex flex-col items-center justify-center p-12 text-center w-full">
                                <i class="pi pi-inbox text-5xl text-slate-300 mb-4"></i>
                                <h3 class="text-lg font-bold text-slate-800">Aucun résultat</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                <div v-for="post in postsList" :key="post.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group flex flex-col cursor-pointer" @click="editPost(post)">

                                    <!-- Image Header -->
                                    <div class="relative h-48 overflow-hidden bg-slate-100">
                                        <img v-if="post.cover_image" :src="post.cover_image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy" />
                                        <i v-else class="pi pi-image absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-slate-300 text-3xl"></i>

                                        <!-- Badges overlay -->
                                        <div class="absolute top-3 left-3 flex flex-col gap-2 items-start">
                                            <Tag :value="getStatusLabel(post.status)" :severity="getStatusSeverity(post.status)" class="shadow-md backdrop-blur-md bg-white/90" />
                                            <div v-if="post.isFeatured" class="bg-amber-500 text-white px-2 py-1 rounded shadow-md text-[10px] uppercase tracking-wider font-bold flex items-center gap-1"><i class="pi pi-star-fill text-[10px]"></i> À la une</div>
                                        </div>

                                        <!-- Read time badge -->
                                        <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-white px-2 py-1 rounded text-xs font-medium flex items-center gap-1">
                                            <i class="pi pi-clock text-[10px]"></i> {{ calculateReadingTime(post.content) }} min
                                        </div>
                                    </div>

                                    <!-- Content Body -->
                                    <div class="p-5 flex-1 flex flex-col">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span :class="['w-2 h-2 rounded-full', post.category?.color ? `bg-${post.category.color}-500` : 'bg-slate-400']"></span>
                                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ post.category?.name || 'Général' }}</span>
                                        </div>
                                        <h3 class="text-lg font-black text-slate-800 leading-tight mb-2 line-clamp-2 group-hover:text-indigo-600 transition-colors">{{ post.title }}</h3>
                                        <p class="text-sm text-slate-500 mb-4 line-clamp-2 flex-1">{{ post.excerpt || 'Aucun résumé disponible pour cet article...' }}</p>

                                        <Divider class="!my-3" />

                                        <div class="flex justify-between items-center text-xs text-slate-500">
                                            <span class="flex items-center gap-2"><Avatar :label="post.author.name.charAt(0)" shape="circle" size="small" class="bg-slate-100 text-slate-600 font-bold" /> <span class="truncate w-24">{{ post.author.name }}</span></span>
                                            <span class="font-medium">{{ formatDate(post.publishDate) }}</span>
                                        </div>
                                    </div>

                                    <!-- Hover Actions -->
                                    <div class="px-4 pb-4 pt-12 flex justify-between gap-2 opacity-0 group-hover:opacity-100 transition-opacity absolute bottom-0 left-0 w-full bg-gradient-to-t from-white via-white to-transparent transform translate-y-4 group-hover:translate-y-0 duration-300">
                                        <Button label="Modifier" icon="pi pi-pencil" class="p-button-sm w-full bg-indigo-50 text-indigo-700 border-none hover:bg-indigo-100" @click.stop="editPost(post)" />
                                        <Button icon="pi pi-copy" class="p-button-sm p-button-secondary p-button-outlined" @click.stop="duplicatePost(post)" v-tooltip.top="'Dupliquer'" />
                                        <Button icon="pi pi-trash" class="p-button-sm p-button-danger p-button-outlined" @click.stop="confirmDeletePost(post)" v-tooltip.top="'Supprimer'" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </Transition>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PRO EDITOR SIDEBAR (FULL SCREEN EXPERIENCE) -->
        <!-- ========================================================================= -->
      <!-- ========================================================================= -->
        <!-- PRO EDITOR DIALOG (MODAL EXPERIENCE) -->
        <!-- ========================================================================= --><!-- ========================================================================= -->
<!-- 1. DIALOG : ÉDITEUR DE PUBLICATION (POST EDITOR) -->
<!-- ========================================================================= -->
<Dialog
    v-model:visible="postSidebarVisible"
    modal
    :showHeader="false"
    :style="{ width: '95vw', height: '95vh', maxWidth: '1600px' }"
    :breakpoints="{ '1024px': '100vw', '640px': '100vw' }"
    class="custom-editor-dialog overflow-hidden rounded-2xl shadow-2xl"
    maximizable
>
    <div class="flex flex-col h-full bg-slate-50/50">

        <!-- HEADER FIXE (Navigation & Actions) -->
        <header class="flex-none flex w-full items-center justify-between px-4 lg:px-6 py-3 lg:py-4 bg-white/95 backdrop-blur-md border-b border-slate-200 z-50 shadow-sm transition-all">
            <div class="flex items-center gap-4">
                <Button
                    icon="pi pi-times"
                    class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-red-50 hover:text-red-600 border border-transparent hover:border-red-100 transition-all duration-300"
                    @click="hideDialog"
                    v-tooltip.right="'Fermer l\'éditeur'"
                />
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg lg:text-xl font-black text-slate-800 m-0 tracking-tight line-clamp-1">
                            {{ isEditing ? 'Éditer la publication' : 'Créer une publication' }}
                        </h2>
                        <Tag
                            v-if="isEditing"
                            :value="getStatusLabel(currentPost?.status)"
                            :severity="getStatusSeverity(currentPost?.status)"
                            class="hidden sm:inline-flex text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-md"
                        />
                    </div>
                    <p class="text-[11px] lg:text-xs text-slate-500 m-0 mt-1 flex items-center gap-1.5 font-medium">
                        <i class="pi pi-cloud-upload text-emerald-500"></i>
                        Brouillon auto-sauvegardé à {{ new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'}) }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 lg:gap-3">
                <Button
                    icon="pi pi-desktop"
                    label="Aperçu"
                    class="p-button-outlined p-button-secondary hidden md:flex p-button-sm font-semibold hover:bg-slate-50"
                    v-tooltip.bottom="'Voir le rendu final'"
                    @click="openPreview"
                />
                <SplitButton
                    :label="isEditing && currentPost?.status === 'published' ? 'Mettre à jour' : 'Publier'"
                    icon="pi pi-send"
                    @click="savePost"
                    :model="saveActionMenu"
                    class="custom-splitbutton shadow-md hover:shadow-lg transition-shadow"
                />
            </div>
        </header>

        <!-- CORPS DE L'ÉDITEUR -->
        <div class="flex-1 flex flex-col xl:flex-row overflow-hidden">

            <!-- MAIN (GAUCHE) : ZONE D'ÉDITION PRINCIPALE -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-8 xl:p-10 custom-scrollbar pb-32">
                <div class="max-w-4xl mx-auto space-y-8">

                    <!-- Zone de Titre -->
                    <div class="group relative">
                        <Textarea
                            v-model="currentPost.title"
                            autoResize
                            placeholder="Saisissez un titre percutant ici..."
                            class="w-full text-4xl lg:text-5xl font-black text-slate-800 border-2 border-transparent bg-transparent shadow-none hover:bg-white focus:bg-white focus:border-indigo-100 rounded-2xl p-4 transition-all duration-300 !outline-none focus:ring-4 focus:ring-indigo-500/10 resize-none placeholder-slate-300 leading-tight"
                            rows="1"
                            :class="{'border-red-300 bg-red-50/50': submitted && !currentPost?.title}"
                        />
                        <div v-if="submitted && !currentPost?.title" class="absolute -bottom-6 left-4 flex items-center gap-1.5 text-red-600 text-xs font-bold bg-red-50 border border-red-100 px-2.5 py-1 rounded-md shadow-sm">
                            <i class="pi pi-exclamation-circle"></i> Le titre est requis.
                        </div>
                    </div>

                    <!-- Permalien -->
                    <div class="px-4 flex flex-wrap items-center gap-2 text-sm text-slate-500 bg-white shadow-sm p-2 rounded-xl border border-slate-200 inline-flex group hover:border-indigo-200 transition-colors">
                        <i class="pi pi-link text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                        <span class="font-semibold hidden sm:inline text-slate-600">Permalien :</span>
                        <span class="text-slate-400">aprojed.org/post/</span>
                        <InputText
                            v-model="currentPost.slug"
                            class="p-inputtext-sm border-transparent hover:border-slate-300 focus:border-indigo-400 bg-slate-50 hover:bg-white focus:bg-white px-2 py-1 rounded-md w-auto min-w-[150px] shadow-none font-mono text-xs text-indigo-600 transition-all focus:ring-2 focus:ring-indigo-500/20"
                        />
                    </div>

                    <!-- L'ÉDITEUR RICHE (Quill Custom Pro) -->
                    <div :class="[
                        'transition-all duration-500 ease-in-out flex flex-col',
                        isFullscreen ? 'fixed inset-0 z-[9999] bg-slate-100/90 backdrop-blur-sm p-4 md:p-8' : 'bg-white rounded-3xl shadow-sm border border-slate-200 focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-300'
                    ]">
                        <div :class="['flex flex-col h-full overflow-hidden relative', isFullscreen ? 'bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-6xl mx-auto w-full' : '']">

                            <!-- En-tête Plein écran -->
                            <div v-if="isFullscreen" class="flex items-center justify-between p-4 border-b border-slate-100 bg-white">
                                <div class="flex items-center gap-3">
                                    <Badge value="Mode Concentration" class="bg-indigo-50 text-indigo-600 border border-indigo-100 px-3 py-1 text-xs" />
                                    <span class="text-sm font-bold text-slate-800 line-clamp-1">{{ currentPost?.title || 'Article sans titre' }}</span>
                                </div>
                                <Button icon="pi pi-compress" label="Quitter" class="p-button-sm p-button-secondary p-button-text hover:bg-slate-100" @click="toggleFullscreen" />
                            </div>

                            <Editor
                                v-model="currentPost.content"
                                ref="editorRef"
                                @load="forceEditorLoad"
                                :editorStyle="isFullscreen ? 'height: calc(100vh - 200px)' : 'min-height: 550px'"
                                class="super-editor block w-full text-lg text-slate-700"
                            >
                                <template #toolbar>
                                    <div class="flex flex-wrap items-center gap-2 p-2 sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-100">
                                        <!-- Typographie -->
                                        <div class="editor-group flex items-center bg-slate-50 rounded-lg p-1 border border-slate-200">
                                            <select class="ql-font text-xs p-1" title="Police"></select>
                                            <div class="w-px h-4 bg-slate-300 mx-1"></div>
                                            <select class="ql-size text-xs p-1" title="Taille"></select>
                                            <div class="w-px h-4 bg-slate-300 mx-1"></div>
                                            <select class="ql-header text-xs p-1" title="Titres"></select>
                                        </div>

                                        <!-- Formatage basique -->
                                        <div class="editor-group flex items-center bg-slate-50 rounded-lg p-1 border border-slate-200">
                                            <button class="ql-bold p-1.5 text-slate-600 hover:bg-slate-200 rounded transition-colors" title="Gras (Ctrl+B)"></button>
                                            <button class="ql-italic p-1.5 text-slate-600 hover:bg-slate-200 rounded transition-colors" title="Italique (Ctrl+I)"></button>
                                            <button class="ql-underline p-1.5 text-slate-600 hover:bg-slate-200 rounded transition-colors" title="Souligné (Ctrl+U)"></button>
                                        </div>

                                        <!-- Média & Blocs -->
                                        <div class="editor-group flex items-center bg-slate-50 rounded-lg p-1 border border-slate-200">
                                            <button class="ql-blockquote p-1.5 text-slate-600 hover:bg-slate-200 rounded" title="Citation"></button>
                                            <button class="ql-list p-1.5 text-slate-600 hover:bg-slate-200 rounded" value="ordered" title="Numérotée"></button>
                                            <button class="ql-list p-1.5 text-slate-600 hover:bg-slate-200 rounded" value="bullet" title="Puces"></button>
                                            <div class="w-px h-4 bg-slate-300 mx-1"></div>
                                            <button class="ql-link p-1.5 text-slate-600 hover:bg-slate-200 rounded" title="Lien"></button>
                                            <button class="ql-image p-1.5 text-slate-600 hover:bg-slate-200 rounded" title="Image"></button>
                                        </div>

                                        <!-- Bouton Plein écran -->
                                        <div class="ml-auto flex items-center">
                                            <button type="button" class="flex items-center gap-2 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-all" @click="toggleFullscreen">
                                                <i :class="isFullscreen ? 'pi pi-window-minimize' : 'pi pi-window-maximize'"></i>
                                                <!-- <span class="hidden md:inline">{{ isFullscreen ? 'Réduire' : 'Plein écran' }}</span> -->
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </Editor>

                            <!-- Footer Statistiques Éditeur -->
                            <div class="bg-slate-50 border-t border-slate-200 px-5 py-3 flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center gap-1.5 text-emerald-600"><i class="pi pi-check-circle"></i> Auto-save ON</span>
                                    <span v-if="(editorStats?.words || 0) > 0" class="hidden sm:inline-block text-slate-300">|</span>
                                    <span v-if="(editorStats?.words || 0) > 0" class="text-indigo-600 flex items-center gap-1.5"><i class="pi pi-stopwatch"></i> {{ editorStats?.readTime || 1 }} min de lecture</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span>Mots : <span class="font-black text-slate-800">{{ (editorStats?.words || 0).toLocaleString() }}</span></span>
                                    <span>Caractères : <span class="font-black text-slate-800">{{ (editorStats?.chars || 0).toLocaleString() }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Extrait (Excerpt) -->
                    <div class="bg-white rounded-3xl p-6 lg:p-8 shadow-sm border border-slate-200 hover:border-indigo-100 transition-colors">
                        <h3 class="text-lg font-bold text-slate-800 mb-2 flex items-center gap-2">
                            <i class="pi pi-align-left text-indigo-500 bg-indigo-50 p-2 rounded-lg"></i> Extrait (Résumé court)
                        </h3>
                        <p class="text-sm text-slate-500 mb-4 font-medium">Ce texte accrocheur apparaîtra sur les cartes de prévisualisation et sur l'accueil.</p>
                        <Textarea
                            v-model="currentPost.excerpt"
                            rows="3"
                            class="w-full rounded-xl bg-slate-50 border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"
                            placeholder="Rédigez un court résumé de 2 à 3 phrases..."
                        />
                    </div>
                </div>
            </main>

            <!-- ASIDE (DROITE) : PARAMÈTRES (Sidebar latérale fixe) -->
            <aside class="w-full xl:w-[400px] bg-slate-50/50 border-t xl:border-t-0 xl:border-l border-slate-200 overflow-y-auto custom-scrollbar flex-shrink-0 relative z-20 shadow-[-10px_0_20px_rgba(0,0,0,0.02)]">
                <TabView class="custom-tabview h-full">

                    <!-- TAB 1: PARAMÈTRES -->
                    <TabPanel header="Paramètres">
                        <div class="p-6 space-y-8 pb-32">

                            <!-- Image de couverture -->
                            <section>
                                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="pi pi-image"></i> Image de couverture</h4>

                                <div v-if="currentPost?.cover_image" class="relative rounded-2xl overflow-hidden group border border-slate-200 shadow-sm aspect-video bg-slate-100">
                                    <img :src="currentPost.cover_image" alt="Cover" class="w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-3 backdrop-blur-sm">
                                        <Button icon="pi pi-refresh" label="Changer" class="p-button-rounded p-button-info p-button-sm shadow-lg" @click="triggerUpload" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-sm p-button-outlined bg-white/10 border-white/20 text-white hover:bg-red-500" @click="currentPost.cover_image = null" v-tooltip.top="'Supprimer'" />
                                    </div>
                                </div>

                                <div v-else class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center hover:bg-indigo-50 hover:border-indigo-400 transition-all duration-300 cursor-pointer group bg-white" @click="triggerUpload">
                                    <div class="w-16 h-16 bg-slate-50 group-hover:bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4 transition-transform group-hover:scale-110">
                                        <i class="pi pi-cloud-upload text-2xl text-slate-400 group-hover:text-indigo-600 transition-colors"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 m-0">Cliquez ou glissez une image</p>
                                    <p class="text-xs text-slate-400 mt-2 font-medium">Formats rec: PNG, JPG, WEBP.<br>Taille max: 20MB.</p>
                                </div>
                                <FileUpload ref="fileUploadRef" mode="basic" name="demo[]" accept="image/*" :maxFileSize="20000000" @select="onUploadCover" class="hidden" />
                            </section>

                            <!-- Taxonomies -->
                            <section>
                                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="pi pi-tags"></i> Classification</h4>
                                <div class="space-y-5 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">

                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Catégorie <span class="text-red-500">*</span></label>
                                        <Dropdown
                                            v-model="currentPost.category"
                                            :options="categories"
                                            optionLabel="name"
                                            placeholder="Sélectionner..."
                                            class="w-full rounded-xl bg-slate-50 hover:bg-white focus:ring-2 focus:ring-indigo-500/20"
                                            :class="{'p-invalid border-red-300': submitted && !currentPost?.category}"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center gap-2 font-medium">
                                                    <span :class="['w-2.5 h-2.5 rounded-full', slotProps.value.color ? `bg-${slotProps.value.color}-500` : 'bg-slate-400']"></span>
                                                    <span>{{ slotProps.value.name }}</span>
                                                </div>
                                                <span v-else class="text-slate-400">{{ slotProps.placeholder }}</span>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center gap-2 font-medium">
                                                    <span :class="['w-2.5 h-2.5 rounded-full', slotProps.option.color ? `bg-${slotProps.option.color}-500` : 'bg-slate-400']"></span>
                                                    <span>{{ slotProps.option.name }}</span>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Mots-clés (Tags)</label>
                                        <MultiSelect
                                            v-model="currentPost.tags"
                                            :options="tags"
                                            optionLabel="name"
                                            placeholder="Ajouter des tags..."
                                            display="chip"
                                            class="w-full rounded-xl bg-slate-50 hover:bg-white focus:ring-2 focus:ring-indigo-500/20"
                                            :filter="true"
                                        />
                                    </div>
                                </div>
                            </section>

                            <!-- Publication & Visibilité -->
                            <section>
                                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="pi pi-cog"></i> Visibilité & Dates</h4>
                                <div class="space-y-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">

                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Statut actuel</label>
                                        <Dropdown
                                            v-model="currentPost.status"
                                            :options="statuses"
                                            optionLabel="label"
                                            optionValue="value"
                                            class="w-full rounded-xl bg-slate-50 hover:bg-white"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center gap-2 font-bold">
                                                    <i :class="`text-${getStatusSeverity(slotProps.value)}-500 ${getStatusIcon(slotProps.value)}`"></i>
                                                    <span class="text-slate-700">{{ getStatusLabel(slotProps.value) }}</span>
                                                </div>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center gap-2 font-semibold">
                                                    <i :class="`text-${slotProps.option.severity}-500 ${slotProps.option.icon}`"></i>
                                                    <span class="text-slate-700">{{ slotProps.option.label }}</span>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>

                                    <div v-if="currentPost?.status === 'scheduled'" class="flex flex-col gap-2 p-4 bg-amber-50/50 rounded-xl border border-amber-200/60">
                                        <label class="text-sm font-bold text-amber-900 flex items-center gap-2"><i class="pi pi-calendar-plus"></i> Date de planification</label>
                                        <Calendar v-model="currentPost.publishDate" showTime hourFormat="24" class="w-full" inputClass="rounded-lg bg-white border-amber-200 focus:ring-amber-500/20" placeholder="Sélectionnez une date" />
                                    </div>

                                    <div v-if="currentPost?.status === 'published'" class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Date de publication</label>
                                        <Calendar v-model="currentPost.publishDate" class="w-full" inputClass="rounded-lg bg-slate-50" />
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 transition-colors border border-slate-200 rounded-xl cursor-pointer group" @click="currentPost.isFeatured = !currentPost.isFeatured">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                                <i class="pi pi-star-fill text-slate-300 group-hover:text-amber-500 transition-colors" :class="{'text-amber-500': currentPost?.isFeatured}"></i>
                                                Mettre à la une
                                            </span>
                                            <span class="text-xs text-slate-500 mt-1 font-medium">Épingler en haut du blog</span>
                                        </div>
                                        <InputSwitch v-model="currentPost.isFeatured" @click.stop />
                                    </div>
                                </div>
                            </section>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: SEO & META -->
                    <TabPanel header="SEO">
                        <div class="p-6 space-y-8">
                            <div class="bg-gradient-to-br from-indigo-50 to-blue-50/50 border border-indigo-100/50 p-5 rounded-2xl mb-2">
                                <h4 class="text-sm font-black text-indigo-900 mb-2 flex items-center gap-2"><i class="pi pi-google text-lg"></i> Optimisation Moteurs</h4>
                                <p class="text-xs text-indigo-700 m-0 leading-relaxed font-medium">Contrôlez l'apparence de votre article sur Google, LinkedIn et Twitter.</p>
                            </div>

                            <!-- Aperçu Google "Pixel Perfect" -->
                            <div class="border border-slate-200 rounded-xl p-5 bg-white shadow-sm font-sans mb-4">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 block">Aperçu Résultat Google</span>

                                <div class="flex items-center gap-3 mb-2.5">
                                    <div class="w-7 h-7 rounded-full bg-[#f1f3f4] flex items-center justify-center text-[10px]">🌐</div>
                                    <div class="flex flex-col">
                                        <span class="text-[14px] text-[#202124] font-normal leading-tight">aprojed.org</span>
                                        <span class="text-[12px] text-[#4d5156] leading-tight">https://aprojed.org/post/{{ currentPost?.slug || 'mon-article' }}</span>
                                    </div>
                                </div>
                                <div class="text-[20px] text-[#1a0dab] hover:underline cursor-pointer mb-1 line-clamp-1 font-normal" style="font-family: arial, sans-serif;">
                                    {{ currentPost?.seoTitle || currentPost?.title || 'Titre de votre publication' }}
                                </div>
                                <div class="text-[14px] text-[#4d5156] line-clamp-2" style="font-family: arial, sans-serif; line-height: 1.58;">
                                    {{ currentPost?.seoDescription || currentPost?.excerpt || 'Fournissez une méta description pertinente contenant vos mots-clés pour encourager les utilisateurs à cliquer sur votre lien.' }}
                                </div>
                            </div>

                            <div class="space-y-6 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-between items-end">
                                        <label class="text-sm font-bold text-slate-700">Titre SEO (Meta Title)</label>
                                        <span class="text-xs font-mono font-bold" :class="seoTitleLength > 60 ? 'text-red-500' : (seoTitleLength > 50 ? 'text-amber-500' : 'text-slate-400')">
                                            {{ seoTitleLength || 0 }} / 60
                                        </span>
                                    </div>
                                    <InputText v-model="currentPost.seoTitle" placeholder="Laissez vide pour utiliser le titre standard" class="w-full rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500/20" />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-between items-end">
                                        <label class="text-sm font-bold text-slate-700">Méta Description</label>
                                        <span class="text-xs font-mono font-bold" :class="seoDescLength > 160 ? 'text-red-500' : (seoDescLength > 140 ? 'text-amber-500' : 'text-slate-400')">
                                            {{ seoDescLength || 0 }} / 160
                                        </span>
                                    </div>
                                    <Textarea v-model="currentPost.seoDescription" rows="4" placeholder="Description attractive pour les moteurs de recherche..." class="w-full rounded-xl bg-slate-50 focus:bg-white resize-none focus:ring-2 focus:ring-indigo-500/20" />
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </aside>
        </div>
    </div>
</Dialog>


<!-- ========================================================================= -->
<!-- 2. DIALOG : PREVIEW (RENDU FINAL) -->
<!-- ========================================================================= -->
<Dialog
    v-model:visible="previewDialogVisible"
    maximizable
    modal
    header="Mode Aperçu (Rendu Final)"
    :style="{ width: '90vw', maxWidth: '1200px' }"
    :breakpoints="{ '1024px': '95vw', '641px': '100vw' }"
    class="preview-dialog"
    contentClass="bg-slate-50 p-0"
>
    <div class="bg-slate-50 min-h-full py-8 md:py-12">
        <article class="bg-white max-w-4xl mx-auto rounded-3xl shadow-2xl overflow-hidden border border-slate-200/60 transition-all">

            <div class="p-6 md:p-10 lg:p-14">

                <!-- En-tête de l'article -->
                <header class="mb-10">
                    <Tag
                        v-if="currentPost?.category"
                        :value="currentPost.category.name"
                        class="mb-6 bg-indigo-50 text-indigo-700 border border-indigo-100 font-bold tracking-wider uppercase text-[11px] px-4 py-1.5 rounded-full"
                    />
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-slate-900 mb-6 leading-[1.15] tracking-tight font-serif">
                        {{ currentPost?.title || 'Titre de l\'article sans nom' }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-4 text-slate-500 text-sm border-b border-slate-100 pb-6 font-medium">
                        <div class="flex items-center gap-2">
                            <Avatar :label="currentPost?.author ? currentPost.author.charAt(0).toUpperCase() : 'A'" shape="circle" class="bg-slate-100 text-slate-700 font-bold" />
                            <span class="font-bold text-slate-700">{{ currentPost?.author || 'Anonyme' }}</span>
                        </div>
                        <span class="text-slate-300">•</span>
                        <span class="flex items-center gap-1.5"><i class="pi pi-calendar text-slate-400"></i> {{ currentPost?.publishDate ? formatDate(currentPost.publishDate) : 'Date à définir' }}</span>
                        <span class="text-slate-300">•</span>
                        <span class="flex items-center gap-1.5"><i class="pi pi-clock text-slate-400"></i> {{ calculateReadingTime(currentPost?.content) || 1 }} min de lecture</span>
                    </div>
                </header>

                <!-- Image de couverture -->
                <figure v-if="currentPost?.cover_image" class="rounded-2xl overflow-hidden mb-12 shadow-md bg-slate-100">
                    <img :src="currentPost.cover_image" alt="Couverture de l'article" class="w-full h-auto max-h-[550px] object-cover transition-transform hover:scale-[1.02] duration-700" />
                </figure>

                <!-- Contenu Riche (Prose) -->
                <div v-if="currentPost?.content" class="prose prose-lg prose-indigo prose-headings:font-black prose-headings:tracking-tight prose-a:text-indigo-600 prose-img:rounded-xl prose-img:shadow-sm max-w-none text-slate-700 leading-relaxed font-serif" v-html="currentPost.content"></div>

                <!-- Empty State Content -->
                <div v-else class="text-slate-400 italic flex flex-col items-center justify-center p-16 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
                    <i class="pi pi-pen-to-square text-4xl mb-4 text-slate-300"></i>
                    <p class="text-center font-sans font-medium text-lg">Le contenu de l'article apparaîtra ici.</p>
                    <p class="text-center font-sans text-sm mt-2">Commencez à taper dans l'éditeur pour voir le résultat en temps réel.</p>
                </div>

            </div>
        </article>
    </div>
</Dialog>

        <ConfirmDialog></ConfirmDialog>
    </AppLayout>
</template>

<style scoped>
/* ========================================= */
/* ANIMATIONS & TRANSITIONS */
/* ========================================= */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* ========================================= */
/* DATATABLE CUSTOMIZATION */
/* ========================================= */
:deep(.custom-table .p-datatable-thead > tr > th) {
    background: #f8fafc; /* slate-50 */
    color: #475569; /* slate-600 */
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0; /* slate-200 */
    border-top: 1px solid #e2e8f0;
}
:deep(.custom-table .p-datatable-tbody > tr) {
    transition: background-color 0.2s;
}
:deep(.custom-table .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

/* ========================================= */
/* SIDEBAR & TABVIEW CUSTOMIZATION */
/* ========================================= */
:deep(.custom-sidebar .p-sidebar-header) {
    display: none; /* Custom header in template */
}
:deep(.custom-sidebar .p-sidebar-content) {
    padding: 0;
    height: 100%;
}
:deep(.custom-tabview .p-tabview-nav) {
    border-bottom: 1px solid #e2e8f0;
    background: transparent;
}
:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: #64748b;
    font-weight: 700;
    font-size: 0.875rem;
    padding: 1.25rem 1rem;
    box-shadow: none !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
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
/* QUILL EDITOR "PRO" CUSTOMIZATION */
/* ========================================= */
.quill-wrapper {
    display: flex;
    flex-direction: column;
}
:deep(.p-editor-container) {
    border: none !important;
}
:deep(.p-editor-toolbar) {
    border: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
    background-color: #f8fafc;
    padding: 0.5rem 1rem !important;
}
:deep(.p-editor-content) {
    border: none !important;
}
:deep(.ql-container) {
    font-family: inherit !important;
    font-size: inherit !important;
}
:deep(.ql-editor) {
    padding: 2.5rem !important;
}
:deep(.ql-editor p) {
    margin-bottom: 1.2em;
}
:deep(.ql-editor h1) { font-size: 2.25rem; font-weight: 900; color: #0f172a; margin-top: 1.5em; margin-bottom: 0.5em; }
:deep(.ql-editor h2) { font-size: 1.875rem; font-weight: 800; color: #1e293b; margin-top: 1.5em; margin-bottom: 0.5em; }
:deep(.ql-editor h3) { font-size: 1.5rem; font-weight: 700; color: #334155; margin-top: 1.2em; margin-bottom: 0.5em; }
:deep(.ql-editor blockquote) {
    border-left: 4px solid #818cf8;
    padding-left: 1rem;
    color: #64748b;
    font-style: italic;
    background: #e0e7ff20;
    padding: 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
}

/* Toolbar styling */
:deep(.ql-toolbar .ql-formats) {
    margin-right: 0.5rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.2rem;
    display: flex;
    align-items: center;
}
:deep(.ql-toolbar button) {
    border-radius: 0.25rem;
    transition: all 0.2s;
}
:deep(.ql-toolbar button:hover), :deep(.ql-toolbar button.ql-active) {
    color: #4f46e5 !important;
    background: #eff6ff;
}
:deep(.ql-toolbar .ql-stroke) { stroke: #64748b; }
:deep(.ql-toolbar .ql-fill) { fill: #64748b; }
:deep(.ql-toolbar button:hover .ql-stroke), :deep(.ql-toolbar button.ql-active .ql-stroke) { stroke: #4f46e5; }
:deep(.ql-toolbar button:hover .ql-fill), :deep(.ql-toolbar button.ql-active .ql-fill) { fill: #4f46e5; }
:deep(.ql-toolbar .ql-picker) { color: #475569; font-weight: 500; }

/* ========================================= */
/* UTILS & COMPONENT FIXES */
/* ========================================= */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* SplitButton Custom styling */
:deep(.custom-splitbutton .p-splitbutton-defaultbutton) {
    background-color: #4f46e5 !important;
    border-color: #4f46e5 !important;
    font-weight: 700;
}
:deep(.custom-splitbutton .p-splitbutton-menubutton) {
    background-color: #4338ca !important;
    border-color: #4338ca !important;
}

/* MultiSelect Chip Custom */
:deep(.custom-multiselect .p-multiselect-token) {
    background-color: #e0e7ff;
    color: #4f46e5;
    font-weight: 600;
    border-radius: 0.5rem;
}

/* Preview Dialog */
:deep(.preview-dialog .p-dialog-content) {
    padding: 0;
    background: #f8fafc;
}
:deep(.preview-dialog .p-dialog-header) {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}

</style>
