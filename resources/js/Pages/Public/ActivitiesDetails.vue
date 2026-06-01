<!--
  APROJED R.D. Congo - Page Détail d'Article (Activité)
  Version : 4.0 – Utilise PublicLayout, barre de progression, sommaire, commentaires, likes.
-->
<script setup>
import { ref, onMounted, onUnmounted, reactive, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import PublicLayout from '@/sakai/layout/PublicLayout.vue';

// PrimeVue Components
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import ScrollTop from 'primevue/scrolltop';

const toast = useToast();
const page = usePage();

// Props reçues du backend
const props = defineProps({
    post: { type: Object, required: true },
    comments: { type: Object, default: () => ({ data: [] }) },
    relatedPosts: { type: Array, default: () => [] }
});

// États locaux
const userLiked = ref(false);
const likesCount = ref(props.post.likes || 0);
const readingProgress = ref(0);
const commentLoading = ref(false);
const commentForm = reactive({ name: '', email: '', content: '' });
const tableOfContents = ref([]);
const viewsCount = ref(props.post.views || 0);

// Extraction du sommaire depuis le contenu HTML
const extractToc = () => {
    const article = document.querySelector('.article-content');
    if (!article) return;
    const headings = article.querySelectorAll('h2');
    tableOfContents.value = Array.from(headings).map(h2 => {
        let id = h2.id;
        if (!id) {
            id = h2.innerText.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
            h2.id = id;
        }
        return { id, title: h2.innerText };
    });
};

// Scroll fluide
const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
        const y = element.getBoundingClientRect().top + window.scrollY - 100;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }
};

// Barre de progression (relative au document)
const updateScroll = () => {
    const scrollPx = document.documentElement.scrollTop;
    const winHeightPx = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    readingProgress.value = winHeightPx ? (scrollPx / winHeightPx) * 100 : 0;
};

