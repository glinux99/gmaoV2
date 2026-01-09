<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useLayout } from '@/sakai/layout/composables/layout';
import { useI18n } from 'vue-i18n';

// --- COMPOSANTS PRIMEVUE ---
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import InlineMessage from 'primevue/inlinemessage';
import Skeleton from 'primevue/skeleton';
import Message from 'primevue/message';
import Card from 'primevue/card';

import Histories from './Histories.vue';
import jsPDF from 'jspdf';


// --- PROPS & ÉTAT ---
const props = defineProps({
    user: Object,
    regions: Array,
    logins: Array,
});

const { t } = useI18n();
const toast = useToast();
const { isDarkTheme, toggleDarkMode } = useLayout();
const photoPreview = ref(null);
const activeTab = ref(0);
const isPageLoading = ref(true);

// Simulation de chargement pour l'effet Skeleton
onMounted(() => {
    setTimeout(() => { isPageLoading.value = false; }, 1000);
});

// --- FORMULAIRE GÉNÉRAL (PROFIL & DIMENSIONS) ---
const profileForm = useForm({
    _method: 'PUT',
    name: props.user.name || '',
    email: props.user.email || '',
    fonction: props.user.fonction || '',
    numero: props.user.numero || '',
    region_id: props.user.region_id || null,
    pointure: props.user.pointure || null,
    size: props.user.size || null,
    profile_photo: null,
});

// --- FORMULAIRE SÉCURITÉ ---
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteAccountForm = useForm({
    password: '',
});


// --- LOGIQUE PHOTO ---
const onPhotoSelect = (event) => {
    const file = event.files[0];
    profileForm.profile_photo = file;
    const reader = new FileReader();
    reader.onload = (e) => photoPreview.value = e.target.result;
    reader.readAsDataURL(file);
    toast.add({ severity: 'info', summary: 'Aperçu mis à jour', detail: 'N\'oubliez pas d\'enregistrer les modifications.', life: 3000 });
};

// --- LOGIQUE DE CALCUL ---
const profileCompletion = computed(() => {
    const fields = ['name', 'email', 'fonction', 'numero', 'region_id', 'pointure', 'size'];
    const filled = fields.filter(f => props.user[f]).length;
    return Math.round((filled / fields.length) * 100);
});

// --- ACTIONS DE SAUVEGARDE ---
const submitProfile = () => {
    profileForm.post(route('settings.updateProfile'), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Informations mises à jour avec succès.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez corriger les erreurs dans le formulaire.', life: 5000 })
    });
};

const submitPassword = () => {
    passwordForm.put(route('settings.updatePassword'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.add({ severity: 'success', summary: 'Sécurité', detail: 'Votre mot de passe a été modifié.', life: 3000 });
        }
    });
};

