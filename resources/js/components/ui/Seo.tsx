import { Head, usePage } from '@inertiajs/react';

interface SeoProps {
    title?: string;
    description: string;
    image?: string | null;
    type?: 'website' | 'article';
}

/** Rendered server-side through Inertia SSR, so crawlers and link previews see it. */
export function Seo({ title, description, image, type = 'website' }: SeoProps) {
    const page = usePage();
    const { app } = page.props;
    const url = `${app.url.replace(/\/$/, '')}${page.url}`;
    const fullTitle = title ? `${title} · ${app.name}` : app.name;

    return (
        <Head title={title}>
            <meta head-key="description" name="description" content={description} />
            <meta head-key="og:title" property="og:title" content={fullTitle} />
            <meta head-key="og:description" property="og:description" content={description} />
            <meta head-key="og:type" property="og:type" content={type} />
            <meta head-key="og:url" property="og:url" content={url} />
            {image ? <meta head-key="og:image" property="og:image" content={image} /> : null}
            <meta head-key="twitter:card" name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />
        </Head>
    );
}
