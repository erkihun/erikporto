<?php

declare(strict_types=1);

use App\Enums\Discipline;
use App\Models\Experience;
use App\Models\ProcessStep;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use Inertia\Testing\AssertableInertia as Assert;

it('returns 503 until a profile is configured', function (): void {
    $this->get('/')->assertStatus(503);
});

it('renders the home page from database content', function (): void {
    Profile::factory()->create(['name' => 'Erkihun Bewket']);
    Project::factory()->create(['title' => 'EUISIS', 'slug' => 'euisis', 'sort_order' => 1]);
    Project::factory()->design()->create(['title' => 'Brand', 'slug' => 'brand', 'sort_order' => 2]);
    SkillGroup::factory()->create(['discipline' => Discipline::Engineering, 'label' => 'Backend', 'items' => ['Laravel']]);
    ProcessStep::query()->create(['title' => 'Map the process', 'description' => 'Interview users.']);
    Experience::factory()->create(['title' => 'Software Developer']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('profile.name', 'Erkihun Bewket')
            ->has('projects', 2)
            ->where('projects.0.slug', 'euisis')
            ->where('projects.0.url', '/work/euisis')
            ->where('projects.1.category', 'design')
            ->has('capabilities', 1)
            ->where('capabilities.0.groups.0.items', ['Laravel'])
            ->has('process', 1)
            ->has('experience', 1)
            ->has('inquiryTypes', 4)
        );
});

it('hides unpublished projects', function (): void {
    Profile::factory()->create();
    Project::factory()->create(['slug' => 'live']);
    Project::factory()->draft()->create(['slug' => 'draft']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->has('projects', 1)
        ->where('projects.0.slug', 'live'));
});

it('serves fresh content after an edit even though the page is cached', function (): void {
    Profile::factory()->create();
    $project = Project::factory()->create(['title' => 'Old title']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('projects.0.title', 'Old title'));

    $project->update(['title' => 'New title']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('projects.0.title', 'New title'));
});

it('never exposes empty social links or private storage paths', function (): void {
    Profile::factory()->create([
        'cv_path' => 'cv/private.pdf',
        'social_links' => [
            ['label' => 'GitHub', 'url' => 'https://github.com/erkihun', 'handle' => '@erkihun'],
            ['label' => 'LinkedIn', 'url' => null, 'handle' => null],
        ],
    ]);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->has('profile.social_links', 1)
        ->where('profile.cv_url', '/cv')
        ->missing('profile.cv_path'));
});
