<?php

declare(strict_types=1);

namespace App\Support\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Code blocks scroll sideways on narrow screens, so keyboard users must be able to reach
 * them: the <pre> gets tabindex="0" (axe: scrollable-region-focusable). Wraps CommonMark's
 * own fenced/indented code renderers and changes nothing else.
 */
final readonly class FocusableCodeBlockRenderer implements NodeRendererInterface
{
    public function __construct(private NodeRendererInterface $inner) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $html = $this->inner->render($node, $childRenderer);

        if ($html instanceof HtmlElement && $html->getTagName() === 'pre') {
            $html->setAttribute('tabindex', '0');
        }

        return $html;
    }
}
