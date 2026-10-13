import { router } from '@inertiajs/react';

/**
 * A thin bar while a page visit takes longer than a moment (styles: html.is-navigating in
 * app.css). Replaces Inertia's own progress bar, which injects an inline <style> element
 * that the Content Security Policy rightly refuses. Prefetches stay silent.
 */
export function showNavigationProgress(): void {
    let timer: number | undefined;
    const root = document.documentElement;

    router.on('start', (event) => {
        if (event.detail.visit.prefetch) return;
        window.clearTimeout(timer);
        timer = window.setTimeout(() => root.classList.add('is-navigating'), 180);
    });

    router.on('finish', () => {
        window.clearTimeout(timer);
        root.classList.remove('is-navigating');
    });
}
