<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // Production starts empty: real content is entered in /admin.
        if (app()->isLocal() || app()->runningUnitTests()) {
            $this->call(DemoContentSeeder::class);
        }
    }
}
