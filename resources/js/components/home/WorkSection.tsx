import { useState } from 'react';

import type { ProjectCategory, ProjectSummary } from '@/types/portfolio';

import { ProjectCard } from './ProjectCard';
import { SectionHeading } from './SectionHeading';

type Filter = 'all' | ProjectCategory;
const FILTERS: [Filter, string][] = [['all', 'All'], ['software', 'Software'], ['design', 'Design']];

export function WorkSection({ projects }: { projects: ProjectSummary[] }) {
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
            <SectionHeading kicker="Selected work" title="Systems that run institutions. Identities that represent them.">
                Each project is written as a case study: the problem, my role, how it was solved, and what changed.
            </SectionHeading>
            <div className="filters" role="group" aria-label="Filter projects">
                {FILTERS.filter(([f]) => count(f) > 0).map(([f, label]) => (
                    <button key={f} type="button" className="chip" aria-pressed={filter === f} onClick={() => choose(f)}>
                        {label}
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
