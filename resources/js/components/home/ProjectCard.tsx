import { Link } from '@inertiajs/react';
import { useRef, type PointerEvent } from 'react';

import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import { useTrans } from '@/hooks/useTrans';
import type { ProjectSummary } from '@/types/portfolio';

import { ArrowUpRight } from '../ui/icons';
import { ProjectVisual } from './ProjectMock';

export function ProjectCard({ project, hidden }: { project: ProjectSummary; hidden: boolean }) {
    const ref = useRef<HTMLAnchorElement>(null);
    const prefetchOnFocus = usePrefetchOnFocus(project.url);
    const t = useTrans();

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
            prefetch
            onFocus={prefetchOnFocus}
            className={`card rv ${hidden ? 'hide' : ''}`}
            onPointerMove={onMove}
            onPointerLeave={onLeave}
        >
            <span className="shot">
                <ProjectVisual
                    cover={project.cover}
                    mock={project.mock}
                    alt={t('work.screenshot_alt', { title: project.title })}
                    sizes="(min-width: 1180px) 560px, (min-width: 760px) 50vw, 100vw"
                />
            </span>
            <span className="open">
                <ArrowUpRight />
            </span>
            <span className="meta">
                <span className="row mono">
                    <span>{project.kind}</span>
                    <span>{project.year ?? t('work.case_study')}</span>
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
