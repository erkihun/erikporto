import { SiteLayout } from '@/components/layout/SiteLayout';
import { Seo } from '@/components/ui/Seo';
import { formatDate, formatDualDate } from '@/lib/format';
import type { NowPageProps, NowUpdate } from '@/types/portfolio';

export default function Now({ current, previous, owner }: NowPageProps) {
    const updated = formatDualDate(current.published_at);

    return (
        <SiteLayout ownerName={owner.name ?? 'Portfolio'}>
            <Seo title="Now" description={current.summary} />

            <article className="wrap page-enter">
                <header className="cs-hero">
                    <span className="kicker mono">
                        Last updated <time dateTime={current.published_at}>{updated.gregorian}</time> · <span className="eth">{updated.ethiopian}</span>
                    </span>
                    <h1>Now</h1>
                </header>

                <div className="note-layout single">
                    {/* body_html is generated server-side by MarkdownRenderer with raw HTML escaped. */}
                    <div className="prose" dangerouslySetInnerHTML={{ __html: current.body_html }} />
                </div>

                <p style={{ color: 'var(--muted)', fontSize: 14 }}>
                    This is a{' '}
                    <a href="https://nownownow.com/about" target="_blank" rel="noopener noreferrer">
                        now page
                    </a>
                    : what I’m focused on at this point in my life, updated about once a month.
                </p>

                {previous.length > 0 ? (
                    <section className="now-history" aria-labelledby="previous-updates">
                        <h2 id="previous-updates" className="kicker mono">Previous updates</h2>
                        <div className="note-list">
                            {previous.map((update) => (
                                <PreviousUpdate key={update.id} update={update} />
                            ))}
                        </div>
                    </section>
                ) : (
                    <div style={{ marginBottom: 'clamp(48px,8vw,96px)' }} />
                )}
            </article>
        </SiteLayout>
    );
}

function PreviousUpdate({ update }: { update: NowUpdate }) {
    return (
        <details>
            <summary>
                <span className="mono note-date">
                    <time dateTime={update.published_at}>{formatDate(update.published_at)}</time>
                </span>
                <span className="note-excerpt">{update.summary}</span>
            </summary>
            {/* body_html is generated server-side by MarkdownRenderer with raw HTML escaped. */}
            <div className="prose" dangerouslySetInnerHTML={{ __html: update.body_html }} />
        </details>
    );
}
