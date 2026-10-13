<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Profile;

/**
 * Props for the Error page. It must render even when the error is the database
 * itself (500/503), so every lookup has a static fallback.
 */
final class ErrorPageQuery
{
    /** @return array{status: int, owner: array{name: string}} */
    public static function props(int $status): array
    {
        $name = rescue(static fn (): ?string => Profile::current()?->name, null, report: false);

        return [
            'status' => $status,
            'owner' => ['name' => filled($name) ? $name : (string) config('app.name')],
        ];
    }
}
