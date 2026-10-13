import type { Experience } from '@/types/portfolio';

import { SectionHeading } from './SectionHeading';

export function Timeline({ items }: { items: Experience[] }) {
    if (items.length === 0) return null;

    return (
        <section className="block wrap" id="experience">
            <SectionHeading kicker="Experience & education" title="Where I’ve worked and studied.">
                Public-sector software development, alongside graphic design and brand identity work.
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
