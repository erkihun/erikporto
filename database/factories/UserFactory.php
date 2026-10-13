<?php

namespace Database\Factories;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['is_admin' => true]);
    }

    /**
     * An authenticator app is set up (secret + recovery codes), as the admin panel requires.
     * The secret is returned by getAppAuthenticationSecret() for generating test codes.
     */
    public function withMfa(): static
    {
        return $this->afterMaking(static function (User $user): void {
            $provider = AppAuthentication::make();
            $user->app_authentication_secret = $provider->generateSecret();
            $user->app_authentication_recovery_codes = array_map(
                static fn (string $code): string => Hash::make($code),
                ['recovery-code-one', 'recovery-code-two'],
            );
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
