import { usePage } from '@inertiajs/react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { Seo } from '@/components/ui/Seo';
import { useTrans } from '@/hooks/useTrans';

/** Shown on a fresh install until the owner fills in the profile at /admin. */
export default function ComingSoon() {
    const { app } = usePage().props;
    const t = useTrans();

    return (
        <SiteLayout ownerName={app.name} sections={false}>
            <Seo description={t('coming_soon.description', { name: app.name })} noindex />
            <section className="hero wrap page-enter">
                <span className="status mono">
                    <i className="pulse" style={{ background: 'var(--signal)' }} />
                    <span>{t('coming_soon.status')}</span>
                </span>
                <h1 className="name">{app.name}</h1>
                <p className="lede">{t('coming_soon.text')}</p>
            </section>
        </SiteLayout>
    );
}
