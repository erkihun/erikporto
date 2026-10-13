<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Discipline;
use App\Models\SkillGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SkillGroup> */
final class SkillGroupFactory extends Factory
{
    protected $model = SkillGroup::class;

    public function definition(): array
    {
        return [
            'discipline' => Discipline::Engineering,
            'label' => fake()->word(),
            'items' => fake()->words(3),
            'sort_order' => 0,
        ];
    }
}
