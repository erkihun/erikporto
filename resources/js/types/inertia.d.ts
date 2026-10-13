import type { PageProps as InertiaPageProps } from '@inertiajs/core';

import type en from '../../../lang/en.json';

/** Keys of lang/en.json (lang/am.json has the same keys; a test enforces it). */
export type TransKey = keyof typeof en;

export type Locale = 'en' | 'am';

/** Mirrors App\Support\LocalizedUrls::alternates(): the current page in each language. */
export interface LocaleOption {
    code: Locale;
    label: string;
    name: string;
    hreflang: string;
    og_locale: string;
    url: string;
    current: boolean;
    /** True when this page exists in that language (hreflang); false links to that language's home. */
    alternate: boolean;
}

/** Props shared on every response by App\Http\Middleware\HandleInertiaRequests. */
export interface SharedProps {
    app: { name: string; url: string };
    locale: Locale;
    locales: LocaleOption[];
    urls: { home: string; contact: string };
    translations: Record<TransKey, string>;
    hasNotes: boolean;
    hasNow: boolean;
    /** Home section anchors that have content, in page order. */
    homeSections: string[];
    flash: { success: string | null; error: string | null };
    errors: Record<string, string>;
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, SharedProps {}
}
