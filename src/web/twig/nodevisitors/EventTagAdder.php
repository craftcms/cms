<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use Craft;
use craft\helpers\Html;
use Twig\Environment;
use Twig\Node\DoNode;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use yii\base\InvalidArgumentException;

/**
 * EventTagAdder adds missing `head()`, `beginBody()`, and `endBody()` event tags to templates as they’re being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EventTagAdder extends BaseEventTagVisitor
{
    /**
     * @var string|null As much of the <body> tag as we’ve found so far
     */
    private ?string $_bodyTag = null;

    /**
     * @var int|null The end position of the last <body> tag we successfully parsed in $_bodyTag
     */
    private ?int $_bodyAttrOffset = null;

    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        // Ignore if we're not rendering a page template
        if (!Craft::$app->getView()->getIsRenderingPageTemplate()) {
            return $node;
        }

        // If this is a text node and we're still adding event tags, process it
        if ($node instanceof TextNode && !static::foundAllEventTags()) {
            $node = $this->_processTextNode($node, $env);
        }

        return $node;
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        // This needs to run after EventTagFinder
        return 1;
    }

    /**
     * Processes a text node.
     *
     * @param TextNode $node
     * @param Environment $env
     * @return Node
     */
    private function _processTextNode(TextNode $node, Environment $env): Node
    {
        $data = $node->getAttribute('data');

        // Did we just find `</head>`?
        if (static::$foundHead === false && ($endHeadPos = stripos($data, '</head>')) !== false) {
            static::$foundHead = true;

            return $this->_insertEventNode($node, $endHeadPos, 'head');
        }

        // Are we looking for `<body>`?
        if (static::$foundBeginBody === false) {
            if (($newNode = $this->_findBeginBody($node)) !== null) {
                return $newNode;
            }
        }

        // Did we just find `</body>`?
        if (static::$foundEndBody === false && ($endBodyPos = stripos($data, '</body>')) !== false) {
            static::$foundEndBody = true;

            return $this->_insertEventNode($node, $endBodyPos, 'endBody');
        }

        return $node;
    }

    /**
     * Searches the text node for the beginning of the `<body>` tag.
     *
     * @param TextNode $node
     * @return Node|null
     */
    private function _findBeginBody(TextNode $node): ?Node
    {
        $data = $node->getAttribute('data');

        // Does it start here?
        if (!isset($this->_bodyTag)) {
            if (!preg_match('/<body\b/i', $data, $matches, PREG_OFFSET_CAPTURE)) {
                return null;
            }

            $offsetOffset = $matches[0][1];
            $this->_bodyTag = substr($data, $matches[0][1]);
            $this->_bodyAttrOffset = 5;
        } else {
            // Append this text node to $_bodyTag
            $offsetOffset = -strlen($this->_bodyTag);
            $this->_bodyTag .= $data;
        }

        do {
            try {
                $attribute = Html::parseTagAttribute($this->_bodyTag, $this->_bodyAttrOffset, $start, $end);
            } catch (InvalidArgumentException) {
                // The tag is probably split between a couple text nodes. Keep trying on the next text node
                break;
            }

            // No more attributes?
            if ($attribute === null) {
                static::$foundBeginBody = true;
                $beginBodyPos = $offsetOffset + strpos($this->_bodyTag, '>', $this->_bodyAttrOffset) + 1;
                return $this->_insertEventNode($node, $beginBodyPos, 'beginBody');
            }

            // Try again where this one ended
            $this->_bodyAttrOffset = $end;
        } while (true);

        return null;
    }

    /**
     * Inserts a new event function node at a specific point in a given text node’s data.
     *
     * @param TextNode $node
     * @param int $pos
     * @param string $functionName
     * @return Node
     */
    private function _insertEventNode(TextNode $node, int $pos, string $functionName): Node
    {
        $data = $node->getAttribute('data');
        $preSplitHtml = substr($data, 0, $pos);
        $postSplitHtml = substr($data, $pos);
        $startLine = $node->getTemplateLine();
        $splitLine = $startLine + substr_count($preSplitHtml, "\n");

        return new Node([
            new TextNode($preSplitHtml, $startLine),
            new DoNode(new FunctionExpression($functionName, new Node(), $splitLine), $splitLine),
            new TextNode($postSplitHtml, $splitLine),
        ], [], $startLine);
    }
}
