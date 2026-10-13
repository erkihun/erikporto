import type { ReactNode } from 'react';

export function SectionHeading({ kicker, title, children }: { kicker: string; title: string; children?: ReactNode }) {
    return (
        <div className="sec-head rv">
            <div>
                <span className="kicker mono">{kicker}</span>
                <h2>{title}</h2>
            </div>
            {children ? <p>{children}</p> : null}
        </div>
    );
}
