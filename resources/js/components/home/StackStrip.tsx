import type { Capability, Fact } from '@/types/portfolio';

/** Marquee of every listed technology, followed by the profile's highlight facts. */
export function StackStrip({ capabilities, facts }: { capabilities: Capability[]; facts: Fact[] }) {
    const items = [...new Set(capabilities.flatMap((c) => c.groups.flatMap((g) => g.items)))];

    return (
        <>
            {items.length > 0 ? (
                <div className="strip" aria-hidden="true">
                    <div className="strip-in">
                        {[...items, ...items].map((item, i) => (
                            <span key={i}>{item}</span>
                        ))}
                    </div>
                </div>
            ) : null}
            {facts.length > 0 ? (
                <div className="facts">
                    {facts.map((f) => (
                        <div key={f.label}>
                            <b>{f.value}</b>
                            <small>{f.label}</small>
                        </div>
                    ))}
                </div>
            ) : null}
        </>
    );
}
