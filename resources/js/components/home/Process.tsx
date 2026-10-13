import { useTrans } from '@/hooks/useTrans';
import type { ProcessStep } from '@/types/portfolio';

import { SectionHeading } from './SectionHeading';

export function Process({ steps }: { steps: ProcessStep[] }) {
    const t = useTrans();

    if (steps.length === 0) return null;

    return (
        <section className="block wrap" id="process">
            <SectionHeading kicker={t('process.kicker')} title={t('process.title')}>
                {t('process.intro')}
            </SectionHeading>
            <ol className="process" style={{ listStyle: 'none', padding: 0, margin: 0 }}>
                {steps.map((s, i) => (
                    <li className="step rv" key={s.id}>
                        <span className="num" aria-hidden="true">{String(i + 1).padStart(2, '0')}</span>
                        <h3>{s.title}</h3>
                        <p>{s.description}</p>
                    </li>
                ))}
            </ol>
        </section>
    );
}
