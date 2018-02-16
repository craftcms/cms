<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use Craft;

/**
 * EventTagFinder looks for `head()`, `beginBody()`, and `endBody()` event tags in templates as they’re being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EventTagFinder extends BaseEventTagVisitor
{
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

        // Ignore if this isn't a print/do tag
        if (!$node instanceof \Twig_Node_Print && !$node instanceof \Twig_Node_Do) {
            return $node;
        }

        // Get the expression
        $expression = $node->getNode('expr');
        if ($expression instanceof \Twig_Node_Expression_Filter) {
            $expression = $expression->getNode('node');
        }

        // Ignore if the expression isn't a function
        if (!$expression instanceof \Twig_Node_Expression_Function) {
            return $node;
        }

        // See which event function they're calling (if any)
        switch ($expression->getAttribute('name')) {
            case 'head':
                static::$foundHead = true;
                break;
            case 'beginBody':
                static::$foundBeginBody = true;
                break;
            case 'endBody':
                static::$foundEndBody = true;
                break;
            default:
                // Not a function we care about
                return $node;
        }

        if ($node instanceof \Twig_Node_Print) {
            // Switch it to a {% do %} tag, since the functions do their own `echo`ing
            $node = new \Twig_Node_Do($expression, $expression->getTemplateLine());
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
        // This needs to run before EventTagAdder
        return 0;
    }
}
