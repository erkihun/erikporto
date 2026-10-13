import { router } from '@inertiajs/react';

/**
 * Page transitions (styles in app.css, "View transitions").
 *
 * - A project's cover and title carry the shared names `project-cover` / `project-title`
 *   between its card and its case study. Only one card holds them at a time: the one that
 *   was clicked, or the one for the case study being left.
 * - Links pass `viewTransition={forward}`; the back/forward buttons replay Inertia's own
 *   popstate handling inside a transition marked "back", so the slide runs in reverse.
 * - Reduced motion: no transition on history navigation, and CSS removes the animations.
 */
let activeProject: string | null = null;

export const COVER = 'project-cover';
export const TITLE = 'project-title';

/** Shared-element names for a project card: set only on the card the transition belongs to. */
export function sharedNames(slug: string): { cover?: string; title?: string } {
    return activeProject === slug ? { cover: COVER, title: TITLE } : {};
}

/** The case study now on screen (its card takes the names when going back). */
export function setActiveProject(slug: string | null): void {
    activeProject = slug;
}

/** Hands the shared names to the clicked card, right before the old page is captured. */
export function claimSharedNames(slug: string, card: HTMLElement): void {
    activeProject = slug;
    document.querySelectorAll<HTMLElement>('.card .shot, .card h3').forEach((el) => {
        el.style.viewTransitionName = '';
    });
    const cover = card.querySelector<HTMLElement>('.shot');
    const title = card.querySelector<HTMLElement>('h3');
    if (cover) cover.style.viewTransitionName = COVER;
    if (title) title.style.viewTransitionName = TITLE;
}

function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function mark(transition: ViewTransition, direction: 'forward' | 'back'): void {
    const root = document.documentElement;
    root.dataset.vt = direction;
    void transition.finished.finally(() => {
        if (root.dataset.vt === direction) delete root.dataset.vt;
    });
}

/** Inertia Link `viewTransition` option. */
export function forward(transition: ViewTransition): void {
    mark(transition, 'forward');
}

/**
 * Inertia swaps pages on back/forward without a transition. This capture-phase listener runs
 * first, stops that event, and dispatches it again inside a view transition; the new page is
 * captured once Inertia reports the navigation (or after a timeout, so nothing can hang).
 */
export function animateHistoryNavigation(): void {
    if (typeof document === 'undefined' || !('startViewTransition' in document)) return;

    let replaying = false;

    window.addEventListener(
        'popstate',
        (event: PopStateEvent) => {
            const state: unknown = event.state;
            if (replaying || state === null || typeof state !== 'object' || !('page' in state)) return;
            if (prefersReducedMotion() || document.visibilityState === 'hidden') return;

            event.stopImmediatePropagation();

            const transition = document.startViewTransition(
                () =>
                    new Promise<void>((resolve) => {
                        let settled = false;
                        // Not requestAnimationFrame: rendering (and rAF) is paused until this
                        // callback settles. A task is enough for the new page's commit to land.
                        const settle = () => {
                            if (settled) return;
                            settled = true;
                            stop();
                            window.setTimeout(resolve, 0);
                        };
                        const stop = router.on('navigate', settle);
                        window.setTimeout(settle, 1500);

                        replaying = true;
                        window.dispatchEvent(new PopStateEvent('popstate', { state }));
                        replaying = false;
                    }),
            );
            mark(transition, 'back');
        },
        { capture: true },
    );
}
