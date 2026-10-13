<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectCategory;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Project> */
final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'subtitle' => fake()->sentence(5),
            'category' => ProjectCategory::Software,
            'summary' => fake()->sentence(14),
            'problem' => fake()->paragraph(),
            'role' => fake()->sentence(),
            'solution' => fake()->paragraph(),
            'outcome' => fake()->sentence(),
            'stack' => ['Laravel', 'PHP', 'MySQL'],
            'mock' => 'court',
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function draft(): self
    {
        return $this->state(['is_published' => false]);
    }

    public function design(): self
    {
        return $this->state(['category' => ProjectCategory::Design, 'mock' => 'brand']);
    }
}
