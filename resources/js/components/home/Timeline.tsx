import { useTrans } from '@/hooks/useTrans';
import type { Experience } from '@/types/portfolio';

import { SectionHeading } from './SectionHeading';

export function Timeline({ items }: { items: Experience[] }) {
    const t = useTrans();

    if (items.length === 0) return null;

    return (
        <section className="block wrap" id="experience">
            <SectionHeading kicker={t('experience.kicker')} title={t('experience.title')}>
                {t('experience.intro')}
            </SectionHeading>
            <div className="timeline">
                {items.map((x) => (
                    <div className="tl rv" key={x.id}>
                        <span className="mono when">{x.type_label}</span>
                        <div>
                            <h3>{x.title}</h3>
                            <p>
                                {x.organization}
                                {x.location ? `, ${x.location}` : ''}
                                {x.description ? ` · ${x.description}` : ''}
                            </p>
                        </div>
                        <span className="when">{x.period}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}
