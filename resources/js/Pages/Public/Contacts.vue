<!--
  APROJED R.D. Congo - Page Contact (Ultra Premium)
  Version : 3.0 – Données Dynamiques (DB) & Formulaires Inertia
-->
<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import PublicLayout from '@/sakai/layout/PublicLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Badge from 'primevue/badge';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';

const toast = useToast();
const page = usePage();

// 1. Définition des Props venant du Contrôleur (Données de la DB)
const props = defineProps({
    testimonials: {
        type: Array,
        default: () => [] // Les partenaires/témoignages depuis la BD
    }
});

// 2. Récupération des settings (via Middleware)
const settings = computed(() => page.props.settings || {});

const siteName = computed(() => settings.value.site_name || 'APROJED');
const siteEmail = computed(() => settings.value.email || 'contact@aprojed.org');
const secondaryEmail = computed(() => settings.value.secondary_email || 'info@aprojed.org');
const sitePhone = computed(() => settings.value.phone || '+243 810 000 000');
const secondaryPhone = computed(() => settings.value.secondary_phone || '+243 980 000 000');
const addressGoma = computed(() => settings.value.address_goma || 'Avenue du Lac, Quartier Himbi, Goma, RDC');
const addressKinshasa = computed(() => settings.value.address_kinshasa || 'Commune de la Gombe, Kinshasa');

const facebookUrl = computed(() => settings.value.facebook || '#');
const twitterUrl = computed(() => settings.value.twitter || '#');
const linkedinUrl = computed(() => settings.value.linkedin || '#');
const instagramUrl = computed(() => settings.value.instagram || '#');
const youtubeUrl = computed(() => settings.value.youtube || '#');

// 3. Formulaire de Contact (Inertia useForm)
const contactForm = useForm({
    name: '',
    email: '',
    phone: '',
    subject: 'Général',
    message: '',
    isVolunteer: false
});

const handleContactSubmit = () => {
    // Remplacer 'contact.store' par le nom de votre route Laravel
    contactForm.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Message envoyé', detail: `Merci ${contactForm.name}, nous vous répondrons sous 48h.`, life: 5000 });
            contactForm.reset();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs du formulaire.', life: 5000 });
        }
    });
};

// 4. Formulaire Newsletter (Inertia useForm)
const newsletterForm = useForm({
    email: ''
});

const handleNewsletterSubmit = () => {
    // Remplacer 'newsletter.store' par le nom de votre route Laravel
    newsletterForm.post(route('newsletter.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Abonnement réussi', detail: 'Merci pour votre inscription à notre newsletter !', life: 4000 });
            newsletterForm.reset();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Cet email est invalide ou déjà inscrit.', life: 4000 });
        }
    });
};

// 5. Utilitaire pour obtenir les initiales si pas d'avatar
const getInitials = (name) => {
    return name ? name.charAt(0).toUpperCase() : '?';
};
</script>

