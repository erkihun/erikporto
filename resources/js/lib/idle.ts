/**
 * Runs `task` once the browser is idle after the first paint, so decorative work
 * never competes with rendering or LCP. Where requestIdleCallback is missing
 * (Safari) it waits for the next frame plus a macrotask. Returns a cancel function.
 */
export function whenIdle(task: () => void, timeout = 2000): () => void {
    if (typeof window.requestIdleCallback === 'function') {
        const id = window.requestIdleCallback(() => task(), { timeout });
        return () => window.cancelIdleCallback(id);
    }

    let timer = 0;
    const frame = window.requestAnimationFrame(() => {
        timer = window.setTimeout(task, 0);
    });

    return () => {
        window.cancelAnimationFrame(frame);
        window.clearTimeout(timer);
    };
}

/**
 * Like whenIdle, but never before the load event: an idle moment can come while the page is
 * still loading (between hydration and the last request), and work started there counts as
 * blocking time. After a client-side visit the page is already loaded, so it waits for idle only.
 */
export function whenLoadedAndIdle(task: () => void, timeout = 2000): () => void {
    let cancel = () => {};
    const schedule = () => {
        cancel = whenIdle(task, timeout);
    };

    if (document.readyState === 'complete') {
        schedule();
    } else {
        window.addEventListener('load', schedule, { once: true });
    }

    return () => {
        window.removeEventListener('load', schedule);
        cancel();
    };
}
