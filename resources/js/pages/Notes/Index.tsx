import { Link } from '@inertiajs/react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { NoteCard } from '@/components/notes/NoteCard';
import { ArrowLeft, ArrowRight } from '@/components/ui/icons';
import { Seo } from '@/components/ui/Seo';
import type { NotesIndexProps } from '@/types/portfolio';

export default function NotesIndex({ notes, pagination, owner }: NotesIndexProps) {
    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title="Notes" description="Short, practical write-ups on Laravel, Filament, Amharic interfaces and the Ethiopian calendar." />
            <div className="wrap page-enter">
                <header className="cs-hero">
                    <span className="kicker mono">Notes · RSS available</span>
                    <h1>Notes</h1>
                    <p className="sub">Short, practical write-ups from the systems I build.</p>
                    <div>
                        <a className="btn btn-ghost btn-sm" href="/feed.xml">Subscribe via RSS</a>
                    </div>
                </header>

                {notes.length > 0 ? (
                    <div className="note-list" style={{ marginBottom: 48 }}>
                        {notes.map((n) => (
                            <NoteCard key={n.slug} note={n} />
                        ))}
                    </div>
                ) : (
                    <p className="notice" style={{ marginBottom: 96 }}>The first notes are being written. Subscribe to the feed to get them when they’re out.</p>
                )}

                {pagination.prev || pagination.next ? (
                    <nav className="pager" aria-label="Pagination">
                        {pagination.prev ? (
                            <Link className="btn btn-ghost btn-sm" href={pagination.prev} rel="prev">
                                <ArrowLeft /> Newer
                            </Link>
                        ) : <span />}
                        {pagination.next ? (
                            <Link className="btn btn-ghost btn-sm" href={pagination.next} rel="next">
                                Older <ArrowRight />
                            </Link>
                        ) : null}
                    </nav>
                ) : null}
            </div>
        </SiteLayout>
    );
}
