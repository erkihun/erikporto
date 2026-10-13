import type { ReactNode } from 'react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { ArrowUpRight } from '@/components/ui/icons';
import { Seo } from '@/components/ui/Seo';
import { usePageTiming } from '@/hooks/usePageTiming';
import { useTrans } from '@/hooks/useTrans';
import { formatDate } from '@/lib/format';
import type { ColophonPageProps } from '@/types/portfolio';

const kb = (bytes: number) => `${(bytes / 1024).toFixed(1)} KB`;
const seconds = (ms: number) => `${(ms / 1000).toFixed(2)} s`;

function Block({ label, children }: { label: string; children: ReactNode }) {
    return (
        <section className="cs-block">
            <h2 className="mono" style={{ fontFamily: 'var(--f-mono)', fontSize: 12, color: 'var(--muted)' }}>
                {label}
            </h2>
            <div>{children}</div>
        </section>
    );
}

/** How the site is built, from measurements: build output, the last CI run and the visitor's browser. */
export default function ColophonPage({ colophon, owner }: ColophonPageProps) {
    const t = useTrans();
    const timing = usePageTiming();
    const { stack, weight, build, quality, repository_url: repository } = colophon;

    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title={t('colophon.kicker')} description={t('colophon.intro')} />

            <article className="wrap page-enter colophon">
                <header className="cs-hero">
                    <span className="kicker mono">{t('colophon.kicker')}</span>
                    <h1>{t('colophon.title')}</h1>
                    <p className="sub">{t('colophon.intro')}</p>
                </header>

                <Block label={t('colophon.stack')}>
                    <div className="tags">
                        {stack.map((item) => (
                            <span className="tag" key={item.name}>
                                {item.name} {item.version}
                            </span>
                        ))}
                    </div>
                </Block>

                <Block label={t('colophon.type')}>
                    <p>{t('colophon.type_text')}</p>
                </Block>

                {weight ? (
                    <Block label={t('colophon.weight')}>
                        <p>{t('colophon.weight_text', { js: kb(weight.js), css: kb(weight.css), fonts: kb(weight.fonts), fonts_am: kb(weight.fonts_amharic) })}</p>
                    </Block>
                ) : null}

                <Block label={t('colophon.this_visit')}>
                    <p aria-live="polite">
                        {timing === null
                            ? t('colophon.measuring')
                            : timing.lcp === null
                              ? t('colophon.visit_load_only', { load: seconds(timing.load) })
                              : t('colophon.visit_text', { load: seconds(timing.load), lcp: seconds(timing.lcp) })}
                    </p>
                </Block>

                {quality ? (
                    <Block label={t('colophon.quality')}>
                        {quality.tests ? <p>{t('colophon.tests_text', { passed: quality.tests.passed, total: quality.tests.total })}</p> : null}
                        {quality.lighthouse ? <p>{t('colophon.lighthouse_text', { ...quality.lighthouse })}</p> : null}
                        <p style={{ color: 'var(--muted)' }}>{t('colophon.measured_on', { date: formatDate(quality.measured_at) })}</p>
                    </Block>
                ) : null}

                {build ? (
                    <Block label={t('colophon.build')}>
                        <p>
                            {build.commit_url ? (
                                <a href={build.commit_url} target="_blank" rel="noopener noreferrer">
                                    {t('colophon.build_text', { commit: build.commit ?? '', date: formatDate(build.built_at) })}
                                </a>
                            ) : (
                                t('colophon.build_text', { commit: build.commit ?? '—', date: formatDate(build.built_at) })
                            )}
                        </p>
                        {repository ? (
                            <p>
                                <a className="btn btn-ghost btn-sm" href={repository} target="_blank" rel="noopener noreferrer">
                                    {t('colophon.source')} <ArrowUpRight />
                                </a>
                            </p>
                        ) : null}
                    </Block>
                ) : null}

                <Block label={t('colophon.a11y')}>
                    <p>{t('colophon.a11y_text')}</p>
                </Block>

                <Block label={t('colophon.privacy')}>
                    <p>{t('colophon.privacy_text')}</p>
                </Block>
            </article>
        </SiteLayout>
    );
}
