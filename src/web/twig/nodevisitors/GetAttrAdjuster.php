<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use craft\web\twig\nodes\GetAttrNode;
use Twig\Environment;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * GetAttrAdjuster swaps [[GetAttrExpression]] nodes with [[GetAttrNode]] nodes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GetAttrAdjuster implements NodeVisitorInterface
{
    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env)
    {
        // Make sure this is a GetAttrExpression (and not a subclass)
        if (get_class($node) !== GetAttrExpression::class) {
            return $node;
        }

        // Swap it with our custom GetAttrNode
        return new GetAttrNode(
            $node->getNode('node'),
            $node->getNode('attribute'),
            $node->hasNode('arguments') ? $node->getNode('arguments') : null,
            $node->getAttribute('type'),
            $node->getTemplateLine()
        );
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
        return 0;
    }
}
