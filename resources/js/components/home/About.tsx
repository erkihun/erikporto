import type { Profile } from '@/types/portfolio';

const PRINCIPLES = [
    ['Usable', 'Simple for the clerk entering the data, not only for the admin.'],
    ['Secure', 'Access scoped to the organisation and the role.'],
    ['Scalable', 'Structured to grow with new offices and services.'],
] as const;

export function About({ profile }: { profile: Profile }) {
    return (
        <section className="block wrap" id="about">
            <div className="about">
                <div className="portrait rv">
                    {profile.avatar_url ? (
                        <img src={profile.avatar_url} alt={`Portrait of ${profile.name}`} loading="lazy" />
                    ) : (
                        <span className="initials" aria-hidden="true">
                            {profile.name
                                .split(' ')
                                .map((w) => w[0])
                                .join('')
                                .slice(0, 2)}
                        </span>
                    )}
                </div>
                <div className="rv">
                    <span className="kicker mono">About</span>
                    {profile.about_statement ? <p className="big">{profile.about_statement}</p> : null}
                    <div className="body">
                        {profile.bio.map((p, i) => (
                            <p key={i}>{p}</p>
                        ))}
                    </div>
                    <div className="principles">
                        {PRINCIPLES.map(([title, text]) => (
                            <div key={title}>
                                <b>{title}</b>
                                <small>{text}</small>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
