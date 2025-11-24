import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import Aura from '@primevue/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/sakai/assets/styles.scss';
import '@/sakai/assets/tailwind.css';
import { definePreset } from '@primevue/themes';

const appName = import.meta.env.VITE_APP_NAME || 'Sakai';
const MyCustomPreset = definePreset (Aura, {
    semantic: {
        primary: {
            50: '#fef3ec',
            100: '#fde5d4',
            200: '#fbd2b3',
            300: '#f8b488',
            400: '#f48f54',
            500: '#e15f14',
            600: '#cb5211',
            700: '#ac440e',
            800: '#90390c',
            900: '#79300a'
        }
    }
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
