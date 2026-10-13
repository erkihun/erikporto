import type { ProjectMockKind, ResponsiveImage } from '@/types/portfolio';

import { ResponsiveImg } from '../ui/ResponsiveImg';

/**
 * Illustrated stand-in shown until a real screenshot is uploaded in the admin panel.
 * Drawn with theme tokens so it follows light/dark mode.
 */
const L = 'var(--line)', S = 'var(--surface)', A = 'var(--accent)', M = 'var(--muted)', I = 'var(--ink)', G = 'var(--signal)', O = 'var(--ok)', P = 'var(--paper)';

function Chrome() {
    return (
        <>
            <rect width="640" height="400" fill={P} />
            <rect x="24" y="24" width="592" height="376" rx="12" fill={S} stroke={L} />
            {[44, 58, 72].map((cx) => <circle key={cx} cx={cx} cy="44" r="4" fill={L} />)}
            <line x1="24" y1="62" x2="616" y2="62" stroke={L} />
        </>
    );
}

function IdMock() {
    return (
        <>
            <Chrome />
            <rect x="44" y="82" width="120" height="300" fill={P} />
            {[0, 1, 2, 3, 4].map((i) => <rect key={i} x="56" y={98 + i * 26} width={i ? 80 : 96} height="10" rx="5" fill={i ? L : A} />)}
            <rect x="190" y="88" width="190" height="270" rx="14" fill={I} />
            <rect x="190" y="88" width="190" height="64" rx="14" fill={A} />
            <rect x="190" y="130" width="190" height="22" fill={A} />
            <circle cx="285" cy="186" r="34" fill={P} />
            <circle cx="285" cy="178" r="12" fill={M} />
            <path d="M262 206c6-12 40-12 46 0" fill={M} />
            <rect x="225" y="236" width="120" height="10" rx="5" fill={P} />
            <rect x="240" y="254" width="90" height="7" rx="3.5" fill={M} />
            {Array.from({ length: 18 }, (_, i) => (
                <rect key={i} x={218 + (i % 6) * 22} y={282 + Math.floor(i / 6) * 20} width={(i * 7) % 3 ? 14 : 8} height="14" fill={P} />
            ))}
            <path d="M500 110v30M500 140h-60v30M500 140h60v30M440 200v24M560 200v24" stroke={M} strokeWidth="1.5" fill="none" />
            <rect x="465" y="86" width="70" height="26" rx="6" fill={A} />
            {[[405, 170], [525, 170], [405, 224], [525, 224]].map(([x, y]) => (
                <rect key={`${x}-${y}`} x={x} y={y} width="70" height="26" rx="6" fill={P} stroke={L} />
            ))}
            <rect x="405" y="282" width="190" height="76" rx="10" fill={P} stroke={L} />
            <rect x="420" y="297" width="60" height="8" rx="4" fill={M} />
            <rect x="420" y="317" width="110" height="20" rx="4" fill={O} opacity=".85" />
        </>
    );
}

function JobsMock() {
    return (
        <>
            <Chrome />
            <rect x="44" y="80" width="552" height="44" rx="10" fill={P} />
            <rect x="60" y="96" width="180" height="12" rx="6" fill={L} />
            <rect x="500" y="90" width="80" height="24" rx="12" fill={A} />
            {[A, G, O].map((c, i) => (
                <g key={i} transform={`translate(${44 + i * 188},144)`}>
                    <rect width="176" height="210" rx="12" fill={P} stroke={L} />
                    <rect x="16" y="18" width="40" height="40" rx="10" fill={c} opacity=".9" />
                    <rect x="16" y="76" width="120" height="12" rx="6" fill={I} />
                    <rect x="16" y="98" width="90" height="8" rx="4" fill={M} />
                    {[0, 1, 2].map((k) => <rect key={k} x="16" y={124 + k * 16} width={140 - k * 24} height="6" rx="3" fill={L} />)}
                    <rect x="16" y="172" width="70" height="22" rx="11" fill={i === 1 ? A : S} stroke={A} />
                </g>
            ))}
        </>
    );
}

