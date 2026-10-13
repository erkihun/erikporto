import { Link, usePage } from '@inertiajs/react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { ArrowRight } from '@/components/ui/icons';
import { MissingNodeChart } from '@/components/ui/MissingNodeChart';
import { Seo } from '@/components/ui/Seo';
import { useTrans } from '@/hooks/useTrans';
import type { TransKey } from '@/types/inertia';
import type { ErrorPageProps, ErrorStatus } from '@/types/portfolio';

const COPY: Record<ErrorStatus, readonly [kicker: TransKey, title: TransKey, text: TransKey]> = {
    403: ['errors.403.kicker', 'errors.403.title', 'errors.403.text'],
    404: ['errors.404.kicker', 'errors.404.title', 'errors.404.text'],
    429: ['errors.429.kicker', 'errors.429.title', 'errors.429.text'],
    500: ['errors.500.kicker', 'errors.500.title', 'errors.500.text'],
    503: ['errors.503.kicker', 'errors.503.title', 'errors.503.text'],
};

/**
 * 403/404/429/500/503 (App\Http\Responses\ErrorPage). The 404 shows the org chart with a
 * vacant position; server errors stay quiet: one sentence, no navigation, a way to retry.
 */
export default function ErrorPage({ status, owner }: ErrorPageProps) {
    const t = useTrans();
    const page = usePage();
    const { urls } = page.props;
    const [kicker, title, text] = COPY[status];
    const retry = status === 429 || status >= 500;

    return (
        <SiteLayout ownerName={owner.name} sections={status < 500}>
            <Seo title={t(title)} description={t(text)} noindex />

            <div className="wrap page-enter err">
                <header className="cs-hero">
                    <span className="kicker mono">{t(kicker)}</span>
                    <h1>{t(title)}</h1>
                    <p className="sub">{t(text)}</p>
                    <div className="err-actions">
                        {retry ? (
                            <a className="btn btn-primary btn-sm" href={page.url}>
                                {t('errors.retry')} <ArrowRight />
                            </a>
                        ) : null}
                        {status !== 503 ? (
                            <Link className={`btn btn-sm ${retry ? 'btn-ghost' : 'btn-primary'}`} href={urls.home}>
                                {t('errors.home')}
                                {retry ? null : <ArrowRight />}
                            </Link>
                        ) : null}
                        {status === 404 ? (
                            <Link className="btn btn-ghost btn-sm" href={`${urls.home}#work`}>
                                {t('errors.work')}
                            </Link>
                        ) : null}
                    </div>
                </header>

                {status === 404 ? (
                    <figure className="err-graph">
                        <MissingNodeChart label={t('errors.graph_label')} vacant={t('errors.vacant')} />
                        <figcaption className="mono">
                            <span>{t('errors.graph_caption')}</span>
                            <span>404</span>
                        </figcaption>
                    </figure>
                ) : null}
            </div>
        </SiteLayout>
    );
}
