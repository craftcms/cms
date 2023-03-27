<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use craft\helpers\Template;
use craft\web\twig\nodes\FallbackNameExpression;
use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Class FallbackVariableSwapper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class FallbackVariableSwapper implements NodeVisitorInterface
{
    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if (Template::isVariableExpression($node)) {
            // swap the node with a FallbackNameExpression
            $node = new FallbackNameExpression($node->getAttribute('name'), [
                'is_defined_test' => $node->getAttribute('is_defined_test'),
                'ignore_strict_check' => $node->getAttribute('ignore_strict_check'),
            ], $node->getTemplateLine());
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
        return 10;
    }
}
