import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { animateHistoryNavigation } from './lib/view-transitions';

const appName = import.meta.env.VITE_APP_NAME || 'Erkihun Bewket';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        if (el.hasChildNodes()) {
            hydrateRoot(el, <App {...props} />);
            return;
        }
        createRoot(el).render(<App {...props} />);
    },
    progress: { color: '#2747F5', showSpinner: false },
});

// Back/forward buttons get the reverse page transition (a capture-phase popstate listener).
animateHistoryNavigation();

// For whoever opens the console.
console.info(
    '%cHello, curious mind.%c\nLaravel, Inertia and Preact, server-rendered and measured in the colophon:',
    'font: 600 15px system-ui, sans-serif; color: #2747F5',
    'font: 13px system-ui, sans-serif',
    new URL('/colophon', window.location.origin).href,
);
