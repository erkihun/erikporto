/**
 * Gregorian → Ethiopian (Amete Mihret) conversion via Julian Day Numbers.
 * Verified: 2026-09-11 → 1 Meskerem 2019, 2027-09-11 → 6 Pagume 2019 (leap year).
 */

export const ETHIOPIAN_MONTHS = [
    'መስከረም', 'ጥቅምት', 'ኅዳር', 'ታኅሣሥ', 'ጥር', 'የካቲት',
    'መጋቢት', 'ሚያዝያ', 'ግንቦት', 'ሰኔ', 'ሐምሌ', 'ነሐሴ', 'ጳጉሜ',
] as const;

export interface EthiopianDate {
    year: number;
    month: number; // 1..13
    day: number;
}

const ETHIOPIAN_EPOCH_OFFSET = 1723856;

function gregorianToJdn(year: number, month: number, day: number): number {
    const a = Math.floor((14 - month) / 12);
    const y = year + 4800 - a;
    const m = month + 12 * a - 3;
    return day + Math.floor((153 * m + 2) / 5) + 365 * y + Math.floor(y / 4) - Math.floor(y / 100) + Math.floor(y / 400) - 32045;
}

export function toEthiopian(date: Date): EthiopianDate {
    const jdn = gregorianToJdn(date.getFullYear(), date.getMonth() + 1, date.getDate());
    const offset = jdn - ETHIOPIAN_EPOCH_OFFSET;
    const r = ((offset % 1461) + 1461) % 1461;
    const n = (r % 365) + 365 * Math.floor(r / 1460);

    return {
        year: 4 * Math.floor(offset / 1461) + Math.floor(r / 365) - Math.floor(r / 1460),
        month: Math.floor(n / 30) + 1,
        day: (n % 30) + 1,
    };
}

export function formatEthiopian(date: Date): string {
    const e = toEthiopian(date);
    return `${ETHIOPIAN_MONTHS[e.month - 1]} ${e.day}, ${e.year} ዓ.ም.`;
}
