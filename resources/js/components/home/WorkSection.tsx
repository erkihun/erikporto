import { useState } from 'react';

import { useTrans } from '@/hooks/useTrans';
import type { TransKey } from '@/types/inertia';
import type { ProjectCategory, ProjectMockKind, ProjectSummary } from '@/types/portfolio';

import { ProjectCard } from './ProjectCard';
import { ProjectMock } from './ProjectMock';
import { SectionHeading } from './SectionHeading';

type Filter = 'all' | ProjectCategory;
const FILTERS: [Filter, TransKey][] = [['all', 'work.filter_all'], ['software', 'work.filter_software'], ['design', 'work.filter_design']];

/** Shown before the first case study is published: one software and one design card, both clearly in preparation. */
const PLACEHOLDERS: [ProjectMockKind, TransKey][] = [['id', 'work.empty_software'], ['brand', 'work.empty_design']];

export function WorkSection({ projects }: { projects: ProjectSummary[] }) {
    const t = useTrans();
    const [filter, setFilter] = useState<Filter>('all');
    const count = (f: Filter) => (f === 'all' ? projects.length : projects.filter((p) => p.category === f).length);

    const choose = (next: Filter) => {
        const apply = () => setFilter(next);
        if ('startViewTransition' in document && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.startViewTransition(apply);
        } else {
            apply();
        }
    };

    return (
        <section className="block wrap" id="work">
            <SectionHeading kicker={t('work.kicker')} title={t('work.title')}>
                {projects.length > 0 ? t('work.intro') : t('work.empty_intro')}
            </SectionHeading>
            {projects.length === 0 ? (
                <div className="work">
                    {PLACEHOLDERS.map(([mock, title]) => (
                        <div key={mock} className="card placeholder rv">
                            <span className="shot">
                                <ProjectMock kind={mock} />
                            </span>
                            <span className="meta">
                                <span className="row mono">
                                    <span>{t(title)}</span>
                                    <span>{t('work.empty_status')}</span>
                                </span>
                                <p>{t('work.empty_text')}</p>
                            </span>
                        </div>
                    ))}
                </div>
            ) : (
                <>
                    <div className="filters" role="group" aria-label={t('work.filter_label')}>
                        {FILTERS.filter(([f]) => count(f) > 0).map(([f, label]) => (
                            <button key={f} type="button" className="chip" aria-pressed={filter === f} onClick={() => choose(f)}>
                                {t(label)}
                                <span className="n">{count(f)}</span>
                            </button>
                        ))}
                    </div>
                    <div className="work">
                        {projects.map((p) => (
                            <ProjectCard key={p.slug} project={p} hidden={filter !== 'all' && p.category !== filter} />
                        ))}
                    </div>
                </>
            )}
        </section>
    );
}
