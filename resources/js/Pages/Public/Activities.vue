<!--
  APROJED R.D. Congo - Page Activités (Blog / Actualités)
  Version dynamique avec données backend (Inertia)
  Affiche tous les articles publiés, filtrage par catégorie/recherche, pagination.
-->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/sakai/layout/PublicLayout.vue';
import { useToast } from 'primevue/usetoast';

// PrimeVue components
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Paginator from 'primevue/paginator';
import InputText from 'primevue/inputtext';
import Skeleton from 'primevue/skeleton';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';

const toast = useToast();
const page = usePage();

// --- Props reçues du backend (via Inertia) ---
const props = defineProps({
    // Paginator Laravel : { data: [], current_page, last_page, total, etc. }
    posts: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 9, current_page: 1, last_page: 1 })
    },
    categories: {
        type: Array,
        default: () => []
    },
    featuredPost: {
        type: Object,
        default: null
    }
});

// --- États locaux ---
const searchQuery = ref('');
const selectedCategory = ref(null);
const currentPage = ref(props.posts?.current_page || 1);
const perPage = ref(props.posts?.per_page || 9);
const lightboxVisible = ref(false);
const currentImage = ref(null);

// --- Indicateur de chargement (backend peut être lent) ---
const isLoading = ref(false);

// --- Utilisation directe des données paginées du backend (pas de filtrage local pour la pagination)
// Mais nous appliquons le filtrage sur les données déjà reçues (côté client)
const allPosts = computed(() => props.posts?.data || []);

// Filtrage local (recherche + catégorie) sur les posts chargés
const filteredPosts = computed(() => {
    let items = [...allPosts.value];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(p =>
            p.title?.toLowerCase().includes(q) ||
            p.excerpt?.toLowerCase().includes(q)
        );
    }
    if (selectedCategory.value) {
        items = items.filter(p => p.category?.id === selectedCategory.value.id);
    }
    return items;
});

// Pagination locale des articles filtrés (car nous avons tous les posts d'un coup)
const paginatedPosts = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredPosts.value.slice(start, end);
});

const totalRecords = computed(() => filteredPosts.value.length);
const first = computed(() => (currentPage.value - 1) * perPage.value);

// Pas de posts du tout (backend vide)
const hasNoPosts = computed(() => allPosts.value.length === 0);

// --- Méthodes ---
const onPageChange = (event) => {
    currentPage.value = event.page + 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};

const openLightbox = (imageUrl) => {
    if (!imageUrl) return;
    currentImage.value = imageUrl;
    lightboxVisible.value = true;
};

// --- Réinitialisation de la page lors du changement de filtre ---
const resetPagination = () => {
    currentPage.value = 1;
};

// Observateur pour les filtres
import { watch } from 'vue';
watch([searchQuery, selectedCategory], () => {
    resetPagination();
});

// --- Animations au scroll (Intersection Observer) ---
const observer = ref(null);
onMounted(() => {
    // Observer les cartes post-card après un court délai pour que le DOM soit prêt
    setTimeout(() => {
        const cards = document.querySelectorAll('.post-card');
        if (cards.length && observer.value === null) {
            observer.value = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-up');
                        observer.value.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            cards.forEach(card => observer.value.observe(card));
        }
    }, 200);
});

onUnmounted(() => {
    if (observer.value) observer.value.disconnect();
});
</script>

