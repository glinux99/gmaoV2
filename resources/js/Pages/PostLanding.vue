<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

// --- COMPOSANTS PRIMEVUE ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Skeleton from 'primevue/skeleton';

// --- PROPS INERTIA (Simulées pour l'exemple) ---
// Dans la réalité, ces données viendront de votre contrôleur Laravel
const props = defineProps({
    // posts: Object, (contiendrait la data, current_page, total, etc.)
});

// --- GESTION DES ÉTATS ---
const loading = ref(false);
const searchQuery = ref('');
const selectedSort = ref('newest');

// Configuration de la pagination massive (> 1200 pages)
const pagination = reactive({
    first: 0,
    rows: 12, // 12 posts par page
    totalRecords: 14500 // Simulation pour générer ~1208 pages
});

const sortOptions = [
    { label: 'Plus récents', value: 'newest' },
    { label: 'Plus anciens', value: 'oldest' },
    { label: 'Plus commentés', value: 'comments' },
    { label: 'Populaires', value: 'popular' }
];

// --- DONNÉES DE DÉMONSTRATION ---
const categories = [
    { id: 'ALL', label: 'Toutes les actualités', count: 14500, color: 'slate' },
    { id: 'SANT', label: 'Santé & Nutrition', count: 3240, color: 'rose' },
    { id: 'EDUC', label: 'Éducation', count: 2150, color: 'indigo' },
    { id: 'AGRI', label: 'Agriculture & Forêts', count: 4800, color: 'emerald' },
    { id: 'EAU', label: 'Eau & Assainissement', count: 3110, color: 'sky' },
    { id: 'URG', label: 'Urgences & Crises', count: 1200, color: 'orange' }
];

const selectedCategory = ref('ALL');

