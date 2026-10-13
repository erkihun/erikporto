import { router, usePage } from '@inertiajs/react';
import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent, type MouseEvent } from 'react';

import { useTheme } from '@/hooks/useTheme';
import { useTrans } from '@/hooks/useTrans';
import type { TransKey } from '@/types/inertia';
import type { PaletteData } from '@/types/portfolio';

interface Command {
    id: string;
    group: TransKey;
    label: string;
    hint?: string;
    run: () => void;
}

const SECTION_LABELS: Record<string, TransKey> = {
    work: 'nav.work',
    capabilities: 'nav.capabilities',
    experience: 'nav.experience',
    about: 'nav.about',
    contact: 'nav.contact',
};

/** Case studies and notes, kept per language for the rest of the visit. */
const loaded = new Map<string, PaletteData>();

const normalize = (text: string) => text.normalize('NFKD').replace(/\p{M}/gu, '').toLowerCase();

/**
 * ⌘K / Ctrl+K command palette (loaded on first use by SiteLayout). A modal <dialog> gives the
 * focus trap, Escape and focus return; inside, a combobox drives a grouped listbox through
 * aria-activedescendant, and a polite live region announces the number of results.
 */
export default function CommandPalette({ onClose }: { onClose: () => void }) {
    const t = useTrans();
    const { toggle } = useTheme();
    const { urls, homeSections, hasNotes, hasNow, locales } = usePage().props;
    const dialog = useRef<HTMLDialogElement>(null);
    const input = useRef<HTMLInputElement>(null);
    const id = useId();
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const [data, setData] = useState<PaletteData | null>(loaded.get(urls.palette) ?? null);

    useEffect(() => {
        const el = dialog.current;
        if (el && !el.open) el.showModal();
        input.current?.focus();
    }, []);

    useEffect(() => {
        if (data) return;
        let cancelled = false;
        fetch(urls.palette, { headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? (response.json() as Promise<PaletteData>) : Promise.reject(new Error(String(response.status)))))
            .then((result) => {
                loaded.set(urls.palette, result);
                if (!cancelled) setData(result);
            })
            .catch(() => {
                if (!cancelled) setData({ projects: [], notes: [] });
            });
        return () => {
            cancelled = true;
        };
    }, [data, urls.palette]);

    const commands = useMemo<Command[]>(() => {
        const close = () => dialog.current?.close();
        const go = (url: string) => {
            close();
            router.visit(url);
        };
        const section = (anchor: string) => {
            close();
            if (window.location.pathname === urls.home && document.getElementById(anchor)) {
                window.location.hash = anchor;
            } else {
                router.visit(`${urls.home}#${anchor}`);
            }
        };

        const list: Command[] = [{ id: 'home', group: 'palette.go', label: t('palette.home'), run: () => go(urls.home) }];
        for (const anchor of homeSections) {
            const label = SECTION_LABELS[anchor];
            if (label) list.push({ id: `section-${anchor}`, group: 'palette.go', label: t(label), run: () => section(anchor) });
        }
        if (hasNotes) list.push({ id: 'notes', group: 'palette.go', label: t('nav.notes'), run: () => go('/notes') });
        if (hasNow) list.push({ id: 'now', group: 'palette.go', label: t('footer.now'), run: () => go('/now') });
        list.push({ id: 'colophon', group: 'palette.go', label: t('footer.colophon'), run: () => go(urls.colophon) });

        for (const project of data?.projects ?? []) {
            list.push({ id: `project-${project.url}`, group: 'palette.work', label: project.title, hint: project.kind, run: () => go(project.url) });
        }
        for (const note of data?.notes ?? []) {
            list.push({ id: `note-${note.url}`, group: 'palette.notes', label: note.title, run: () => go(note.url) });
        }

        list.push({
            id: 'theme',
            group: 'palette.actions',
            label: t('palette.theme'),
            run: () => {
                close();
                toggle();
            },
        });
        for (const locale of locales) {
            if (locale.current) continue;
            list.push({
                id: `language-${locale.code}`,
                group: 'palette.actions',
                label: t('palette.language', { language: locale.name }),
                // A full page load: <html lang>, fonts and cached props all change with the language.
                run: () => window.location.assign(locale.url),
            });
        }
        list.push({
            id: 'top',
            group: 'palette.actions',
            label: t('palette.top'),
            run: () => {
                close();
                window.scrollTo({ top: 0 });
            },
        });

        return list;
    }, [data, hasNotes, hasNow, homeSections, locales, t, toggle, urls]);

    const results = useMemo(() => {
        const words = normalize(query).split(/\s+/).filter(Boolean);
        if (words.length === 0) return commands;
        return commands.filter((command) => {
            const haystack = normalize(`${command.label} ${command.hint ?? ''} ${t(command.group)}`);
            return words.every((word) => haystack.includes(word));
        });
    }, [commands, query, t]);

    const groups = useMemo(() => {
        const grouped: { group: TransKey; items: { command: Command; index: number }[] }[] = [];
        results.forEach((command, index) => {
            const last = grouped[grouped.length - 1];
            if (last && last.group === command.group) last.items.push({ command, index });
            else grouped.push({ group: command.group, items: [{ command, index }] });
        });
        return grouped;
    }, [results]);

    const optionId = (index: number) => `${id}-option-${index}`;
    const current = Math.min(active, Math.max(results.length - 1, 0));

    useEffect(() => setActive(0), [query]);
    useEffect(() => {
        document.getElementById(optionId(current))?.scrollIntoView({ block: 'nearest' });
    });

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        const last = results.length - 1;
        if (event.key === 'ArrowDown') setActive(current >= last ? 0 : current + 1);
        else if (event.key === 'ArrowUp') setActive(current <= 0 ? last : current - 1);
        else if (event.key === 'Home' && event.ctrlKey) setActive(0);
        else if (event.key === 'End' && event.ctrlKey) setActive(last);
        else if (event.key === 'Enter') results[current]?.run();
        else return;
        event.preventDefault();
    };

    // A click on the backdrop lands on the <dialog> itself.
    const onDialogClick = (event: MouseEvent<HTMLDialogElement>) => {
        if (event.target === dialog.current) dialog.current?.close();
    };

    return (
        <dialog ref={dialog} className="palette" aria-label={t('palette.title')} onClose={onClose} onClick={onDialogClick}>
            <div className="palette-in">
                <input
                    ref={input}
                    className="palette-input"
                    type="text"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls={`${id}-list`}
                    aria-autocomplete="list"
                    aria-activedescendant={results.length > 0 ? optionId(current) : undefined}
                    aria-label={t('palette.placeholder')}
                    placeholder={t('palette.placeholder')}
                    autoComplete="off"
                    spellcheck={false}
                    value={query}
                    onChange={(event) => setQuery(event.currentTarget.value)}
                    onKeyDown={onKeyDown}
                />
                <div id={`${id}-list`} className="palette-list" role="listbox" aria-label={t('palette.list')}>
                    {groups.map(({ group, items }) => (
                        <div key={group} role="group" aria-labelledby={`${id}-${group}`}>
                            <div id={`${id}-${group}`} className="palette-group mono">
                                {t(group)}
                            </div>
                            {items.map(({ command, index }) => (
                                <div
                                    key={command.id}
                                    id={optionId(index)}
                                    role="option"
                                    aria-selected={index === current}
                                    className="palette-option"
                                    onMouseMove={() => index !== current && setActive(index)}
                                    onClick={() => command.run()}
                                >
                                    <span>{command.label}</span>
                                    {command.hint ? <span className="palette-hint mono">{command.hint}</span> : null}
                                </div>
                            ))}
                        </div>
                    ))}
                </div>
                {results.length === 0 ? <p className="palette-empty">{t('palette.empty', { query })}</p> : null}
                {data === null ? <p className="palette-empty">{t('palette.loading')}</p> : null}
                <p className="sr-only" aria-live="polite">
                    {t('palette.results', { count: results.length })}
                </p>
                <p className="palette-keys mono" aria-hidden="true">
                    {t('palette.keys')}
                </p>
            </div>
        </dialog>
    );
}
