import { Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import { useTrans } from '@/hooks/useTrans';
import type { TransKey } from '@/types/inertia';

import { Close } from '../ui/icons';

interface MobileMenuProps {
    /** Section links, already filtered to the ones the home page actually renders. */
    sections: readonly (readonly [string, TransKey])[];
    /** Builds a section's href: an in-page anchor on the home page, a link home elsewhere. */
    href: (id: string) => string;
    hasNotes: boolean;
    onClose: () => void;
}

/**
 * The phone navigation (its own chunk, loaded on first open). The panel reuses the prototype's
 * sheet styling, so it slides in from the right like the project sheet was meant to.
 *
 * The dialog is opened with showModal() for the focus trap and backdrop, but closing always
 * goes through onClose() rather than the element's own "close" event: under preact/compat that
 * event does not reach us, which previously left the trigger stuck at aria-expanded="true" and
 * the menu impossible to reopen. Escape and backdrop clicks are therefore handled here.
 */
export default function MobileMenu({ sections, href, hasNotes, onClose }: MobileMenuProps) {
    const t = useTrans();
    const dialog = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const element = dialog.current;
        if (!element) return;

        if (!element.open) element.showModal();

        // cancel fires on Escape; preventing it keeps the element and React in step.
        const onCancel = (event: Event) => {
            event.preventDefault();
            onClose();
        };
        element.addEventListener('cancel', onCancel);

        return () => {
            element.removeEventListener('cancel', onCancel);
            if (element.open) element.close();
        };
    }, [onClose]);

    // A click landing on the dialog itself (not its contents) is a click on the backdrop.
    const onBackdrop = (event: { target: EventTarget | null }) => {
        if (event.target === dialog.current) onClose();
    };

    return (
        <dialog ref={dialog} className="menu-sheet" aria-label={t('nav.sections')} onClick={onBackdrop}>
            <div className="menu-top">
                <span className="mono">{t('nav.sections')}</span>
                <button className="icon-btn" type="button" onClick={onClose} aria-label={t('nav.close_menu')}>
                    <Close />
                </button>
            </div>
            <nav className="menu-links">
                {sections.map(([id, label]) => (
                    <a key={id} href={href(id)} onClick={onClose}>
                        {t(label)}
                    </a>
                ))}
                {hasNotes ? (
                    <Link href="/notes" onClick={onClose}>
                        {t('nav.notes')}
                    </Link>
                ) : null}
            </nav>
        </dialog>
    );
}