<template>
    <PublicLayout>
        <Head title="Actualités - APROJED R.D. Congo" />

        <!-- BANNIÈRE D'EN-TÊTE -->
        <div class="relative bg-slate-900 pt-32 pb-20 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=1920')"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <Badge value="Nos Actualités" severity="info" class="bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 font-bold uppercase text-[10px] tracking-widest" />
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-white mt-4">Toutes nos publications</h1>
                <p class="text-slate-300 text-lg mt-4 max-w-2xl mx-auto">Suivez nos actions, rapports, événements et actualités terrain en RDC.</p>
            </div>
        </div>

        <!-- ARTICLE MIS EN AVANT (FEATURED) -->
        <div v-if="featuredPost" class="bg-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-12 items-center bg-slate-50 rounded-3xl overflow-hidden shadow-lg">
                    <div class="h-80 overflow-hidden">
                        <img :src="featuredPost.cover_image || 'https://placehold.co/800x600?text=APROJED'" class="w-full h-full object-cover" />
                    </div>
                    <div class="p-8 space-y-4">
                        <Badge value="À la une" severity="danger" />
                        <h2 class="text-3xl font-black">{{ featuredPost.title }}</h2>
                        <p class="text-slate-500">{{ featuredPost.excerpt }}</p>
                        <div class="flex items-center gap-4 text-sm text-slate-400">
                            <span><i class="pi pi-calendar mr-1"></i> {{ formatDate(featuredPost.published_at) }}</span>
                            <span><i class="pi pi-tag mr-1"></i> {{ featuredPost.category?.name }}</span>
                        </div>
                        <Link :href="`/blog/${featuredPost.slug}`" class="inline-block">
                            <Button label="Lire l'article" icon="pi pi-arrow-right" iconPos="right" class="bg-emerald-500 hover:bg-emerald-600 border-none" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTRES ET RECHERCHE -->
        <div class="bg-white py-12 border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between gap-6 items-center">
                    <div class="flex flex-wrap gap-4">
                        <Button
                            label="Toutes"
                            @click="selectedCategory = null"
                            :class="{'bg-slate-800 text-white': !selectedCategory}"
                            class="rounded-full"
                        />
                        <Button
                            v-for="cat in categories"
                            :key="cat.id"
                            :label="cat.name"
                            @click="selectedCategory = cat"
                            :class="{'bg-slate-800 text-white': selectedCategory?.id === cat.id}"
                            class="rounded-full"
                        />
                    </div>
                    <div class="relative w-full md:w-64">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Rechercher un article..."
                            class="pl-10 w-full rounded-full border-slate-200"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- GRILLE DES ARTICLES -->
        <div class="bg-slate-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- État de chargement (skeleton) -->
                <div v-if="isLoading" class="grid md:grid-cols-3 gap-8">
                    <div v-for="n in 6" :key="n"><Skeleton height="320px" class="rounded-2xl" /></div>
                </div>

                <!-- Aucun article du tout (backend vide) -->
                <div v-else-if="hasNoPosts" class="text-center py-20">
                    <i class="pi pi-inbox text-7xl text-slate-300 mb-5 block"></i>
                    <h3 class="text-2xl font-bold text-slate-800">Aucun article publié pour le moment</h3>
                    <p class="text-slate-500 mt-3 max-w-md mx-auto">Revenez bientôt pour découvrir nos actualités, rapports et projets.</p>
                </div>

                <!-- Aucun résultat après filtrage -->
                <div v-else-if="filteredPosts.length === 0" class="text-center py-16">
                    <i class="pi pi-search-slash text-6xl text-slate-300 mb-4 block"></i>
                    <h3 class="text-xl font-bold text-slate-800">Aucun article ne correspond</h3>
                    <p class="text-slate-500 mt-2">Essayez de modifier vos filtres de recherche.</p>
                </div>

                <!-- Affichage des articles -->
                <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="post in paginatedPosts"
                        :key="post.id"
                        class="post-card bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 opacity-0 transform translate-y-5"
                    >
                        <!-- Image d'illustration -->
                        <div
                            class="relative h-48 overflow-hidden cursor-pointer"
                            @click="openLightbox(post.cover_image)"
                        >
                            <img
                                :src="post.cover_image || 'https://placehold.co/600x400?text=Image+APROJED'"
                                class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                :alt="post.title"
                            />
                            <div class="absolute top-3 left-3 flex gap-2">
                                <Tag v-if="post.is_featured" value="Une" severity="danger" class="text-[10px]" />
                                <Tag v-if="post.category" :value="post.category.name" severity="info" class="text-[10px]" />
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-5 space-y-3">
                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <span><i class="pi pi-calendar"></i> {{ formatDate(post.published_at) }}</span>
                                <span><i class="pi pi-eye"></i> {{ post.views || 0 }} vues</span>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 line-clamp-2">{{ post.title }}</h3>
                            <p class="text-slate-500 text-sm line-clamp-3">{{ post.excerpt || (post.content?.substring(0, 120) + '...') }}</p>
                            <Divider />
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Avatar
                                        :image="post.author?.avatar"
                                        :label="post.author?.name?.charAt(0)"
                                        shape="circle"
                                        size="small"
                                        class="bg-slate-200"
                                    />
                                    <span class="text-xs text-slate-500">{{ post.author?.name || 'APROJED' }}</span>
                                </div>
                                <Link :href="`/activites/${post.id}`" class="text-emerald-600 text-sm font-bold hover:underline">
                                    Lire la suite <i class="pi pi-arrow-right ml-1"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination (si plus d'une page) -->
                <div v-if="totalRecords > perPage" class="mt-12 flex justify-center">
                    <Paginator
                        :first="first"
                        :rows="perPage"
                        :totalRecords="totalRecords"
                        @page="onPageChange"
                        class="bg-transparent"
                    />
                </div>
            </div>
        </div>

        <!-- LIGHTBOX POUR LES IMAGES -->
        <Dialog v-model:visible="lightboxVisible" modal :style="{ width: '80vw', maxWidth: '900px' }" :closable="true" class="p-0">
            <img :src="currentImage" class="w-full h-auto rounded-lg" />
        </Dialog>


    </PublicLayout>
</template>

<style scoped>
/* --- Animations des cartes --- */
.post-card {
    transition: all 0.3s ease;
}
.animate-fade-up {
    animation: fadeUp 0.6s ease forwards;
}
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* --- Paginator transparent --- */
:deep(.p-paginator) {
    background: transparent;
}

/* --- Limitation de lignes pour les titres et extraits --- */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* --- Responsive adjustments --- */
@media (max-width: 768px) {
    :deep(.p-paginator .p-paginator-pages .p-paginator-page) {
        min-width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
    }
}
</style>
