<template>
    <AppLayout>
        <Head title="Paramètres - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-900/50 to-violet-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-purple-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-violet-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <Badge value="Module Paramètres" severity="contrast" class="bg-purple-500/20 text-purple-300 border border-purple-500/30 font-mono text-[10px] tracking-widest" />
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Paramètres du site</h1>
                    <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez toutes les informations de configuration de votre site.</p>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <form @submit.prevent="saveSettings">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                        <!-- Onglets -->
                        <div class="border-b border-slate-200 bg-white">
                            <div class="flex overflow-x-auto">
                                <button v-for="tab in tabs" :key="tab.value"
                                    type="button"
                                    @click="activeTab = tab.value"
                                    class="px-6 py-4 font-medium text-sm transition-colors border-b-2 flex-shrink-0"
                                    :class="activeTab === tab.value
                                        ? 'border-purple-600 text-purple-700 bg-purple-50/50'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'">
                                    <i :class="tab.icon" class="mr-2"></i> {{ tab.label }}
                                </button>
                            </div>
                        </div>

                        <div class="p-4 lg:p-8">
                            <!-- Général -->
                            <div v-show="activeTab === 'general'" class="space-y-6">
                                <h2 class="text-lg font-bold text-slate-800 border-b pb-2">Informations générales</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Nom du site</label>
                                        <InputText v-model="form.site_name" :invalid="!!form.errors.site_name" class="w-full rounded-xl" placeholder="Mon Application" />
                                        <small v-if="form.errors.site_name" class="text-red-500">{{ form.errors.site_name }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Slogan</label>
                                        <InputText v-model="form.tagline" :invalid="!!form.errors.tagline" class="w-full rounded-xl" placeholder="Le slogan du site" />
                                        <small v-if="form.errors.tagline" class="text-red-500">{{ form.errors.tagline }}</small>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-bold text-slate-700">Description</label>
                                    <Textarea v-model="form.description" :invalid="!!form.errors.description" rows="3" class="w-full rounded-xl" placeholder="Courte description" />
                                    <small v-if="form.errors.description" class="text-red-500">{{ form.errors.description }}</small>
                                </div>

                                <!-- Aperçu de la campagne Hero -->
                                <div v-if="form.hero_campaign_active" class="mt-8 p-6 bg-slate-900 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden relative">
                                    <div class="absolute top-0 right-0 p-4">
                                        <Badge value="Aperçu Live" severity="success" />
                                    </div>
                                    <div class="max-w-md">
                                        <div class="flex items-center justify-between mb-6">
                                            <span class="text-sm font-bold text-emerald-400">{{ form.hero_campaign_badge || 'Urgence' }}</span>
                                            <span class="px-3 py-1.5 rounded bg-red-500/20 text-red-400 border border-red-500/30 text-xs uppercase font-bold animate-pulse">Vital</span>
                                        </div>
                                        <h3 class="text-2xl font-black text-white mb-2">{{ form.hero_campaign_title || 'Titre de la campagne' }}</h3>
                                        <p class="text-slate-400 text-sm mb-6 line-clamp-2">{{ form.hero_campaign_description || 'Description de la campagne...' }}</p>

                                        <div class="space-y-3 mb-8">
                                            <div class="flex justify-between text-xs font-bold text-white">
                                                <span>Financement : {{ (form.hero_campaign_current || 0).toLocaleString() }} / {{ (form.hero_campaign_target || 0).toLocaleString() }} USD</span>
                                                <span class="text-emerald-400">{{ Math.round((form.hero_campaign_current / form.hero_campaign_target) * 100) || 0 }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-800 rounded-full h-3">
                                                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500"
                                                     :style="{ width: (Math.min(100, (form.hero_campaign_current / form.hero_campaign_target) * 100) || 0) + '%' }"></div>
                                            </div>
                                        </div>
                                        <Button :label="form.hero_campaign_btn_text || 'Participer'" icon="pi pi-heart-fill" class="w-full bg-white hover:bg-emerald-50 border-none py-3 rounded-xl font-black text-slate-900 text-sm" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Logo</label>
                                        <FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" @select="onLogoUpload" class="w-full" chooseLabel="Choisir un logo" />
                                        <img v-if="logoPreview" :src="logoPreview" class="h-16 mt-2 object-contain bg-slate-50 p-1 rounded border border-slate-100" />
                                        <Button v-if="logoPreview" label="Supprimer le logo" icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm mt-1 w-fit" @click="removeLogo" />
                                        <small v-if="form.errors.logo" class="text-red-500">{{ form.errors.logo }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Favicon</label>
                                        <FileUpload mode="basic" accept="image/*" :maxFileSize="1000000" @select="onFaviconUpload" class="w-full" chooseLabel="Choisir un favicon" />
                                        <img v-if="faviconPreview" :src="faviconPreview" class="h-10 mt-2 object-contain bg-slate-50 p-1 rounded border border-slate-100" />
                                        <Button v-if="faviconPreview" label="Supprimer le favicon" icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm mt-1 w-fit" @click="removeFavicon" />
                                        <small v-if="form.errors.favicon" class="text-red-500">{{ form.errors.favicon }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact -->
                            <div v-show="activeTab === 'contact'" class="space-y-6">
                                <h2 class="text-lg font-bold text-slate-800 border-b pb-2">Coordonnées de contact</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Email principal</label>
                                        <InputText v-model="form.email" :invalid="!!form.errors.email" type="email" class="w-full rounded-xl" placeholder="contact@monsite.com" />
                                        <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Email secondaire</label>
                                        <InputText v-model="form.secondary_email" :invalid="!!form.errors.secondary_email" type="email" class="w-full rounded-xl" placeholder="info@monsite.com" />
                                        <small v-if="form.errors.secondary_email" class="text-red-500">{{ form.errors.secondary_email }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Téléphone principal</label>
                                        <InputText v-model="form.phone" :invalid="!!form.errors.phone" class="w-full rounded-xl" placeholder="+33 1 23 45 67 89" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Téléphone secondaire</label>
                                        <InputText v-model="form.secondary_phone" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2 md:col-span-2">
                                        <label class="text-sm font-bold text-slate-700">Adresse</label>
                                        <InputText v-model="form.address" class="w-full rounded-xl" placeholder="123 rue..." />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Code postal</label>
                                        <InputText v-model="form.postal_code" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Ville</label>
                                        <InputText v-model="form.city" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2 md:col-span-2">
                                        <label class="text-sm font-bold text-slate-700">Pays</label>
                                        <InputText v-model="form.country" class="w-full rounded-xl" />
                                    </div>
                                </div>
                            </div>

                            <!-- Réseaux sociaux -->
                            <div v-show="activeTab === 'social'" class="space-y-6">
                                <h2 class="text-lg font-bold text-slate-800 border-b pb-2">Réseaux sociaux</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div v-for="social in socialLinks" :key="social.key" class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700 capitalize">{{ social.label }}</label>
                                        <InputText v-model="form[social.key]" :invalid="!!form.errors[social.key]" class="w-full rounded-xl" :placeholder="social.placeholder" />
                                        <small v-if="form.errors[social.key]" class="text-red-500">{{ form.errors[social.key] }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Banque & Légal -->
                            <div v-show="activeTab === 'bank'" class="space-y-6">
                                <h2 class="text-lg font-bold text-slate-800 border-b pb-2">Informations bancaires et légales</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Nom de la banque</label>
                                        <InputText v-model="form.bank_name" class="w-full rounded-xl" placeholder="Banque XYZ" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">IBAN</label>
                                        <InputText v-model="form.bank_iban" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">BIC / SWIFT</label>
                                        <InputText v-model="form.bank_bic" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Numéro de compte</label>
                                        <InputText v-model="form.bank_account" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">RCCM</label>
                                        <InputText v-model="form.rccm" class="w-full rounded-xl" placeholder="N° RCCM" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Numéro d'identification fiscale (NIF)</label>
                                        <InputText v-model="form.tax_id" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Capital social</label>
                                        <InputText v-model="form.capital" class="w-full rounded-xl" />
                                    </div>
                                </div>
                            </div>

                            <!-- Autres -->
                            <div v-show="activeTab === 'other'" class="space-y-6">
                                <h2 class="text-lg font-bold text-slate-800 border-b pb-2">Autres paramètres</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Copyright / Footer</label>
                                        <InputText v-model="form.copyright_text" class="w-full rounded-xl" placeholder="© 2024 MonSite" />
                                    </div>
                                </div>

                                <h2 class="text-lg font-bold text-slate-800 border-b pb-2 mt-8">Campagne d'urgence (Hero)</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Badge de la campagne</label>
                                        <InputText v-model="form.hero_campaign_badge" class="w-full rounded-xl" placeholder="Urgence Forage" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Titre de la campagne</label>
                                        <InputText v-model="form.hero_campaign_title" class="w-full rounded-xl" placeholder="Urgence : Eau propre..." />
                                    </div>
                                    <div class="flex flex-col gap-2 md:col-span-2">
                                        <label class="text-sm font-bold text-slate-700">Description de la campagne</label>
                                        <Textarea v-model="form.hero_campaign_description" rows="2" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Montant actuel (USD)</label>
                                        <InputNumber v-model="form.hero_campaign_current" class="w-full" mode="currency" currency="USD" locale="en-US" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Objectif total (USD)</label>
                                        <InputNumber v-model="form.hero_campaign_target" class="w-full" mode="currency" currency="USD" locale="en-US" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Texte du bouton</label>
                                        <InputText v-model="form.hero_campaign_btn_text" class="w-full rounded-xl" placeholder="Participer à ce projet" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Statut</label>
                                        <div class="flex items-center gap-2 mt-2">
                                            <InputSwitch v-model="form.hero_campaign_active" />
                                            <span class="text-sm text-slate-600">{{ form.hero_campaign_active ? 'Affichée' : 'Masquée' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Lien Politique de confidentialité</label>
                                        <InputText v-model="form.privacy_policy_url" :invalid="!!form.errors.privacy_policy_url" class="w-full rounded-xl" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-bold text-slate-700">Lien Conditions générales</label>
                                        <InputText v-model="form.terms_url" :invalid="!!form.errors.terms_url" class="w-full rounded-xl" />
                                    </div>
                                </div>
                            </div>

                            <!-- Bouton sauvegarde -->
                            <div class="mt-10 pt-6 border-t border-slate-200 flex justify-end">
                                <Button type="submit" label="Enregistrer les paramètres" icon="pi pi-save" :loading="form.processing" class="bg-purple-600 hover:bg-purple-700 border-none shadow-lg shadow-purple-600/30 text-white font-bold px-8" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

// PrimeVue components
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';

const toast = useToast();

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({})
    },
});

// Onglets
const activeTab = ref('general');
const tabs = [
    { label: 'Général', value: 'general', icon: 'pi pi-cog' },
    { label: 'Contact', value: 'contact', icon: 'pi pi-phone' },
    { label: 'Réseaux sociaux', value: 'social', icon: 'pi pi-share-alt' },
    { label: 'Banque & Légal', value: 'bank', icon: 'pi pi-credit-card' },
    { label: 'Autres', value: 'other', icon: 'pi pi-sliders-h' },
];

// Formulaire géré nativement par Inertia
const form = useForm({
    // Général
    site_name: props.settings.site_name || '',
    tagline: props.settings.tagline || '',
    description: props.settings.description || '',
    logo: null,
    favicon: null,
    // Contact
    email: props.settings.email || '',
    secondary_email: props.settings.secondary_email || '',
    phone: props.settings.phone || '',
    secondary_phone: props.settings.secondary_phone || '',
    address: props.settings.address || '',
    city: props.settings.city || '',
    postal_code: props.settings.postal_code || '',
    country: props.settings.country || '',
    // Réseaux sociaux
    facebook: props.settings.facebook || '',
    twitter: props.settings.twitter || '',
    instagram: props.settings.instagram || '',
    linkedin: props.settings.linkedin || '',
    youtube: props.settings.youtube || '',
    // Banque & Légal
    bank_name: props.settings.bank_name || '',
    bank_iban: props.settings.bank_iban || '',
    bank_bic: props.settings.bank_bic || '',
    bank_account: props.settings.bank_account || '',
    rccm: props.settings.rccm || '',
    tax_id: props.settings.tax_id || '',
    capital: props.settings.capital || '',
    // Autres
    copyright_text: props.settings.copyright_text || '',
    privacy_policy_url: props.settings.privacy_policy_url || '',
    terms_url: props.settings.terms_url || '',
    // Campagne Hero
    hero_campaign_active: props.settings.hero_campaign_active === '1' || props.settings.hero_campaign_active === true,
    hero_campaign_badge: props.settings.hero_campaign_badge || '',
    hero_campaign_title: props.settings.hero_campaign_title || '',
    hero_campaign_description: props.settings.hero_campaign_description || '',
    hero_campaign_target: parseFloat(props.settings.hero_campaign_target) || 0,
    hero_campaign_current: parseFloat(props.settings.hero_campaign_current) || 0,
    hero_campaign_btn_text: props.settings.hero_campaign_btn_text || '',
    // Actions sur les fichiers
    delete_logo: false,
    delete_favicon: false,
});

// Prévisualisations
const logoPreview = ref(props.settings.logo_url || null);
const faviconPreview = ref(props.settings.favicon_url || null);

// Gestion des fichiers via URL.createObjectURL (plus performant)
const onLogoUpload = (event) => {
    const file = event.files[0];
    form.logo = file;
    form.delete_logo = false;
    logoPreview.value = URL.createObjectURL(file);
};

const removeLogo = () => {
    form.logo = null;
    form.delete_logo = true;
    logoPreview.value = null;
};

const onFaviconUpload = (event) => {
    const file = event.files[0];
    form.favicon = file;
    form.delete_favicon = false;
    faviconPreview.value = URL.createObjectURL(file);
};

const removeFavicon = () => {
    form.favicon = null;
    form.delete_favicon = true;
    faviconPreview.value = null;
};

// Soumission avec prise en compte des fichiers
const saveSettings = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
        forceFormData: true, // Requis par Inertia pour l'envoi de fichiers
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Les paramètres ont été mis à jour.', life: 3000 });

            // Réinitialisation de l'état des fichiers (pour éviter de les renvoyer à la prochaine sauvegarde)
            form.logo = null;
            form.favicon = null;
            form.delete_logo = false;
            form.delete_favicon = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs du formulaire.', life: 4000 });
        }
    });
};

// Configuration de la boucle des réseaux sociaux
const socialLinks = [
    { key: 'facebook', label: 'Facebook', placeholder: 'https://facebook.com/...' },
    { key: 'twitter', label: 'X (Twitter)', placeholder: 'https://twitter.com/...' },
    { key: 'instagram', label: 'Instagram', placeholder: 'https://instagram.com/...' },
    { key: 'linkedin', label: 'LinkedIn', placeholder: 'https://linkedin.com/...' },
    { key: 'youtube', label: 'YouTube', placeholder: 'https://youtube.com/...' },
];
</script>
