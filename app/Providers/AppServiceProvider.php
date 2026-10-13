<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Telegram\TelegramChannel;
use App\Support\Images\ResponsiveImages;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TelegramChannel::class, static fn (): TelegramChannel => TelegramChannel::fromConfig());
        // Per request (and per Octane request): a page preloads its image rows once.
        $this->app->scoped(ResponsiveImages::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        RateLimiter::for('contact', static function (Request $request): array {
            $tooMany = static fn () => back()->withErrors([
                'message' => __('contact.validation.rate_limited'),
            ]);

            return [
                Limit::perMinute((int) config('portfolio.contact.per_minute'))->by('min:'.$request->ip())->response($tooMany),
                Limit::perDay((int) config('portfolio.contact.per_day'))->by('day:'.$request->ip())->response($tooMany),
            ];
        });
    }
}
