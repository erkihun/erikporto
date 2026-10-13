import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import type { InquiryType, Option, Profile } from '@/types/portfolio';

import { ArrowRight } from '../ui/icons';

interface ContactForm {
    name: string;
    email: string;
    inquiry_type: InquiryType;
    message: string;
    website: string; // honeypot
}

export function ContactSection({ profile, inquiryTypes }: { profile: Profile; inquiryTypes: Option<InquiryType>[] }) {
    const [copied, setCopied] = useState(false);
    const [sent, setSent] = useState(false);
    const form = useForm<ContactForm>({
        name: '',
        email: '',
        inquiry_type: inquiryTypes[0]?.value ?? 'web_system',
        message: '',
        website: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/contact', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setSent(true);
            },
        });
    };

    const copyEmail = async () => {
        if (!profile.email) return;
        try {
            await navigator.clipboard.writeText(profile.email);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            window.location.href = `mailto:${profile.email}`;
        }
    };

    return (
        <section className="block wrap" id="contact">
            <div className="contact rv">
                <div>
                    <span className="kicker mono" style={{ color: 'var(--accent)' }}>Contact</span>
                    <h2>Let’s build something that lasts.</h2>
                    <p className="sub">Tell me about the system or the brand you need. I usually reply within two working days.</p>
                    <div className="chan">
                        {profile.email ? (
                            <button type="button" onClick={copyEmail}>
                                <span>Email</span>
                                <span>{copied ? 'Copied ✓' : `${profile.email} ⧉`}</span>
                            </button>
                        ) : null}
                        {profile.social_links.map((link) => (
                            <a key={link.label} href={link.url} target="_blank" rel="noopener noreferrer">
                                <span>{link.label}</span>
                                <span>{link.handle ?? 'Open'} ↗</span>
                            </a>
                        ))}
                    </div>
                </div>

                {sent ? (
                    <div className="sent" role="status">
                        <span className="kicker mono">Sent</span>
                        <h3 style={{ fontSize: 32, fontStretch: '85%' }}>Thank you.</h3>
                        <p style={{ opacity: 0.75, marginTop: 10 }}>Your message is on its way. I’ll reply within two working days.</p>
                    </div>
                ) : (
                    <form className="cf" onSubmit={submit} noValidate>
                        <div className="two">
                            <Field id="name" label="Name" error={form.errors.name}>
                                <input id="name" autoComplete="name" required maxLength={120} value={form.data.name} onChange={(e) => form.setData('name', e.currentTarget.value)} aria-invalid={!!form.errors.name} />
                            </Field>
                            <Field id="email" label="Email" error={form.errors.email}>
                                <input id="email" type="email" autoComplete="email" required maxLength={190} value={form.data.email} onChange={(e) => form.setData('email', e.currentTarget.value)} aria-invalid={!!form.errors.email} />
                            </Field>
                        </div>
                        <Field id="inquiry_type" label="Project type" error={form.errors.inquiry_type}>
                            <select id="inquiry_type" value={form.data.inquiry_type} onChange={(e) => form.setData('inquiry_type', e.currentTarget.value as InquiryType)}>
                                {inquiryTypes.map((o) => (
                                    <option key={o.value} value={o.value}>{o.label}</option>
                                ))}
                            </select>
                        </Field>
                        <Field id="message" label="Message" error={form.errors.message}>
                            <textarea id="message" rows={5} required minLength={20} maxLength={5000} value={form.data.message} onChange={(e) => form.setData('message', e.currentTarget.value)} aria-invalid={!!form.errors.message} />
                        </Field>
                        <input className="hp" tabIndex={-1} autoComplete="off" name="website" aria-hidden="true" value={form.data.website} onChange={(e) => form.setData('website', e.currentTarget.value)} />
                        <div>
                            <button className="btn btn-primary" type="submit" disabled={form.processing}>
                                {form.processing ? 'Sending…' : 'Send message'} <ArrowRight />
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </section>
    );
}

function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="field">
            <label htmlFor={id}>{label}</label>
            {children}
            {error ? <span className="err" id={`${id}-error`}>{error}</span> : null}
        </div>
    );
}
