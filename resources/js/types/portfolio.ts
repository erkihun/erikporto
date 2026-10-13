/**
 * Backend contracts. Each type mirrors a Laravel API Resource:
 *   ProfileResource, ProjectSummaryResource, ProjectResource, ExperienceResource,
 *   and App\Queries\HomePageQuery for the capability/process shapes.
 */

export type ProjectCategory = 'software' | 'design';
export type ProjectMockKind = 'id' | 'jobs' | 'court' | 'brand';
export type ExperienceType = 'work' | 'education';
export type InquiryType = 'web_system' | 'brand_identity' | 'ui_ux' | 'other';

export interface SocialLink {
    label: string;
    url: string;
    handle: string | null;
}

export interface Fact {
    value: string;
    label: string;
}

export interface Profile {
    name: string;
    headline: string;
    lede: string;
    bio: string[];
    about_statement: string | null;
    location: string | null;
    email: string | null;
    is_available: boolean;
    avatar_url: string | null;
    cv_url: string | null;
    social_links: SocialLink[];
    facts: Fact[];
    meta_description: string;
}

export interface ProjectSummary {
    slug: string;
    title: string;
    subtitle: string | null;
    category: ProjectCategory;
    kind: string;
    summary: string;
    year: number | null;
    stack: string[];
    cover_url: string | null;
    mock: ProjectMockKind | null;
    url: string;
}

export interface ProjectDetail extends ProjectSummary {
    problem: string;
    role: string;
    solution: string;
    outcome: string;
    metrics: Fact[];
    tools: string[];
    repository_url: string | null;
    live_url: string | null;
    gallery_urls: string[];
}

export interface CapabilityGroup {
    label: string;
    items: string[];
}

export interface Capability {
    discipline: 'engineering' | 'design';
    title: string;
    tagline: string;
    groups: CapabilityGroup[];
}

export interface ProcessStep {
    id: number;
    title: string;
    description: string;
}

export interface Experience {
    id: number;
    type: ExperienceType;
    type_label: string;
    title: string;
    organization: string;
    location: string | null;
    period: string | null;
    description: string | null;
}

export interface Option<T extends string = string> {
    value: T;
    label: string;
}

export interface NoteSummary {
    slug: string;
    title: string;
    excerpt: string;
    tags: string[];
    reading_minutes: number;
    published_at: string; // ISO 8601
    cover_url: string | null;
    url: string;
}

export interface TocEntry {
    id: string;
    text: string;
    level: 2 | 3;
}

export interface NoteDetail extends NoteSummary {
    /** Server-rendered Markdown; raw HTML in the source is escaped by MarkdownRenderer. */
    body_html: string;
    toc: TocEntry[];
    updated_at: string;
}

export interface Owner {
    name: string | null;
    email: string | null;
}

export interface NotesIndexProps {
    notes: NoteSummary[];
    pagination: { page: number; prev: string | null; next: string | null };
    owner: Owner;
}

export interface NoteShowProps {
    note: NoteDetail;
    older: NoteSummary | null;
    newer: NoteSummary | null;
    owner: Owner;
}

export interface HomePageProps {
    profile: Profile;
    projects: ProjectSummary[];
    capabilities: Capability[];
    process: ProcessStep[];
    experience: Experience[];
    latestNotes: NoteSummary[];
    inquiryTypes: Option<InquiryType>[];
}

export interface ProjectPageProps {
    project: ProjectDetail;
    position: { index: number; total: number };
    next: ProjectSummary | null;
    owner: Owner;
}
