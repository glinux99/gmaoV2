<template>
    <AppLayout>
        <Head title="Conditions générales - APROJED" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/50 to-teal-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-emerald-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-teal-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

                <div class="max-w-screen-2xl mx-auto relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <Badge value="Cadre légal" severity="success" class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px] tracking-widest" />
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Conditions générales</h1>
                    <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Conditions d’utilisation du site et de nos services.</p>
                    <p class="text-slate-500 text-sm mt-4">Dernière mise à jour : 1er juin 2025</p>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-4xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden p-6 lg:p-10 prose prose-slate max-w-none">

                    <h2>1. Préambule</h2>
                    <p>Les présentes conditions générales d’utilisation (CGU) régissent l’accès et l’utilisation du site internet <strong>aprojed.org</strong> (ci-après « le Site ») ainsi que des services proposés par l’association <strong>APROJED</strong> (Association pour la Promotion de la Jeunesse et du Développement). En utilisant le Site, vous acceptez pleinement et sans réserve les présentes CGU.</p>

                    <h2>2. Objet du site</h2>
                    <p>Le Site a pour objet de :</p>
                    <ul>
                        <li>Présenter les actions et projets de l’association APROJED ;</li>
                        <li>Permettre aux visiteurs de faire des dons (ponctuels ou mensuels) ;</li>
                        <li>Informer sur les événements et campagnes de sensibilisation ;</li>
                        <li>Proposer un espace de contact et d’inscription à la newsletter.</li>
                    </ul>

                    <h2>3. Acceptation des conditions</h2>
                    <p>L’utilisation du Site emporte acceptation irrévocable des présentes CGU. Si vous n’acceptez pas ces conditions, vous devez vous abstenir d’utiliser le Site. APROJED se réserve le droit de modifier les CGU à tout moment. Les modifications sont opposables dès leur mise en ligne.</p>

                    <h2>4. Dons en ligne</h2>
                    <p>Les dons effectués via le Site sont régis par les règles suivantes :</p>
                    <ul>
                        <li>Le donateur doit être majeur ou disposer d’une autorisation parentale.</li>
                        <li>Le montant du don est libre, avec un minimum symbolique de 5 USD.</li>
                        <li>Les dons sont définitifs et non remboursables, sauf erreur technique ou cas de fraude avérée.</li>
                        <li>Un reçu fiscal (pour les contribuables éligibles) est envoyé automatiquement par email.</li>
                        <li>Les données de paiement sont traitées par nos prestataires sécurisés (Stripe, PayPal). APROJED ne stocke pas les informations bancaires complètes.</li>
                    </ul>

                    <h2>5. Propriété intellectuelle</h2>
                    <p>Tous les contenus du Site (textes, images, logos, vidéos, graphismes) sont la propriété exclusive d’APROJED ou de ses partenaires. Toute reproduction, distribution ou utilisation sans autorisation préalable est interdite.</p>

                    <h2>6. Responsabilité</h2>
                    <p>APROJED s’efforce de maintenir le Site accessible et sécurisé, mais ne peut garantir une absence totale d’interruptions ou d’erreurs. L’association ne pourra être tenue responsable en cas de dommages indirects liés à l’utilisation du Site (perte de données, préjudice financier, etc.). Les liens externes vers des sites tiers sont fournis à titre informatif et n’engagent pas la responsabilité d’APROJED.</p>

                    <h2>7. Protection des données personnelles</h2>
                    <p>Les données collectées sur le Site (nom, email, téléphone, adresse, historique des dons) sont traitées conformément à notre <a :href="route('privacy.policy')">Politique de confidentialité</a>. Vous disposez d’un droit d’accès, de rectification et de suppression de vos données en nous contactant à <a href="mailto:dpo@aprojed.org">dpo@aprojed.org</a>.</p>

                    <h2>8. Cookies</h2>
                    <p>Le Site utilise des cookies fonctionnels et analytiques. Vous pouvez paramétrer vos préférences via le bandeau de consentement. Le refus des cookies peut dégrader certaines fonctionnalités.</p>

                    <h2>9. Loi applicable et juridiction</h2>
                    <p>Les présentes CGU sont régies par le droit ivoirien. En cas de litige, une solution amiable sera recherchée avant toute action judiciaire. À défaut d’accord, les tribunaux d’Abidjan seront compétents.</p>

                    <h2>10. Contact</h2>
                    <p>Pour toute question relative aux CGU :</p>
                    <address class="not-italic">
                        <strong>APROJED</strong><br>
                        123 Rue de l’Association, Abidjan, Côte d’Ivoire<br>
                        Email : <a href="mailto:contact@aprojed.org">contact@aprojed.org</a><br>
                        Téléphone : +225 27 20 00 00 00
                    </address>

                    <hr class="my-8">

                    <p class="text-sm text-slate-500">En utilisant ce site, vous reconnaissez avoir lu, compris et accepté ces conditions générales d’utilisation.</p>
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
