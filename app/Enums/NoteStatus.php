<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Derived from `published_at`, never stored: one column cannot disagree with itself.
 */
enum NoteStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public static function fromPublishedAt(?CarbonInterface $publishedAt): self
    {
        return match (true) {
            $publishedAt === null => self::Draft,
            $publishedAt->isFuture() => self::Scheduled,
            default => self::Published,
        };
    }

    public function getLabel(): string
    {
        return __(ucfirst($this->value));
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Published => 'success',
        };
    }
}
