<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\NoteStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

/**
 * Publication state derived from one `published_at` column: null = draft,
 * future = scheduled, past = published. Shared by notes and /now updates.
 *
 * @property Carbon|null $published_at
 * @property-read NoteStatus $status
 */
trait HasPublicationDate
{
    /** Seconds until the next scheduled record goes live, used to expire cached pages on time. */
    public static function secondsUntilNextScheduled(): ?int
    {
        $next = static::query()->where('published_at', '>', now())->min('published_at');

        return $next === null ? null : max(1, (int) ceil(now()->diffInSeconds(Carbon::parse($next))));
    }

    public function isLive(): bool
    {
        return $this->status === NoteStatus::Published;
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /** @return Attribute<NoteStatus, never> */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): NoteStatus => NoteStatus::fromPublishedAt($this->published_at));
    }
}
