<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Discipline;
use App\Enums\ExperienceType;
use App\Enums\ProjectCategory;
use App\Models\Experience;
use App\Models\ProcessStep;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo content for local development and tests. Never runs in production,
 * where the site starts empty and content is entered in /admin (see
 * DatabaseSeeder). Idempotent: rows are matched on natural keys.
 */
final class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->profile();
            $this->projects();
            $this->skills();
            $this->process();
            $this->experience();
        });
    }

    private function profile(): void
    {
        $profile = Profile::current() ?? new Profile;

        $profile->fill([
            'name' => 'Erkihun Bewket',
            'headline' => 'Software Developer & Graphic Designer',
            'lede' => 'I build Laravel systems that move institutional work online (staff records, recruitment, court cases, services) and design the brand identities that represent them.',
            'bio' => [
                'I work as a software developer in a public institution, turning paper-based processes into web systems: organisation structures, employee records, digital IDs, recruitment and case management.',
                'Alongside the code I design logos and brand identities, so I care about how a system looks and reads as much as how it is structured. Every interface I ship supports Amharic and English, and both Ethiopian and Gregorian dates.',
            ],
            'about_statement' => 'I build web systems for institutions and design the brands they’re known by.',
            'is_available' => true,
            'social_links' => [
                ['label' => 'GitHub', 'url' => 'https://github.com/erkihun', 'handle' => '@erkihun'],
                ['label' => 'LinkedIn', 'url' => null, 'handle' => null],
            ],
            'facts' => [
                ['value' => '4', 'label' => 'Featured projects'],
                ['value' => '3', 'label' => 'Institutional systems'],
                ['value' => 'Am · En', 'label' => 'Bilingual interfaces'],
                ['value' => 'E.C. · G.C.', 'label' => 'Dual-calendar dates'],
            ],
            'meta_description' => 'Erkihun Bewket, software developer and graphic designer building Laravel systems for institutions and designing brand identities.',
        ])->save();
    }

    private function projects(): void
    {
        $projects = [
            [
                'slug' => 'euisis',
                'title' => 'EUISIS',
                'subtitle' => 'Employee Unified ID & Service Integration System',
                'category' => ProjectCategory::Software,
                'mock' => 'id',
                'summary' => 'Connects organisation structure, staff records, positions, digital IDs and services like the cafeteria in one system.',
                'problem' => 'Employee data, positions and service access (such as the cafeteria) lived in separate records, so verifying who a person is and what they are entitled to was slow and inconsistent across organisations.',
                'role' => 'System development, database design and interface improvements.',
                'solution' => 'A single Laravel system modelling the organisation hierarchy, employees and positions, issuing a digital ID per employee, and connecting services to that identity. Access is scoped by organisation boundary.',
                'outcome' => 'Staff information and services managed from one place, with permissions enforced per organisation.',
                'stack' => ['Laravel', 'PHP', 'MySQL', 'Blade', 'Tailwind CSS', 'Alpine.js'],
                'repository_url' => 'https://github.com/erkihun/EUISIS',
            ],
            [
                'slug' => 'recruitment-portal',
                'title' => 'Recruitment Portal',
                'subtitle' => 'Job advertisement & application management',
                'category' => ProjectCategory::Software,
                'mock' => 'jobs',
                'summary' => 'Publishes vacancies, registers applicants and manages applications against eligibility rules.',
                'problem' => 'Applications arrived on paper with no consistent check of eligibility, and duplicate applications were hard to detect.',
                'role' => 'Built the applicant and admin areas, validation and the application workflow.',
                'solution' => 'An online portal where applicants register and apply to open positions. Eligibility criteria are managed per vacancy in a Filament admin, and duplicate applications are controlled by defined rules.',
                'outcome' => 'Applications received online, with eligibility and duplicate rules enforced by the system instead of by hand.',
                'stack' => ['Laravel', 'PHP', 'MySQL', 'Filament', 'Tailwind CSS'],
            ],
            [
                'slug' => 'court-case-management',
                'title' => 'Court Case Management',
                'subtitle' => 'Case registration, assignment & appeals',
                'category' => ProjectCategory::Software,
                'mock' => 'court',
                'summary' => 'Registers, searches, assigns and tracks court cases and appeals, with user permissions.',
                'problem' => 'Case files were tracked manually, making it hard to find a case, see who it was assigned to, or follow an appeal.',
                'role' => 'Web application development, dashboard, case management and user access structure.',
                'solution' => 'A case management system with registration, search, assignment, appeals and role-based permissions, plus a dashboard for workload tracking.',
                'outcome' => 'Case information held in an organised structure and work follow-up made faster.',
                'stack' => ['Laravel', 'PHP', 'MySQL', 'Blade', 'Tailwind CSS', 'Alpine.js'],
                'repository_url' => 'https://github.com/erkihun/court-ms',
            ],
            [
                'slug' => 'tvet-bureau-identity',
                'title' => 'TVET Bureau Identity',
                'subtitle' => 'Addis Ababa TVET Bureau · logo & brand identity',
                'category' => ProjectCategory::Design,
                'mock' => 'brand',
                'summary' => 'Logo concepts, colour system, typography and brand usage guidelines for the bureau.',
                'problem' => 'The bureau needed an identity that represents its mission and stays consistent across documents, signage and digital channels.',
                'role' => 'Design direction, logo concept development and brand identity preparation.',
                'solution' => 'Logo concepts grounded in the bureau’s mission, a defined colour system and typography, and a usage guide covering spacing, colour and misuse.',
                'outcome' => 'Logo concepts and a brand guideline document.',
                'stack' => ['Logo design', 'Brand identity', 'Typography'],
            ],
        ];

        foreach ($projects as $order => $attributes) {
            Project::query()->updateOrCreate(
                ['slug' => $attributes['slug']],
                [...$attributes, 'is_published' => true, 'sort_order' => $order],
            );
        }
    }

    private function skills(): void
    {
        $groups = [
            [Discipline::Engineering, 'Backend', ['PHP', 'Laravel 12', 'REST APIs', 'Authentication', 'Roles & permissions']],
            [Discipline::Engineering, 'Frontend', ['Blade', 'Tailwind CSS', 'Alpine.js', 'JavaScript', 'HTML/CSS']],
            [Discipline::Engineering, 'Data & admin', ['MySQL', 'Filament', 'Database design', 'Dashboards', 'Reporting']],
            [Discipline::Engineering, 'Tools', ['VS Code', 'Git', 'GitHub']],
            [Discipline::Design, 'Interface', ['UI/UX', 'Responsive interfaces', 'Visual consistency']],
            [Discipline::Design, 'Brand', ['Logo design', 'Brand identity', 'Typography']],
            [Discipline::Design, 'Localization', ['Amharic / English UI', 'Ethiopian & Gregorian dates']],
        ];

        foreach ($groups as $order => [$discipline, $label, $items]) {
            SkillGroup::query()->updateOrCreate(
                ['discipline' => $discipline, 'label' => $label],
                ['items' => $items, 'sort_order' => $order],
            );
        }
    }

    private function process(): void
    {
        $steps = [
            ['Map the process', 'Sit with the people who do the work today and document every form, approval and exception.'],
            ['Model the data', 'Design the schema, organisation boundaries and roles before any screen is built.'],
            ['Build & secure', 'Ship in small increments with validation, permissions and audit trails in place.'],
            ['Design & refine', 'Make the interface consistent, bilingual and easy for first-time users.'],
        ];

        foreach ($steps as $order => [$title, $description]) {
            ProcessStep::query()->updateOrCreate(['title' => $title], ['description' => $description, 'sort_order' => $order]);
        }
    }

    private function experience(): void
    {
        // Dates and education are left for the owner to complete in /admin.
        $rows = [
            [ExperienceType::Work, 'Software Developer', 'Public institution', 'Digitising institutional processes, managing information and improving the usability of internal systems.'],
            [ExperienceType::Work, 'Graphic & Brand Designer', 'Independent', 'Logo design and brand identity work for institutions, including the Addis Ababa TVET Bureau.'],
        ];

        foreach ($rows as $order => [$type, $title, $organization, $description]) {
            Experience::query()->updateOrCreate(
                ['title' => $title, 'organization' => $organization],
                ['type' => $type, 'description' => $description, 'sort_order' => $order],
            );
        }
    }
}
