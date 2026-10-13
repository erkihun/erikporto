import { Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState, type ComponentType, type ReactNode } from 'react';

import { usePrefetchOnFocus } from '@/hooks/usePrefetchOnFocus';
import { useTheme } from '@/hooks/useTheme';
import { useTrans } from '@/hooks/useTrans';
import { formatEthiopian } from '@/lib/ethiopian-calendar';
import type { TransKey } from '@/types/inertia';

import { Monogram, Search, Sun } from '../ui/icons';
import { Toast } from '../ui/Toast';

const SECTIONS: readonly (readonly [string, TransKey])[] = [
    ['work', 'nav.work'],
    ['capabilities', 'nav.capabilities'],
    ['experience', 'nav.experience'],
    ['about', 'nav.about'],
    ['contact', 'nav.contact'],
];

interface SiteLayoutProps {
    ownerName: string;
    /** On the home page section links are in-page anchors; elsewhere they point back home. */
    isHome?: boolean;
    /** Hide the section links and "Hire me" on pages where those sections don't exist (coming soon). */
    sections?: boolean;
    children: ReactNode;
}

export function SiteLayout({ ownerName, isHome = false, sections = true, children }: SiteLayoutProps) {
    const { flash, hasNotes, hasNow, homeSections, locale, locales, urls } = usePage().props;
    const t = useTrans();
    const { toggle } = useTheme();
    const prefetchHome = usePrefetchOnFocus(urls.home);
    const prefetchNotes = usePrefetchOnFocus('/notes');
    const prefetchNow = usePrefetchOnFocus('/now');
    const [scrolled, setScrolled] = useState(false);
    const [active, setActive] = useState<string | null>(null);
    const [toast, setToast] = useState<string | null>(null);
    const [today, setToday] = useState<{ greg: string; eth: string } | null>(null);
    const firstName = ownerName.split(' ')[0] ?? ownerName;
    const href = (id: string) => (isHome ? `#${id}` : `${urls.home}#${id}`);
    const links = SECTIONS.filter(([id]) => homeSections.includes(id));

    useEffect(() => setToast(flash.success ?? flash.error), [flash.success, flash.error]);
    const clearToast = useCallback(() => setToast(null), []);

    // ⌘K / Ctrl+K: the command palette is its own chunk, loaded on first use.
    const [Palette, setPalette] = useState<ComponentType<{ onClose: () => void }> | null>(null);
    const [paletteOpen, setPaletteOpen] = useState(false);
    const openPalette = useCallback(() => {
        void import('../palette/CommandPalette').then((module) => {
            setPalette(() => module.default);
            setPaletteOpen(true);
        });
    }, []);
    const closePalette = useCallback(() => setPaletteOpen(false), []);
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                openPalette();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [openPalette]);

    // Dates are client-only so SSR markup never disagrees with the visitor's local day.
    useEffect(() => {
        const now = new Date();
        const greg = now.toLocaleDateString(locale === 'am' ? 'am-ET' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        setToday({ greg, eth: formatEthiopian(now) });
    }, [locale]);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        const onPointer = (e: PointerEvent) => {
            document.documentElement.style.setProperty('--mx', `${e.clientX}px`);
            document.documentElement.style.setProperty('--my', `${e.clientY}px`);
        };
        window.addEventListener('pointermove', onPointer, { passive: true });

        let observer: IntersectionObserver | null = null;
        if (isHome) {
            observer = new IntersectionObserver(
                (entries) => entries.forEach((en) => en.isIntersecting && setActive(en.target.id)),
                { rootMargin: '-45% 0px -50% 0px' },
            );
            SECTIONS.forEach(([id]) => {
                const el = document.getElementById(id);
                if (el) observer?.observe(el);
            });
        }

        return () => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('pointermove', onPointer);
            observer?.disconnect();
        };
    }, [isHome]);

    return (
        <>
            <a className="skip" href="#main">{t('nav.skip')}</a>
            <div className="progress" aria-hidden="true" />
            <div className="spot" aria-hidden="true" />

            <header className={`nav ${scrolled ? 'scrolled' : ''}`}>
                <div className="wrap nav-in">
                    <Link className="brand" href={urls.home} prefetch onFocus={prefetchHome} aria-label={t('nav.home_label', { name: ownerName })}>
                        <Monogram />
                        <span>{firstName}</span>
                    </Link>
                    {sections ? (
                        <nav className="nav-links" aria-label={t('nav.sections')}>
                            {links.map(([id, label]) => (
                                <a key={id} href={href(id)} className={active === id ? 'active' : undefined}>
                                    {t(label)}
                                </a>
                            ))}
                            {hasNotes ? <Link href="/notes" prefetch onFocus={prefetchNotes}>{t('nav.notes')}</Link> : null}
                        </nav>
                    ) : null}
                    {/* Full page loads: <html lang>, fonts and cached props all switch with the language. */}
                    <nav className="lang" aria-label={t('nav.language')} style={sections ? undefined : { marginLeft: 'auto' }}>
                        {locales.map((option) => (
                            <a
                                key={option.code}
                                className="chip"
                                href={option.url}
                                hrefLang={option.hreflang}
                                lang={option.code}
                                title={option.name}
                                aria-current={option.current ? 'page' : undefined}
                            >
                                {option.label}
                            </a>
                        ))}
                    </nav>
                    <button className="icon-btn palette-btn" type="button" onClick={openPalette} aria-label={t('palette.open')} aria-keyshortcuts="Control+K Meta+K" title="⌘K">
                        <Search />
                    </button>
                    <button className="icon-btn" type="button" onClick={toggle} aria-label={t('nav.theme')}>
                        <Sun />
                    </button>
                    {sections ? (
                        <a className="btn btn-primary btn-sm" href={href('contact')}>
                            {t('nav.hire')}
                        </a>
                    ) : null}
                </div>
            </header>

            <main id="main">{children}</main>

            <footer className="wrap">
                <div className="foot">
                    <span>
                        © {new Date().getFullYear()} {ownerName}
                    </span>
                    <span className="mono">
                        {today ? (
                            locale === 'am' ? (
                                <>
                                    <span className="eth">{today.eth}</span> · {today.greg}
                                </>
                            ) : (
                                <>
                                    {today.greg} · <span className="eth">{today.eth}</span>
                                </>
                            )
                        ) : null}
                    </span>
                    <span className="mono" style={{ display: 'flex', gap: 18 }}>
                        {hasNow ? (
                            <Link href="/now" prefetch onFocus={prefetchNow} style={{ textDecoration: 'none' }}>
                                {t('footer.now')}
                            </Link>
                        ) : null}
                        <Link href={urls.colophon} prefetch style={{ textDecoration: 'none' }}>
                            {t('footer.colophon')}
                        </Link>
                        {hasNotes ? (
                            <a href="/feed.xml" style={{ textDecoration: 'none' }}>
                                {t('footer.rss')}
                            </a>
                        ) : null}
                        <a href="#main" style={{ textDecoration: 'none' }}>
                            {t('footer.back_to_top')}
                        </a>
                    </span>
                </div>
            </footer>

            <Toast message={toast} onDone={clearToast} />
            {paletteOpen && Palette ? <Palette onClose={closePalette} /> : null}
        </>
    );
}
