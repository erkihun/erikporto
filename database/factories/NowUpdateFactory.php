<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NowUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NowUpdate> */
final class NowUpdateFactory extends Factory
{
    protected $model = NowUpdate::class;

    public function definition(): array
    {
        return [
            'body' => 'Building **'.fake()->words(3, true)."**.\n\n- ".fake()->sentence()."\n- ".fake()->sentence(),
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
