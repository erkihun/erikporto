<?php

declare(strict_types=1);

use App\Models\Experience;
use App\Models\ProcessStep;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config([
        'portfolio.admin.email' => 'admin@example.com',
        'portfolio.admin.password' => 'a-long-test-password',
    ]);
});

it('seeds only the admin user in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::query()->where('email', 'admin@example.com')->sole()->is_admin)->toBeTrue()
        ->and(Profile::count())->toBe(0)
        ->and(Project::count())->toBe(0)
        ->and(SkillGroup::count())->toBe(0)
        ->and(Experience::count())->toBe(0)
        ->and(ProcessStep::count())->toBe(0);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('ComingSoon'));
});

it('seeds demo content outside production', function (string $environment): void {
    app()->detectEnvironment(fn (): string => $environment);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::query()->where('is_admin', true)->count())->toBe(1)
        ->and(Profile::count())->toBe(1)
        ->and(Project::count())->toBeGreaterThan(0)
        ->and(SkillGroup::count())->toBeGreaterThan(0);
})->with(['local', 'testing']);
