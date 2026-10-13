import { Link, usePage } from '@inertiajs/react';

import { ProjectVisual } from '@/components/home/ProjectMock';
import { SiteLayout } from '@/components/layout/SiteLayout';
import { ArrowLeft, ArrowRight, ArrowUpRight } from '@/components/ui/icons';
import { Placeholder } from '@/components/ui/Placeholder';
import { ResponsiveImg } from '@/components/ui/ResponsiveImg';
import { Seo } from '@/components/ui/Seo';
import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import { useTrans } from '@/hooks/useTrans';
import type { ProjectPageProps } from '@/types/portfolio';

export default function ProjectShow({ project, position, next, owner }: ProjectPageProps) {
    const t = useTrans();
    const { urls } = usePage().props;
    const isDesign = project.category === 'design';
    const prefetchHome = usePrefetchOnFocus(urls.home);
    const prefetchNext = usePrefetchOnFocus(next?.url ?? urls.home);
    const sections: [string, string][] = [
        [t('case.problem'), project.problem],
        [t('case.role'), project.role],
        [t('case.solution'), project.solution],
        [t('case.outcome'), project.outcome],
    ];

    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title={project.title} description={project.summary} image={project.cover?.url} type="article" />

            <article className="wrap page-enter">
                <header className="cs-hero">
                    <Link className="back mono" href={`${urls.home}#work`} prefetch onFocus={prefetchHome}>
                        <ArrowLeft width={14} height={14} /> {t('case.all_work')}
                    </Link>
                    <span className="kicker mono">
                        {t('case.kind_line', { kind: project.kind, index: position.index, total: position.total })}
                        {project.year ? ` · ${project.year}` : ''}
                    </span>
                    <h1>{project.title}</h1>
                    {project.subtitle ? <p className="sub">{project.subtitle}</p> : null}
                </header>

                <div className="cs-cover">
                    <ProjectVisual
                        cover={project.cover}
                        mock={project.mock}
                        alt={t('case.cover_alt', { title: project.title })}
                        sizes="(min-width: 1240px) 1200px, 100vw"
                        eager
                    />
                </div>

                <div className="cs-layout">
                    <div>
                        {sections.map(([label, text]) => (
                            <section className="cs-block rv" key={label}>
                                <h2 className="mono" style={{ fontFamily: 'var(--f-mono)', fontSize: 12, color: 'var(--muted)' }}>{label}</h2>
                                <p>{text}</p>
                            </section>
                        ))}

                        <section className="cs-block rv">
                            <h2 className="mono" style={{ fontFamily: 'var(--f-mono)', fontSize: 12, color: 'var(--muted)' }}>{t('case.results')}</h2>
                            {project.metrics.length > 0 ? (
                                <div className="metrics">
                                    {project.metrics.map((m) => (
                                        <div key={m.label}>
                                            <b>{m.value}</b>
                                            <small>{m.label}</small>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="notice">{t('case.results_pending')}</p>
                            )}
                        </section>

                        {project.gallery.length > 0 ? (
                            <section className="cs-block rv">
                                <h2 className="mono" style={{ fontFamily: 'var(--f-mono)', fontSize: 12, color: 'var(--muted)' }}>{t('case.gallery')}</h2>
                                <div className="gallery">
                                    {project.gallery.map((image, i) => (
                                        <ResponsiveImg
                                            key={image.url}
                                            image={image}
                                            alt={t('case.gallery_alt', { title: project.title, n: i + 1 })}
                                            sizes="(min-width: 1100px) 380px, (min-width: 640px) 45vw, 100vw"
                                        />
                                    ))}
                                </div>
                            </section>
                        ) : null}
                    </div>

                    <aside>
                        <div>
                            <span className="kicker mono">{isDesign ? t('case.deliverables') : t('case.stack')}</span>
                            <div className="tags">
                                {project.stack.map((s) => <span className="tag" key={s}>{s}</span>)}
                            </div>
                        </div>
                        {project.tools.length > 0 ? (
                            <div>
                                <span className="kicker mono">{t('case.tools')}</span>
                                <div className="tags">
                                    {project.tools.map((tool) => <span className="tag" key={tool}>{tool}</span>)}
                                </div>
                            </div>
                        ) : null}
                        <div className="links">
                            {project.repository_url ? (
                                <a className="btn btn-ghost btn-sm" href={project.repository_url} target="_blank" rel="noopener noreferrer">
                                    {t('case.repository')} <ArrowUpRight />
                                </a>
                            ) : null}
                            {project.live_url ? (
                                <a className="btn btn-primary btn-sm" href={project.live_url} target="_blank" rel="noopener noreferrer">
                                    {t('case.live')} <ArrowUpRight />
                                </a>
                            ) : null}
                            {!project.repository_url && !project.live_url ? (
                                <Placeholder label={isDesign ? t('case.showcase_soon') : t('case.demo_on_request')} />
                            ) : null}
                        </div>
                    </aside>
                </div>

                {next ? (
                    <Link className="next rv" href={next.url} prefetch onFocus={prefetchNext} style={{ marginBottom: 'clamp(48px,8vw,96px)' }}>
                        <div>
                            <span className="kicker mono">{t('case.next')}</span>
                            <h2>{next.title}</h2>
                        </div>
                        <ArrowRight width={40} height={40} />
                    </Link>
                ) : null}
            </article>
        </SiteLayout>
    );
}
