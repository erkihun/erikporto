<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Profile> */
final class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'headline' => 'Software Developer & Graphic Designer',
            'lede' => fake()->sentence(18),
            'bio' => [fake()->paragraph(), fake()->paragraph()],
            'email' => fake()->safeEmail(),
            'is_available' => true,
            'social_links' => [['label' => 'GitHub', 'url' => 'https://github.com/example', 'handle' => '@example']],
            'facts' => [['value' => '4', 'label' => ['en' => 'Featured projects']]],
        ];
    }
}
