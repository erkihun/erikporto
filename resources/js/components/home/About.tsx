import { useTrans } from '@/hooks/useTrans';
import type { TransKey } from '@/types/inertia';
import type { Profile } from '@/types/portfolio';

const PRINCIPLES: readonly (readonly [TransKey, TransKey])[] = [
    ['about.usable_title', 'about.usable_text'],
    ['about.secure_title', 'about.secure_text'],
    ['about.scalable_title', 'about.scalable_text'],
];

export function About({ profile }: { profile: Profile }) {
    const t = useTrans();

    return (
        <section className="block wrap" id="about">
            <div className="about">
                <div className="portrait rv">
                    {profile.avatar_url ? (
                        <img src={profile.avatar_url} alt={t('about.portrait_alt', { name: profile.name })} loading="lazy" />
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
                    <span className="kicker mono">{t('about.kicker')}</span>
                    {profile.about_statement ? <p className="big">{profile.about_statement}</p> : null}
                    <div className="body">
                        {profile.bio.map((p, i) => (
                            <p key={i}>{p}</p>
                        ))}
                    </div>
                    <div className="principles">
                        {PRINCIPLES.map(([title, text]) => (
                            <div key={title}>
                                <b>{t(title)}</b>
                                <small>{t(text)}</small>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