// Like / Unlike
const toggleLike = async () => {
    try {
        const response = await axios.post(`/api/posts/${props.post.id}/like`);
        userLiked.value = response.data.liked;
        likesCount.value = response.data.likes_count;
        toast.add({
            severity: 'success',
            summary: userLiked.value ? 'Merci pour votre soutien !' : 'Like retiré',
            life: 2000
        });
    } catch (error) {
        if (error.response?.status === 401) {
            toast.add({ severity: 'warn', summary: 'Connexion requise', detail: 'Connectez-vous pour aimer cet article.', life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de traiter votre demande.', life: 3000 });
        }
    }
};

// Copier le lien
const copyShareLink = () => {
    navigator.clipboard.writeText(window.location.href);
    toast.add({ severity: 'info', summary: 'Lien copié', detail: 'Le lien a été copié dans le presse-papier.', life: 3000 });
};

// Envoi d'un commentaire
const submitComment = async () => {
    if (!commentForm.content.trim() || !commentForm.name.trim()) {
        toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez remplir le nom et le message.', life: 3000 });
        return;
    }
    commentLoading.value = true;
    try {
        const response = await axios.post(`/api/posts/${props.post.id}/comment`, {
            name: commentForm.name,
            email: commentForm.email,
            content: commentForm.content
        });
        const newComment = response.data.comment;
        props.comments.data.unshift({
            id: newComment.id,
            author_name: newComment.author_name,
            author_email: newComment.author_email,
            content: newComment.content,
            created_at: new Date().toISOString(),
            user: null
        });
        toast.add({ severity: 'success', summary: 'Commentaire publié', detail: 'Merci pour votre contribution !', life: 4000 });
        commentForm.content = '';
        commentForm.name = '';
        commentForm.email = '';
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d’envoyer le commentaire.', life: 3000 });
    } finally {
        commentLoading.value = false;
    }
};

// Formatage date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
};

// Temps de lecture estimé
const readTime = computed(() => {
    if (!props.post.content) return '5 min';
    const text = props.post.content.replace(/<[^>]*>/g, '');
    const wordCount = text.split(/\s+/).length;
    const minutes = Math.ceil(wordCount / 200);
    return `${minutes} min de lecture`;
});

onMounted(() => {
    window.addEventListener('scroll', updateScroll);
    setTimeout(extractToc, 200); // Délai pour laisser v-html se charger
});
onUnmounted(() => {
    window.removeEventListener('scroll', updateScroll);
});
</script>

<template>
    <PublicLayout>
        <Head :title="`${post.title} - APROJED`" />
        <Toast />

        <!-- Bouton remonter en haut -->
        <ScrollTop :threshold="400" icon="pi pi-arrow-up" class="custom-scrolltop fixed bottom-8 right-8 z-50" />

        <!-- Barre de progression fixe sous le header -->
        <div class="fixed top-[88px] left-0 w-full h-1 bg-slate-100 z-40">
            <div class="h-full bg-emerald-500 transition-all duration-150 ease-out" :style="{ width: readingProgress + '%' }"></div>
        </div>

        <!-- ==================== HERO ARTICLE ==================== -->
        <div class="relative bg-slate-950 pt-24 pb-32 overflow-hidden mt-[-1px]">
            <img :src="post.cover_image || 'https://placehold.co/1920x800?text=APROJED'" :alt="post.title" class="absolute inset-0 w-full h-full object-cover mix-blend-luminosity opacity-40" />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/80 to-slate-950/20"></div>
            <div class="relative z-10 max-w-6xl mx-auto px-6 text-center space-y-8">
                <div class="flex flex-wrap justify-center gap-4 text-white font-mono text-xs uppercase font-bold">
                    <span class="bg-emerald-500 px-3 py-1.5 rounded-lg shadow-md">{{ post.category?.name }}</span>
                    <span class="bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-lg border border-slate-700"><i class="pi pi-calendar mr-1 text-emerald-400"></i> {{ formatDate(post.published_at) }}</span>
                    <span class="bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-lg"><i class="pi pi-eye mr-1 text-emerald-400"></i> {{ viewsCount }} vues</span>
                    <span class="bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-lg"><i class="pi pi-heart mr-1 text-emerald-400"></i> {{ likesCount }} likes</span>
                    <span class="bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-lg"><i class="pi pi-book mr-1 text-emerald-400"></i> {{ readTime }}</span>
                </div>
                <h1 class="text-4xl sm:text-6xl md:text-7xl font-black tracking-tighter leading-[1.1] text-white max-w-5xl mx-auto">{{ post.title }}</h1>
                <div class="flex items-center justify-center gap-4 pt-6">
                    <Avatar :image="post.author?.avatar" shape="circle" size="xlarge" class="border-2 border-emerald-500 shadow-xl" />
                    <div class="text-left">
                        <p class="text-sm font-bold text-white uppercase tracking-wider">{{ post.author?.name || 'APROJED' }}</p>
                        <p class="text-xs text-emerald-400 font-mono">{{ post.author?.position || 'Auteur' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== CONTENU PRINCIPAL + SIDEBAR ==================== -->
        <div class="bg-white py-16">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24 items-start">

                    <!-- SIDEBAR GAUCHE (Sticky) -->
                    <div class="hidden lg:block lg:col-span-3 space-y-8 sticky top-32">
                        <!-- Sommaire -->
                        <div
                                v-if="tableOfContents.length"
                                class="bg-slate-50 rounded-3xl p-6 border border-slate-200 shadow-sm w-full max-w-full overflow-hidden"
                            >
                                <h4 class="text-sm font-black uppercase tracking-widest flex items-center gap-2 mb-6 text-slate-700">
                                    <i class="pi pi-list text-emerald-500"></i> Sommaire
                                </h4>

                                <ul class="space-y-3 w-full">
                                    <li
                                        v-for="item in tableOfContents"
                                        :key="item.id"
                                        class="w-full"
                                    >
                                        <button
                                            @click="scrollToSection(item.id)"
                                            class="text-left text-sm font-medium text-slate-600 hover:text-emerald-600 transition-colors duration-200 w-full block break-words whitespace-normal focus:outline-none focus:text-emerald-600"
                                        >
                                            {{ item.title }}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                        <!-- Actions : like & partage -->
                        <div class="flex justify-center gap-4">
                            <Button icon="pi pi-heart-fill" :class="['p-button-rounded p-button-text', userLiked ? 'text-red-500' : 'text-slate-400']" @click="toggleLike" v-tooltip.top="'Aimer'" />
                            <Button icon="pi pi-share-alt" class="p-button-rounded p-button-text text-slate-400" @click="copyShareLink" v-tooltip.top="'Copier le lien'" />
                            <Button icon="pi pi-facebook" class="p-button-rounded p-button-text text-slate-400 hover:text-[#1877F2]" />
                            <Button icon="pi pi-twitter" class="p-button-rounded p-button-text text-slate-400 hover:text-[#1DA1F2]" />
                        </div>

                        <!-- CTA don -->
                        <div class="bg-slate-900 rounded-3xl p-6 text-white text-center shadow-xl">
                            <i class="pi pi-heart-fill text-emerald-400 text-3xl mb-3 block"></i>
                            <h3 class="text-xl font-black">Soutenez nos actions</h3>
                            <p class="text-slate-300 text-sm mt-2">Financez directement nos projets terrain.</p>
                            <Button label="Faire un don" icon="pi pi-heart-fill" class="w-full bg-emerald-500 hover:bg-emerald-400 border-none font-bold mt-4" />
                        </div>

                        <!-- Articles similaires -->
                        <div v-if="relatedPosts.length" class="bg-white rounded-3xl p-6 border shadow-sm space-y-4">
                            <h4 class="text-sm font-black uppercase flex items-center gap-2"><i class="pi pi-bolt text-emerald-500"></i> À lire aussi</h4>
                            <Link v-for="p in relatedPosts" :key="p.id" :href="`/activites/${p.slug}`" class="flex gap-4 items-center group">
                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0"><img :src="p.cover_image || 'https://placehold.co/200x150'" class="w-full h-full object-cover group-hover:scale-110 transition" /></div>
                                <div><p class="text-xs text-slate-400">{{ formatDate(p.published_at) }}</p><h5 class="text-sm font-bold group-hover:text-emerald-600 line-clamp-2">{{ p.title }}</h5></div>
                            </Link>
                        </div>
                    </div>

                    <!-- COLONNE DROITE : ARTICLE + COMMENTAIRES -->
                    <div class="lg:col-span-9 space-y-12">
                        <!-- Sommaire mobile -->
                        <div v-if="tableOfContents.length" class="lg:hidden bg-slate-50 rounded-2xl p-6">
                            <h4 class="text-sm font-black uppercase mb-4"><i class="pi pi-list text-emerald-500 mr-2"></i> Sommaire</h4>
                            <ul class="space-y-2">
                                <li v-for="item in tableOfContents" :key="item.id">
                                    <button @click="scrollToSection(item.id)" class="text-left text-sm hover:text-emerald-600">{{ item.title }}</button>
                                </li>
                            </ul>
                        </div>

                        <!-- Contenu HTML (CMS) -->
                         <article
                            class="article-content w-full max-w-full overflow-hidden break-words prose prose-slate"
                            v-html="post.content"
                        ></article>

                        <!-- Tags -->
                        <div class="flex flex-wrap gap-2 pt-6 border-t">
                            <span class="text-sm font-bold text-slate-400 uppercase flex items-center"><i class="pi pi-tags mr-2"></i> Mots-clés :</span>
                            <Badge v-for="tag in post.tags" :key="tag.id" :value="tag.name" class="bg-slate-100 text-slate-600 font-bold border" />
                        </div>

                        <!-- Auteur box -->
                        <div class="bg-slate-50 p-6 rounded-3xl flex flex-col sm:flex-row items-center gap-6">
                            <Avatar :image="post.author?.avatar" shape="circle" class="w-24 h-24 border-2 border-white shadow-md" />
                            <div class="text-center sm:text-left">
                                <p class="text-xs font-bold uppercase text-emerald-500">Rédigé par</p>
                                <h3 class="text-xl font-black">{{ post.author?.name }}</h3>
                                <p class="text-sm font-bold text-slate-500">{{ post.author?.position }}</p>
                                <p class="text-slate-600 text-sm mt-2">{{ post.author?.bio }}</p>
                            </div>
                        </div>

                        <Divider />

                        <!-- COMMENTAIRES -->
                        <div id="comments" class="space-y-8">
                            <h3 class="text-2xl font-black flex items-center gap-3">Commentaires <Badge :value="comments.data.length" class="bg-slate-800 text-white" /></h3>

                            <!-- Formulaire -->
                            <div class="bg-white p-6 rounded-3xl border shadow-sm">
                                <h4 class="text-lg font-bold mb-4">Laisser un commentaire</h4>
                                <form @submit.prevent="submitComment" class="space-y-4">
                                    <div class="grid sm:grid-cols-2 gap-4">
                                        <InputText v-model="commentForm.name" required placeholder="Votre nom *" class="w-full rounded-xl bg-slate-50" />
                                        <InputText v-model="commentForm.email" type="email" placeholder="Votre e-mail (facultatif)" class="w-full rounded-xl bg-slate-50" />
                                    </div>
                                    <Textarea v-model="commentForm.content" required rows="4" placeholder="Partagez votre réflexion..." class="w-full rounded-xl bg-slate-50 resize-none" />
                                    <div class="flex justify-end">
                                        <Button type="submit" :loading="commentLoading" label="Publier" icon="pi pi-send" class="bg-emerald-500 hover:bg-emerald-600 border-none font-bold px-6 rounded-xl" />
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des commentaires -->
                            <div class="space-y-6">
                                <div v-for="c in comments.data" :key="c.id" class="flex gap-4 p-6 bg-slate-50 rounded-3xl">
                                    <Avatar :label="(c.author_name || 'Anonyme').charAt(0)" shape="circle" size="large" class="bg-indigo-100 text-indigo-700 font-black shadow-sm" />
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <h5 class="font-bold">{{ c.author_name }}</h5>
                                            <span class="text-xs text-slate-400">{{ formatDate(c.created_at) }}</span>
                                        </div>
                                        <p class="text-slate-600 text-sm mt-2">{{ c.content }}</p>
                                    </div>
                                </div>
                                <div v-if="comments.data.length === 0" class="text-center py-10 text-slate-500">
                                    <i class="pi pi-comment text-5xl text-slate-300 mb-3 block"></i>
                                    <p>Soyez le premier à commenter cet article.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
/* ========== STYLES POUR LE CONTENU RICHE (WYSIWYG) ========== */
.article-content {
    font-size: 1.1875rem;
    line-height: 1.85;
    color: #334155;
}

:deep(.article-content .lead) {
    font-size: 1.5rem;
    font-weight: 300;
    color: #0f172a;
    margin-bottom: 2rem;
}

:deep(.article-content p:not(.lead):first-of-type::first-letter) {
    float: left;
    font-size: 4.5rem;
    line-height: 0.8;
    padding-right: 0.75rem;
    font-weight: 900;
    font-family: 'Montserrat', sans-serif;
    color: #10b981;
}

:deep(.article-content h2) {
    font-size: 2rem;
    font-weight: 900;
    margin-top: 3.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
    scroll-margin-top: 100px;
}

:deep(.article-content h3) {
    font-size: 1.5rem;
    font-weight: 800;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

:deep(.article-content blockquote) {
    position: relative;
    padding: 1.5rem 2rem;
    margin: 2rem 0;
    background: #f8fafc;
    border-left: 6px solid #10b981;
    border-radius: 0 1rem 1rem 0;
    font-style: italic;
    color: #1e293b;
}

:deep(.article-content blockquote::before) {
    content: '"';
    position: absolute;
    top: 0.5rem;
    left: 1rem;
    font-size: 4rem;
    color: #cbd5e1;
    opacity: 0.5;
}

:deep(.article-content figure) {
    margin: 2rem 0;
}

:deep(.article-content figure img) {
    width: 100%;
    border-radius: 1rem;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}

:deep(.article-content figcaption) {
    text-align: center;
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.5rem;
}

:deep(.article-content table) {
    width: 100%;
    margin: 2rem 0;
    border-collapse: collapse;
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

:deep(.article-content th) {
    background: #f1f5f9;
    padding: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
}

:deep(.article-content td) {
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.custom-scrolltop {
    position: fixed !important;
    bottom: 2rem;
    right: 2rem;
    z-index: 50;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Ajustement responsive */
@media (max-width: 768px) {
    :deep(.article-content) {
        font-size: 1rem;
    }
    :deep(.article-content h2) {
        font-size: 1.75rem;
    }
}
</style>
