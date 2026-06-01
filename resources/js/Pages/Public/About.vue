<!--
  APROJED R.D. Congo - Page À propos
  Organigramme dynamique : Organisation -> Équipes (hiérarchie) -> Membres
  Données : témoignages et partenaires depuis la BDD
-->
<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import PublicLayout from '@/sakai/layout/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

// PrimeVue Components
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Timeline from 'primevue/timeline';
import Avatar from 'primevue/avatar';
import OrganizationChart from 'primevue/organizationchart';
import Carousel from 'primevue/carousel';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Rating from 'primevue/rating';

const toast = useToast();

const props = defineProps({
  // Données provenant du contrôleur
  teams: { type: Array, default: () => [] },          // Équipes avec hiérarchie et membres
  testimonials: { type: Array, default: () => [] },   // Témoignages actifs triés par 'order'
  partners: { type: Array, default: () => [] },       // Partenaires actifs triés par 'order'
  stats: {
    type: Object,
    default: () => ({ years: 15, provinces: 8, projects: 120, beneficiaries: 200000 })
  }
});

// ============================================================================
// ORDRE DES POSTES POUR TRI DES MEMBRES
// ============================================================================
const positionOrder = [
  'Directeur Exécutif',
  'Directeur Exécutif & Fondateur',
  'Coordonnatrice des Opérations',
  'Coordonnateur des Opérations',
  'Chef de Projet',
  'Responsable Technique',
  'Chargé de Communication',
  'Assistant',
  'Membre'
];

// ============================================================================
// CONSTRUCTION DE L'ORGANIGRAMME AVEC HIÉRARCHIE PARENT/ENFANT
// ============================================================================
const organizationTree = computed(() => {
  if (!props.teams || props.teams.length === 0) return null;

  const teamsMap = new Map();
  props.teams.forEach(team => {
    const teamCopy = { ...team, children: [] };
    teamsMap.set(team.id, teamCopy);
  });

  const rootTeams = [];
  teamsMap.forEach(team => {
    if (team.parent_id && teamsMap.has(team.parent_id)) {
      const parent = teamsMap.get(team.parent_id);
      parent.children.push(team);
    } else {
      rootTeams.push(team);
    }
  });

  const sortTeamsByOrder = (teamsArray) => {
    teamsArray.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
    teamsArray.forEach(team => {
      if (team.children.length) sortTeamsByOrder(team.children);
    });
  };
  sortTeamsByOrder(rootTeams);

  function buildTeamNode(team) {
    const sortedUsers = [...(team.users || [])].sort((a, b) => {
      const idxA = positionOrder.findIndex(p => (a.position || 'Membre').includes(p));
      const idxB = positionOrder.findIndex(p => (b.position || 'Membre').includes(p));
      const valA = idxA === -1 ? 999 : idxA;
      const valB = idxB === -1 ? 999 : idxB;
      return valA - valB;
    });

    const userNodes = sortedUsers.map(user => ({
      key: `user-${user.id}`,
      type: 'person',
      data: user,
      children: []
    }));

    const childTeamNodes = (team.children || []).map(child => buildTeamNode(child));

    return {
      key: `team-${team.id}`,
      type: 'team',
      data: team,
      children: [...childTeamNodes, ...userNodes]
    };
  }

  const rootTeamNodes = rootTeams.map(team => buildTeamNode(team));

  return {
    key: 'root',
    type: 'organization',
    data: { name: 'APROJED RDC', description: 'Direction Générale' },
    children: rootTeamNodes
  };
});

