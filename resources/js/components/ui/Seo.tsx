import { Head, usePage } from '@inertiajs/react';

interface SeoProps {
    title?: string;
    description: string;
    image?: string | null;
    type?: 'website' | 'article';
    /** Keep placeholder pages (coming soon) out of search indexes. */
    noindex?: boolean;
}

/** Rendered server-side through Inertia SSR, so crawlers and link previews see it. */
export function Seo({ title, description, image, type = 'website', noindex = false }: SeoProps) {
    const page = usePage();
    const { app, locales } = page.props;
    const url = `${app.url.replace(/\/$/, '')}${page.url}`;
    const fullTitle = title ? `${title} · ${app.name}` : app.name;
    const current = locales.find((l) => l.current);
    // hreflang only for pages that exist in every language; English is the x-default.
    const alternates = locales.filter((l) => l.alternate);
    const fallback = alternates.find((l) => l.code === 'en');

    return (
        <Head title={title}>
            <meta head-key="description" name="description" content={description} />
            <meta head-key="og:title" property="og:title" content={fullTitle} />
            <meta head-key="og:description" property="og:description" content={description} />
            <meta head-key="og:type" property="og:type" content={type} />
            <meta head-key="og:url" property="og:url" content={url} />
            {current ? <meta head-key="og:locale" property="og:locale" content={current.og_locale} /> : null}
            {alternates
                .filter((l) => !l.current)
                .map((l) => (
                    <meta key={l.code} head-key={`og:locale:alternate:${l.code}`} property="og:locale:alternate" content={l.og_locale} />
                ))}
            {image ? <meta head-key="og:image" property="og:image" content={image} /> : null}
            <meta head-key="twitter:card" name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />
            {alternates.map((l) => (
                <link key={l.code} head-key={`hreflang:${l.code}`} rel="alternate" hrefLang={l.hreflang} href={l.url} />
            ))}
            {fallback ? <link head-key="hreflang:x-default" rel="alternate" hrefLang="x-default" href={fallback.url} /> : null}
            {noindex ? <meta head-key="robots" name="robots" content="noindex" /> : null}
        </Head>
    );
}
