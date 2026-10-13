import { Link } from '@inertiajs/react';

import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import { useTrans } from '@/hooks/useTrans';
import { formatDate } from '@/lib/format';
import type { NoteSummary } from '@/types/portfolio';

import { ArrowUpRight } from '../ui/icons';

export function NoteCard({ note }: { note: NoteSummary }) {
    const t = useTrans();
    const prefetchOnFocus = usePrefetchOnFocus(note.url);

    return (
        <Link href={note.url} prefetch onFocus={prefetchOnFocus} className="note-row rv">
            <span className="mono note-date">
                <time dateTime={note.published_at}>{formatDate(note.published_at)}</time>
            </span>
            <span className="note-main">
                <span className="note-title">{note.title}</span>
                <span className="note-excerpt">{note.excerpt}</span>
                {note.tags.length > 0 ? (
                    <span className="tags">
                        {note.tags.map((tag) => (
                            <span className="tag" key={tag}>{tag}</span>
                        ))}
                    </span>
                ) : null}
            </span>
            <span className="mono note-meta">
                {t('notes.min', { n: note.reading_minutes })} <ArrowUpRight width={14} height={14} />
            </span>
        </Link>
    );
}
