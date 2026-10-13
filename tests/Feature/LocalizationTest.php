<?php

declare(strict_types=1);

use App\Enums\Discipline;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Profile::factory()->create([
        'headline' => ['en' => 'Software Developer', 'am' => 'ሶፍትዌር ገንቢ'],
        'lede' => ['en' => 'I build systems.'],
        'facts' => [['value' => '4', 'label' => ['en' => 'Featured projects', 'am' => 'ፕሮጀክቶች']]],
    ]);
});

it('serves the Amharic site under /am with Amharic content and UI strings', function (): void {
    Project::factory()->create(['slug' => 'euisis', 'title' => ['en' => 'EUISIS', 'am' => 'ኢዩሲስ']]);

    $this->get('/am')
        ->assertOk()
        ->assertSee('<html lang="am">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('locale', 'am')
            ->where('urls.home', '/am')
            ->where('urls.contact', '/am/contact')
            ->where('profile.headline', 'ሶፍትዌር ገንቢ')
            ->where('profile.facts.0.label', 'ፕሮጀክቶች')
            ->where('projects.0.title', 'ኢዩሲስ')
            ->where('projects.0.url', '/am/work/euisis')
            ->where('translations', fn ($t): bool => $t['nav.work'] === __('nav.work', locale: 'am'))
            ->where('inquiryTypes.0.label', __('enums.inquiry.web_system', locale: 'am')));
});

it('falls back to English when a field has no Amharic translation', function (): void {
    Project::factory()->create(['slug' => 'euisis', 'summary' => ['en' => 'English only.']]);

    $this->get('/am')
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.lede', 'I build systems.')
            ->where('projects.0.summary', 'English only.'));
});

it('keeps English and Amharic responses in separate cache entries', function (): void {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('profile.headline', 'Software Developer'));
    $this->get('/am')->assertInertia(fn (Assert $page) => $page->where('profile.headline', 'ሶፍትዌር ገንቢ'));
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('profile.headline', 'Software Developer'));
});

it('links every language version of a page for the switcher and hreflang', function (): void {
    Project::factory()->create(['slug' => 'euisis']);
    $base = rtrim((string) config('app.url'), '/');

    $this->get('/am/work/euisis')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('locales.0.code', 'en')
            ->where('locales.0.url', "{$base}/work/euisis")
            ->where('locales.0.current', false)
            ->where('locales.0.alternate', true)
            ->where('locales.1.code', 'am')
            ->where('locales.1.url', "{$base}/am/work/euisis")
            ->where('locales.1.og_locale', 'am_ET')
            ->where('locales.1.current', true));
});

it('keeps notes in one language and points the switcher at each home page', function (): void {
    $base = rtrim((string) config('app.url'), '/');

    $this->get('/notes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('locales.0.alternate', false)
            ->where('locales.1.url', "{$base}/am"));

    $this->get('/am/notes')->assertNotFound();
});

it('converts existing content to translations and back without losing it', function (): void {
    if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
        // DDL commits the test transaction there; CI runs this round trip with artisan instead.
        $this->markTestSkipped('Covered by the MySQL CI job.');
    }

    $migration = require database_path('migrations/2026_10_14_000004_make_content_translatable.php');
    Project::factory()->create([
        'slug' => 'euisis',
        'title' => ['en' => 'EUISIS', 'am' => 'ኢዩሲስ'],
        'metrics' => [['value' => '1,200', 'label' => ['en' => 'records', 'am' => 'መዝገቦች']]],
    ]);

    $migration->down();
    $row = DB::table('projects')->where('slug', 'euisis')->first();
    $profile = DB::table('profiles')->first();
    expect($row->title)->toBe('EUISIS')
        ->and(json_decode($row->metrics, true))->toBe([['value' => '1,200', 'label' => 'records']])
        ->and($profile->headline)->toBe('Software Developer')
        ->and(json_decode($profile->facts, true))->toBe([['value' => '4', 'label' => 'Featured projects']]);

    $migration->up();
    $project = Project::query()->where('slug', 'euisis')->sole();
    expect($project->getTranslations('title'))->toBe(['en' => 'EUISIS'])
        ->and($project->metrics)->toBe([['value' => '1,200', 'label' => ['en' => 'records']]])
        ->and(Profile::current()?->getTranslations('headline'))->toBe(['en' => 'Software Developer']);
});

it('lists both languages with alternates in the sitemap', function (): void {
    Project::factory()->create(['slug' => 'euisis']);
    $base = rtrim((string) config('app.url'), '/');

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee("<loc>{$base}/am/work/euisis</loc>", false)
        ->assertSee('hreflang="am" href="'.$base.'/am/work/euisis"', false)
        ->assertSee('hreflang="x-default" href="'.$base.'/work/euisis"', false);
});

it('validates the Amharic contact form in Amharic', function (): void {
    $this->from('/am')
        ->post('/am/contact', ['name' => '', 'email' => 'not-an-email', 'inquiry_type' => 'web_system', 'message' => 'short'])
        ->assertRedirect('/am')
        ->assertSessionHasErrors([
            'name' => __('contact.validation.name_required', locale: 'am'),
            'email' => __('contact.validation.email_invalid', locale: 'am'),
        ]);
});

it('formats experience periods and capability labels in Amharic', function (): void {
    Experience::factory()->create([
        'title' => ['en' => 'Developer', 'am' => 'ገንቢ'],
        'starts_on' => Carbon::parse('2023-01-01'),
        'ends_on' => null,
    ]);
    SkillGroup::factory()->create(['discipline' => Discipline::Engineering, 'label' => ['en' => 'Backend', 'am' => 'ባክኤንድ']]);

    $this->get('/am')
        ->assertInertia(fn (Assert $page) => $page
            ->where('experience.0.title', 'ገንቢ')
            ->where('experience.0.period', fn (string $period): bool => str_ends_with($period, __('experience.present', locale: 'am')))
            ->where('capabilities.0.title', __('enums.discipline.engineering', locale: 'am'))
            ->where('capabilities.0.groups.0.label', 'ባክኤንድ'));
});

it('does not store blank translations', function (): void {
    $project = Project::factory()->create(['title' => ['en' => 'EUISIS', 'am' => '']]);

    expect($project->getTranslations('title'))->toBe(['en' => 'EUISIS'])
        ->and(json_decode((string) DB::table('projects')->where('id', $project->id)->value('title'), true))->toBe(['en' => 'EUISIS']);
});

it('has the same UI string keys in English and Amharic, none of them blank', function (): void {
    $en = json_decode((string) file_get_contents(lang_path('en.json')), true);
    $am = json_decode((string) file_get_contents(lang_path('am.json')), true);

    expect(array_keys($am))->toEqualCanonicalizing(array_keys($en))
        ->and(array_filter($am, static fn (mixed $value): bool => ! is_string($value) || trim($value) === ''))->toBe([]);
});
