<template>
    <AppLayout>
        <Head title="Politique de confidentialité - APROJED" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/50 to-teal-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-emerald-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-teal-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <Badge value="Informations légales" severity="success" class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px] tracking-widest" />
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Politique de confidentialité</h1>
                    <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Comment nous protégeons vos données personnelles.</p>
                    <p class="text-slate-500 text-sm mt-4">Dernière mise à jour : 1er juin 2025</p>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-4xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden p-6 lg:p-10 prose prose-slate max-w-none">

                    <h2>1. Introduction</h2>
                    <p>APROJED (Association pour la Promotion de la Jeunesse et du Développement) attache une grande importance à la protection de vos données personnelles. Cette politique de confidentialité explique comment nous collectons, utilisons, partageons et protégeons vos informations lorsque vous utilisez notre site web, faites un don, ou interagissez avec nous.</p>

                    <h2>2. Collecte des données</h2>
                    <p>Nous pouvons collecter les types de données suivants :</p>
                    <ul>
                        <li><strong>Informations d’identification :</strong> nom, prénom, adresse email, numéro de téléphone.</li>
                        <li><strong>Données de paiement :</strong> lorsque vous effectuez un don, nous collectons les informations nécessaires à la transaction (via nos prestataires de paiement sécurisés). Nous ne stockons pas vos informations bancaires complètes.</li>
                        <li><strong>Données techniques :</strong> adresse IP, type de navigateur, pages visitées, durée de visite (via cookies analytiques).</li>
                        <li><strong>Historique des dons :</strong> montant, date, type de don.</li>
                    </ul>

                    <h2>3. Utilisation des données</h2>
                    <p>Vos données sont utilisées pour :</p>
                    <ul>
                        <li>Traiter vos dons et vous envoyer un reçu fiscal.</li>
                        <li>Vous informer de nos activités, événements et appels aux dons (si vous avez consenti).</li>
                        <li>Améliorer notre site web et notre expérience utilisateur.</li>
                        <li>Respecter nos obligations légales.</li>
                    </ul>

                    <h2>4. Partage des données</h2>
                    <p>Nous ne vendons ni ne louons vos données personnelles. Elles peuvent être partagées avec :</p>
                    <ul>
                        <li>Nos prestataires techniques (hébergement, services de paiement, envoi d’emails) strictement nécessaires à nos opérations.</li>
                        <li>Les autorités légales si la loi nous y oblige.</li>
                    </ul>

                    <h2>5. Conservation des données</h2>
                    <p>Les données relatives aux dons sont conservées pendant la durée requise par la loi fiscale (10 ans en France). Les données de navigation sont conservées 13 mois maximum.</p>

                    <h2>6. Vos droits</h2>
                    <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                    <ul>
                        <li>Droit d’accès, de rectification et d’effacement de vos données.</li>
                        <li>Droit à la limitation du traitement.</li>
                        <li>Droit à la portabilité.</li>
                        <li>Droit d’opposition aux communications marketing.</li>
                    </ul>
                    <p>Pour exercer vos droits, contactez-nous à : <a href="mailto:contact@aprojed.org">contact@aprojed.org</a> ou par courrier à l’adresse indiquée ci-dessous.</p>

                    <h2>7. Cookies</h2>
                    <p>Notre site utilise des cookies essentiels pour le fonctionnement et des cookies analytiques (Google Analytics). Vous pouvez paramétrer vos préférences via le bandeau de consentement.</p>

                    <h2>8. Sécurité</h2>
                    <p>Nous mettons en œuvre des mesures techniques et organisationnelles pour protéger vos données contre tout accès non autorisé, perte ou altération. Les transactions de paiement sont sécurisées par chiffrement SSL/TLS et nos partenaires de paiement certifiés PCI DSS.</p>

                    <h2>9. Modifications de la politique</h2>
                    <p>Cette politique peut être mise à jour occasionnellement. La date de la dernière modification est indiquée en haut de page. Nous vous encourageons à la consulter régulièrement.</p>

                    <h2>10. Contact</h2>
                    <p>Pour toute question relative à cette politique ou à vos données personnelles :</p>
                    <address class="not-italic">
                        <strong>APROJED</strong><br>
                        123 Rue de l’Association, Abidjan, Côte d’Ivoire<br>
                        Email : <a href="mailto:dpo@aprojed.org">dpo@aprojed.org</a><br>
                        Téléphone : +225 27 20 00 00 00
                    </address>

                    <hr class="my-8">

                    <p class="text-sm text-slate-500">Cette politique de confidentialité est conforme au Règlement Général sur la Protection des Données (RGPD) et à la législation ivoirienne sur la protection des données personnelles.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Badge from 'primevue/badge';
</script>

<style scoped>
.prose {
    font-family: inherit;
}
.prose h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #1e293b;
}
.prose p {
    margin-bottom: 1rem;
    line-height: 1.6;
    color: #334155;
}
.prose ul {
    margin-top: 0.5rem;
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}
.prose li {
    margin-bottom: 0.25rem;
}
.prose a {
    color: #10b981;
    text-decoration: none;
}
.prose a:hover {
    text-decoration: underline;
}
.prose address {
    background-color: #f8fafc;
    padding: 1rem;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}
</style>
