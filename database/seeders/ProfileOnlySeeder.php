<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;

/**
 * A just-launched site: the profile is filled in, nothing else yet. Used to check the
 * empty states in a browser (PERF_SEEDER=ProfileOnlySeeder node scripts/perf-server.mjs).
 * Never runs in production.
 */
final class ProfileOnlySeeder extends Seeder
{
    public function run(): void
    {
        $profile = new Profile;
        $profile->fill([
            'name' => 'Erkihun Bewket',
            'headline' => ['en' => 'Software Developer & Graphic Designer', 'am' => 'ሶፍትዌር ገንቢ እና ግራፊክ ዲዛይነር'],
            'lede' => ['en' => 'I build Laravel systems for institutions and design the brand identities that represent them.'],
            'bio' => ['en' => ['I work as a software developer in a public institution.']],
            'email' => 'hello@example.com',
            'is_available' => true,
        ])->save();
    }
}
