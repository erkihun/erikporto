<?php

declare(strict_types=1);

namespace App\Support\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Block\IndentedCodeRenderer;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Converts editor Markdown into HTML that is safe to inject with dangerouslySetInnerHTML.
 *
 * Safety comes from the parser configuration, not from post-filtering:
 * raw HTML is escaped and javascript:/data: links are dropped by CommonMark itself.
 */
final class MarkdownRenderer
{
    private const WORDS_PER_MINUTE = 200;

    private ?MarkdownConverter $converter = null;

    public function render(string $markdown): RenderedMarkdown
    {
        $result = $this->converter()->convert($markdown);

        $toc = [];
        foreach ($result->getDocument()->iterator() as $node) {
            if (! $node instanceof Heading || $node->getLevel() > 3 || $node->getLevel() < 2) {
                continue;
            }

            $id = $node->data->get('attributes/id', null);
            if (! is_string($id) || $id === '') {
                continue;
            }

            $toc[] = [
                'id' => $id,
                'text' => StringContainerHelper::getChildText($node),
                'level' => $node->getLevel(),
            ];
        }

        return new RenderedMarkdown(
            html: (string) $result->getContent(),
            toc: $toc,
            readingMinutes: self::readingMinutes($markdown),
        );
    }

    public static function readingMinutes(string $markdown): int
    {
        // Unicode-aware word count so Amharic text is counted too.
        $words = preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($markdown));

        return max(1, (int) ceil(((int) $words) / self::WORDS_PER_MINUTE));
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'id_prefix' => '',
                'fragment_prefix' => '',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
            ],
            'external_link' => [
                'internal_hosts' => [(string) parse_url((string) config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addEventListener(DocumentParsedEvent::class, new ResponsiveMarkdownImages);
        // Above the core renderers' priority (0): same output plus a focusable <pre>.
        $environment->addRenderer(FencedCode::class, new FocusableCodeBlockRenderer(new FencedCodeRenderer), 10);
        $environment->addRenderer(IndentedCode::class, new FocusableCodeBlockRenderer(new IndentedCodeRenderer), 10);

        return $this->converter = new MarkdownConverter($environment);
    }
}
