<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * A public record that is posted once to the Telegram channel when it goes live.
 */
interface AnnouncesOnTelegram
{
    /** Whether the record is publicly visible right now. */
    public function isLive(): bool;

    /** Telegram HTML message (only <b>, <i>, <a> are used). */
    public function telegramMessage(): string;

    public function wasAnnounced(): bool;

    public function markAnnounced(): void;
}
