<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use Craft;
use Twig\Environment;
use Twig\Node\DoNode;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;

/**
 * EventTagAdder adds missing `head()`, `beginBody()`, and `endBody()` event tags to templates as they’re being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EventTagAdder extends BaseEventTagVisitor
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether we're in the middle of finding the `beginBody()` tag
     */
    private $_findingBeginBody = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env)
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
    public function leaveNode(Node $node, Environment $env)
    {
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        // This needs to run after EventTagFinder
        return 1;
    }

    // Private Methods
    // =========================================================================

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
            // We haven't found any part of `<body>` yet, right?
            if ($this->_findingBeginBody === false) {
                // Did we just find `<body(>)`?
                if (preg_match('/(<body\b[^>]*)(>)?/', $data, $matches, PREG_OFFSET_CAPTURE) === 1) {
                    // Did it include the `>`?
                    if (!empty($matches[2][0])) {
                        static::$foundBeginBody = true;
                        $beginBodyPos = $matches[0][1] + strlen($matches[0][0]);

                        return $this->_insertEventNode($node, $beginBodyPos, 'beginBody');
                    }

// Will have to wait for the next text node
                    $this->_findingBeginBody = true;
                }
            } else {
                // Did we just find the `>`?
                if (preg_match('/^[^>]*>/', $data, $matches)) {
                    $this->_findingBeginBody = false;
                    static::$foundBeginBody = true;
                    $beginBodyPos = strlen($matches[0]);

                    return $this->_insertEventNode($node, $beginBodyPos, 'beginBody');
                }
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
     * Inserts a new event function node at a specific point in a given text node’s data.
     *
     * @param TextNode $node
     * @param Environment $env
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
