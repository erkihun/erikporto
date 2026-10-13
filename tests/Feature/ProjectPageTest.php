<?php

declare(strict_types=1);

use App\Models\Profile;
use App\Models\Project;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Profile::factory()->create(['name' => 'Erkihun Bewket']));

it('shows a published case study with the next project', function (): void {
    $first = Project::factory()->create(['slug' => 'first', 'sort_order' => 1, 'metrics' => [['value' => '1,200', 'label' => 'records']]]);
    Project::factory()->create(['slug' => 'second', 'sort_order' => 2]);

    $this->get('/work/first')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('project.slug', 'first')
            ->where('project.problem', $first->problem)
            ->where('project.metrics.0.value', '1,200')
            ->where('position', ['index' => 1, 'total' => 2])
            ->where('next.slug', 'second')
            ->where('owner.name', 'Erkihun Bewket'));
});

it('wraps the next project back to the first', function (): void {
    Project::factory()->create(['slug' => 'first', 'sort_order' => 1]);
    Project::factory()->create(['slug' => 'last', 'sort_order' => 2]);

    $this->get('/work/last')->assertInertia(fn (Assert $page) => $page->where('next.slug', 'first'));
});

it('has no next project when only one is published', function (): void {
    Project::factory()->create(['slug' => 'only']);

    $this->get('/work/only')->assertInertia(fn (Assert $page) => $page->where('next', null));
});

it('returns 404 for drafts and unknown slugs', function (): void {
    Project::factory()->draft()->create(['slug' => 'secret']);

    $this->get('/work/secret')->assertNotFound();
    $this->get('/work/missing')->assertNotFound();
});
