import { Link } from '@inertiajs/react';

import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import { useTrans } from '@/hooks/useTrans';
import type { NoteSummary } from '@/types/portfolio';

import { NoteCard } from '../notes/NoteCard';
import { ArrowRight } from '../ui/icons';
import { SectionHeading } from './SectionHeading';

export function LatestNotes({ notes }: { notes: NoteSummary[] }) {
    const t = useTrans();
    const prefetchNotes = usePrefetchOnFocus('/notes');

    if (notes.length === 0) return null;

    return (
        <section className="block wrap" id="notes">
            <SectionHeading kicker={t('notes.kicker')} title={t('notes.home_title')}>
                {t('notes.home_intro')}
            </SectionHeading>
            <div className="note-list">
                {notes.map((n) => (
                    <NoteCard key={n.slug} note={n} />
                ))}
            </div>
            <div style={{ marginTop: 28 }}>
                <Link className="btn btn-ghost btn-sm" href="/notes" prefetch onFocus={prefetchNotes}>
                    {t('notes.all')} <ArrowRight />
                </Link>
            </div>
        </section>
    );
}
