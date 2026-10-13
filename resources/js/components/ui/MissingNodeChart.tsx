/**
 * The hero's org structure, drawn once (no canvas, no JS) with one position missing:
 * the 404 illustration. Theme tokens keep it right in light and dark mode.
 */
const L = 'var(--line)', I = 'var(--ink)', A = 'var(--accent)', M = 'var(--muted)';

const DIRECTORATES = [140, 320, 500];
const TEAM_OFFSETS = [-56, 0, 56];
/** Directorate 3, last team: the page that isn't there. */
const MISSING = { x: 556, y: 214 };

export function MissingNodeChart({ label, vacant }: { label: string; vacant: string }) {
    return (
        <svg viewBox="0 0 640 330" role="img" aria-label={label}>
            <circle cx="320" cy="44" r="11" fill={A} />
            {DIRECTORATES.map((x) => (
                <g key={x}>
                    <path d={`M320 55V92H${x}V122`} stroke={M} strokeWidth="1.5" fill="none" />
                    <circle cx={x} cy="130" r="8" fill={I} />
                    {TEAM_OFFSETS.map((offset) => {
                        const tx = x + offset;
                        const missing = tx === MISSING.x;
                        return (
                            <g key={tx}>
                                <path
                                    d={`M${x} 138V170H${tx}V${missing ? 192 : 200}`}
                                    stroke={missing ? A : M}
                                    strokeWidth="1.5"
                                    strokeDasharray={missing ? '4 5' : undefined}
                                    fill="none"
                                />
                                {missing ? null : (
                                    <>
                                        <rect x={tx - 7} y="200" width="14" height="14" rx="2" fill={M} />
                                        {[-12, 0, 12].map((dx) => (
                                            <circle key={dx} cx={tx + dx} cy="262" r="4" fill={dx === 0 ? I : L} />
                                        ))}
                                        <path d={`M${tx} 214V250`} stroke={L} strokeWidth="1.5" />
                                    </>
                                )}
                            </g>
                        );
                    })}
                </g>
            ))}
            <g className="err-missing">
                <circle cx={MISSING.x} cy={MISSING.y} r="22" fill="none" stroke={A} strokeWidth="2" strokeDasharray="5 5" />
                <text x={MISSING.x} y={MISSING.y + 5} textAnchor="middle" fontFamily="var(--f-mono)" fontSize="13" fill={A}>
                    ?
                </text>
                <text x={MISSING.x} y={MISSING.y + 48} textAnchor="middle" fontFamily="var(--f-mono)" fontSize="12" fill={M}>
                    {vacant}
                </text>
            </g>
        </svg>
    );
}