function CourtMock() {
    const fills = [I, A, G, O];
    return (
        <>
            <Chrome />
            {[60, 44, 70, 36].map((w, i) => (
                <g key={i} transform={`translate(${44 + i * 140},80)`}>
                    <rect width="128" height="70" rx="10" fill={P} stroke={L} />
                    <rect x="14" y="14" width="50" height="7" rx="3.5" fill={M} />
                    <rect x="14" y="32" width={w} height="22" rx="4" fill={fills[i]} />
                </g>
            ))}
            <rect x="44" y="166" width="552" height="214" rx="10" fill={P} stroke={L} />
            <rect x="44" y="166" width="552" height="32" rx="10" fill={L} opacity=".6" />
            {[A, G, O, A, M].map((c, i) => (
                <g key={i} transform={`translate(60,${214 + i * 32})`}>
                    <rect width="90" height="9" rx="4.5" fill={I} opacity=".75" />
                    <rect x="130" width="150" height="9" rx="4.5" fill={M} opacity=".6" />
                    <rect x="320" width="80" height="9" rx="4.5" fill={M} opacity=".6" />
                    <rect x="440" y="-5" width="76" height="20" rx="10" fill={c} opacity=".85" />
                </g>
            ))}
        </>
    );
}

function BrandMock() {
    return (
        <>
            <rect width="640" height="400" fill={P} />
            <rect x="24" y="24" width="300" height="352" rx="14" fill={I} />
            <g transform="translate(174,170)">
                <circle r="70" fill="none" stroke={P} strokeWidth="10" />
                <path d="M-40 30L0-46 40 30Z" fill={A} />
                <circle cy="8" r="14" fill={G} />
            </g>
            <rect x="104" y="280" width="140" height="12" rx="6" fill={P} />
            <rect x="124" y="302" width="100" height="7" rx="3.5" fill={M} />
            {[A, G, I, O].map((c, i) => (
                <rect key={i} x={344 + (i % 2) * 140} y={24 + Math.floor(i / 2) * 92} width="128" height="80" rx="12" fill={c} />
            ))}
            <rect x="344" y="212" width="272" height="164" rx="14" fill={S} stroke={L} />
            <text x="364" y="290" fontFamily="Archivo, sans-serif" fontSize="64" fontWeight="800" fill={I}>Aa</text>
            <text x="364" y="330" fontFamily="'Noto Sans Ethiopic', sans-serif" fontSize="30" fontWeight="600" fill={A}>ሀ ለ ሐ መ</text>
            <rect x="500" y="244" width="96" height="7" rx="3.5" fill={M} />
            <rect x="500" y="260" width="70" height="7" rx="3.5" fill={L} />
        </>
    );
}

const MOCKS: Record<ProjectMockKind, () => React.JSX.Element> = { id: IdMock, jobs: JobsMock, court: CourtMock, brand: BrandMock };

export function ProjectMock({ kind }: { kind: ProjectMockKind | null }) {
    const Mock = MOCKS[kind ?? 'court'];
    return (
        <svg viewBox="0 0 640 400" aria-hidden="true" preserveAspectRatio="xMidYMid slice">
            <Mock />
        </svg>
    );
}

/** Real screenshot when uploaded, illustrated mock otherwise. */
interface ProjectVisualProps {
    cover: ResponsiveImage | null;
    mock: ProjectMockKind | null;
    alt: string;
    sizes: string;
    eager?: boolean;
}

export function ProjectVisual({ cover, mock, alt, sizes, eager = false }: ProjectVisualProps) {
    return cover ? <ResponsiveImg image={cover} alt={alt} sizes={sizes} eager={eager} /> : <ProjectMock kind={mock} />;
}
