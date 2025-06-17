/// <reference types="vite/client" />
/// <reference types="vitest" />

// For Vue SFCs
declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, never>, Record<string, never>, any>;
    export default component;
}

// For test files
declare module '*.spec.ts' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, never>, Record<string, never>, any>;
    export default component;
}

// For aliases
declare module '@/*' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, never>, Record<string, never>, any>;
    export default component;
}
