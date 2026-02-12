import './bootstrap';
import '../css/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css'; // Font Awesome

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import 'primevue/resources/primevue.min.css'; // Estilos base de PrimeVue
import 'primevue/resources/themes/lara-light-blue/theme.css'; // O el tema que prefieras
import 'primeicons/primeicons.css'; // Iconos
import Tooltip from 'primevue/tooltip';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast'
import 'primeflex/primeflex.css';



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
