import { usePage } from '@inertiajs/react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { Seo } from '@/components/ui/Seo';

/** Shown on a fresh install until the owner fills in the profile at /admin. */
export default function ComingSoon() {
    const { app } = usePage().props;

    return (
        <SiteLayout ownerName={app.name} sections={false}>
            <Seo description={`${app.name}: portfolio launching soon.`} noindex />
            <section className="hero wrap page-enter">
                <span className="status mono">
                    <i className="pulse" style={{ background: 'var(--signal)' }} />
                    <span>Coming soon</span>
                </span>
                <h1 className="name">{app.name}</h1>
                <p className="lede">The portfolio is being prepared. Case studies, capabilities and contact details will be published here shortly.</p>
            </section>
        </SiteLayout>
    );
}
