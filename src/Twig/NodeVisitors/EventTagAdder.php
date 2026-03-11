<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\NodeVisitors;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Nodes\BaseNode;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\TemplateRenderer;
use InvalidArgumentException;
use Twig\Environment;
use Twig\Node\DoNode;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\TwigFunction;

/**
 * EventTagAdder adds missing `head()`, `beginBody()`, and `endBody()`
 * event tags to templates as they’re being compiled.
 */
final class EventTagAdder extends BaseEventTagVisitor
{
    /**
     * @var string|null As much of the <body> tag as we’ve found so far
     */
    private ?string $bodyTag = null;

    /**
     * @var int|null The end position of the last <body> tag we successfully parsed in
     */
    private ?int $bodyAttrOffset = null;

    public function __construct(
        private readonly PageLifecycle $lifecycle,
    ) {}

    public function enterNode(Node $node, Environment $env): Node
    {
        // Ignore if we're not rendering a page template
        if (! app(TemplateRenderer::class)->isRenderingPageTemplate()) {
            return $node;
        }

        // If this is a text node and we're still adding event tags, process it
        if ($node instanceof TextNode && ! self::foundAllEventTags()) {
            return $this->processTextNode($node);
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        // This needs to run after EventTagFinder
        return 1;
    }

    private function processTextNode(TextNode $node): Node
    {
        $data = $node->getAttribute('data');

        // Did we just find `</head>`?
        if (self::$foundHead === false && ($endHeadPos = stripos((string) $data, '</head>')) !== false) {
            self::$foundHead = true;

            return $this->insertEventNode($node, $endHeadPos, 'head');
        }

        // Are we looking for `<body>`?
        if (self::$foundBeginBody === false && ($newNode = $this->findBeginBody($node)) !== null) {
            return $newNode;
        }

        // Did we just find `</body>`?
        if (self::$foundEndBody === false && ($endBodyPos = stripos((string) $data, '</body>')) !== false) {
            self::$foundEndBody = true;

            return $this->insertEventNode($node, $endBodyPos, 'endBody');
        }

        return $node;
    }

    /**
     * Searches the text node for the beginning of the `<body>` tag.
     */
    private function findBeginBody(TextNode $node): ?Node
    {
        $data = $node->getAttribute('data');

        // Does it start here?
        if (! isset($this->bodyTag)) {
            if (! preg_match('/<body\b/i', (string) $data, $matches, PREG_OFFSET_CAPTURE)) {
                return null;
            }

            $offsetOffset = $matches[0][1];
            $this->bodyTag = substr((string) $data, $matches[0][1]);
            $this->bodyAttrOffset = 5;
        } else {
            // Append this text node to $_bodyTag
            $offsetOffset = -strlen($this->bodyTag);
            $this->bodyTag .= $data;
        }

        do {
            try {
                $attribute = Html::parseTagAttribute(
                    html: $this->bodyTag,
                    offset: $this->bodyAttrOffset,
                    start: $start,
                    end: $end,
                );
            } catch (InvalidArgumentException) {
                // The tag is probably split between a couple text nodes. Keep trying on the next text node
                break;
            }

            // No more attributes?
            if ($attribute === null) {
                self::$foundBeginBody = true;
                $beginBodyPos = $offsetOffset + strpos($this->bodyTag, '>', $this->bodyAttrOffset) + 1;

                return $this->insertEventNode($node, $beginBodyPos, 'beginBody');
            }

            // Try again where this one ended
            $this->bodyAttrOffset = $end;
        } while (true);

        return null;
    }

    /**
     * Inserts a new event function node at a specific point in a given text node’s data.
     */
    private function insertEventNode(TextNode $node, int $pos, string $functionName): Node
    {
        $data = $node->getAttribute('data');
        $preSplitHtml = substr((string) $data, 0, $pos);
        $postSplitHtml = substr((string) $data, $pos);
        $startLine = $node->getTemplateLine();
        $splitLine = $startLine + substr_count($preSplitHtml, "\n");

        return new BaseNode([
            new TextNode($preSplitHtml, $startLine),
            new DoNode(new FunctionExpression(new TwigFunction($functionName, [$this->lifecycle, $functionName]), new BaseNode, $splitLine), $splitLine),
            new TextNode($postSplitHtml, $splitLine),
        ], [], $startLine);
    }
}
