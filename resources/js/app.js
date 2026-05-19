import './bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css'; // Font Awesome

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import 'primevue/resources/primevue.min.css'; // Estilos base de PrimeVue
import { applyPrimeVueTheme, preloadPrimeVueThemes } from './theme-loader';
import 'primeicons/primeicons.css'; // Iconos
import Tooltip from 'primevue/tooltip';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast'
import ConfirmationService from 'primevue/confirmationservice';
import 'primeflex/primeflex.css';

// Tailwind se importa AL FINAL para que sus utilidades ganen por orden de cascada
// frente a PrimeFlex (que tiene reglas con !important para bg-white, text-*, etc.)
import '../css/app.css';

// Aplicar el tema PrimeVue según .dark en <html> (ya seteado por el script anti-FOUC en app.blade.php)
applyPrimeVueTheme(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
preloadPrimeVueThemes();



const appName = import.meta.env.VITE_APP_NAME || 'Laravel';


createInertiaApp({
    title: (title) => `${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue)
            .use(ToastService)
            .use(ConfirmationService)
            .directive('tooltip', Tooltip)
            .component('Toast', Toast);

        // Merge locale to keep PrimeVue defaults (monthNames, dayNames, etc.)
        app.config.globalProperties.$primevue.config.locale = {
            ...app.config.globalProperties.$primevue.config.locale,
            clear: 'Borrar',
            apply: 'Aplicar'
        };

        return app.mount(el);
            
    },
    progress: {
        color: '#4B5563',
    },
});
