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
