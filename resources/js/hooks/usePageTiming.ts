import { useEffect, useState } from 'react';

export interface PageTiming {
    /** Milliseconds from navigation start to the end of the load event. */
    load: number;
    /** Largest Contentful Paint in milliseconds; null where the browser doesn't report it (Safari). */
    lcp: number | null;
}

/**
 * The visitor's own numbers for the first document of this visit (Navigation Timing and a
 * buffered PerformanceObserver), measured in the browser only, so SSR shows a placeholder.
 */
export function usePageTiming(): PageTiming | null {
    const [timing, setTiming] = useState<PageTiming | null>(null);

    useEffect(() => {
        const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming | undefined;
        if (!navigation) return;

        let lcp: number | null = null;
        const publish = () => {
            if (navigation.loadEventEnd > 0) setTiming({ load: navigation.loadEventEnd, lcp });
        };

        let observer: PerformanceObserver | null = null;
        if (PerformanceObserver.supportedEntryTypes?.includes('largest-contentful-paint')) {
            observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const last = entries[entries.length - 1];
                if (last) lcp = last.startTime;
                publish();
            });
            observer.observe({ type: 'largest-contentful-paint', buffered: true });
        }

        if (navigation.loadEventEnd > 0) {
            publish();
        } else {
            // loadEventEnd is set right after the load handlers run.
            window.addEventListener('load', () => window.setTimeout(publish, 0), { once: true });
        }

        return () => observer?.disconnect();
    }, []);

    return timing;
}
