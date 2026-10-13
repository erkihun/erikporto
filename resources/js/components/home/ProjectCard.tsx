import { Link } from '@inertiajs/react';
import { useRef, type PointerEvent } from 'react';

import type { ProjectSummary } from '@/types/portfolio';

import { ArrowUpRight } from '../ui/icons';
import { ProjectVisual } from './ProjectMock';

export function ProjectCard({ project, hidden }: { project: ProjectSummary; hidden: boolean }) {
    const ref = useRef<HTMLAnchorElement>(null);

    const onMove = (e: PointerEvent<HTMLAnchorElement>) => {
        const el = ref.current;
        if (!el || e.pointerType !== 'mouse') return;
        const r = el.getBoundingClientRect();
        el.style.setProperty('--ry', `${((e.clientX - r.left) / r.width - 0.5) * 6}deg`);
        el.style.setProperty('--rx', `${(0.5 - (e.clientY - r.top) / r.height) * 6}deg`);
    };
    const onLeave = () => {
        ref.current?.style.setProperty('--rx', '0deg');
        ref.current?.style.setProperty('--ry', '0deg');
    };
    const extra = project.stack.length - 4;

    return (
        <Link
            ref={ref}
            href={project.url}
            className={`card rv ${hidden ? 'hide' : ''}`}
            onPointerMove={onMove}
            onPointerLeave={onLeave}
            aria-label={`Case study: ${project.title}`}
        >
            <span className="shot">
                <ProjectVisual coverUrl={project.cover_url} mock={project.mock} alt={`${project.title} screenshot`} />
            </span>
            <span className="open">
                <ArrowUpRight />
            </span>
            <span className="meta">
                <span className="row mono">
                    <span>{project.kind}</span>
                    <span>{project.year ?? 'Case study'}</span>
                </span>
                <h3>{project.title}</h3>
                <p>{project.summary}</p>
                <span className="tags">
                    {project.stack.slice(0, 4).map((s) => (
                        <span className="tag" key={s}>{s}</span>
                    ))}
                    {extra > 0 ? <span className="tag">+{extra}</span> : null}
                </span>
            </span>
        </Link>
    );
}
