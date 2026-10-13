import { Link } from '@inertiajs/react';

import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import type { NoteSummary } from '@/types/portfolio';

import { NoteCard } from '../notes/NoteCard';
import { ArrowRight } from '../ui/icons';
import { SectionHeading } from './SectionHeading';

export function LatestNotes({ notes }: { notes: NoteSummary[] }) {
    const prefetchNotes = usePrefetchOnFocus('/notes');

    if (notes.length === 0) return null;

    return (
        <section className="block wrap" id="notes">
            <SectionHeading kicker="Notes" title="Things I learned building them.">
                Short, practical write-ups from real projects: Laravel, Filament, Amharic interfaces and the Ethiopian calendar.
            </SectionHeading>
            <div className="note-list">
                {notes.map((n) => (
                    <NoteCard key={n.slug} note={n} />
                ))}
            </div>
            <div style={{ marginTop: 28 }}>
                <Link className="btn btn-ghost btn-sm" href="/notes" prefetch onFocus={prefetchNotes}>
                    All notes <ArrowRight />
                </Link>
            </div>
        </section>
    );
}
