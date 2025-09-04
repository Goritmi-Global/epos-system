// --------------------------------------------------
// 🌐 Tailwind & Application Styles
// --------------------------------------------------
import "../css/app.css";

// --------------------------------------------------
// 🎨 3rd-Party CSS (Bootstrap, Animate, DataTables, FontAwesome)
// --------------------------------------------------
import "../assets/css/bootstrap.min.css";
import "../assets/css/animate.css";
import "../assets/css/dataTables.bootstrap4.min.css";
import "../assets/plugins/fontawesome/css/fontawesome.min.css";
import "../assets/plugins/fontawesome/css/all.min.css";
import "../assets/css/style.css";

// --------------------------------------------------
// 🧠 Core Scripts
// --------------------------------------------------
import "./bootstrap";

// --------------------------------------------------
// 📦 3rd-Party JS Libraries
// --------------------------------------------------
import "../assets/js/jquery-3.6.0.min.js";
import "../assets/js/feather.min.js";
import "../assets/js/jquery.slimscroll.min.js";
import "../assets/js/jquery.dataTables.min.js";
import "../assets/js/dataTables.bootstrap4.min.js";
import "../assets/js/bootstrap.bundle.min.js";
import "../assets/js/script.js";
import "bootstrap-icons/font/bootstrap-icons.css";

// --------------------------------------------------
// 🔔 Vue3 Toastify (Toastr)
// --------------------------------------------------
import Toast from "vue3-toastify";
import "vue3-toastify/dist/index.css";

// --------------------------------------------------
// 🧩 Vue + Inertia + Ziggy Setup
// --------------------------------------------------
import { createApp, h, nextTick } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import VueTelInput from "vue-tel-input";
import "vue-tel-input/vue-tel-input.css";

// Global Components
import ConfirmModal from "@/Components/ConfirmModal.vue";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import ImageZoomModal from "@/Components/ImageZoomModal.vue";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        //  Global components
        vueApp.component("ConfirmModal", ConfirmModal);
        vueApp.component("ImageCropperModal", ImageCropperModal);
        vueApp.component("ImageZoomModal", ImageZoomModal);
        const VueTelInputOptions = {
            mode: "international",
        };

        vueApp.use(plugin);
        vueApp.use(ZiggyVue);
        vueApp.use(PrimeVue, {
            theme: {
                preset: Aura,
            },
        });
        vueApp.use(Toast, {
            autoClose: 3000,
            position: "bottom-right",
            pauseOnFocusLoss: true,
            pauseOnHover: true,
            theme: "light",
        });
        vueApp.use(VueTelInput);

        const mountedApp = vueApp.mount(el);

        // ✅ Trigger Feather Icons after DOM is ready
        nextTick(() => {
            window.feather?.replace();
        });

        return mountedApp;
    },
    progress: {
        color: "#4B5563",
    },
});
