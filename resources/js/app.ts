import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import piniaPluginPersistedState from 'pinia-plugin-persistedstate';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { initializeSentry } from './lib/sentry';

const appName = import.meta.env.VITE_APP_NAME;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Convert route names to file paths
        // e.g., 'manage-maps' -> './pages/manage-maps/ManageMaps.vue'
        // e.g., 'settings.api-tokens' -> './pages/settings/ApiTokens.vue'
        // e.g., 'maps.edit' -> './pages/maps/Edit.vue'
        // e.g., 'maps.test' -> './pages/maps/Test.vue'
        // e.g., 'auth/Login' -> './pages/auth/Login.vue'
        // e.g., 'dashboard' -> './pages/dashboard/Dashboard.vue'
        // e.g., 'welcome' -> './pages/welcome/Welcome.vue'

        // Handle different naming patterns
        if (name.includes('/')) {
            // Direct path like 'auth/Login' -> './pages/auth/Login.vue'
            return resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue'));
        }

        const parts = name.split('.');
        const directory = parts[0];

        // Convert kebab-case to PascalCase for component name
        const componentName =
            parts.length > 1
                ? parts[parts.length - 1]
                      .split('-')
                      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                      .join('')
                : directory
                      .split('-')
                      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                      .join('');

        const fullPath = `./pages/${directory}/${componentName}.vue`;

        return resolvePageComponent(fullPath, import.meta.glob<DefineComponent>('./pages/**/*.vue'));
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        pinia.use(piniaPluginPersistedState);

        const app = createApp({ render: () => h(App, props) });

        // Initialize Sentry
        initializeSentry(app, appName);

        app.use(plugin).use(pinia).use(ZiggyVue).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
