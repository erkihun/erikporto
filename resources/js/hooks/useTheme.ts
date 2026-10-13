import { useCallback } from 'react';

type Theme = 'light' | 'dark';
const STORAGE_KEY = 'eb-theme';

function resolvedTheme(): Theme {
    const explicit = document.documentElement.dataset.theme;
    if (explicit === 'light' || explicit === 'dark') return explicit;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/** The initial theme is applied by an inline script in app.blade.php before paint. */
export function useTheme(): { toggle: () => void } {
    const toggle = useCallback(() => {
        const next: Theme = resolvedTheme() === 'dark' ? 'light' : 'dark';
        const apply = () => {
            document.documentElement.dataset.theme = next;
        };
        // Cross-fade the whole page where the View Transitions API exists.
        if ('startViewTransition' in document && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.startViewTransition(apply);
        } else {
            apply();
        }
        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch {
            /* storage unavailable: theme still applies for this visit */
        }
    }, []);

    return { toggle };
}
