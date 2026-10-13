import { Link } from '@inertiajs/react';

import { SiteLayout } from '@/components/layout/SiteLayout';
import { NoteCard } from '@/components/notes/NoteCard';
import { ArrowLeft, ArrowRight } from '@/components/ui/icons';
import { Seo } from '@/components/ui/Seo';
import { useTrans } from '@/hooks/useTrans';
import type { NotesIndexProps } from '@/types/portfolio';

export default function NotesIndex({ notes, pagination, owner }: NotesIndexProps) {
    const t = useTrans();

    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title={t('notes.index_title')} description={t('notes.description')} />
            <div className="wrap page-enter">
                <header className="cs-hero">
                    <span className="kicker mono">{t('notes.index_kicker')}</span>
                    <h1>{t('notes.index_title')}</h1>
                    <p className="sub">{t('notes.index_intro')}</p>
                    <div>
                        <a className="btn btn-ghost btn-sm" href="/feed.xml">{t('notes.subscribe')}</a>
                    </div>
                </header>

                {notes.length > 0 ? (
                    <div className="note-list" style={{ marginBottom: 48 }}>
                        {notes.map((n) => (
                            <NoteCard key={n.slug} note={n} />
                        ))}
                    </div>
                ) : (
                    <p className="notice" style={{ marginBottom: 96 }}>{t('notes.empty')}</p>
                )}

                {pagination.prev || pagination.next ? (
                    <nav className="pager" aria-label={t('notes.pagination')}>
                        {pagination.prev ? (
                            <Link className="btn btn-ghost btn-sm" href={pagination.prev} rel="prev">
                                <ArrowLeft /> {t('notes.newer')}
                            </Link>
                        ) : <span />}
                        {pagination.next ? (
                            <Link className="btn btn-ghost btn-sm" href={pagination.next} rel="next">
                                {t('notes.older')} <ArrowRight />
                            </Link>
                        ) : null}
                    </nav>
                ) : null}
            </div>
        </SiteLayout>
    );
}
