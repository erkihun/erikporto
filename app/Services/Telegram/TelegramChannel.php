<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Bot API client for posting to the owner's public channel.
 */
final readonly class TelegramChannel
{
    public function __construct(
        private ?string $botToken,
        private ?string $channelId,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('services.telegram.bot_token'), config('services.telegram.channel_id'));
    }

    public function isConfigured(): bool
    {
        return filled($this->botToken) && filled($this->channelId);
    }

    /**
     * @throws RequestException when Telegram rejects the message, so the queued job retries
     */
    public function post(string $html): void
    {
        Http::baseUrl('https://api.telegram.org')
            ->timeout(10)
            ->asJson()
            ->post("/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->channelId,
                'text' => $html,
                'parse_mode' => 'HTML',
                'link_preview_options' => ['is_disabled' => false],
            ])
            ->throw();
    }
}
