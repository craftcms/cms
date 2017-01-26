<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\nodevisitors;

use craft\web\twig\nodes\GetAttr;

/**
 * GetAttrAdjuster swaps Twig_Node_Expression_GetAttr nodes with [[GetAttr]] nodes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GetAttrAdjuster implements \Twig_NodeVisitorInterface
{
    /**
     * @inheritdoc
     */
    public function enterNode(\Twig_Node $node, \Twig_Environment $env)
    {
        // Is it a Twig_Node_Expression_GetAttr (and not a subclass)?
        if (get_class($node) === \Twig_Node_Expression_GetAttr::class) {
            // "Clone" it into a GetAttr node
            $nodes = [
                'node' => $node->getNode('node'),
                'attribute' => $node->getNode('attribute')
            ];

            if ($node->hasNode('arguments')) {
                $nodes['arguments'] = $node->getNode('arguments');
            }

            $attributes = [
                'type' => $node->getAttribute('type'),
                'is_defined_test' => $node->getAttribute('is_defined_test'),
                'ignore_strict_check' => $node->getAttribute('ignore_strict_check')
            ];

            $node = new GetAttr($nodes, $attributes, $node->getTemplateLine(), $node->getNodeTag());
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
        return 0;
    }
}
