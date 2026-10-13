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
 * DatabaseSeeder). Idempotent: rows are matched on natural keys (the English
 * text for translated columns).
 *
 * The Amharic copy is a draft so /am has something to show: TODO(review-am).
 * Case study bodies are English only, which demonstrates the fallback.
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
            'headline' => self::both('Software Developer & Graphic Designer', 'ሶፍትዌር ገንቢ እና ግራፊክ ዲዛይነር'),
            'lede' => self::both(
                'I build Laravel systems that move institutional work online (staff records, recruitment, court cases, services) and design the brand identities that represent them.',
                'የተቋማትን ሥራ ወደ ኦንላይን የሚያሸጋግሩ የLaravel ሥርዓቶችን (የሠራተኞች መረጃ፣ ቅጥር፣ የፍርድ ቤት ጉዳዮች፣ አገልግሎቶች) እገነባለሁ፤ እነሱን የሚወክሉ የብራንድ መለያዎችንም እቀርፃለሁ።',
            ),
            'bio' => [
                'en' => [
                    'I work as a software developer in a public institution, turning paper-based processes into web systems: organisation structures, employee records, digital IDs, recruitment and case management.',
                    'Alongside the code I design logos and brand identities, so I care about how a system looks and reads as much as how it is structured. Every interface I ship supports Amharic and English, and both Ethiopian and Gregorian dates.',
                ],
                'am' => [
                    'በአንድ የመንግሥት ተቋም ውስጥ በሶፍትዌር ገንቢነት እሠራለሁ፤ በወረቀት የሚከናወኑ ሂደቶችን ወደ ድረ-ገጽ ሥርዓቶች እቀይራለሁ፦ የድርጅት መዋቅር፣ የሠራተኞች መረጃ፣ ዲጂታል መታወቂያ፣ ቅጥር እና የጉዳይ አስተዳደር።',
                    'ከኮዱ ጎን ለጎን አርማዎችንና የብራንድ መለያዎችን እቀርፃለሁ፤ ስለዚህ አንድ ሥርዓት እንዴት እንደተዋቀረ ብቻ ሳይሆን እንዴት እንደሚታይና እንደሚነበብም ያሳስበኛል። የማቀርበው እያንዳንዱ በይነገጽ አማርኛና እንግሊዝኛን እንዲሁም የኢትዮጵያና የግሪጎሪያን ቀኖችን ይደግፋል።',
                ],
            ],
            'about_statement' => self::both(
                'I build web systems for institutions and design the brands they’re known by.',
                'ለተቋማት የድረ-ገጽ ሥርዓቶችን እገነባለሁ፤ የሚታወቁበትን ብራንድም እቀርፃለሁ።',
            ),
            'is_available' => true,
            'social_links' => [
                ['label' => 'GitHub', 'url' => 'https://github.com/erkihun', 'handle' => '@erkihun'],
                ['label' => 'LinkedIn', 'url' => null, 'handle' => null],
            ],
            'facts' => [
                ['value' => '4', 'label' => self::both('Featured projects', 'ተለይተው የቀረቡ ፕሮጀክቶች')],
                ['value' => '3', 'label' => self::both('Institutional systems', 'ተቋማዊ ሥርዓቶች')],
                ['value' => 'Am · En', 'label' => self::both('Bilingual interfaces', 'ባለሁለት ቋንቋ በይነገጾች')],
                ['value' => 'E.C. · G.C.', 'label' => self::both('Dual-calendar dates', 'ባለሁለት የቀን አቆጣጠር')],
            ],
            'meta_description' => self::both(
                'Erkihun Bewket, software developer and graphic designer building Laravel systems for institutions and designing brand identities.',
                'ለተቋማት የLaravel ሥርዓቶችን የሚገነባ እና የብራንድ መለያዎችን የሚቀርፅ ሶፍትዌር ገንቢ እና ግራፊክ ዲዛይነር።',
            ),
        ])->save();
    }

    private function projects(): void
    {
        $projects = [
            [
                'slug' => 'euisis',
                'title' => 'EUISIS',
                'subtitle' => self::both('Employee Unified ID & Service Integration System', 'የሠራተኞች የተዋሃደ መታወቂያ እና የአገልግሎት ትስስር ሥርዓት'),
                'category' => ProjectCategory::Software,
                'mock' => 'id',
                'summary' => self::both(
                    'Connects organisation structure, staff records, positions, digital IDs and services like the cafeteria in one system.',
                    'የድርጅት መዋቅርን፣ የሠራተኞች መረጃን፣ የሥራ መደቦችን፣ ዲጂታል መታወቂያዎችንና እንደ ካፌቴሪያ ያሉ አገልግሎቶችን በአንድ ሥርዓት ያገናኛል።',
                ),
                'problem' => 'Employee data, positions and service access (such as the cafeteria) lived in separate records, so verifying who a person is and what they are entitled to was slow and inconsistent across organisations.',
                'role' => 'System development, database design and interface improvements.',
                'solution' => 'A single Laravel system modelling the organisation hierarchy, employees and positions, issuing a digital ID per employee, and connecting services to that identity. Access is scoped by organisation boundary.',
                'outcome' => 'Staff information and services managed from one place, with permissions enforced per organisation.',
                'stack' => ['Laravel', 'PHP', 'MySQL', 'Blade', 'Tailwind CSS', 'Alpine.js'],
                'repository_url' => 'https://github.com/erkihun/EUISIS',
            ],
            [
                'slug' => 'recruitment-portal',
                'title' => self::both('Recruitment Portal', 'የቅጥር ፖርታል'),
                'subtitle' => self::both('Job advertisement & application management', 'የሥራ ማስታወቂያ እና የማመልከቻ አስተዳደር'),
                'category' => ProjectCategory::Software,
                'mock' => 'jobs',
                'summary' => self::both(
                    'Publishes vacancies, registers applicants and manages applications against eligibility rules.',
                    'ክፍት የሥራ መደቦችን ያወጣል፣ አመልካቾችን ይመዘግባል፣ ማመልከቻዎችንም በብቁነት መስፈርቶች መሠረት ያስተዳድራል።',
                ),
                'problem' => 'Applications arrived on paper with no consistent check of eligibility, and duplicate applications were hard to detect.',
                'role' => 'Built the applicant and admin areas, validation and the application workflow.',
                'solution' => 'An online portal where applicants register and apply to open positions. Eligibility criteria are managed per vacancy in a Filament admin, and duplicate applications are controlled by defined rules.',
                'outcome' => 'Applications received online, with eligibility and duplicate rules enforced by the system instead of by hand.',
                'stack' => ['Laravel', 'PHP', 'MySQL', 'Filament', 'Tailwind CSS'],
            ],
            [
                'slug' => 'court-case-management',
                'title' => self::both('Court Case Management', 'የፍርድ ቤት ጉዳዮች አስተዳደር'),
                'subtitle' => self::both('Case registration, assignment & appeals', 'የጉዳይ ምዝገባ፣ ምደባ እና ይግባኝ'),
                'category' => ProjectCategory::Software,
                'mock' => 'court',
                'summary' => self::both(
                    'Registers, searches, assigns and tracks court cases and appeals, with user permissions.',
                    'የፍርድ ቤት ጉዳዮችንና ይግባኞችን ይመዘግባል፣ ይፈልጋል፣ ይመድባል እንዲሁም ይከታተላል፤ በተጠቃሚ ፈቃዶች ይጠበቃል።',
                ),
                'problem' => 'Case files were tracked manually, making it hard to find a case, see who it was assigned to, or follow an appeal.',
                'role' => 'Web application development, dashboard, case management and user access structure.',
                'solution' => 'A case management system with registration, search, assignment, appeals and role-based permissions, plus a dashboard for workload tracking.',
                'outcome' => 'Case information held in an organised structure and work follow-up made faster.',
                'stack' => ['Laravel', 'PHP', 'MySQL', 'Blade', 'Tailwind CSS', 'Alpine.js'],
                'repository_url' => 'https://github.com/erkihun/court-ms',
            ],
            [
                'slug' => 'tvet-bureau-identity',
                'title' => self::both('TVET Bureau Identity', 'የቴክኒክና ሙያ ቢሮ መለያ'),
                'subtitle' => self::both('Addis Ababa TVET Bureau · logo & brand identity', 'የአዲስ አበባ ቴክኒክና ሙያ ትምህርትና ሥልጠና ቢሮ · አርማ እና የብራንድ መለያ'),
                'category' => ProjectCategory::Design,
                'mock' => 'brand',
                'summary' => self::both(
                    'Logo concepts, colour system, typography and brand usage guidelines for the bureau.',
                    'ለቢሮው የአርማ ጽንሰ ሐሳቦች፣ የቀለም ሥርዓት፣ የፊደል አጻጻፍ እና የብራንድ አጠቃቀም መመሪያ።',
                ),
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
            [Discipline::Engineering, ['Backend', 'ባክኤንድ'], ['PHP', 'Laravel 12', 'REST APIs', 'Authentication', 'Roles & permissions']],
            [Discipline::Engineering, ['Frontend', 'ፍሮንትኤንድ'], ['Blade', 'Tailwind CSS', 'Alpine.js', 'JavaScript', 'HTML/CSS']],
            [Discipline::Engineering, ['Data & admin', 'ዳታ እና አስተዳደር'], ['MySQL', 'Filament', 'Database design', 'Dashboards', 'Reporting']],
            [Discipline::Engineering, ['Tools', 'መሣሪያዎች'], ['VS Code', 'Git', 'GitHub']],
            [Discipline::Design, ['Interface', 'በይነገጽ'], ['UI/UX', 'Responsive interfaces', 'Visual consistency']],
            [Discipline::Design, ['Brand', 'ብራንድ'], ['Logo design', 'Brand identity', 'Typography']],
            [Discipline::Design, ['Localization', 'አካባቢያዊ ማድረግ'], ['Amharic / English UI', 'Ethiopian & Gregorian dates']],
        ];

        foreach ($groups as $order => [$discipline, [$en, $am], $items]) {
            $group = SkillGroup::query()->where('discipline', $discipline)->where('label->en', $en)->first() ?? new SkillGroup;
            $group->fill(['discipline' => $discipline, 'label' => self::both($en, $am), 'items' => $items, 'sort_order' => $order])->save();
        }
    }

    private function process(): void
    {
        $steps = [
            [
                ['Map the process', 'ሂደቱን መለየት'],
                ['Sit with the people who do the work today and document every form, approval and exception.', 'ዛሬ ሥራውን ከሚሠሩት ሰዎች ጋር ተቀምጬ እያንዳንዱን ቅጽ፣ ማጽደቂያ እና ልዩ ሁኔታ እመዘግባለሁ።'],
            ],
            [
                ['Model the data', 'መረጃውን መቅረጽ'],
                ['Design the schema, organisation boundaries and roles before any screen is built.', 'ማንኛውም ገጽ ከመገንባቱ በፊት የዳታቤዝ መዋቅሩን፣ የድርጅት ወሰኖችን እና ሚናዎችን እቀርፃለሁ።'],
            ],
            [
                ['Build & secure', 'መገንባት እና ማስጠበቅ'],
                ['Ship in small increments with validation, permissions and audit trails in place.', 'ማረጋገጫ፣ ፈቃዶች እና የኦዲት መዝገቦች በቦታቸው ሆነው በትንንሽ ደረጃዎች አቀርባለሁ።'],
            ],
            [
                ['Design & refine', 'መቅረጽ እና ማሻሻል'],
                ['Make the interface consistent, bilingual and easy for first-time users.', 'በይነገጹን ወጥ፣ ባለሁለት ቋንቋ እና ለመጀመሪያ ጊዜ ተጠቃሚዎች ቀላል አደርገዋለሁ።'],
            ],
        ];

        foreach ($steps as $order => [$title, $description]) {
            $step = ProcessStep::query()->where('title->en', $title[0])->first() ?? new ProcessStep;
            $step->fill(['title' => self::both(...$title), 'description' => self::both(...$description), 'sort_order' => $order])->save();
        }
    }

    private function experience(): void
    {
        // Dates and education are left for the owner to complete in /admin.
        $rows = [
            [
                ExperienceType::Work,
                ['Software Developer', 'ሶፍትዌር ገንቢ'],
                ['Public institution', 'የመንግሥት ተቋም'],
                ['Digitising institutional processes, managing information and improving the usability of internal systems.', 'ተቋማዊ ሂደቶችን ዲጂታል ማድረግ፣ መረጃን ማስተዳደር እና የውስጥ ሥርዓቶችን አጠቃቀም ማሻሻል።'],
            ],
            [
                ExperienceType::Work,
                ['Graphic & Brand Designer', 'ግራፊክ እና ብራንድ ዲዛይነር'],
                ['Independent', 'በግል'],
                ['Logo design and brand identity work for institutions, including the Addis Ababa TVET Bureau.', 'ለተቋማት የአርማ ዲዛይን እና የብራንድ መለያ ሥራ፣ የአዲስ አበባ ቴክኒክና ሙያ ትምህርትና ሥልጠና ቢሮን ጨምሮ።'],
            ],
        ];

        foreach ($rows as $order => [$type, $title, $organization, $description]) {
            $row = Experience::query()->where('title->en', $title[0])->where('organization->en', $organization[0])->first() ?? new Experience;
            $row->fill([
                'type' => $type,
                'title' => self::both(...$title),
                'organization' => self::both(...$organization),
                'description' => self::both(...$description),
                'sort_order' => $order,
            ])->save();
        }
    }

    /**
     * A translated value: English plus the Amharic draft (TODO(review-am)).
     *
     * @return array{en: string, am: string}
     */
    private static function both(string $en, string $am): array
    {
        return ['en' => $en, 'am' => $am];
    }
}
