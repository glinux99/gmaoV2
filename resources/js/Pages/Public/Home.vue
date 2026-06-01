<!--
  APROJED R.D. Congo - Page d'Accueil (Dynamique Ultra Premium)
  Version intégrant tous les paramètres du site (Settings)
  Toutes les données sont injectées via Inertia props.
-->
<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick, reactive } from 'vue';
import PublicLayout from '@/sakai/layout/PublicLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
// PrimeVue Components
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Dropdown from 'primevue/dropdown';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Skeleton from 'primevue/skeleton';
import Carousel from 'primevue/carousel';
import ProgressBar from 'primevue/progressbar';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Galleria from 'primevue/galleria';
import Image from 'primevue/image';
import ToggleButton from 'primevue/togglebutton';
import Select from 'primevue/select';

const toast = useToast();
const page = usePage();

// ==================== PROPS ====================
const props = defineProps({
    heroSlides: { type: Array, default: () => [] },
    globalStats: { type: Object, default: () => ({}) },
    initiatives: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    teamMembers: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
    testimonials: { type: Array, default: () => [] },
    latestPosts: { type: Array, default: () => [] },
    faqItems: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    donationAmounts: { type: Array, default: () => [15, 30, 50, 100, 250] },
    settings: { type: Object, default: () => ({}) }, // Paramètres du site
});

// ==================== ÉTATS LOCAUX ====================
const activeInitiativeTab = ref(0);
const selectedProvinceFilter = ref('ALL');
const donationModalVisible = ref(false);
const donationStep = ref(1);
const donationForm = ref({
    amountType: 'once', amount: 50, customAmount: null,
    paymentMethod: null, firstName: '', lastName: '',
    email: '', phone: '', taxReceipt: false
});
const currentSlideIndex = ref(0);
let slideInterval = null;
const counters = ref({ water: 0, health: 0, education: 0, trees: 0 });
const statsObserved = ref(false);
const animatedElements = ref([]);
let observer = null;

// Configuration des méthodes de paiement (depuis settings ou par défaut)
const paymentMethods = ref([]);
const configModalVisible = ref(false);
const showAddMethodDialog = ref(false);
const newMethod = reactive({ label: '', icon: '', type: '', clientId: '', secret: '', mode: 'sandbox' });
const availableTypes = [
    { label: 'PayPal', value: 'paypal' },
    { label: 'Stripe', value: 'stripe' },
    { label: 'Virement', value: 'transfer' }
];

// ==================== COMPUTED ====================
const filteredProjects = computed(() => {
    if (!props.projects.length) return [];
    if (selectedProvinceFilter.value === 'ALL') return props.projects;
    return props.projects.filter(p => p.province === selectedProvinceFilter.value);
});

const activePaymentMethods = computed(() => paymentMethods.value.filter(m => m.active));

// ==================== MÉTHODES ====================
const setDonationAmount = (amount) => {
    donationForm.value.amount = amount;
    donationForm.value.customAmount = null;
};
const onCustomAmountInput = () => {
    if (donationForm.value.customAmount) donationForm.value.amount = donationForm.value.customAmount;
};

const handleDonationSubmit = () => {
    if (!donationForm.value.firstName?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le prénom est requis.' });
        return;
    }
    if (!donationForm.value.lastName?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le nom est requis.' });
        return;
    }
    if (!donationForm.value.email?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'L\'email est requis.' });
        return;
    }
    if (!donationForm.value.paymentMethod) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le moyen de paiement est requis.' });
        return;
    }

    const amount = donationForm.value.amountType === 'once' && donationForm.value.customAmount
        ? donationForm.value.customAmount
        : donationForm.value.amount;

    if (!amount || amount <= 0) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le montant du don est invalide.' });
        return;
    }

    donationStep.value = 3;
    const formData = new FormData();
    formData.append('amountType', donationForm.value.amountType);
    formData.append('amount', amount);
    formData.append('paymentMethod', donationForm.value.paymentMethod);
    formData.append('firstName', donationForm.value.firstName);
    formData.append('lastName', donationForm.value.lastName);
    formData.append('email', donationForm.value.email);
    formData.append('phone', donationForm.value.phone || '');
    formData.append('taxReceipt', donationForm.value.taxReceipt ? 1 : 0);

    router.post(route('donations.store'), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'En attente de réception !',
                detail: `Merci pour votre don de ${amount} USD. Notre équipe vous contactera dans les plus brefs délais.`,
                life: 6000
            });
            donationForm.value = {
                amountType: 'once', amount: 50, customAmount: null,
                paymentMethod: null, firstName: '', lastName: '',
                email: '', phone: '', taxReceipt: false
            };
            donationModalVisible.value = false;
            donationStep.value = 1;
        },
        onError: (errors) => {
            donationStep.value = 1;
            const errorMsg = Object.values(errors).flat()[0];
            toast.add({ severity: 'error', summary: 'Erreur', detail: errorMsg || 'Une erreur est survenue.' });
        }
    });
};

