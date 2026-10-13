<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('portfolio.admin.email');
        $password = config('portfolio.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Skipping admin user: set PORTFOLIO_ADMIN_EMAIL and PORTFOLIO_ADMIN_PASSWORD in .env.');

            return;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => config('portfolio.admin.name'),
            'password' => $password, // hashed by the model cast
            'is_admin' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