// ============================================================================
// TIMELINE (HISTOIRE) STATIQUE (peut être aussi en BDD si besoin)
// ============================================================================
const historyEvents = [
  {
    date: "2008",
    title: "Création d'APROJED",
    description: "Fondation de l'Association pour la Promotion de la Jeunesse et le Développement à Goma, Nord-Kivu.",
    icon: "pi-flag",
    image: "https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?auto=format&fit=crop&w=300&h=200"
  },
  {
    date: "2010",
    title: "Premier projet d'eau potable",
    description: "Forage de 15 puits d'eau potable dans les territoires de Sake et Masisi, améliorant l'accès à l'eau pour 25 000 personnes.",
    icon: "pi-water",
    image: "https://images.unsplash.com/photo-1581091226033-d5c48150dbaa?auto=format&fit=crop&w=300&h=200"
  },
  {
    date: "2013",
    title: "Programme d'alphabétisation",
    description: "Lancement de l'école communautaire 'Savoir pour Tous' avec 300 adultes formés chaque année.",
    icon: "pi-book",
    image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=300&h=200"
  },
  {
    date: "2015",
    title: "Agroforesterie",
    description: "Plantation de 50 000 arbres fruitiers et forestiers, création de 12 pépinières villageoises.",
    icon: "pi-globe",
    image: "https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?auto=format&fit=crop&w=300&h=200"
  },
  {
    date: "2018",
    title: "Autonomisation des femmes",
    description: "Mise en place de 20 coopératives agricoles gérées par des femmes, soutien à l'entreprenariat féminin.",
    icon: "pi-heart",
    image: "https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?auto=format&fit=crop&w=300&h=200"
  },
  {
    date: "2022",
    title: "Centre numérique",
    description: "Ouverture d'un centre de formation aux métiers du numérique pour les jeunes (programmation, design, marketing digital).",
    icon: "pi-desktop",
    image: "https://images.unsplash.com/photo-1581091226033-d5c48150dbaa?auto=format&fit=crop&w=300&h=200"
  },
  {
    date: "2025",
    title: "Reconnaissance internationale",
    description: "Partenariat avec l'Union Européenne et l'UNESCO pour étendre nos programmes éducatifs et environnementaux.",
    icon: "pi-star",
    image: "https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=300&h=200"
  }
];

// ============================================================================
// VALEURS (statiques ou configurables)
// ============================================================================
const values = [
  { title: "Intégrité", description: "Agir avec honnêteté et transparence dans toutes nos actions.", icon: "pi-shield" },
  { title: "Solidarité", description: "Travailler main dans la main avec les communautés locales.", icon: "pi-users" },
  { title: "Durabilité", description: "Construire des solutions pérennes respectueuses de l'environnement.", icon: "pi-globe" },
  { title: "Innovation", description: "Adopter des approches créatives pour résoudre les défis complexes.", icon: "pi-lightbulb" },
  { title: "Équité", description: "Promouvoir l'égalité des chances, en particulier pour les femmes et les jeunes.", icon: "pi-heart" }
];

// ============================================================================
// ANIMATION DES STATISTIQUES
// ============================================================================
const animatedStats = ref({ years: 0, provinces: 0, projects: 0, beneficiaries: 0 });
let statsObserver = null;

const startStatsAnimation = (entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const targets = {
        years: props.stats.years,
        provinces: props.stats.provinces,
        projects: props.stats.projects,
        beneficiaries: props.stats.beneficiaries
      };
      const duration = 1500;
      const stepTime = 20;
      const steps = duration / stepTime;
      let step = 0;
      const interval = setInterval(() => {
        step++;
        animatedStats.value.years = Math.min(targets.years, Math.ceil((targets.years * step) / steps));
        animatedStats.value.provinces = Math.min(targets.provinces, Math.ceil((targets.provinces * step) / steps));
        animatedStats.value.projects = Math.min(targets.projects, Math.ceil((targets.projects * step) / steps));
        animatedStats.value.beneficiaries = Math.min(targets.beneficiaries, Math.ceil((targets.beneficiaries * step) / steps));
        if (step >= steps) clearInterval(interval);
      }, stepTime);
      if (statsObserver) statsObserver.disconnect();
    }
  });
};

onMounted(() => {
  const statsSection = document.getElementById('stats-about');
  if (statsSection) {
    statsObserver = new IntersectionObserver(startStatsAnimation, { threshold: 0.3 });
    statsObserver.observe(statsSection);
  }
});
onUnmounted(() => { if (statsObserver) statsObserver.disconnect(); });
</script>