// Slider
const startSlider = () => {
    if (!props.heroSlides.length) return;
    slideInterval = setInterval(() => {
        currentSlideIndex.value = (currentSlideIndex.value + 1) % props.heroSlides.length;
    }, 6000);
};
const setSlide = (index) => {
    currentSlideIndex.value = index;
    if (slideInterval) { clearInterval(slideInterval); startSlider(); }
};

// Animation des compteurs
const animateCounters = (entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !statsObserved.value) {
            statsObserved.value = true;
            const targets = {
                water: props.globalStats.beneficiariesWater ? parseInt(props.globalStats.beneficiariesWater.replace(/\D/g, '')) : 90000,
                health: props.globalStats.medicalConsultations ? parseInt(props.globalStats.medicalConsultations.replace(/\D/g, '')) : 14200,
                education: props.globalStats.students ? parseInt(props.globalStats.students.replace(/\D/g, '')) : 4500,
                trees: props.globalStats.plantedTrees ? parseInt(props.globalStats.plantedTrees.replace(/\D/g, '')) : 120000,
            };
            const duration = 1500;
            const stepTime = 20;
            const steps = duration / stepTime;
            let step = 0;
            const interval = setInterval(() => {
                step++;
                counters.value.water = Math.min(targets.water, Math.ceil((targets.water * step) / steps));
                counters.value.health = Math.min(targets.health, Math.ceil((targets.health * step) / steps));
                counters.value.education = Math.min(targets.education, Math.ceil((targets.education * step) / steps));
                counters.value.trees = Math.min(targets.trees, Math.ceil((targets.trees * step) / steps));
                if (step >= steps) clearInterval(interval);
            }, stepTime);
        }
    });
};

// Animation fade-up au scroll
const setupScrollAnimations = () => {
    const elements = document.querySelectorAll('.scroll-animate');
    if (!elements.length) return;
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-up');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    elements.forEach(el => observer.observe(el));
};

// Chargement des moyens de paiement depuis les settings (ou défaut)
const loadPaymentMethodsFromSettings = () => {
    if (props.settings?.payment_methods) {
        try {
            paymentMethods.value = JSON.parse(props.settings.payment_methods);
        } catch(e) {
            setDefaultPaymentMethods();
        }
    } else {
        setDefaultPaymentMethods();
    }
};

const setDefaultPaymentMethods = () => {
    paymentMethods.value = [
        { id: 1, label: 'PayPal', icon: 'pi-paypal', active: true, type: 'paypal', needConfig: true, isDefault: false },
        { id: 2, label: 'Carte bancaire (Stripe)', icon: 'pi-credit-card', active: true, type: 'stripe', needConfig: true, isDefault: false },
        { id: 3, label: 'Virement bancaire', icon: 'pi-building', active: true, type: 'transfer', needConfig: false, description: 'Renseignements bancaires', isDefault: true }
    ];
};

// Configuration des méthodes de paiement (admin)
const openMethodConfig = (method) => {
    console.log('Configurer', method);
    // À implémenter avec enregistrement en base
};
const addMethod = () => {
    if (!newMethod.label || !newMethod.type) return;
    paymentMethods.value.push({
        id: Date.now(),
        label: newMethod.label,
        icon: newMethod.icon || 'pi-question-circle',
        active: true,
        type: newMethod.type,
        needConfig: (newMethod.type !== 'transfer'),
        ...newMethod
    });
    showAddMethodDialog.value = false;
    // Sauvegarder dans settings via API
};
const removeMethod = (index) => {
    paymentMethods.value.splice(index, 1);
};

// Formattage date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
};

// Ouvre la modale de don avec un montant pré-rempli
const openDonationWithAmount = (amount) => {
    window.dispatchEvent(new CustomEvent('open-donation-modal', { detail: { amount } }));
};

// ==================== HOOKS ====================
onMounted(() => {
    startSlider();
    const statsSection = document.getElementById('stats-section');
    if (statsSection) {
        const statsObserver = new IntersectionObserver(animateCounters, { threshold: 0.3 });
        statsObserver.observe(statsSection);
    }
    nextTick(() => setupScrollAnimations());
    loadPaymentMethodsFromSettings();
});

