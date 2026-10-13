import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { ArrowLeft } from '@/components/ui/icons';
import { Seo } from '@/components/ui/Seo';
import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import { formatDualDate } from '@/lib/format';
import type { NoteShowProps, NoteSummary } from '@/types/portfolio';

export default function NoteShow({ note, older, newer, owner }: NoteShowProps) {
    const date = formatDualDate(note.published_at);
    const activeId = useActiveHeading(note.toc.map((t) => t.id));
    const prefetchNotes = usePrefetchOnFocus('/notes');

    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title={note.title} description={note.excerpt} image={note.cover_url} type="article" />

            <article className="wrap page-enter">
                <header className="cs-hero note-hero">
                    <Link className="back mono" href="/notes" prefetch onFocus={prefetchNotes}>
                        <ArrowLeft width={14} height={14} /> All notes
                    </Link>
                    <span className="kicker mono">
                        <time dateTime={note.published_at}>{date.gregorian}</time> · <span className="eth">{date.ethiopian}</span> · {note.reading_minutes} min read
                    </span>
                    <h1>{note.title}</h1>
                    <p className="sub">{note.excerpt}</p>
                </header>

                {note.cover_url ? (
                    <div className="cs-cover" style={{ marginBottom: 24 }}>
                        <img src={note.cover_url} alt="" />
                    </div>
                ) : null}

                <div className={note.toc.length > 1 ? 'note-layout' : 'note-layout single'}>
                    {/* body_html is generated server-side from Markdown with raw HTML escaped. */}
                    <div className="prose" dangerouslySetInnerHTML={{ __html: note.body_html }} />

                    {note.toc.length > 1 ? (
                        <nav className="toc" aria-label="On this page">
                            <span className="kicker mono">On this page</span>
                            <ol>
                                {note.toc.map((t) => (
                                    <li key={t.id} className={`lvl-${t.level} ${activeId === t.id ? 'active' : ''}`}>
                                        <a href={`#${t.id}`}>{t.text}</a>
                                    </li>
                                ))}
                            </ol>
                        </nav>
                    ) : null}
                </div>

                {note.tags.length > 0 ? (
                    <div className="tags" style={{ marginBottom: 40 }}>
                        {note.tags.map((t) => (
                            <span className="tag" key={t}>{t}</span>
                        ))}
                    </div>
                ) : null}

                <nav className="note-pager" aria-label="More notes">
                    <PagerLink label="Newer" note={newer} />
                    <PagerLink label="Older" note={older} align="end" />
                </nav>
            </article>
        </SiteLayout>
    );
}

function PagerLink({ label, note, align = 'start' }: { label: string; note: NoteSummary | null; align?: 'start' | 'end' }) {
    const prefetchOnFocus = usePrefetchOnFocus(note?.url ?? '/notes');

    if (!note) return <span />;
    return (
        <Link href={note.url} prefetch onFocus={prefetchOnFocus} className="note-pager-link" style={{ textAlign: align }}>
            <span className="kicker mono">{label}</span>
            <span className="note-title">{note.title}</span>
        </Link>
    );
}

/** Highlights the table-of-contents entry for the heading currently near the top of the viewport. */
function useActiveHeading(ids: string[]): string | null {
    const [active, setActive] = useState<string | null>(null);
    const key = ids.join('|');

    useEffect(() => {
        if (ids.length < 2) return;
        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                if (visible[0]) setActive(visible[0].target.id);
            },
            { rootMargin: '-80px 0px -70% 0px' },
        );
        ids.forEach((id) => {
            const el = document.getElementById(id);
            if (el) observer.observe(el);
        });
        return () => observer.disconnect();
    }, [key]);

    return active;
}
