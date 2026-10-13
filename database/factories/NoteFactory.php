<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Note> */
final class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'excerpt' => fake()->sentence(16),
            'body' => "## Context\n\n".fake()->paragraph()."\n\n## Approach\n\n".fake()->paragraph(),
            'tags' => ['laravel'],
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): self
    {
        return $this->state(['published_at' => null]);
    }

    public function scheduled(string $in = '+1 hour'): self
    {
        return $this->state(['published_at' => now()->modify($in)]);
    }
}
