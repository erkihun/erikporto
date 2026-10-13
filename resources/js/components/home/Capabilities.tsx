import type { Capability } from '@/types/portfolio';

import { SectionHeading } from './SectionHeading';

export function Capabilities({ capabilities }: { capabilities: Capability[] }) {
    if (capabilities.length === 0) return null;

    return (
        <section className="block wrap" id="capabilities">
            <SectionHeading kicker="Capabilities" title="Two disciplines, one standard.">
                I design the interface and build the system behind it, so the data model, the permissions and the visual language stay consistent.
            </SectionHeading>
            <div className="caps">
                {capabilities.map((c) => (
                    <div className="cap rv" key={c.discipline}>
                        <h3>
                            {c.title}
                            <small>{c.tagline}</small>
                        </h3>
                        {c.groups.map((g) => (
                            <div className="grp" key={g.label}>
                                <span className="mono">{g.label}</span>
                                <div className="pills">
                                    {g.items.map((item) => (
                                        <span className="pill" key={item}>{item}</span>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        </section>
    );
}
