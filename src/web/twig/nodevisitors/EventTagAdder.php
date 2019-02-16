<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use Craft;

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
    public function enterNode(\Twig_Node $node, \Twig_Environment $env)
    {
        // Ignore if we're not rendering a page template
        if (!Craft::$app->getView()->getIsRenderingPageTemplate()) {
            return $node;
        }

        // If this is a text node and we're still adding event tags, process it
        if ($node instanceof \Twig_Node_Text && !static::foundAllEventTags()) {
            $node = $this->_processTextNode($node, $env);
        }

        return $node;
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(\Twig_Node $node, \Twig_Environment $env)
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
     * @param \Twig_Node_Text $node
     * @param \Twig_Environment $env
     * @return \Twig_Node
     */
    private function _processTextNode(\Twig_Node_Text $node, \Twig_Environment $env): \Twig_Node
    {
        $data = $node->getAttribute('data');

        // Did we just find `</head>`?
        if (static::$foundHead === false && ($endHeadPos = stripos($data, '</head>')) !== false) {
            static::$foundHead = true;

            return $this->_insertEventNode($node, $env, $endHeadPos, 'head');
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

                        return $this->_insertEventNode($node, $env, $beginBodyPos, 'beginBody');
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

                    return $this->_insertEventNode($node, $env, $beginBodyPos, 'beginBody');
                }
            }
        }

        // Did we just find `</body>`?
        if (static::$foundEndBody === false && ($endBodyPos = stripos($data, '</body>')) !== false) {
            static::$foundEndBody = true;

            return $this->_insertEventNode($node, $env, $endBodyPos, 'endBody');
        }

        return $node;
    }

    /**
     * Inserts a new event function node at a specific point in a given text node’s data.
     *
     * @param \Twig_Node_Text $node
     * @param \Twig_Environment $env
     * @param int $pos
     * @param string $functionName
     * @return \Twig_Node
     */
    private function _insertEventNode(\Twig_Node_Text $node, \Twig_Environment $env, int $pos, string $functionName): \Twig_Node
    {
        $data = $node->getAttribute('data');
        $preSplitHtml = substr($data, 0, $pos);
        $postSplitHtml = substr($data, $pos);
        $startLine = $node->getTemplateLine();
        $splitLine = $startLine + substr_count($preSplitHtml, "\n");

        return new \Twig_Node([
            new \Twig_Node_Text($preSplitHtml, $startLine),
            new \Twig_Node_Do(new \Twig_Node_Expression_Function($functionName, new \Twig_Node(), $splitLine), $splitLine),
            new \Twig_Node_Text($postSplitHtml, $splitLine),
        ], [], $startLine);
    }
}
