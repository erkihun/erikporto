import { router } from '@inertiajs/react';
import { useCallback } from 'react';

/**
 * Inertia's <Link prefetch> fires on hover only. This gives keyboard users the
 * same head start when the link receives focus; Inertia dedupes repeat requests.
 */
export function usePrefetchOnFocus(url: string): () => void {
    return useCallback(() => router.prefetch(url, { method: 'get' }), [url]);
}