<template>
  <PublicLayout>
    <Head title="À propos - APROJED R.D. Congo" />

    <!-- HERO BANNIÈRE -->
    <div class="relative bg-slate-900 pt-32 pb-20 overflow-hidden">
      <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=1920')"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <Badge value="Notre Organisation" severity="success" class="bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 font-bold uppercase text-[10px] tracking-widest" />
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-white mt-4">Au service du développement</h1>
        <p class="text-slate-300 text-lg max-w-3xl mx-auto mt-6">Depuis 2008, APROJED œuvre pour l'autonomisation des communautés à travers l'éducation, la santé, l'environnement et l'économie locale.</p>
      </div>
    </div>

    <!-- MISSION, VISION, VALEURS -->
    <div class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-blue-50 p-8 rounded-3xl text-center shadow-sm hover:shadow-md transition">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg">
              <i class="pi pi-flag text-white text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800">Notre Mission</h3>
            <p class="text-slate-600 mt-3 leading-relaxed">Promouvoir le développement intégré et durable des communautés rurales et périurbaines en RDC par l'accès à l'eau, l'éducation, la santé et l'autonomisation économique.</p>
          </div>
          <div class="bg-emerald-50 p-8 rounded-3xl text-center shadow-sm hover:shadow-md transition">
            <div class="w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg">
              <i class="pi pi-eye text-white text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800">Notre Vision</h3>
            <p class="text-slate-600 mt-3 leading-relaxed">Une société congolaise où chaque individu, en particulier les jeunes et les femmes, a la capacité de participer activement à la transformation positive de son environnement.</p>
          </div>
          <div class="bg-amber-50 p-8 rounded-3xl text-center shadow-sm hover:shadow-md transition">
            <div class="w-16 h-16 bg-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg">
              <i class="pi pi-heart text-white text-2xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800">Nos Valeurs</h3>
            <p class="text-slate-600 mt-3 leading-relaxed">Intégrité, solidarité, durabilité, innovation et équité guident chacune de nos actions en faveur des communautés vulnérables.</p>
          </div>
        </div>

        <div class="mt-20">
          <div class="text-center mb-12">
            <Badge value="Nos principes" severity="secondary" />
            <h2 class="text-3xl font-black mt-4 text-slate-800">Ce qui nous anime</h2>
            <p class="text-slate-500 max-w-2xl mx-auto">Des valeurs fondamentales qui forgent notre identité et nos actions quotidiennes.</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <div v-for="val in values" :key="val.title" class="bg-slate-50 rounded-2xl p-6 text-center border border-slate-100 hover:border-amber-200 transition">
              <i :class="['pi', val.icon, 'text-3xl text-amber-500 mb-4 block']"></i>
              <h4 class="font-bold text-lg text-slate-800">{{ val.title }}</h4>
              <p class="text-sm text-slate-500 mt-2">{{ val.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CHIFFRES CLÉS (STATS ANIMÉES) -->
    <div id="stats-about" class="bg-gradient-to-r from-blue-900 to-slate-800 py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
        <div>
          <div class="text-5xl font-black">{{ animatedStats.years }}+</div>
          <div class="text-blue-200 font-medium mt-2 uppercase tracking-wide">Années d'action</div>
        </div>
        <div>
          <div class="text-5xl font-black">{{ animatedStats.provinces }}</div>
          <div class="text-blue-200 font-medium mt-2 uppercase tracking-wide">Provinces couvertes</div>
        </div>
        <div>
          <div class="text-5xl font-black">{{ animatedStats.projects }}+</div>
          <div class="text-blue-200 font-medium mt-2 uppercase tracking-wide">Projets réalisés</div>
        </div>
        <div>
          <div class="text-5xl font-black">{{ animatedStats.beneficiaries.toLocaleString() }}+</div>
          <div class="text-blue-200 font-medium mt-2 uppercase tracking-wide">Bénéficiaires</div>
        </div>
      </div>
    </div>

    <!-- ORGANIGRAMME -->
    <!-- ORGANIGRAMME HIÉRARCHIQUE – STYLE ULTRA PRO -->
<div class="bg-gradient-to-b from-slate-50 to-blue-50/30 py-20">
  <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête de section -->
    <div class="text-center mb-16">
      <Badge value="Gouvernance" severity="info" class="bg-blue-100 text-blue-800 font-mono text-xs tracking-wider px-4 py-1 rounded-full" />
      <h2 class="text-4xl font-black mt-6 bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">Notre organigramme</h2>
      <p class="text-slate-500 mt-3 max-w-2xl mx-auto">Découvrez la structure hiérarchique d’APROJED, de la direction générale aux équipes terrain.</p>
    </div>

    <!-- Conteneur de l'organigramme avec scroll horizontal si nécessaire -->
    <div class="overflow-x-auto pb-8 flex justify-center">
      <OrganizationChart
        v-if="organizationTree"
        :value="organizationTree"
        class="org-chart-modern min-w-[800px] md:min-w-full"
      >
        <!-- ==================== NŒUD RACINE (ORGANISATION) ==================== -->
       <template #organization="slotProps">
        <div class="relative group">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-500 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-300"></div>

            <div class="relative bg-slate-900/60 backdrop-blur-md border border-slate-800 group-hover:border-emerald-500/50 rounded-2xl shadow-2xl px-8 py-5 text-center min-w-[220px] transition-colors duration-300">

            <i class="pi pi-building text-3xl text-emerald-400 mb-3 block group-hover:scale-105 transition-transform duration-300"></i>

            <span class="font-black text-xl text-white block tracking-tight mb-1">{{ slotProps.node.data.name }}</span>

            <span class="text-emerald-300/80 text-[11px] uppercase tracking-widest font-bold block">{{ slotProps.node.data.description }}</span>

            </div>
        </div>
    </template>

        <!-- ==================== NŒUD ÉQUIPE (niveaux 1,2,…) ==================== -->
        <template #team="slotProps">
          <div class="relative group w-64">
            <!-- Carte principale -->
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-slate-100 hover:border-blue-200">
              <!-- Bandeau couleur (basé sur la couleur de l'équipe) -->
              <div class="h-2" :style="{ backgroundColor: slotProps.node.data.color || '#10b981' }"></div>

              <div class="p-5 text-center">
                <!-- Icône dynamique -->
                <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center shadow-md mb-3" :style="{ backgroundColor: slotProps.node.data.color || '#10b981', color: '#fff' }">
                  <i :class="[slotProps.node.data.slug?.includes('tech') ? 'pi pi-wrench' : 'pi pi-sitemap', 'text-2xl']"></i>
                </div>

                <h3 class="font-bold text-lg text-slate-800">{{ slotProps.node.data.name }}</h3>

                <!-- Localisation (optionnelle) -->
                <div v-if="slotProps.node.data.location" class="flex items-center justify-center gap-1 text-xs text-slate-500 mt-1">
                  <i class="pi pi-map-marker text-[10px]"></i>
                  <span>{{ slotProps.node.data.location }}</span>
                </div>

                <!-- Métriques rapides -->
                <div class="flex justify-center gap-4 mt-3 text-xs font-medium text-slate-500">
                  <div class="flex items-center gap-1">
                    <i class="pi pi-users text-slate-400"></i>
                    <span>{{ (slotProps.node.children || []).filter(c => c.type === 'person').length }} membres</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <i class="pi pi-sitemap text-slate-400"></i>
                    <span>{{ (slotProps.node.children || []).filter(c => c.type === 'team').length }} sous‑équipes</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ==================== NŒUD MEMBRE (UTILISATEUR) ==================== -->
        <template #person="slotProps">
          <div class="relative group w-72">
            <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-3 border border-slate-100 hover:border-blue-200 flex items-start gap-3">
              <!-- Avatar avec statut actif/inactif -->
              <div class="relative shrink-0">
                <img v-if="slotProps.node.data.avatar_url" :src="slotProps.node.data.avatar_url" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm" />
                <Avatar v-else :label="(slotProps.node.data.name?.charAt(0) || '?') + (slotProps.node.data.last_name?.charAt(0) || '')" class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold text-lg shadow-sm" />
                <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full" :class="slotProps.node.data.is_active ? 'bg-emerald-500 ring-2 ring-white' : 'bg-rose-400 ring-2 ring-white'"></span>
              </div>

              <!-- Infos -->
              <div class="flex-1 min-w-0">
                <div class="font-bold text-slate-800 text-sm truncate">
                  {{ slotProps.node.data.name }} {{ slotProps.node.data.last_name || '' }}
                </div>
                <div class="text-xs text-blue-600 font-medium truncate mt-0.5">
                  {{ slotProps.node.data.position || 'Membre' }}
                </div>
                <div v-if="slotProps.node.data.email" class="text-[10px] text-slate-400 truncate mt-1 flex items-center gap-1">
                  <i class="pi pi-envelope"></i> {{ slotProps.node.data.email }}
                </div>
                <div v-if="slotProps.node.data.phone" class="text-[10px] text-slate-400 truncate flex items-center gap-1">
                  <i class="pi pi-phone"></i> {{ slotProps.node.data.phone }}
                </div>
              </div>

              <!-- Badge contrat (optionnel) -->
              <!-- <div v-if="slotProps.node.data.phone" class="shrink-0">
                <span class="text-[9px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-full">
                  {{ slotProps.node.data.phone }}
                </span>
              </div> -->
            </div>
          </div>
        </template>
      </OrganizationChart>

      <!-- Message si aucun arbre -->
      <div v-else class="text-center py-12 bg-white rounded-2xl shadow-md w-full max-w-lg mx-auto">
        <i class="pi pi-sitemap text-5xl text-slate-300 mb-3 block"></i>
        <p class="text-slate-500 font-medium">La structure des équipes sera bientôt disponible.</p>
      </div>
    </div>
  </div>
</div>

    <!-- TIMELINE (HISTOIRE) -->
    <div class="py-20 bg-white">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <Badge value="Notre parcours" severity="info" class="bg-slate-100 text-slate-700" />
          <h2 class="text-3xl font-black mt-4 text-slate-800">Une histoire d'impact</h2>
          <p class="text-slate-500 max-w-2xl mx-auto">Découvrez les étapes clés de notre engagement depuis 2008.</p>
        </div>
        <Timeline :value="historyEvents" align="alternate" class="custom-timeline">
          <template #marker="slotProps">
            <span class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 shadow-md border-2 border-white">
              <i :class="[slotProps.item.icon, 'text-xl']"></i>
            </span>
          </template>
          <template #content="slotProps">
            <Card class="overflow-hidden shadow-lg hover:shadow-xl transition">
              <template #header>
                <div class="relative">
                  <img :src="slotProps.item.image" alt="Illustration" class="h-48 w-full object-cover" />
                  <div class="absolute top-4 left-4 bg-black/60 text-white text-sm font-bold px-3 py-1 rounded-full backdrop-blur-sm">{{ slotProps.item.date }}</div>
                </div>
              </template>
              <template #title>
                <span class="font-black text-xl text-slate-800">{{ slotProps.item.title }}</span>
              </template>
              <template #content>
                <p class="text-slate-600 leading-relaxed">{{ slotProps.item.description }}</p>
              </template>
            </Card>
          </template>
        </Timeline>
      </div>
    </div>

    <!-- PARTENAIRES (depuis la BDD) -->
    <div class="bg-slate-50 py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <Badge value="Ils nous soutiennent" severity="success" class="bg-emerald-100 text-emerald-700" />
          <h2 class="text-3xl font-black mt-4 text-slate-800">Nos partenaires</h2>
          <p class="text-slate-500">Un réseau de confiance pour amplifier notre impact.</p>
        </div>
        <div class="flex flex-wrap justify-center items-center gap-12">
          <div v-for="partner in partners" :key="partner.id" class="bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-all w-36 h-36 flex items-center justify-center grayscale hover:grayscale-0 group">
            <a :href="partner.website" target="_blank" class="block" v-if="partner.website">
              <img :src="partner.logo_url" :alt="partner.name" class="max-h-16 max-w-full object-contain" />
            </a>
            <img v-else :src="partner.logo_url" :alt="partner.name" class="max-h-16 max-w-full object-contain" />
            <span class="sr-only">{{ partner.name }}</span>
          </div>
        </div>
        <div v-if="!partners.length" class="text-center py-8 text-slate-500 italic">
          Aucun partenaire enregistré pour le moment.
        </div>
      </div>
    </div>

    <!-- TÉMOIGNAGES (depuis la BDD) -->
    <div class="py-20 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <Badge value="Paroles de terrain" severity="info" />
          <h2 class="text-3xl font-black mt-4 text-slate-800">Ce qu'ils disent de nous</h2>
        </div>
        <Carousel v-if="testimonials.length" :value="testimonials" :numVisible="1" :numScroll="1" class="testimonial-carousel" :autoplayInterval="5000" :circular="true">
          <template #item="slotProps">
            <div class="bg-blue-50 rounded-3xl p-8 text-center shadow-md">
              <Avatar :image="slotProps.data.avatar" shape="circle" size="xlarge" class="mx-auto mb-4 border-4 border-white shadow-lg" />
              <div class="flex justify-center mb-3">
                <Rating v-model="slotProps.data.rating" :readonly="true" :stars="5" cancel="false" class="rating-custom" />
              </div>
              <p class="text-slate-700 italic text-lg">"{{ slotProps.data.content }}"</p>
              <h4 class="font-bold text-blue-800 mt-4">{{ slotProps.data.name }}</h4>
              <p class="text-sm text-slate-500">{{ slotProps.data.position }} {{ slotProps.data.company ? ', ' + slotProps.data.company : '' }}</p>
            </div>
          </template>
        </Carousel>
        <div v-else class="text-center py-12 bg-slate-50 rounded-3xl">
          <i class="pi pi-comment text-4xl text-slate-300 mb-3 block"></i>
          <p class="text-slate-500">Aucun témoignage disponible pour le moment.</p>
        </div>
      </div>
    </div>


    <Divider />
  </PublicLayout>
</template>

<style scoped>
/* Styles pour l'organigramme (PrimeVue OrganizationChart) */
.org-chart-google :deep(.p-organizationchart-table) {
  margin: 0 auto;
}
.org-chart-google :deep(.p-organizationchart-node-content) {
  border: none !important;
  background: transparent !important;
  padding: 0 5px !important;
}
.org-chart-google :deep(.p-organizationchart-line-down),
.org-chart-google :deep(.p-organizationchart-line-top) {
  background-color: #cbd5e1 !important;
  border-color: #cbd5e1 !important;
  width: 2px !important;
}
.org-chart-google :deep(.p-organizationchart-line-left),
.org-chart-google :deep(.p-organizationchart-line-right) {
  border-color: #cbd5e1 !important;
  border-width: 2px !important;
}
.org-chart-google :deep(.p-organizationchart-line-down) {
  background: #cbd5e1 !important;
}
.org-chart-google :deep(tr:nth-child(2) > td) {
  padding-top: 1.5rem !important;
}

/* Timeline personnalisée */
.custom-timeline :deep(.p-timeline-event-opposite) {
  flex: 0 0 20%;
}
.custom-timeline :deep(.p-card) {
  border-radius: 1.5rem;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
.custom-timeline :deep(.p-timeline-event) {
  margin-bottom: 3rem;
}

/* Carrousel témoignages */
.testimonial-carousel :deep(.p-carousel-indicators) {
  margin-top: 2rem;
}
.testimonial-carousel :deep(.p-carousel-indicator button) {
  background-color: #cbd5e1;
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 9999px;
}
.testimonial-carousel :deep(.p-carousel-indicator.p-highlight button) {
  background-color: #10b981;
}
/* Style des étoiles de notation */
.rating-custom :deep(.p-rating-icon) {
  color: #fbbf24;
  width: 1.2rem;
}
</style>
