import type { Page } from '@inertiajs/core';
import type { route as routeFn } from 'ziggy-js';

export interface PageProps {
    // Added by HandleInertiaRequests middleware
    name: string;
    auth: {
        user?: any;
    };
    flash?: {
        success?: string;
        error?: string;
    };
    ziggy: {
        location: string;
        [key: string]: any;
    };
    sidebarOpen: boolean;
    [key: string]: any;
}

declare global {
    const route: typeof routeFn;
    var global: typeof globalThis & {
        route?: typeof routeFn;
    };
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $page: Page<PageProps>;
    }
}

declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}
