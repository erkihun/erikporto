import type { ProcessStep } from '@/types/portfolio';

import { SectionHeading } from './SectionHeading';

export function Process({ steps }: { steps: ProcessStep[] }) {
    if (steps.length === 0) return null;

    return (
        <section className="block wrap" id="process">
            <SectionHeading kicker="How I work" title="From paper process to working system.">
                Most of my projects replace a manual institutional workflow. This is the order I take them in.
            </SectionHeading>
            <ol className="process" style={{ listStyle: 'none', padding: 0, margin: 0 }}>
                {steps.map((s, i) => (
                    <li className="step rv" key={s.id}>
                        <span className="num" aria-hidden="true">{String(i + 1).padStart(2, '0')}</span>
                        <h4>{s.title}</h4>
                        <p>{s.description}</p>
                    </li>
                ))}
            </ol>
        </section>
    );
}
