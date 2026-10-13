import type { PageProps as InertiaPageProps } from '@inertiajs/core';

/** Props shared on every response by App\Http\Middleware\HandleInertiaRequests. */
export interface SharedProps {
    app: { name: string; url: string };
    hasNotes: boolean;
    flash: { success: string | null };
    errors: Record<string, string>;
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, SharedProps {}
}
