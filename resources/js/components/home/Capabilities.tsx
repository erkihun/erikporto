import { useTrans } from '@/hooks/useTrans';
import type { Capability } from '@/types/portfolio';

import { SectionHeading } from './SectionHeading';

export function Capabilities({ capabilities }: { capabilities: Capability[] }) {
    const t = useTrans();

    if (capabilities.length === 0) return null;

    return (
        <section className="block wrap" id="capabilities">
            <SectionHeading kicker={t('capabilities.kicker')} title={t('capabilities.title')}>
                {t('capabilities.intro')}
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
