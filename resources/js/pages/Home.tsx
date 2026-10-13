import { About } from '@/components/home/About';
import { Capabilities } from '@/components/home/Capabilities';
import { ContactSection } from '@/components/home/ContactSection';
import { Hero } from '@/components/home/Hero';
import { LatestNotes } from '@/components/home/LatestNotes';
import { Process } from '@/components/home/Process';
import { StackStrip } from '@/components/home/StackStrip';
import { Timeline } from '@/components/home/Timeline';
import { WorkSection } from '@/components/home/WorkSection';
import { SiteLayout } from '@/components/layout/SiteLayout';
import { Seo } from '@/components/ui/Seo';
import type { HomePageProps } from '@/types/portfolio';

export default function Home({ profile, projects, capabilities, process, experience, latestNotes, inquiryTypes }: HomePageProps) {
    return (
        <SiteLayout ownerName={profile.name} isHome>
            <Seo description={profile.meta_description} image={profile.avatar?.url} />
            <Hero profile={profile} />
            <StackStrip capabilities={capabilities} facts={profile.facts} />
            <WorkSection projects={projects} />
            <Capabilities capabilities={capabilities} />
            <Process steps={process} />
            <Timeline items={experience} />
            <About profile={profile} />
            <LatestNotes notes={latestNotes} />
            <ContactSection profile={profile} inquiryTypes={inquiryTypes} />
        </SiteLayout>
    );
}
