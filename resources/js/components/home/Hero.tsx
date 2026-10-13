import type { Profile } from '@/types/portfolio';

import { ArrowRight, Download } from '../ui/icons';
import { MagneticButton } from '../ui/MagneticButton';
import { OrgGraph } from './OrgGraph';

/** Splits "Software Developer & Graphic Designer" so the ampersand can carry the accent. */
function HeadlineParts({ headline }: { headline: string }) {
    const parts = headline.split('&').map((p) => p.trim());
    if (parts.length !== 2) return <span>{headline}</span>;
    return (
        <>
            <span>{parts[0]}</span>
            <span className="amp">&amp;</span>
            <span>{parts[1]}</span>
        </>
    );
}

export function Hero({ profile }: { profile: Profile }) {
    let i = 0;

    return (
        <section className="hero wrap">
            <div className="hero-grid">
                <div>
                    <span className="status mono">
                        <i className="pulse" style={profile.is_available ? undefined : { background: 'var(--signal)' }} />
                        <span>{profile.is_available ? 'Available for projects' : 'Currently booked'}</span>
                        {profile.location ? <span>· {profile.location}</span> : null}
                    </span>
                    <h1 className="name" aria-label={profile.name}>
                        {profile.name.split(' ').map((word) => (
                            <span className="w" key={word} aria-hidden="true">
                                {[...word].map((ch) => (
                                    <span className="ch" key={i} style={{ ['--i' as string]: i++ }}>
                                        {ch}
                                    </span>
                                ))}
                            </span>
                        ))}
                    </h1>
                    <div className="role">
                        <HeadlineParts headline={profile.headline} />
                    </div>
                    <p className="lede">{profile.lede}</p>
                    <div className="ctas">
                        <MagneticButton href="#work">
                            View my work <ArrowRight />
                        </MagneticButton>
                        <MagneticButton href="#contact" variant="ghost">
                            Contact me
                        </MagneticButton>
                        {profile.cv_url ? (
                            <MagneticButton href={profile.cv_url} variant="ghost" download>
                                Download CV <Download />
                            </MagneticButton>
                        ) : null}
                    </div>
                </div>
                <OrgGraph />
            </div>
        </section>
    );
}