onUnmounted(() => {
    if (slideInterval) clearInterval(slideInterval);
    if (observer) observer.disconnect();
});
</script>

<template>
    <PublicLayout>
        <Head title="Accueil - APROJED R.D. Congo" />

        <!-- ==================== HERO SLIDER ==================== -->
        <section class="relative bg-slate-950 pt-20 pb-32 overflow-hidden text-white min-h-[90vh] flex items-center">
            <div class="absolute inset-0 z-0">
                <div v-for="(slide, idx) in heroSlides" :key="idx"
                     class="absolute inset-0 bg-cover bg-center transition-all duration-[1500ms] ease-in-out"
                     :class="currentSlideIndex === idx ? 'opacity-40 scale-105 z-10' : 'opacity-0 scale-100 z-0'"
                     :style="{ backgroundImage: `url('${slide.image}')` }">
                </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/90 to-emerald-950/40 mix-blend-multiply z-10"></div>
            <div class="w-full px-6 lg:px-12 xl:px-20 mx-auto max-w-[1800px] relative z-20">
                <div class="grid lg:grid-cols-12 gap-16 items-center">
                    <div class="lg:col-span-7">
                        <Transition name="fade-slide" mode="out-in">
                            <div :key="currentSlideIndex" class="space-y-8">
                                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 font-mono text-sm tracking-widest uppercase font-bold backdrop-blur-md">
                                    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                                    {{ heroSlides[currentSlideIndex]?.badge || 'APROJED EN ACTION' }}
                                </div>
                                <h1 class="text-5xl sm:text-6xl md:text-7xl font-black tracking-tighter leading-[1.1]">
                                    {{ heroSlides[currentSlideIndex]?.titlePre || '' }}
                                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
                                        {{ heroSlides[currentSlideIndex]?.titleHighlight || '' }}
                                    </span>
                                    {{ heroSlides[currentSlideIndex]?.titlePost || '' }}
                                </h1>
                                <p class="text-slate-300 text-xl font-light max-w-2xl">{{ heroSlides[currentSlideIndex]?.description || '' }}</p>
                            </div>
                        </Transition>
                        <div class="mt-10 space-y-8">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <Button label="Découvrir nos Actions" icon="pi pi-arrow-down" class="bg-emerald-500 hover:bg-emerald-600 border-none text-white font-bold px-8 py-4 rounded-2xl" @click="document.getElementById('initiatives').scrollIntoView({behavior: 'smooth'})" />
                                <Button label="Soutenir APROJED" icon="pi pi-heart" class="p-button-outlined !text-white !border-slate-500 hover:!bg-slate-800 px-8 py-4 rounded-2xl font-bold" @click="openDonationWithAmount(300)" >
                                  <template #loadingicon="slotProps"></template>
                                </Button>
                            </div>
                            <div class="flex items-center gap-3">
                                <button v-for="(_, idx) in heroSlides" :key="idx" @click="setSlide(idx)" class="h-1.5 rounded-full transition-all" :class="currentSlideIndex === idx ? 'w-10 bg-emerald-400' : 'w-4 bg-slate-600 hover:bg-slate-400'"></button>
                            </div>
                        </div>
                    </div>
                    <!-- CTA droite : Forage d'urgence (ou autre campagne) -->
                    <div v-if="settings.hero_campaign_active" class="lg:col-span-5 hidden lg:block">
                        <div class="bg-slate-900/70 backdrop-blur-xl rounded-2xl p-10 border border-slate-700/60 shadow-2xl">
                            <div class="flex items-center justify-between mb-8">
                                <span class="text-sm font-bold text-emerald-400">{{ settings.hero_campaign_badge || 'Urgence' }}</span>
                                <span class="px-3 py-1.5 rounded bg-red-500/20 text-red-400 border border-red-500/30 text-xs uppercase font-bold animate-pulse">Vital</span>
                            </div>
                            <h3 class="text-3xl font-black text-white">{{ settings.hero_campaign_title }}</h3>
                            <p class="text-slate-300 text-lg mb-8">{{ settings.hero_campaign_description }}</p>
                            <div class="space-y-3 mb-10">
                                <div class="flex justify-between text-sm font-bold">
                                    <span>Financement : {{ settings.hero_campaign_current?.toLocaleString() }} / {{ settings.hero_campaign_target?.toLocaleString() }} USD</span>
                                    <span class="text-emerald-400">{{ Math.round((settings.hero_campaign_current / settings.hero_campaign_target) * 100) || 0 }}%</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-4">
                                    <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full" :style="{ width: (Math.min(100, (settings.hero_campaign_current / settings.hero_campaign_target) * 100) || 0) + '%' }"></div>
                                </div>
                            </div>
                            <Button :label="settings.hero_campaign_btn_text || 'Participer'" icon="pi pi-heart-fill" class="w-full bg-white hover:bg-emerald-50 border-none py-5 rounded-2xl font-black text-slate-900" @click="openDonationWithAmount(100)" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== MISSION (3 CARTES) ==================== -->
        <section id="about" class="py-24 bg-white scroll-mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center space-y-4 mb-20">
                    <Badge value="Notre Raison d'Être" severity="success" class="bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 font-bold uppercase text-[10px] tracking-widest" />
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900">Transformer l'urgence humanitaire en autonomie durable</h2>
                    <p class="text-slate-500 text-lg">APROJED agit au cœur des territoires vulnérables de RDC.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100 shadow-sm transition hover:shadow-xl hover:-translate-y-1 scroll-animate"><div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center text-2xl"><i class="pi pi-shield"></i></div><h3 class="text-xl font-bold mt-6 mb-3">Pérennité locale</h3><p class="text-slate-500 text-sm">Création de comités de gestion formés pour pérenniser les infrastructures.</p></div>
                    <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100 shadow-sm transition hover:shadow-xl hover:-translate-y-1 scroll-animate"><div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 text-white flex items-center justify-center text-2xl"><i class="pi pi-chart-line"></i></div><h3 class="text-xl font-bold mt-6 mb-3">Transparence Absolue</h3><p class="text-slate-500 text-sm">83% des ressources vont directement aux programmes terrain.</p></div>
                    <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100 shadow-sm transition hover:shadow-xl hover:-translate-y-1 scroll-animate"><div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 text-white flex items-center justify-center text-2xl"><i class="pi pi-users"></i></div><h3 class="text-xl font-bold mt-6 mb-3">Développement Endogène</h3><p class="text-slate-500 text-sm">Structures co-développées avec des experts locaux.</p></div>
                </div>
            </div>
        </section>

        <!-- ==================== PILIERS D'INTERVENTION ==================== -->
        <section id="initiatives" class="py-24 bg-slate-50 scroll-mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center space-y-4 mb-16">
                    <Badge value="Secteurs clés" severity="info" class="bg-indigo-500/10 text-indigo-700 border border-indigo-500/20 font-bold uppercase text-[10px] tracking-widest" />
                    <h2 class="text-3xl sm:text-4xl font-black">Nos Piliers d'Intervention</h2>
                </div>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
                    <TabView v-model:activeIndex="activeInitiativeTab" class="custom-landing-tabview">
                        <TabPanel v-for="(init, idx) in initiatives" :key="idx">
                            <template #header><div class="flex items-center gap-2 px-3 py-1"><i :class="['pi', init.icon, `text-${init.color}-500`]"></i><span class="font-bold">{{ init.title }}</span></div></template>
                            <div class="p-6 sm:p-10 lg:p-12">
                                <div class="grid lg:grid-cols-2 gap-12 items-center">
                                    <div class="space-y-6">
                                        <Tag :value="`Pilier n°0${idx+1}`" :severity="idx===0?'danger':idx===1?'info':idx===2?'success':'warning'" class="font-bold text-[10px] tracking-widest rounded-full" />
                                        <h3 class="text-3xl font-black">{{ init.title }}</h3>
                                        <p class="text-emerald-700 font-semibold">{{ init.summary }}</p>
                                        <p class="text-slate-500">{{ init.description }}</p>
                                        <Divider />
                                        <div class="grid grid-cols-3 gap-4">
                                            <div v-for="m in init.metrics" :key="m.label" class="p-3 bg-slate-50 rounded-xl text-center scroll-animate"><span class="text-xl font-black">{{ m.value }}</span><span class="text-[10px] font-bold text-slate-400 uppercase block">{{ m.label }}</span></div>
                                        </div>
                                    </div>

                                    <div class="relative h-80 rounded-2xl overflow-hidden bg-slate-100 flex items-center justify-center">
                                        <img v-if="init.image" :src="`/media/${init.image}`" class="w-full h-full object-cover" />
                                        <div v-else class="flex flex-col items-center text-slate-300">
                                            <i :class="['pi', init.icon || 'pi-image']" class="text-7xl mb-4 opacity-20"></i>
                                            <span class="text-xs font-bold uppercase tracking-widest opacity-40">Aprojed Impact</span>
                                        </div>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent pointer-events-none"></div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </section>

        <!-- ==================== PROJETS TERRAIN ==================== -->
        <section id="projects" class="py-24 bg-white scroll-mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row justify-between gap-6 mb-16">
                    <div><Badge value="Transparence Opérationnelle" severity="success" class="bg-emerald-500/10 text-emerald-700 border border-emerald-500/20" /><h2 class="text-3xl sm:text-4xl font-black mt-4">Projets Actifs en R.D. Congo</h2><p class="text-slate-500 text-lg">Suivez l'avancement réel, budgets et responsables.</p></div>
                    <div class="flex items-center gap-2"><span class="text-sm font-bold">Filtrer par province :</span><Dropdown v-model="selectedProvinceFilter" :options="['ALL', ...provinces]" placeholder="Toutes" class="w-44" /></div>
                </div>
                <div class="grid md:grid-cols-2 gap-8">
                    <div v-for="prj in filteredProjects" :key="prj.id" class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition overflow-hidden group scroll-animate">
                        <div class="relative h-56"><img :src="`/media/${prj.image}`" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" /><div class="absolute top-4 left-4 flex flex-col gap-1"><span class="bg-black/60 backdrop-blur text-white text-xs px-2 py-1 rounded-md">{{ prj.provinceLabel }}</span><span v-if="prj.progress===100" class="bg-emerald-500 text-white text-[10px] px-2 py-0.5 rounded-md">Terminé</span></div><div class="absolute bottom-4 right-4 bg-black/60 text-white text-xs px-2 py-1 rounded-md">{{ prj.budget }}</div></div>
                        <div class="p-6 space-y-4"><h3 class="text-xl font-bold">{{ prj.title }}</h3><p class="text-slate-500 text-sm">{{ prj.target }}</p><ProgressBar :value="prj.progress" :showValue="true" class="h-2 rounded-full" /><Divider /><div class="flex justify-between text-xs text-slate-500"><span><i class="pi pi-user mr-1"></i> {{ prj.lead }}</span><span class="font-mono">{{ prj.id }}</span></div></div>
                    </div>
                </div>
                <div v-if="filteredProjects.length===0" class="text-center py-16 bg-slate-50 rounded-2xl border-dashed"><i class="pi pi-info-circle text-4xl text-slate-300 mb-3 block"></i><p>Aucun projet actif dans cette province.</p></div>
            </div>
        </section>

        <!-- ==================== STATS GLOBALES ==================== -->
        <section id="stats-section" class="py-20 bg-slate-900 text-white relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-12">
                <h2 class="text-3xl font-black">L'Impact Mesuré de nos Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="p-6 bg-slate-800/40 rounded-3xl backdrop-blur-sm"><span class="text-5xl font-black text-emerald-400">{{ counters.water.toLocaleString() }}+</span><p class="text-xs mt-2">Bénéficiaires d'eau</p></div>
                    <div class="p-6 bg-slate-800/40 rounded-3xl backdrop-blur-sm"><span class="text-5xl font-black text-indigo-400">{{ counters.health.toLocaleString() }}+</span><p class="text-xs mt-2">Consultations médicales</p></div>
                    <div class="p-6 bg-slate-800/40 rounded-3xl backdrop-blur-sm"><span class="text-5xl font-black text-rose-400">{{ counters.education.toLocaleString() }}+</span><p class="text-xs mt-2">Enfants scolarisés</p></div>
                    <div class="p-6 bg-slate-800/40 rounded-3xl backdrop-blur-sm"><span class="text-5xl font-black text-teal-400">{{ counters.trees.toLocaleString() }}+</span><p class="text-xs mt-2">Arbres plantés</p></div>
                </div>
            </div>
        </section>

        <!-- ==================== ÉQUIPE ==================== -->
        <section id="team" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 mb-16">
                <Badge value="Équipe de Direction" severity="success" class="bg-emerald-500/10 text-emerald-700 border border-emerald-500/20" />
                <h2 class="text-3xl font-black">Unis pour le Développement Durable de la RDC</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto px-6">
                <div v-for="member in teamMembers" :key="member.name" class="bg-slate-50 rounded-2xl p-6 text-center shadow-sm hover:shadow-lg transition scroll-animate">
                    <Avatar :image="member.avatar" shape="circle" size="xlarge" class="mx-auto w-24 h-24 shadow-md border-2 border-white" />
                    <h3 class="text-lg font-bold mt-4">{{ member.name }}</h3>
                    <p class="text-xs text-emerald-600 font-bold uppercase">{{ member.role }}</p>
                    <p class="text-sm text-slate-500 mt-3">{{ member.bio }}</p>
                </div>
            </div>
        </section>

        <!-- ==================== PARTENAIRES ==================== -->
        <section class="py-20 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <Badge value="Nos Partenaires" severity="info" class="bg-indigo-100 text-indigo-800" />
                <h2 class="text-3xl font-black mt-4 mb-12">Ils nous soutiennent</h2>
                <div v-if="partners.length === 0" class="text-center py-12 text-slate-400"><i class="pi pi-building text-4xl mb-3 block"></i><p>Liste des partenaires à venir.</p></div>
                <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8">

                    <div v-for="partner in partners" :key="partner.id" class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition flex flex-col items-center text-center group scroll-animate">

                        <img v-if="partner.logo_url" :src="`${partner.logo_url}`" :alt="partner.name" class="h-20 w-auto object-contain mb-4 filter grayscale group-hover:grayscale-0 transition duration-300" />
                        <i v-else class="pi pi-image text-4xl text-slate-200 mb-4"></i>
                        <h3 class="font-bold text-slate-800">{{ partner.name }}</h3>
                        <p class="text-xs text-slate-500 mt-1">{{ partner.description }}</p>
                        <a v-if="partner.website" :href="partner.website" target="_blank" class="text-xs text-emerald-600 hover:underline mt-2">Visiter le site</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== TÉMOIGNAGES ==================== -->
        <section class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <Badge value="Témoignages" severity="success" class="bg-emerald-100 text-emerald-800" />
                <h2 class="text-3xl font-black mt-4 mb-12">Ce qu'ils disent de nous</h2>
                <div v-if="testimonials.length === 0" class="text-center py-12 text-slate-400"><i class="pi pi-comment text-4xl mb-3 block"></i><p>Aucun témoignage pour le moment.</p></div>
                <Carousel v-else :value="testimonials" :numVisible="1" :numScroll="1" class="max-w-3xl mx-auto" :autoplayInterval="5000" :circular="true">
                    <template #item="slotProps">
                        <div class="bg-slate-50 rounded-3xl p-8 mx-4 text-center">
                            <Avatar :image="slotProps.data.avatar" :label="slotProps.data.author?.charAt(0)" shape="circle" class="w-20 h-20 mx-auto mb-4 shadow-md" />
                            <p class="text-slate-500 italic text-lg">"{{ slotProps.data.content }}"</p>
                            <h4 class="font-bold text-slate-800 mt-6">{{ slotProps.data.author }}</h4>
                            <span class="text-xs text-emerald-600">{{ slotProps.data.role }}</span>
                        </div>
                    </template>
                </Carousel>
            </div>
        </section>

        <!-- ==================== DERNIERS ARTICLES ==================== -->
        <section class="py-24 bg-slate-50 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16 max-w-2xl mx-auto">
                    <Badge value="Actualités" class="bg-amber-100 text-amber-700 border border-amber-200 px-3 py-1 font-black tracking-widest uppercase text-[10px]" />
                    <h2 class="text-4xl font-black mt-5 text-slate-900 tracking-tight">Derniers articles terrain</h2>
                    <p class="text-slate-500 mt-4 text-lg">Découvrez nos dernières actions, nos rapports de mission et l'impact de nos projets en temps réel.</p>
                </div>

                <div v-if="!latestPosts || latestPosts.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-100 shadow-sm text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="pi pi-file-edit text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">Aucune publication</h3>
                    <p class="text-slate-500 max-w-sm mt-1">Nous n'avons pas encore publié d'articles. Revenez très bientôt !</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
                    <Link
                        v-for="post in latestPosts.slice(0,3)"
                        :key="post.id"
                        :href="`/activites/${post.slug}`"
                        class="group bg-white rounded-[2rem] overflow-hidden shadow-md shadow-slate-200/50 hover:shadow-2xl hover:shadow-emerald-500/10 hover:-translate-y-1.5 transition-all duration-300 border border-slate-100 flex flex-col h-full scroll-animate"
                    >
                        <div class="relative h-60 w-full overflow-hidden bg-slate-100 shrink-0">
                            <img
                                v-if="post.cover_image"
                                :src="post.cover_image"
                                :alt="post.title"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400">
                                <i class="pi pi-image text-5xl mb-2 opacity-50"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest opacity-50">Aucune image</span>
                            </div>
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
                        </div>
                        <div class="p-6 sm:p-8 flex flex-col flex-grow relative">
                            <div class="flex items-center mb-4">
                                <span class="text-xs font-black text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-100/50 flex items-center gap-2">
                                    <i class="pi pi-calendar text-[10px]"></i> {{ formatDate(post.published_at) }}
                                </span>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 leading-snug mb-3 group-hover:text-emerald-600 transition-colors line-clamp-2">
                                {{ post.title }}
                            </h3>
                            <div class="mt-auto pt-6 flex items-center text-emerald-600 text-sm font-black uppercase tracking-wide group-hover:text-emerald-700 transition-colors">
                                Lire l'article complet
                                <i class="pi pi-arrow-right ml-2 transform group-hover:translate-x-1.5 transition-transform duration-300"></i>
                            </div>
                        </div>
                    </Link>
                </div>

                <div class="text-center mt-16">
                    <Link
                        href="/activites"
                        class="inline-flex items-center gap-2 bg-slate-900 hover:bg-emerald-600 text-white font-bold px-8 py-4 rounded-2xl shadow-xl shadow-slate-900/20 transition-all hover:-translate-y-1 hover:shadow-emerald-600/30"
                    >
                        Consulter toutes les actualités
                        <i class="pi pi-arrow-right text-sm"></i>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ==================== CONTACT & ASSISTANCE ==================== -->
        <section class="py-24 bg-slate-50 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <Badge value="Support & Assistance" class="bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1 font-black tracking-widest uppercase text-[10px]" />
                    <h2 class="text-4xl font-black mt-5 text-slate-900 tracking-tight">Toujours à vos côtés</h2>
                    <p class="text-slate-500 mt-4 text-lg">Une question sur nos actions, un partenariat ou un problème avec un don ? Notre équipe est prête à vous répondre.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <a :href="`tel:${settings.phone || '+243123456789'}`" class="group bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1.5 transition-all duration-300 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-sm">
                            <i class="pi pi-phone text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Appel d'urgence</h3>
                        <p class="text-2xl font-black text-emerald-600 mb-3 tracking-tight group-hover:text-emerald-700 transition-colors">
                            {{ settings.phone || '+243 123 456 789' }}
                        </p>
                        <p class="text-sm text-slate-500 mt-auto">Ligne directe pour les urgences ou l'assistance aux dons.</p>
                    </a>

                    <a :href="`mailto:${settings.email || 'contact@aprojed.org'}`" class="group bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1.5 transition-all duration-300 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-sm">
                            <i class="pi pi-envelope text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Email officiel</h3>
                        <p class="text-lg font-bold text-indigo-600 mb-3 break-all group-hover:text-indigo-700 transition-colors">
                            {{ settings.email || 'contact@aprojed.org' }}
                        </p>
                        <p class="text-sm text-slate-500 mt-auto">Pour les partenariats, requêtes presse et informations générales.</p>
                    </a>

                    <div class="group bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-rose-500/10 hover:-translate-y-1.5 transition-all duration-300 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-rose-500 group-hover:text-white transition-all duration-300 shadow-sm">
                            <i class="pi pi-map-marker text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Siège social</h3>
                        <p class="text-base font-medium text-slate-700 mb-3 leading-relaxed">
                            {{ [settings.address, settings.city, settings.postal_code, settings.country].filter(Boolean).join(', ') || 'Adresse non configurée' }}
                        </p>
                        <p class="text-sm text-slate-500 mt-auto">Bureaux ouverts du Lundi au Vendredi, sur rendez-vous.</p>
                    </div>
                </div>

                <div class="mt-12 bg-slate-900 rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden shadow-2xl">
                    <div class="absolute top-0 right-0 -mt-32 -mr-32 w-96 h-96 bg-emerald-500 rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                        <div class="lg:w-1/3">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-xs font-black uppercase tracking-widest mb-4">
                                <i class="pi pi-shield"></i> Transaction Sécurisée
                            </div>
                            <h3 class="text-2xl lg:text-3xl font-black text-white leading-tight">Coordonnées Bancaires</h3>
                            <p class="text-slate-400 mt-3 text-sm leading-relaxed">
                                Pour soutenir nos actions via un virement bancaire classique, veuillez utiliser les informations ci-contre. Précisez "Don" dans le motif.
                            </p>
                        </div>
                        <div class="lg:w-2/3 w-full bg-slate-800/50 backdrop-blur-md rounded-2xl p-6 border border-slate-700/50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Établissement</span>
                                    <div class="flex items-center gap-3 text-white font-medium bg-slate-900/50 p-3 rounded-xl border border-slate-700">
                                        <i class="pi pi-building text-emerald-400"></i>
                                        {{ settings.bank_name || 'Nom de la banque non renseigné' }}
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Titulaire du compte</span>
                                    <div class="flex items-center gap-3 text-white font-medium bg-slate-900/50 p-3 rounded-xl border border-slate-700">
                                        <i class="pi pi-user text-emerald-400"></i>
                                        APROJED ASBL
                                    </div>
                                </div>
                                <div class="flex flex-col md:col-span-2">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">IBAN / Numéro de compte international</span>
                                    <div class="flex items-center justify-between gap-3 text-white font-mono text-lg bg-slate-900/50 p-4 rounded-xl border border-slate-700 shadow-inner">
                                        <span class="tracking-widest">{{ settings.bank_iban || 'XX00 0000 0000 0000 0000 00' }}</span>
                                        <i class="pi pi-check-circle text-emerald-500 opacity-50" v-tooltip.top="'IBAN Vérifié'"></i>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">BIC / SWIFT</span>
                                    <div class="flex items-center gap-3 text-white font-mono bg-slate-900/50 p-3 rounded-xl border border-slate-700">
                                        <i class="pi pi-globe text-emerald-400"></i>
                                        {{ settings.bank_bic || 'XXXXXX' }}
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Numéro de Compte Local</span>
                                    <div class="flex items-center gap-3 text-white font-mono bg-slate-900/50 p-3 rounded-xl border border-slate-700">
                                        <i class="pi pi-credit-card text-emerald-400"></i>
                                        {{ settings.bank_account || 'Non applicable' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== MODALE DE CONFIGURATION DES PAIEMENTS (ADMIN) ==================== -->
        <Dialog v-model:visible="configModalVisible" header="Configuration des moyens de paiement" :style="{ width: '600px' }">
            <div class="space-y-4">
                <div v-for="(method, index) in paymentMethods" :key="method.id" class="flex items-center gap-3 p-3 border rounded-lg">
                    <i :class="method.icon" class="text-xl w-8"></i>
                    <div class="flex-1">
                        <div class="font-bold">{{ method.label }}</div>
                        <div class="text-xs text-slate-500">{{ method.description }}</div>
                    </div>
                    <ToggleButton v-model="method.active" onLabel="Actif" offLabel="Inactif" class="w-20" />
                    <Button icon="pi pi-cog" class="p-button-rounded p-button-text" @click="openMethodConfig(method)" v-if="method.needConfig" />
                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text text-red-500" @click="removeMethod(index)" v-if="!method.isDefault" />
                </div>
                <Button label="Ajouter un moyen de paiement" icon="pi pi-plus" class="w-full" @click="showAddMethodDialog = true" />
            </div>
            <Dialog v-model:visible="showAddMethodDialog" header="Nouveau moyen de paiement" :modal="true" :style="{ width: '450px' }">
                <div class="space-y-3">
                    <InputText v-model="newMethod.label" placeholder="Nom (ex: Carte bancaire)" />
                    <InputText v-model="newMethod.icon" placeholder="Classe d'icône PrimeVue (ex: pi-credit-card)" />
                    <Select v-model="newMethod.type" :options="availableTypes" optionLabel="label" optionValue="value" placeholder="Type de passerelle" />
                    <div v-if="newMethod.type === 'paypal'" class="space-y-2">
                        <InputText v-model="newMethod.clientId" placeholder="Client ID PayPal" />
                        <InputText v-model="newMethod.secret" placeholder="Secret PayPal" />
                        <Select v-model="newMethod.mode" :options="[{label:'Sandbox',value:'sandbox'},{label:'Live',value:'live'}]" />
                    </div>
                    <div v-if="newMethod.type === 'stripe'" class="space-y-2">
                        <InputText v-model="newMethod.publishableKey" placeholder="Clé publique Stripe" />
                        <InputText v-model="newMethod.secretKey" placeholder="Clé secrète Stripe" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Annuler" @click="showAddMethodDialog=false" />
                    <Button label="Ajouter" @click="addMethod" />
                </template>
            </Dialog>
        </Dialog>
    </PublicLayout>
</template>

<style scoped>
.custom-landing-tabview :deep(.p-tabview-nav) { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.custom-landing-tabview :deep(.p-tabview-nav li .p-tabview-nav-link) { background: transparent; border: none; font-weight: 700; color: #475569; padding: 1rem; border-bottom: 2px solid transparent; }
.custom-landing-tabview :deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) { color: #10b981; border-bottom-color: #10b981; }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
.scroll-animate { opacity: 0; transform: translateY(30px); transition: all 0.7s ease-out; }
.animate-fade-up { opacity: 1 !important; transform: translateY(0) !important; }
:deep(.p-progressbar .p-progressbar-value) { background: #10b981; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
