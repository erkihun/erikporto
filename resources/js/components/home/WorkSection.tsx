import { useState } from 'react';

import { useTrans } from '@/hooks/useTrans';
import type { TransKey } from '@/types/inertia';
import type { ProjectCategory, ProjectSummary } from '@/types/portfolio';

import { ProjectCard } from './ProjectCard';
import { SectionHeading } from './SectionHeading';

type Filter = 'all' | ProjectCategory;
const FILTERS: [Filter, TransKey][] = [['all', 'work.filter_all'], ['software', 'work.filter_software'], ['design', 'work.filter_design']];

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
                {t('work.intro')}
            </SectionHeading>
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
        </section>
    );
}