const confirmDeleteUser = () => {
    deleteAccountForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'warn', summary: 'Compte Supprimé', detail: 'Votre compte a été supprimé avec succès.', life: 5000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le mot de passe fourni est incorrect.', life: 5000 });
        },
    });
};
// --- LOGIQUE D'EXPORT ---
const exportAsJson = () => {
    const dataStr = JSON.stringify(props.user, null, 2);
    const blob = new Blob([dataStr], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `profil-${props.user.name.replace(/\s+/g, '_').toLowerCase()}.json`;
    link.click();
    URL.revokeObjectURL(url);
};

const exportAsPdf = () => {
    const doc = new jsPDF();
    doc.setFontSize(18);
    doc.text(`Profil de ${props.user.name}`, 14, 22);
    doc.setFontSize(11);
    doc.setTextColor(100);

    const profileData = [
        ["Nom Complet", props.user.name],
        ["Email", props.user.email],
        ["Fonction", props.user.fonction || 'N/A'],
        ["Téléphone", props.user.numero || 'N/A'],
        ["Région", Array.isArray(props.regions) ? props.regions.find(r => r.id === props.user.region_id)?.name || 'N/A' : 'N/A'],
    ];

    doc.autoTable({
        startY: 30,
        head: [['Champ', 'Valeur']],
        body: profileData,
        theme: 'striped',
        headStyles: { fillColor: [22, 160, 133] },
    });

    doc.save(`profil-${props.user.name.replace(/\s+/g, '_').toLowerCase()}.pdf`);
};

</script>

<template>
    <AppLayout :title="t('profile.title')">
        <Head :title="t('profile.headTitle')" />
        <Toast position="bottom-right" />

        <div v-if="isPageLoading" class="min-h-screen p-8 bg-slate-50">
            <div class="max-w-6xl mx-auto space-y-6">
                <Skeleton width="100%" height="250px" borderRadius="1.5rem" />
                <Skeleton width="60%" height="40px" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Skeleton height="300px" borderRadius="1.5rem" />
                    <Skeleton height="300px" borderRadius="1.5rem" />
                </div>
            </div>
        </div>

        <div v-else class="min-h-screen bg-[#f8fafc] pb-20">

           <div class="relative w-full h-80 bg-slate-900 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-700/50 via-slate-900 to-black z-0"></div>

    <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none select-none z-10">
        <h2 class="text-[7vw] font-black uppercase tracking-tighter text-transparent"
            style="-webkit-text-stroke: 2px white;">
            Virunga Energies
        </h2>
    </div>

    <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-500/10 rounded-full blur-[100px] z-10"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-[100px] z-10"></div>

    <div class="max-w-7xl mx-auto h-full relative px-6 flex flex-col justify-center items-center md:items-start z-20">
        <div class="flex flex-col md:flex-row items-center gap-8 mt-12">

            <div class="relative group">
                <div class="p-2 bg-white/10 backdrop-blur-md rounded-full shadow-2xl ring-1 ring-white/20">
                    <Avatar
                        :image="photoPreview || user.profile_photo_url"
                        :label="!user.profile_photo_url && !photoPreview ? user.name.charAt(0) : null"
                        size="xlarge"
                        shape="circle"
                        class="w-40 h-40 !border-4 !border-white/20 text-4xl font-black bg-primary-600 text-white"
                    />
                </div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                    <FileUpload mode="basic" auto customUpload @select="onPhotoSelect" chooseIcon="pi pi-camera"
                        class="p-button-rounded p-button-lg !bg-white !text-slate-900 shadow-xl" />
                </div>
            </div>

            <div class="text-center md:text-left text-white space-y-2">
                <h1 class="text-4xl md:text-5xl font-black tracking-tighter text-white  drop-shadow-lg">
                    {{ user.name }}
                </h1>
                <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                    <Tag severity="success" class="!bg-emerald-500/20 !text-emerald-400 border border-emerald-500/30 px-3 py-1">
                        <i class="pi pi-verified mr-2"></i> Compte Actif
                    </Tag>
                    <Tag severity="info" class="!bg-white/10 !text-white border border-white/20 px-3 py-1 uppercase text-[10px] font-bold tracking-widest">
                        {{ user.fonction || 'Membre' }}
                    </Tag>
                </div>
            </div>

        </div>
    </div>
</div>

            <div class="max-w-7xl mx-auto px-4 md:px-6 -mt-12 relative z-20">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/50 overflow-hidden">

                    <TabView v-model:activeIndex="activeTab" class="custom-tabs">

                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-4 py-2">
                                    <i class="pi pi-user text-lg"></i>
                                    <span class="font-bold uppercase tracking-widest text-xs">Profil & Mensurations</span>
                                </div>
                            </template>

                            <div class="p-6 md:p-12 animate-fadein">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">

                                    <div class="lg:col-span-8 space-y-10">
                                        <div class="space-y-6">
                                            <h3 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                                                <span class="w-2 h-8 bg-primary-500 rounded-full"></span>
                                                Informations de base
                                            </h3>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Nom Complet</label>
                                                    <InputText v-model="profileForm.name" class="w-full !rounded-2xl !p-4 !bg-slate-50 !border-slate-100 focus:!bg-white focus:!ring-4 focus:!ring-primary-500/10 transition-all" />
                                                </div>
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Email Professionnel</label>
                                                    <InputText v-model="profileForm.email" class="w-full !rounded-2xl !p-4 !bg-slate-50 !border-slate-100" />
                                                </div>
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Poste / Fonction</label>
                                                    <InputText v-model="profileForm.fonction" class="w-full !rounded-2xl !p-4 !bg-slate-50 !border-slate-100 "  />
                                                </div>
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Numéro Mobile</label>
                                                    <InputText v-model="profileForm.numero" class="w-full !rounded-2xl !p-4 !bg-slate-50 !border-slate-100" />
                                                </div>
                                            </div>
                                        </div>

                                        <Divider />

                                        <div class="space-y-6">
                                            <h3 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                                                <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                                                Mensurations & Logistique
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Pointure (EU)</label>
                                                    <InputNumber v-model="profileForm.pointure" showButtons buttonLayout="horizontal" :min="30" :max="50"
                                                        inputClass="!rounded-2xl !p-4 !text-center !bg-slate-50 !border-none font-bold"
                                                        incrementButtonClass="!bg-slate-200 !text-slate-700 !rounded-r-2xl"
                                                        decrementButtonClass="!bg-slate-200 !text-slate-700 !rounded-l-2xl" />
                                                </div>
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Taille (cm)</label>
                                                    <InputNumber v-model="profileForm.size" :min="100" :max="250" suffix=" cm"
                                                        inputClass="w-full !rounded-2xl !p-4 !bg-slate-50 !border-none font-bold" />
                                                </div>
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-xs font-black text-slate-500 uppercase ml-2">Région / Affectation</label>
                                                    <Dropdown v-model="profileForm.region_id" :options="regions" optionLabel="designation" optionValue="id"
                                                        placeholder="Sélectionner" class="w-full !rounded-2xl !bg-slate-50 !border-slate-100" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex justify-end pt-10">
                                            <Button @click="submitProfile" label="Enregistrer les modifications" icon="pi pi-check-circle"
                                                :loading="profileForm.processing"
                                                class="!bg-primary-600 !border-none !rounded-2xl !py-4 !px-8 shadow-2xl shadow-primary-200 hover:!scale-105 transition-all" />
                                        </div>
                                    </div>

                                    <div class="lg:col-span-4 space-y-6 ">
                                        <div class="bg-slate-50 p-8 rounded-3xl border border-slate-100">
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Aperçu du Profil</h4>
                                            <div class="space-y-4">
                                                <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm">
                                                    <span class="text-xs text-slate-500 font-bold">Complétion</span>
                                                    <span class="text-primary-600 font-black">{{ profileCompletion }}%</span>
                                                </div>
                                                <ProgressBar :value="profileCompletion" class="!h-2 !bg-slate-200"></ProgressBar>

                                                <div class="pt-6 space-y-3">
                                                    <div class="flex items-center gap-3 text-sm text-slate-600">
                                                        <i class="pi pi-calendar"></i>
                                                        <span>Inscrit le : <b>{{ new Date(user.created_at).toLocaleDateString() }}</b></span>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-sm text-slate-600">
                                                        <i class="pi pi-shield"></i>
                                                        <span>Auth : <b>{{ user.provider_name || 'Email local' }}</b></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <Message severity="info" :closable="false" class="!rounded-xl">
                                            Les dimensions permettent de calibrer vos équipements de dotation.
                                        </Message>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-4 py-2">
                                    <i class="pi pi-lock text-lg"></i>
                                    <span class="font-bold uppercase tracking-widest text-xs">Sécurité</span>
                                </div>
                            </template>

                            <div class="p-6 md:p-12 animate-fadein max-w-2xl mx-auto">
                                <div class="text-center mb-12">
                                    <div class="w-20 h-20 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i class="pi pi-shield text-3xl"></i>
                                    </div>
                                    <h3 class="text-3xl font-black text-slate-800">Gestion du mot de passe</h3>
                                    <p class="text-slate-500">Mettez à jour vos identifiants pour sécuriser l'accès à votre console.</p>
                                </div>

                                <form @submit.prevent="submitPassword" class="space-y-6">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-xs font-black text-slate-500 uppercase ml-2">Mot de passe actuel</label>
                                        <InputText type="password" v-model="passwordForm.current_password"
                                            class="w-full !rounded-2xl !p-5 !bg-slate-50 !border-none"
                                            :class="{'!ring-2 !ring-red-500': passwordForm.errors.current_password}"/>
                                        <small v-if="passwordForm.errors.current_password" class="text-red-500 ml-2 font-bold">{{ passwordForm.errors.current_password }}</small>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="flex flex-col gap-2">
                                            <label class="text-xs font-black text-slate-500 uppercase ml-2">Nouveau mot de passe</label>
                                            <InputText type="password" v-model="passwordForm.password" class="w-full !rounded-2xl !p-5 !bg-slate-50 !border-none" />
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label class="text-xs font-black text-slate-500 uppercase ml-2">Confirmation</label>
                                            <InputText type="password" v-model="passwordForm.password_confirmation" class="w-full !rounded-2xl !p-5 !bg-slate-50 !border-none" />
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-4 pt-6">
                                        <Button type="submit" label="Réinitialiser mon mot de passe" icon="pi pi-refresh"
                                            :loading="passwordForm.processing"
                                            class="w-full !bg-slate-900 !border-none !rounded-xl !py-5 font-black text-lg" />
                                        <p class="text-center text-xs text-slate-400 italic">Dernière modification : il y a 3 mois</p>
                                    </div>
                                </form>
                            </div>
                        </TabPanel>

                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-4 py-2">
                                    <i class="pi pi-cog text-lg"></i>
                                    <span class="font-bold uppercase tracking-widest text-xs">Préférences & Données</span>
                                </div>
                            </template>

                            <div class="p-6 md:p-12 animate-fadein">
                                <div class="max-w-4xl mx-auto space-y-12">

                                    <div class="space-y-6">
                                        <h3 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                                            <span class="w-2 h-8 bg-primary-500 rounded-full"></span>
                                            Paramètres d'affichage
                                        </h3>
                                        <div class="space-y-6">
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between p-6 bg-slate-50 rounded-xl border border-slate-100">
                                                    <div>
                                                        <p class="font-bold text-slate-700">Mode Sombre</p>
                                                        <p class="text-xs text-slate-500">Activez l'interface à faible luminosité pour le confort visuel.</p>
                                                    </div>
                                                    <InputSwitch :modelValue="isDarkTheme" @change="toggleDarkMode" />
                                                </div>
                                                <div class="flex items-center justify-between p-6 bg-slate-50 rounded-xl border border-slate-100">
                                                    <div>
                                                        <p class="font-bold text-slate-700">Mode Haute Densité</p>
                                                        <p class="text-xs text-slate-500">Affiche plus de données à l'écran.</p>
                                                    </div>
                                                    <InputSwitch :modelValue="false" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <Divider />

                                    <div class="p-10 bg-primary-950 rounded-2xl text-white flex flex-col md:flex-row items-center justify-between gap-8 shadow-2xl">
                                        <div class="space-y-2 text-center md:text-left">
                                            <h3 class="text-2xl font-black">Exporter mes données</h3>
                                            <p class="text-indigo-200 text-sm">Téléchargez l'intégralité de vos informations au format JSON ou PDF.</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <Button label="JSON" icon="pi pi-code" @click="exportAsJson" class="!bg-white/10 !text-white !rounded-xl !py-3 !px-6 font-black border-none hover:!bg-white/20" />
                                            <Button label="PDF" icon="pi pi-file-pdf" @click="exportAsPdf" class="!bg-white !text-primary-900 !rounded-xl !py-3 !px-6 font-black border-none hover:!bg-primary-50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-4 py-2">
                                    <i class="pi pi-history text-lg"></i>
                                    <span class="font-bold uppercase tracking-widest text-xs">Historique</span>
                                </div>
                            </template>

                            <div class="p-6 md:p-12 animate-fadein">
                                <Histories :logins="logins" />
                            </div>
                        </TabPanel>

                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-4 py-2 text-red-500">
                                    <i class="pi pi-exclamation-triangle text-lg"></i>
                                    <span class="font-bold uppercase tracking-widest text-xs">Zone de Danger</span>
                                </div>
                            </template>

                            <div class="p-6 md:p-12 animate-fadein">
                                <div class="max-w-2xl mx-auto p-8 bg-red-50 border border-red-200 rounded-2xl">
                                    <h3 class="text-2xl font-black text-red-800">Supprimer le compte</h3>
                                    <p class="text-red-600 mt-2 mb-6">
                                        Une fois votre compte supprimé, toutes ses ressources et données seront définitivement effacées.
                                        Avant de supprimer votre compte, veuillez télécharger les données ou informations que vous souhaitez conserver.
                                    </p>

                                    <div class="flex flex-col gap-4">
                                        <InputText
                                            type="password"
                                            v-model="deleteAccountForm.password"
                                            placeholder="Entrez votre mot de passe pour confirmer"
                                            class="w-full !rounded-xl !p-4 !bg-white"
                                            :class="{'!ring-2 !ring-red-500': deleteAccountForm.errors.password}"
                                        />
                                        <Button
                                            label="Supprimer définitivement mon compte"
                                            icon="pi pi-trash"
                                            @click="confirmDeleteUser"
                                            :loading="deleteAccountForm.processing"
                                            class="!bg-red-600 !border-none !rounded-xl !py-4 !px-6 font-bold justify-center" />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>

            <footer class="max-w-7xl mx-auto px-6 mt-12 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                <p>&copy; 2026 Enterprise Profile Module — Build 12.0.4.5</p>
            </footer>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   DESIGN V12 CUSTOMIZATION
   ========================================================== */

.custom-tabs :deep(.p-tabview-nav) {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 1rem 2rem 0 2rem;
}

.custom-tabs :deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    color: #64748b;
    font-weight: 800;
    padding: 1.5rem 2rem;
    transition: all 0.3s ease;
    border-radius: 1rem 1rem 0 0;
}

.custom-tabs :deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    background: white;
    color: #0f172a;
    box-shadow: 0 -10px 20px -5px rgba(0,0,0,0.05);
}

.custom-tabs :deep(.p-tabview-panels) {
    background: white;
    padding: 0;
}

.animate-fadein {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

:deep(.p-inputnumber-input) {
    width: 100%;
}

:deep(.p-fileupload-choose) {
    transition: transform 0.2s;
}

:deep(.p-fileupload-choose:hover) {
    transform: scale(1.1);
}

/* Scrollbar Customization */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
</style>
