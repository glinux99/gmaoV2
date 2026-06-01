<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// --- IMPORTS PRIMEVUE ---
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Card from 'primevue/card';

// --- PROPS OPTIONNELLES ---
const props = defineProps({
    user: Object, // L'utilisateur connecté passé par Inertia (ex: props.auth.user)
});

const progress = ref(0);

// Animation fluide de la barre de progression (Simulation de traitement)
onMounted(() => {
    let interval = setInterval(() => {
        let val = progress.value;
        val += Math.floor(Math.random() * 10) + 5;
        if (val >= 65) {
            progress.value = 65; // On bloque à 65% (En attente de l'admin)
            clearInterval(interval);
        } else {
            progress.value = val;
        }
    }, 200);
});

// Action de déconnexion
const logout = () => {
    router.post(route('logout'));
};

// Recharger la page pour vérifier si l'admin a donné les droits
const checkStatus = () => {
    router.reload({ only: ['auth'] }); // Adapte selon la logique de ton app
};
</script>

<template>
    <Head title="Bienvenue - Validation en cours" />

    <!-- ARRIÈRE-PLAN AVEC EFFETS GLASSMORPHISM & BLUR -->
    <div class="relative min-h-screen bg-slate-50 flex items-center justify-center p-4 overflow-hidden font-sans">

        <!-- Blobs décoratifs en fond -->
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-emerald-400 rounded-full mix-blend-multiply filter blur-[100px] opacity-20 animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-96 h-96 bg-teal-400 rounded-full mix-blend-multiply filter blur-[100px] opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-sky-400 rounded-full mix-blend-multiply filter blur-[100px] opacity-20 animate-blob animation-delay-4000"></div>

        <!-- CONTENEUR PRINCIPAL -->
        <div class="relative z-10 w-full max-w-4xl quantum-entrance">

            <div class="bg-white/70 backdrop-blur-2xl border border-white rounded-[2.5rem] shadow-2xl overflow-hidden">

                <div class="grid grid-cols-1 lg:grid-cols-12">

                    <!-- COLONNE GAUCHE : MESSAGE PRINCIPAL -->
                    <div class="lg:col-span-7 p-8 lg:p-12 flex flex-col justify-center">

                        <!-- Header / Logo -->
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                                <i class="pi pi-users text-white text-2xl"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest">Plateforme de Gestion</h2>
                                <h1 class="text-2xl font-black text-slate-800 tracking-tight">APROJED ASBL</h1>
                            </div>
                        </div>

                        <!-- Titre d'accueil -->
                        <h3 class="text-4xl font-black text-slate-900 leading-tight mb-4">
                            Bienvenue, <span class="text-emerald-600">{{ props.user?.name || 'Visiteur' }}</span> ! 🎉
                        </h3>

                        <p class="text-slate-500 font-medium leading-relaxed mb-6">
                            Nous sommes ravis de vous compter parmi les utilisateurs de notre plateforme. Votre compte a été créé avec succès et est actuellement sous le profil <strong>Visiteur</strong>.
                        </p>

                        <!-- Bloc d'information (Le cœur de la demande) -->
                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5 mb-8 flex gap-4">
                            <div class="mt-1">
                                <i class="pi pi-shield text-emerald-500 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-emerald-900 uppercase tracking-wider mb-1">Élévation des privilèges</h4>
                                <p class="text-xs text-emerald-700 font-medium leading-relaxed">
                                    Nos administrateurs ont été notifiés de votre inscription. <strong>Vos permissions seront élevées dans les plus brefs délais</strong> afin de vous donner accès aux différents menus et modules sécurisés de l'application. Merci de votre patience !
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center gap-4">
                            <Button label="Vérifier mon statut" icon="pi pi-refresh" class="p-button-success !bg-emerald-600 !border-none !rounded-xl font-bold shadow-lg shadow-emerald-200 px-6 py-3" @click="checkStatus" />
                            <Button label="Se déconnecter" icon="pi pi-sign-out" class="p-button-text p-button-secondary font-bold" @click="logout" />
                        </div>
                    </div>

                    <!-- COLONNE DROITE : STATUT & TEASING -->
                    <div class="lg:col-span-5 bg-slate-900 p-8 lg:p-12 text-white flex flex-col justify-center relative overflow-hidden">

                        <!-- Effet de lueur sombre -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500 rounded-full mix-blend-screen filter blur-[80px] opacity-20"></div>
>
  <template #loadingicon="slotProps"></template>

                        <div class="relative z-10">
                            <!-- Barre de progression de l'onboarding -->
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">État de votre profil</h4>
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-2xl font-black text-white">Validation requise</span>
                                <span class="text-sm font-bold text-emerald-400">{{ progress }}%</span>
                            </div>
                            <ProgressBar :value="progress" :showValue="false" class="!h-2 !bg-slate-800 mb-10">
                                <template #default>
                                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full" :style="{width: progress + '%'}"></div>
                                </template>
                            </ProgressBar>

                            <Divider class="before:border-slate-700 mb-8" />

                            <!-- Ce qui l'attend (Teasing) -->
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Accès à venir</h4>

                            <div class="space-y-4">
                                <div class="flex items-center gap-4 opacity-50">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                                        <i class="pi pi-chart-bar text-slate-400"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Rapports & Impact Social</span>
                                        <span class="text-[10px] text-slate-500">Visualisation des indicateurs terrain</span>
                                    </div>
                                    <i class="pi pi-lock text-slate-600 ml-auto text-xs"></i>
                                </div>

                                <div class="flex items-center gap-4 opacity-50">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                                        <i class="pi pi-wrench text-slate-400"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Suivi des Projets</span>
                                        <span class="text-[10px] text-slate-500">Activités et jalons de développement</span>
                                    </div>
                                    <i class="pi pi-lock text-slate-600 ml-auto text-xs"></i>
                                </div>

                                <div class="flex items-center gap-4 opacity-50">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                                        <i class="pi pi-box text-slate-400"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Ressources & Équipes</span>
                                        <span class="text-[10px] text-slate-500">Gestion des inventaires et du personnel</span>
                                    </div>
                                    <i class="pi pi-lock text-slate-600 ml-auto text-xs"></i>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer minimaliste -->
            <div class="text-center mt-6">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    Besoin d'aide urgente ? <a href="mailto:contact@aprojed.org" class="text-emerald-500 hover:text-emerald-600 transition-colors">Contactez le support</a>
                </p>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* =========================================
   ANIMATIONS
========================================= */

/* Entrée fluide du composant principal */
.quantum-entrance {
    animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
    transform: translateY(40px);
}

@keyframes slideUpFade {
    0% {
        opacity: 0;
        transform: translateY(40px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animation des blobs en arrière-plan */
.animate-blob {
    animation: blob 7s infinite alternate;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

/* =========================================
   SURCHARGES PRIMEVUE
========================================= */
:deep(.p-progressbar) {
    overflow: hidden;
    border-radius: 9999px;
}
:deep(.p-divider.p-divider-horizontal:before) {
    border-top-style: solid;
}
</style>