<template>
    <PublicLayout>
        <Head title="Contact - APROJED R.D. Congo" />

        <!-- BANNIÈRE -->
        <div class="relative bg-slate-900 pt-32 pb-24 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=1920')"></div>
            <div class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-white to-transparent z-10"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 text-center">
                <Badge value="Nous Contacter" class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-black uppercase text-[10px] tracking-widest px-4 py-1.5 mb-4 shadow-sm" />
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-white mt-4 tracking-tight">Prenons contact</h1>
                <p class="text-slate-300 text-lg mt-6 max-w-2xl mx-auto leading-relaxed">Que vous souhaitiez devenir donateur, partenaire, bénévole ou simplement poser une question, nos équipes sont à votre écoute.</p>
            </div>
        </div>

        <!-- SECTION PRINCIPALE : FORMULAIRE + COORDONNÉES -->
        <div class="bg-white py-16 -mt-12 relative z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

                    <!-- Colonne gauche : infos de contact -->
                    <div class="lg:col-span-5 space-y-8">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Nos bureaux</h2>
                            <p class="text-slate-500 mt-2">Nous sommes présents dans plusieurs villes pour être au plus proche des communautés.</p>
                        </div>

                        <!-- Bureau Goma -->
                        <div class="bg-slate-50 rounded-[2rem] p-8 border border-slate-100 hover:shadow-lg transition-shadow duration-300 group">
                            <div class="flex items-start gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl group-hover:bg-emerald-500 group-hover:text-white transition-colors shadow-sm shrink-0">
                                    <i class="pi pi-map-marker"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900">Bureau principal (Nord-Kivu)</h3>
                                    <p class="text-slate-500 text-sm mt-2 leading-relaxed">{{ addressGoma }}</p>
                                    <div class="mt-4 space-y-2">
                                        <a :href="`tel:${sitePhone}`" class="flex items-center text-slate-600 text-sm hover:text-emerald-600 transition-colors font-medium"><i class="pi pi-phone mr-3 text-emerald-500"></i> {{ sitePhone }}</a>
                                        <a :href="`mailto:${siteEmail}`" class="flex items-center text-slate-600 text-sm hover:text-emerald-600 transition-colors font-medium"><i class="pi pi-envelope mr-3 text-emerald-500"></i> {{ siteEmail }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bureau Kinshasa -->
                        <div class="bg-slate-50 rounded-[2rem] p-8 border border-slate-100 hover:shadow-lg transition-shadow duration-300 group">
                            <div class="flex items-start gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl group-hover:bg-indigo-500 group-hover:text-white transition-colors shadow-sm shrink-0">
                                    <i class="pi pi-building"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900">Bureau de liaison (Kinshasa)</h3>
                                    <p class="text-slate-500 text-sm mt-2 leading-relaxed">{{ addressKinshasa }}</p>
                                    <div class="mt-4 space-y-2">
                                        <a :href="`tel:${secondaryPhone}`" class="flex items-center text-slate-600 text-sm hover:text-indigo-600 transition-colors font-medium"><i class="pi pi-phone mr-3 text-indigo-500"></i> {{ secondaryPhone }}</a>
                                        <a :href="`mailto:${secondaryEmail}`" class="flex items-center text-slate-600 text-sm hover:text-indigo-600 transition-colors font-medium"><i class="pi pi-envelope mr-3 text-indigo-500"></i> {{ secondaryEmail }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Réseaux sociaux -->
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Suivez-nous sur les réseaux</h3>
                            <div class="flex gap-3">
                                <a :href="facebookUrl" target="_blank" class="w-12 h-12 rounded-full border border-slate-200 bg-white shadow-sm flex items-center justify-center text-slate-600 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 hover:-translate-y-1 transition-all"><i class="pi pi-facebook text-lg"></i></a>
                                <a :href="twitterUrl" target="_blank" class="w-12 h-12 rounded-full border border-slate-200 bg-white shadow-sm flex items-center justify-center text-slate-600 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 hover:-translate-y-1 transition-all"><i class="pi pi-twitter text-lg"></i></a>
                                <a :href="linkedinUrl" target="_blank" class="w-12 h-12 rounded-full border border-slate-200 bg-white shadow-sm flex items-center justify-center text-slate-600 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 hover:-translate-y-1 transition-all"><i class="pi pi-linkedin text-lg"></i></a>
                                <a :href="instagramUrl" target="_blank" class="w-12 h-12 rounded-full border border-slate-200 bg-white shadow-sm flex items-center justify-center text-slate-600 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 hover:-translate-y-1 transition-all"><i class="pi pi-instagram text-lg"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne droite : Formulaire Inertia -->
                    <div class="lg:col-span-7">
                        <div class="bg-white rounded-[2.5rem] p-8 sm:p-12 border border-slate-100 shadow-2xl shadow-slate-200/50">
                            <h2 class="text-3xl font-black text-slate-900 mb-8 tracking-tight">Envoyez-nous un message</h2>

                            <form @submit.prevent="handleContactSubmit" class="space-y-6">
                                <div class="grid sm:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Nom complet <span class="text-red-500">*</span></label>
                                        <InputText v-model="contactForm.name" required class="w-full rounded-2xl bg-slate-50 border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner p-3.5" :class="{'p-invalid': contactForm.errors.name}" placeholder="Jean Dupont" />
                                        <small v-if="contactForm.errors.name" class="text-red-500 font-bold">{{ contactForm.errors.name }}</small>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Email <span class="text-red-500">*</span></label>
                                        <InputText type="email" v-model="contactForm.email" required class="w-full rounded-2xl bg-slate-50 border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner p-3.5" :class="{'p-invalid': contactForm.errors.email}" placeholder="votre@email.com" />
                                        <small v-if="contactForm.errors.email" class="text-red-500 font-bold">{{ contactForm.errors.email }}</small>
                                    </div>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Téléphone</label>
                                        <InputText v-model="contactForm.phone" class="w-full rounded-2xl bg-slate-50 border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner p-3.5" placeholder="+243 ..." />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-wide text-slate-500">Objet de la demande</label>
                                        <Dropdown v-model="contactForm.subject" :options="['Général', 'Bénévolat', 'Soutien financier', 'Partenariat', 'Médias/Presse']" class="w-full rounded-2xl bg-slate-50 border-slate-200 shadow-inner" />
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Votre Message <span class="text-red-500">*</span></label>
                                    <Textarea v-model="contactForm.message" rows="5" required class="w-full rounded-2xl bg-slate-50 border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 shadow-inner p-4 resize-none" :class="{'p-invalid': contactForm.errors.message}" placeholder="Comment pouvons-nous vous aider ?" />
                                    <small v-if="contactForm.errors.message" class="text-red-500 font-bold">{{ contactForm.errors.message }}</small>
                                </div>

                                <div class="flex items-center justify-between p-5 bg-emerald-50/50 rounded-2xl border border-emerald-100 cursor-pointer transition hover:border-emerald-300 group" @click="contactForm.isVolunteer = !contactForm.isVolunteer">
                                    <div>
                                        <p class="font-black text-sm text-emerald-800 flex items-center gap-2">
                                            <i class="pi pi-users text-emerald-500 bg-white w-8 h-8 rounded-full flex items-center justify-center shadow-sm"></i>
                                            Je postule comme bénévole
                                        </p>
                                        <p class="text-xs text-emerald-600/70 mt-1 pl-10 font-medium">Je souhaite mettre mes compétences au service des projets terrain.</p>
                                    </div>
                                    <input type="checkbox" v-model="contactForm.isVolunteer" class="w-6 h-6 accent-emerald-600 rounded cursor-pointer" @click.stop />
                                </div>

                                <Button
                                    type="submit"
                                    :loading="contactForm.processing"
                                    label="Envoyer le message"
                                    icon="pi pi-send"
                                    class="w-full bg-slate-900 hover:bg-emerald-600 text-white font-black py-4 rounded-2xl shadow-xl shadow-slate-900/20 hover:shadow-emerald-600/30 transition-all duration-300 hover:-translate-y-1"
                                />
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- CARTE INTERACTIVE GOMA -->
        <div class="bg-slate-50 py-20 relative border-t border-slate-200/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center space-y-4 mb-12 max-w-2xl mx-auto">
                    <Badge value="Localisation" class="bg-indigo-100 text-indigo-700 border border-indigo-200 font-black uppercase text-[10px] tracking-widest px-3 py-1.5" />
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Retrouvez-nous au Siège</h2>
                    <p class="text-slate-500 text-lg">Notre bureau central est situé au cœur de la ville, ouvert du lundi au vendredi.</p>
                </div>

                <div class="rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-white h-[400px] lg:h-[500px] relative group">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d796.5!2d29.2345803!3d-1.6747816!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19dd05092a832ca1%3A0x7a11cf8551ea36ad!2sBureau%20Central%20-%20APROJED!5e0!3m2!1sfr!2sfr!4v1747652823902!5m2!1sfr!2sfr"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Carte du bureau central APROJED à Goma"
                        class="grayscale hover:grayscale-0 transition-all duration-700"
                    ></iframe>
                    <!-- Overlay discret qui disparait au survol -->
                    <div class="absolute inset-0 bg-emerald-900/10 pointer-events-none group-hover:opacity-0 transition-opacity duration-700"></div>
                </div>
            </div>
        </div>

        <!-- ==================== TÉMOIGNAGES (DONNÉES DE LA BDD) ==================== -->
        <div v-if="testimonials && testimonials.length > 0" class="bg-white py-24 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16 max-w-2xl mx-auto">
                    <Badge value="Témoignages & Partenaires" class="bg-amber-100 text-amber-700 border border-amber-200 font-black uppercase text-[10px] tracking-widest px-3 py-1.5" />
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mt-5 tracking-tight">Ils nous font confiance</h2>
                    <p class="text-slate-500 text-lg mt-4">Découvrez les retours de ceux qui accompagnent nos projets sur le terrain.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Boucle sur les données de la base de données (limitée aux 3 premiers pour le design) -->
                    <div
                        v-for="testimonial in testimonials.slice(0,3)"
                        :key="testimonial.id"
                        class="bg-slate-50 rounded-[2rem] p-8 border border-slate-100 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 relative group"
                    >
                        <i class="pi pi-quote-left absolute top-8 right-8 text-5xl text-slate-200 group-hover:text-emerald-100 transition-colors"></i>

                        <div class="flex items-center gap-4 mb-6 relative z-10">
                            <!-- Avatar depuis la BDD ou fallback -->
                            <div class="w-16 h-16 rounded-full overflow-hidden bg-white shadow-sm border-2 border-emerald-50 flex items-center justify-center shrink-0">
                                <img v-if="testimonial.avatar_url || testimonial.avatar" :src="testimonial.avatar_url || `/storage/${testimonial.avatar}`" :alt="testimonial.name || testimonial.author" class="w-full h-full object-cover" />
                                <span v-else class="text-xl font-black text-emerald-600">{{ getInitials(testimonial.name || testimonial.author) }}</span>
                            </div>

                            <div>
                                <h4 class="font-black text-slate-800 text-lg leading-tight">{{ testimonial.name || testimonial.author }}</h4>
                                <span class="text-sm text-emerald-600 font-bold flex items-center gap-1 mt-0.5">
                                    {{ testimonial.position || testimonial.role || 'Partenaire' }}
                                    <span v-if="testimonial.company" class="text-slate-400 font-normal">@ {{ testimonial.company }}</span>
                                </span>
                            </div>
                        </div>

                        <p class="text-slate-600 leading-relaxed italic relative z-10">
                            "{{ testimonial.content }}"
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </PublicLayout>
</template>

<style scoped>
/* (Styles inchangés ou non nécessaires car gérés par Tailwind) */
</style>
