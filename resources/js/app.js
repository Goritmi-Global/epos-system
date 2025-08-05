// Tailwind + app styles
import '../css/app.css';
import './bootstrap';

// 3rd-party CSS
import '../assets/css/bootstrap.min.css';
import '../assets/css/animate.css';
import '../assets/css/dataTables.bootstrap4.min.css';
import '../assets/plugins/fontawesome/css/fontawesome.min.css';
import '../assets/plugins/fontawesome/css/all.min.css';
import '../assets/css/style.css';

// 3rd-party JS
import '../assets/js/jquery-3.6.0.min.js';
import '../assets/js/feather.min.js';
import '../assets/js/jquery.slimscroll.min.js';
import '../assets/js/jquery.dataTables.min.js';
import '../assets/js/dataTables.bootstrap4.min.js';
import '../assets/js/bootstrap.bundle.min.js';
 
import '../assets/js/script.js';


// ✅ Properly trigger Feather Icons
window.feather?.replace();

// Inertia + Vue setup
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
