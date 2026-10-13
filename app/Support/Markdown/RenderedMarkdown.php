<?php

declare(strict_types=1);

namespace App\Support\Markdown;

final readonly class RenderedMarkdown
{
    /**
     * @param  list<array{id: string, text: string, level: int}>  $toc
     */
    public function __construct(
        public string $html,
        public array $toc,
        public int $readingMinutes,
    ) {}
}
