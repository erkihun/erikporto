import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

import type { TransKey } from '@/types/inertia';

export type Translate = (key: TransKey, replacements?: Record<string, string | number>) => string;

/**
 * UI strings for the current locale (lang/{locale}.json, shared by HandleInertiaRequests).
 * Placeholders use Laravel's syntax: t('nav.home_label', { name }) fills ":name".
 */
export function useTrans(): Translate {
    const { translations } = usePage().props;

    return useCallback<Translate>(
        (key, replacements) => {
            let text = translations[key] ?? key;
            // Longest names first (as Laravel does), so :fonts never eats the start of :fonts_am.
            const entries = Object.entries(replacements ?? {}).sort(([a], [b]) => b.length - a.length);
            for (const [name, value] of entries) {
                text = text.replaceAll(`:${name}`, String(value));
            }
            return text;
        },
        [translations],
    );
}
