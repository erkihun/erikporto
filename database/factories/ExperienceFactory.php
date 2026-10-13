<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExperienceType;
use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Experience> */
final class ExperienceFactory extends Factory
{
    protected $model = Experience::class;

    public function definition(): array
    {
        return [
            'type' => ExperienceType::Work,
            'title' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'starts_on' => fake()->dateTimeBetween('-6 years', '-1 year'),
            'ends_on' => null,
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
