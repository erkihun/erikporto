import { Link } from '@inertiajs/react';

import { ProjectVisual } from '@/components/home/ProjectMock';
import { SiteLayout } from '@/components/layout/SiteLayout';
import { ArrowLeft, ArrowRight, ArrowUpRight } from '@/components/ui/icons';
import { Placeholder } from '@/components/ui/Placeholder';
import { Seo } from '@/components/ui/Seo';
import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import type { ProjectPageProps } from '@/types/portfolio';

export default function ProjectShow({ project, position, next, owner }: ProjectPageProps) {
    const isDesign = project.category === 'design';
    const prefetchHome = usePrefetchOnFocus('/');
    const prefetchNext = usePrefetchOnFocus(next?.url ?? '/');
    const sections: [string, string][] = [
        ['Problem', project.problem],
        ['My role', project.role],
        ['Solution', project.solution],
        ['Outcome', project.outcome],
    ];

    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title={project.title} description={project.summary} image={project.cover_url} type="article" />

            <article className="wrap page-enter">
                <header className="cs-hero">
                    <Link className="back mono" href="/#work" prefetch onFocus={prefetchHome}>
                        <ArrowLeft width={14} height={14} /> All work
                    </Link>
                    <span className="kicker mono">
                        {project.kind} · Case study {position.index} / {position.total}
                        {project.year ? ` · ${project.year}` : ''}
                    </span>
                    <h1>{project.title}</h1>
                    {project.subtitle ? <p className="sub">{project.subtitle}</p> : null}
                </header>

                <div className="cs-cover">
                    <ProjectVisual coverUrl={project.cover_url} mock={project.mock} alt={`${project.title} cover`} />
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
                            <h2 className="mono" style={{ fontFamily: 'var(--f-mono)', fontSize: 12, color: 'var(--muted)' }}>Results</h2>
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
                                <p className="notice">Measured results will be added once verified.</p>
                            )}
                        </section>

                        {project.gallery_urls.length > 0 ? (
                            <section className="cs-block rv">
                                <h2 className="mono" style={{ fontFamily: 'var(--f-mono)', fontSize: 12, color: 'var(--muted)' }}>Gallery</h2>
                                <div className="gallery">
                                    {project.gallery_urls.map((src, i) => (
                                        <img key={src} src={src} alt={`${project.title} screen ${i + 1}`} loading="lazy" decoding="async" />
                                    ))}
                                </div>
                            </section>
                        ) : null}
                    </div>

                    <aside>
                        <div>
                            <span className="kicker mono">{isDesign ? 'Deliverables' : 'Stack'}</span>
                            <div className="tags">
                                {project.stack.map((s) => <span className="tag" key={s}>{s}</span>)}
                            </div>
                        </div>
                        {project.tools.length > 0 ? (
                            <div>
                                <span className="kicker mono">Tools</span>
                                <div className="tags">
                                    {project.tools.map((t) => <span className="tag" key={t}>{t}</span>)}
                                </div>
                            </div>
                        ) : null}
                        <div className="links">
                            {project.repository_url ? (
                                <a className="btn btn-ghost btn-sm" href={project.repository_url} target="_blank" rel="noopener noreferrer">
                                    Repository <ArrowUpRight />
                                </a>
                            ) : null}
                            {project.live_url ? (
                                <a className="btn btn-primary btn-sm" href={project.live_url} target="_blank" rel="noopener noreferrer">
                                    Live demo <ArrowUpRight />
                                </a>
                            ) : null}
                            {!project.repository_url && !project.live_url ? (
                                <Placeholder label={isDesign ? 'Showcase link coming soon' : 'Demo available on request'} />
                            ) : null}
                        </div>
                    </aside>
                </div>

                {next ? (
                    <Link className="next rv" href={next.url} prefetch onFocus={prefetchNext} style={{ marginBottom: 'clamp(48px,8vw,96px)' }}>
                        <div>
                            <span className="kicker mono">Next case study</span>
                            <h2>{next.title}</h2>
                        </div>
                        <ArrowRight width={40} height={40} />
                    </Link>
                ) : null}
            </article>
        </SiteLayout>
    );
}
