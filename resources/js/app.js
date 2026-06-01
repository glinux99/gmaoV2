import './bootstrap';
import '../css/app.css';
import "primeicons/primeicons.css";
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import 'quill/dist/quill.snow.css'; // or quill.bubble.css

import Aura from '@primevue/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/sakai/assets/styles.scss';
import '@/sakai/assets/tailwind.css';
import { createI18n } from 'vue-i18n';
import { definePreset } from '@primevue/themes';

// 1. Importer les fichiers de traduction
import fr from './locales/fr.json';
import en from './locales/en.json';
import sw from './locales/sw.json';
import ln from './locales/ln.json';

const appName = import.meta.env.VITE_APP_NAME || 'Sakai';
const MyCustomPreset = definePreset (Aura, {
    semantic: {
        primary: {
           50: '#f0f9f4',  // Très clair, presque blanc teinté
    100: '#dcf0e3', // Clair et doux
    200: '#bbe1c8', // Pastel moyen
    300: '#8ecba5', // Vert tendre
    400: '#5fb37e', // Vert vibrant
    500: '#38945a', // Couleur de base (Équivalent de votre orange 500)
    600: '#2d7a4a', // Plus profond
    700: '#25623d', // Vert sapin
    800: '#1e4f32', // Sombre
    900: '#184129'  // Très sombre
        }
    }
});

// 2. Configurer i18n avec les messages importés
const i18n = createI18n({
    legacy: false, // Important pour le mode Composition API
    locale: 'fr', // Langue par défaut
    fallbackLocale: 'fr',
    messages: {
        fr, // Raccourci pour fr: fr
        en, // Raccourci pour en: en
        sw,
        ln,
    },
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                sematic:{

                },
                theme: {
                    preset: MyCustomPreset,
                    options: {
                        darkModeSelector: '.app-dark'
                    }
                }
            })
            .use(i18n)
            .use(ToastService)
            .use(ConfirmationService)
            .mixin({
                methods: {
                    can: function (permissions) {
                        var allPermissions = this.$page.props.auth.can;
                        var hasPermission = false;
                        permissions.forEach(function (item) {
                            if (allPermissions[item]) hasPermission = true;
                        });
                        return hasPermission;
                    },
                },
            })
            .mount(el);
    },
    progress: {
        color: '#e15f14',
    },
});
