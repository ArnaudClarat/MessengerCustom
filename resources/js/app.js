// Importer la configuration WebSocket.
// Echo ne sera initialisé que si Reverb est configuré.
import './echo';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

// Initialiser Inertia avec les composants Vue.
createInertiaApp({

    // Rechercher la page Vue demandée par Laravel.
    resolve: (name) => {
        const pages = import.meta.glob(
            './Pages/**/*.vue',
            { eager: true }
        );

        return pages[`./Pages/${name}.vue`];
    },

    // Créer et monter l'application Vue.
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(App, props),
        })
            .use(plugin)
            .mount(el);
    },
});