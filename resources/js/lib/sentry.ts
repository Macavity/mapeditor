import * as Sentry from '@sentry/vue';
import type { App } from 'vue';

export function initializeSentry(app: App, appName: string): void {
    if (!import.meta.env.VITE_SENTRY_FRONTEND_DSN) {
        return;
    }

    Sentry.init({
        app,
        dsn: import.meta.env.VITE_SENTRY_FRONTEND_DSN,
        environment: import.meta.env.APP_ENV || 'development',
        release: import.meta.env.SENTRY_RELEASE,

        // Performance monitoring
        tracesSampleRate: 1.0,
        profilesSampleRate: 1.0,

        // Enable Vue integration
        integrations: [
            Sentry.browserTracingIntegration(),
            Sentry.replayIntegration({
                maskAllText: false,
                blockAllMedia: false,
            }),
        ],

        // Set sampling rate for session replay
        replaysSessionSampleRate: 0.1,
        replaysOnErrorSampleRate: 1.0,
    });

    Sentry.setTag('app', appName);
}
