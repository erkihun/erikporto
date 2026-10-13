import { formatEthiopian } from './ethiopian-calendar';

/** "13 Oct 2026" — fixed locale and UTC so SSR and the browser render the same string. */
export function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
}

/** Both calendars, e.g. "13 Oct 2026 · ጥቅምት 3, 2019 ዓ.ም." */
export function formatDualDate(iso: string): { gregorian: string; ethiopian: string } {
    const d = new Date(iso);
    const utcDay = new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate());
    return { gregorian: formatDate(iso), ethiopian: formatEthiopian(utcDay) };
}
