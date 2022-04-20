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
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;

/**
 * EventTagFinder looks for `head()`, `beginBody()`, and `endBody()` event tags in templates as they’re being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EventTagFinder extends BaseEventTagVisitor
{
    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        // Ignore if we're not rendering a page template
        if (!Craft::$app->getView()->getIsRenderingPageTemplate()) {
            return $node;
        }

        // Ignore if this isn't a print/do tag
        if (!$node instanceof PrintNode && !$node instanceof DoNode) {
            return $node;
        }

        // Get the expression
        $expression = $node->getNode('expr');
        if ($expression instanceof FilterExpression) {
            $expression = $expression->getNode('node');
        }

        // Ignore if the expression isn't a function
        if (!$expression instanceof FunctionExpression) {
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

        if ($node instanceof PrintNode) {
            // Switch it to a {% do %} tag, since the functions do their own `echo`ing
            $node = new DoNode($expression, $expression->getTemplateLine());
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
        // This needs to run before EventTagAdder
        return 0;
    }
}
