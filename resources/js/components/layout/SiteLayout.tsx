import { Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState, type ReactNode } from 'react';

import { useTheme } from '@/hooks/useTheme';
import { formatEthiopian } from '@/lib/ethiopian-calendar';

import { Monogram, Sun } from '../ui/icons';
import { Toast } from '../ui/Toast';

const SECTIONS = [
    ['work', 'Work'],
    ['capabilities', 'Capabilities'],
    ['experience', 'Experience'],
    ['about', 'About'],
    ['contact', 'Contact'],
] as const;

interface SiteLayoutProps {
    ownerName: string;
    /** On the home page section links are in-page anchors; elsewhere they point back home. */
    isHome?: boolean;
    /** Hide the section links and "Hire me" on pages where those sections don't exist (coming soon). */
    sections?: boolean;
    children: ReactNode;
}

export function SiteLayout({ ownerName, isHome = false, sections = true, children }: SiteLayoutProps) {
    const { flash } = usePage().props;
    const { toggle } = useTheme();
    const [scrolled, setScrolled] = useState(false);
    const [active, setActive] = useState<string | null>(null);
    const [toast, setToast] = useState<string | null>(null);
    const [today, setToday] = useState<{ greg: string; eth: string } | null>(null);
    const firstName = ownerName.split(' ')[0] ?? ownerName;
    const href = (id: string) => (isHome ? `#${id}` : `/#${id}`);

    useEffect(() => setToast(flash.success), [flash.success]);
    const clearToast = useCallback(() => setToast(null), []);

    // Dates are client-only so SSR markup never disagrees with the visitor's local day.
    useEffect(() => {
        const now = new Date();
        setToday({ greg: now.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }), eth: formatEthiopian(now) });
    }, []);

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
            <a className="skip" href="#main">Skip to content</a>
            <div className="progress" aria-hidden="true" />
            <div className="spot" aria-hidden="true" />

            <header className={`nav ${scrolled ? 'scrolled' : ''}`}>
                <div className="wrap nav-in">
                    <Link className="brand" href="/" aria-label={`${ownerName}, home`}>
                        <Monogram />
                        <span>{firstName}</span>
                    </Link>
                    {sections ? (
                        <nav className="nav-links" aria-label="Sections">
                            {SECTIONS.map(([id, label]) => (
                                <a key={id} href={href(id)} className={active === id ? 'active' : undefined}>
                                    {label}
                                </a>
                            ))}
                        </nav>
                    ) : null}
                    <button className="icon-btn" type="button" onClick={toggle} aria-label="Toggle colour theme" style={sections ? undefined : { marginLeft: 'auto' }}>
                        <Sun />
                    </button>
                    {sections ? (
                        <a className="btn btn-primary btn-sm" href={href('contact')}>
                            Hire me
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
                            <>
                                {today.greg} · <span className="eth">{today.eth}</span>
                            </>
                        ) : null}
                    </span>
                    <a href="#main" className="mono" style={{ textDecoration: 'none' }}>
                        Back to top ↑
                    </a>
                </div>
            </footer>

            <Toast message={toast} onDone={clearToast} />
        </>
    );
}
