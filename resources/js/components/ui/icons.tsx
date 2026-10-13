import type { SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

const base: IconProps = { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2.2, 'aria-hidden': true };

export const ArrowRight = (p: IconProps) => (
    <svg {...base} {...p}><path d="M5 12h14M13 6l6 6-6 6" /></svg>
);
export const ArrowUpRight = (p: IconProps) => (
    <svg {...base} {...p}><path d="M7 17L17 7M9 7h8v8" /></svg>
);
export const ArrowLeft = (p: IconProps) => (
    <svg {...base} {...p}><path d="M19 12H5M11 6l-6 6 6 6" /></svg>
);
export const Download = (p: IconProps) => (
    <svg {...base} {...p}><path d="M12 4v12M6 11l6 6 6-6M5 20h14" /></svg>
);
export const Sun = (p: IconProps) => (
    <svg {...base} strokeWidth={2} strokeLinecap="round" {...p}>
        <circle cx="12" cy="12" r="4.5" />
        <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    </svg>
);

export const Search = (p: IconProps) => (
    <svg {...base} strokeWidth={2} strokeLinecap="round" {...p}>
        <circle cx="11" cy="11" r="6.5" />
        <path d="M16 16l4.5 4.5" />
    </svg>
);

export function Monogram() {
    return (
        <svg viewBox="0 0 32 32" aria-hidden="true">
            <rect x="1" y="1" width="30" height="30" rx="8" fill="var(--ink)" />
            <path d="M9 9h8M9 16h6M9 23h8M9 9v14" stroke="var(--paper)" strokeWidth="2.4" strokeLinecap="round" />
            <circle cx="22.5" cy="19.5" r="3.6" fill="var(--accent)" />
        </svg>
    );
}
