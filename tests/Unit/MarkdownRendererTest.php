<?php

declare(strict_types=1);

use App\Support\Markdown\MarkdownRenderer;

beforeEach(fn () => $this->renderer = new MarkdownRenderer);

it('escapes raw HTML instead of rendering it', function (): void {
    $html = $this->renderer->render("Hello\n\n<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>")->html;

    expect($html)
        ->not->toContain('<script')
        ->not->toContain('<img src=x')
        ->toContain('&lt;script&gt;');
});

it('drops dangerous link schemes', function (string $markdown): void {
    $html = $this->renderer->render($markdown)->html;

    expect(strtolower($html))->not->toContain('javascript:')->not->toContain('vbscript:');
})->with([
    'javascript link' => ['[click](javascript:alert(1))'],
    'encoded javascript' => ['[click](JaVaScRiPt:alert(1))'],
    'vbscript link' => ['[click](vbscript:msgbox(1))'],
    'javascript image' => ['![x](javascript:alert(1))'],
]);

it('marks external links and keeps internal ones in the same tab', function (): void {
    config(['app.url' => 'https://erkihun.dev']);
    $html = (new MarkdownRenderer)->render('[gh](https://github.com/erkihun) and [home](https://erkihun.dev/notes)')->html;

    expect($html)
        ->toContain('<a rel="noopener noreferrer" target="_blank" href="https://github.com/erkihun">gh</a>')
        ->toContain('<a href="https://erkihun.dev/notes">home</a>');
});

it('builds a table of contents from h2 and h3 with stable ids, including Amharic', function (): void {
    $toc = $this->renderer->render("# Title\n\n## Why it matters\n\ntext\n\n### ዝርዝር\n\n#### too deep")->toc;

    expect($toc)->toBe([
        ['id' => 'why-it-matters', 'text' => 'Why it matters', 'level' => 2],
        ['id' => 'ዝርዝር', 'text' => 'ዝርዝር', 'level' => 3],
    ]);
});

it('renders GitHub-flavoured tables and fenced code with a language class', function (): void {
    $html = $this->renderer->render("| a | b |\n|---|---|\n| 1 | 2 |\n\n```php\necho '<b>';\n```")->html;

    expect($html)->toContain('<table>')
        ->toContain('<code class="language-php">echo \'&lt;b&gt;\';');
});

it('estimates reading time with a one minute minimum, counting Amharic words', function (): void {
    expect(MarkdownRenderer::readingMinutes('short'))->toBe(1)
        ->and(MarkdownRenderer::readingMinutes(str_repeat('word ', 401)))->toBe(3)
        ->and(MarkdownRenderer::readingMinutes(str_repeat('ሰላም ', 450)))->toBe(3);
});