const posts = ref([
    {
        id: 1,
        title: "Inauguration du nouveau complexe de pompage solaire à Mbandaka",
        excerpt: "Face à la recrudescence des maladies hydriques, nos équipes ont finalisé l'installation de 5 forages profonds. Une étape cruciale pour la santé publique locale...",
        image: "https://images.unsplash.com/photo-1541813131343-4a004473347b?auto=format&fit=crop&q=80&w=800",
        category: "Eau & Assainissement",
        categoryCode: "EAU",
        date: "12 Mai 2026",
        author: { name: "Jean-Baptiste M.", avatar: "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" },
        commentsCount: 24
    },
    {
        id: 2,
        title: "Comment l'agroforesterie redonne vie aux terres du Nord-Kivu",
        excerpt: "Découvrez le témoignage de Mama Kanyere, agricultrice à Beni, qui a vu ses rendements doubler grâce aux techniques d'association de cultures enseignées par APROJED.",
        image: "https://images.unsplash.com/photo-1530595467537-0b5996c41f2d?auto=format&fit=crop&q=80&w=800",
        category: "Agriculture & Forêts",
        categoryCode: "AGRI",
        date: "08 Mai 2026",
        author: { name: "Prof. Augustin K.", avatar: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" },
        commentsCount: 156
    },
    {
        id: 3,
        title: "Rapport Mensuel : Distribution de suppléments nutritionnels (Avril 2026)",
        excerpt: "Le bilan de notre clinique mobile dans le Sud-Kivu est en ligne. Plus de 1200 enfants ont été pris en charge ce mois-ci malgré les difficultés d'accès.",
        image: "https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&q=80&w=800",
        category: "Santé & Nutrition",
        categoryCode: "SANT",
        date: "02 Mai 2026",
        author: { name: "Marie Kanyere", avatar: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" },
        commentsCount: 8
    },
    {
        id: 4,
        title: "Construction de la 12ème école écologique en briques stabilisées",
        excerpt: "Le chantier de Mahagi avance à grands pas. Utilisant la terre locale pressée sans cuisson, ce bâtiment abritera 300 élèves dès la prochaine rentrée.",
        image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&q=80&w=800",
        category: "Éducation",
        categoryCode: "EDUC",
        date: "28 Avril 2026",
        author: { name: "Équipe Technique", avatar: null },
        commentsCount: 42
    },
    {
        id: 5,
        title: "Alerte Urgence : Éruption volcanique et déploiement de l'aide",
        excerpt: "Nos équipes d'intervention rapide sont mobilisées suite aux récents événements. Mise en place de dortoirs et distribution d'eau potable en cours.",
        image: "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=800",
        category: "Urgences & Crises",
        categoryCode: "URG",
        date: "15 Avril 2026",
        author: { name: "Cellule de Crise", avatar: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" },
        commentsCount: 312
    },
    {
        id: 6,
        title: "Formation : Les 5 règles d'or de la consignation électrique",
        excerpt: "Dans le cadre de l'installation de mini-réseaux solaires, nos techniciens ont suivi une formation rigoureuse sur la sécurité au travail et l'habilitation.",
        image: "https://images.unsplash.com/photo-1508514177221-188b1cf16e9d?auto=format&fit=crop&q=80&w=800",
        category: "Éducation",
        categoryCode: "EDUC",
        date: "10 Avril 2026",
        author: { name: "Sophie L.", avatar: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" },
        commentsCount: 5
    }
]);

// --- FONCTIONS ---
const getCategoryColor = (code) => {
    const cat = categories.find(c => c.id === code);
    return cat ? cat.color : 'slate';
};

const handlePageChange = (event) => {
    loading.value = true;
    pagination.first = event.first;
    // Ici, appel vers Laravel via Inertia :
    /*
    router.get('/posts', { page: event.page + 1, category: selectedCategory.value }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => loading.value = false
    });
    */

    // Simulation du temps de chargement réseau
    setTimeout(() => {
        window.scrollTo({ top: document.getElementById('posts-grid').offsetTop - 100, behavior: 'smooth' });
        loading.value = false;
    }, 600);
};

const selectCategory = (id) => {
    selectedCategory.value = id;
    pagination.first = 0; // Reset page
    loading.value = true;
    setTimeout(() => loading.value = false, 400);
};
</script>

<template>
    <Head title="Actualités & Rapports Terrain - APROJED R.D. Congo" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">

        <!-- ========================================== -->
        <!-- HEADER (SIMPLIFIÉ POUR CETTE PAGE) -->
        <!-- ========================================== -->
        <header class="fixed top-0 left-0 w-full z-50 bg-white/90 backdrop-blur-xl border-b border-slate-200/60 transition-all duration-300">
            <div class="w-full max-w-[1800px] mx-auto px-6 lg:px-12 xl:px-20 h-20 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-4 group">
                    <img src="https://aprojed.org/storage/uploads/logo.png" alt="Logo" class="h-12 w-auto object-contain fallback-logo group-hover:scale-105 transition-transform" />
                    <span class="text-xl font-black text-slate-900 tracking-tighter uppercase">APROJED</span>
                </Link>
                <div class="flex items-center gap-4">
                    <Link href="/" class="text-sm font-bold text-slate-500 hover:text-emerald-600 transition-colors"><i class="pi pi-arrow-left mr-2"></i>Retour à l'accueil</Link>
                </div>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- HERO SECTION BLOG -->
        <!-- ========================================== -->
        <section class="relative bg-slate-950 pt-32 pb-20 overflow-hidden text-white">
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&q=80&w=1920')] bg-cover bg-center opacity-20 mix-blend-luminosity"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/80 to-transparent z-10"></div>

            <div class="w-full px-6 lg:px-12 xl:px-20 mx-auto max-w-[1800px] relative z-20 text-center space-y-6 mt-10">
                <Badge value="JOURNAL DE BORD" severity="success" class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold uppercase text-xs tracking-widest px-3 py-1" />
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black tracking-tighter leading-tight" style="font-family: 'Montserrat', sans-serif;">
                    Actualités & Rapports <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">Terrain</span>
                </h1>
                <p class="text-slate-300 text-lg font-light max-w-2xl mx-auto">
                    Découvrez nos dernières interventions, les rapports de projets en RDC et les témoignages de nos bénéficiaires.
                </p>

                <!-- Barre de recherche massive -->
                <div class="max-w-3xl mx-auto mt-10 relative group">
                    <i class="pi pi-search absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 text-xl group-focus-within:text-emerald-400 transition-colors"></i>
                    <input type="text" v-model="searchQuery" placeholder="Rechercher un article, un projet, un rapport..." class="w-full bg-white/10 backdrop-blur-md border-2 border-white/10 focus:border-emerald-500 text-white placeholder-slate-400 rounded-full py-5 pl-16 pr-6 text-lg outline-none transition-all shadow-2xl" />
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MAIN CONTENT: SIDEBAR + GRID -->
        <!-- ========================================== -->
        <section id="posts-grid" class="py-16">
            <div class="w-full max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-12 xl:px-20">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">

                    <!-- LEFT SIDEBAR : FILTERS -->
                    <div class="lg:col-span-3 space-y-8 sticky top-28">

                        <!-- Sort Dropdown -->
                        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider font-mono flex items-center gap-2">
                                <i class="pi pi-sort-alt text-emerald-500"></i> Trier l'affichage
                            </h3>
                            <Dropdown v-model="selectedSort" :options="sortOptions" optionLabel="label" optionValue="value" class="w-full rounded-xl bg-slate-50 border-slate-200" />
                        </div>

                        <!-- Categories List -->
                        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider font-mono flex items-center gap-2 mb-4">
                                <i class="pi pi-filter text-emerald-500"></i> Catégories
                            </h3>

                            <ul class="space-y-2">
                                <li v-for="cat in categories" :key="cat.id">
                                    <button @click="selectCategory(cat.id)"
                                            :class="['w-full flex items-center justify-between p-3 rounded-2xl transition-all duration-200 border',
                                            selectedCategory === cat.id ? `bg-${cat.color}-50 border-${cat.color}-200 text-${cat.color}-700 shadow-sm` : 'bg-transparent border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-200']">
                                        <span class="font-bold text-sm">{{ cat.label }}</span>
                                        <Badge :value="cat.count" :class="selectedCategory === cat.id ? `bg-${cat.color}-500 text-white` : 'bg-slate-100 text-slate-500'" class="text-[10px]" />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- RIGHT MAIN: POSTS GRID & PAGINATION -->
                    <div class="lg:col-span-9 space-y-8">

                        <!-- Top Bar Stats -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                            <div class="text-sm text-slate-500 font-medium">
                                Affichage de <span class="font-black text-slate-900">{{ pagination.first + 1 }}</span> à <span class="font-black text-slate-900">{{ Math.min(pagination.first + pagination.rows, pagination.totalRecords) }}</span> sur <span class="text-emerald-600 font-black">{{ pagination.totalRecords }}</span> articles
                            </div>
                        </div>

                        <!-- Grid -->
                        <div :class="['grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 transition-opacity duration-300', loading ? 'opacity-40 pointer-events-none' : 'opacity-100']">

                            <article v-for="post in posts" :key="post.id" class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group flex flex-col cursor-pointer h-full">

                                <!-- Image Container -->
                                <div class="relative h-52 overflow-hidden bg-slate-100">
                                    <img :src="post.image" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>

                                    <!-- Category Badge -->
                                    <div class="absolute top-4 left-4">
                                        <span :class="[`bg-${getCategoryColor(post.categoryCode)}-500`]" class="text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-md">
                                            {{ post.category }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="p-6 flex-1 flex flex-col justify-between space-y-5">

                                    <div class="space-y-3">
                                        <div class="flex items-center gap-2 text-slate-400 font-mono text-xs uppercase tracking-wider font-bold">
                                            <i class="pi pi-calendar"></i> {{ post.date }}
                                        </div>

                                        <h3 class="text-xl font-black text-slate-800 leading-snug group-hover:text-emerald-600 transition-colors line-clamp-3">
                                            {{ post.title }}
                                        </h3>

                                        <p class="text-slate-500 text-sm font-light leading-relaxed line-clamp-3">
                                            {{ post.excerpt }}
                                        </p>
                                    </div>

                                    <Divider class="!my-0" />

                                    <!-- Footer: Author & Comments -->
                                    <div class="flex items-center justify-between pt-2">
                                        <div class="flex items-center gap-3">
                                            <Avatar v-if="post.author.avatar" :image="post.author.avatar" shape="circle" class="border border-slate-200" />
                                            <Avatar v-else :label="post.author.name.charAt(0)" shape="circle" class="bg-slate-100 text-slate-600 font-bold border border-slate-200" />
                                            <span class="text-xs font-bold text-slate-700">{{ post.author.name }}</span>
                                        </div>

                                        <div class="flex items-center gap-1.5 text-slate-400 hover:text-emerald-600 transition-colors" title="Commentaires">
                                            <i class="pi pi-comments text-sm"></i>
                                            <span class="text-xs font-bold">{{ post.commentsCount }}</span>
                                        </div>
                                    </div>
                                </div>
                            </article>

                        </div>

                        <!-- ========================================== -->
                        <!-- PAGINATION MASSIVE (> 1200 pages) -->
                        <!-- ========================================== -->
                        <div class="mt-16 bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex justify-center">
                            <!--
                                Configuration spécifique : JumpToPageDropdown est essentiel
                                quand on a des milliers de pages pour éviter de cliquer 1000 fois.
                            -->
                            <Paginator
                                v-model:first="pagination.first"
                                :rows="pagination.rows"
                                :totalRecords="pagination.totalRecords"
                                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink JumpToPageDropdown"
                                @page="handlePageChange"
                                class="custom-massive-paginator"
                            >
                            </Paginator>
                        </div>

                    </div>
                </div>
            </div>
        </section>

    </div>
</template>

<style scoped>
/* ========================================= */
/* OVERRIDES PRIMEVUE POUR CORRESPONDRE AU THEME */
/* ========================================= */

.fallback-logo {
    max-height: 48px;
}

/* Style de la dropdown standard */
:deep(.p-dropdown) {
    padding: 0.5rem 0.75rem;
    border-color: #e2e8f0;
    box-shadow: none !important;
    border-radius: 0.75rem;
}
:deep(.p-dropdown:hover) { border-color: #cbd5e1; }
:deep(.p-dropdown:focus-within) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
}

/* ========================================= */
/* STYLE DU PAGINATEUR MASSIF */
/* ========================================= */
:deep(.custom-massive-paginator) {
    background: transparent;
    border: none;
    padding: 0;
}

:deep(.custom-massive-paginator .p-paginator-page),
:deep(.custom-massive-paginator .p-paginator-first),
:deep(.custom-massive-paginator .p-paginator-prev),
:deep(.custom-massive-paginator .p-paginator-next),
:deep(.custom-massive-paginator .p-paginator-last) {
    border-radius: 0.75rem;
    min-width: 2.5rem;
    height: 2.5rem;
    margin: 0 0.25rem;
    color: #475569;
    font-weight: 700;
    border: 1px solid transparent;
    transition: all 0.2s;
}

/* Hover effects */
:deep(.custom-massive-paginator .p-paginator-page:not(.p-highlight):hover),
:deep(.custom-massive-paginator .p-paginator-first:not(.p-disabled):hover),
:deep(.custom-massive-paginator .p-paginator-prev:not(.p-disabled):hover),
:deep(.custom-massive-paginator .p-paginator-next:not(.p-disabled):hover),
:deep(.custom-massive-paginator .p-paginator-last:not(.p-disabled):hover) {
    background-color: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}

/* Active page */
:deep(.custom-massive-paginator .p-paginator-page.p-highlight) {
    background-color: #10b981; /* Emerald 500 */
    border-color: #10b981;
    color: white;
    box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.39);
}

/* Dropdown pour "Sauter à la page" (JumpToPageDropdown) */
:deep(.custom-massive-paginator .p-dropdown) {
    margin-left: 1rem;
    height: 2.5rem;
    align-items: center;
}
:deep(.custom-massive-paginator .p-dropdown .p-dropdown-label) {
    padding-top: 0;
    padding-bottom: 0;
    display: flex;
    align-items: center;
    font-weight: 700;
    color: #0f172a;
}

/* Disabled states */
:deep(.custom-massive-paginator .p-disabled) {
    opacity: 0.5;
}

/* Utilitaires dynamiques pour les couleurs de catégories */
.bg-rose-500 { background-color: #f43f5e; }
.bg-rose-50 { background-color: #fff1f2; }
.border-rose-200 { border-color: #fecdd3; }
.text-rose-700 { color: #be123c; }

.bg-indigo-500 { background-color: #6366f1; }
.bg-indigo-50 { background-color: #eef2ff; }
.border-indigo-200 { border-color: #c7d2fe; }
.text-indigo-700 { color: #4338ca; }

.bg-emerald-500 { background-color: #10b981; }
.bg-emerald-50 { background-color: #ecfdf5; }
.border-emerald-200 { border-color: #a7f3d0; }
.text-emerald-700 { color: #047857; }

.bg-sky-500 { background-color: #0ea5e9; }
.bg-sky-50 { background-color: #f0f9ff; }
.border-sky-200 { border-color: #bae6fd; }
.text-sky-700 { color: #0369a1; }

.bg-orange-500 { background-color: #f97316; }
.bg-orange-50 { background-color: #fff7ed; }
.border-orange-200 { border-color: #fed7aa; }
.text-orange-700 { color: #c2410c; }

.bg-slate-500 { background-color: #64748b; }
</style>
