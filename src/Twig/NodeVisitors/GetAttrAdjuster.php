<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\NodeVisitors;

use CraftCms\Cms\Twig\Nodes\GetAttrNode;
use Twig\Environment;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * GetAttrAdjuster swaps [[GetAttrExpression]]
 * nodes with [[GetAttrNode]] nodes.
 */
class GetAttrAdjuster implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        // Make sure this is a GetAttrExpression (and not a subclass)
        if ($node::class !== GetAttrExpression::class) {
            return $node;
        }

        // Swap it with our custom GetAttrNode
        $nodes = [
            'node' => $node->getNode('node'),
            'attribute' => $node->getNode('attribute'),
        ];

        if ($node->hasNode('arguments')) {
            $nodes['arguments'] = $node->getNode('arguments');
        }

        $isDefinedTest = $node->isDefinedTestEnabled();

        $attributes = [
            'type' => $node->getAttribute('type'),
            'is_defined_test' => $isDefinedTest,
            'ignore_strict_check' => $node->getAttribute('ignore_strict_check'),
            'optimizable' => $node->getAttribute('optimizable'),
        ];

        if ($node->hasAttribute('spread')) {
            $attributes['spread'] = $node->getAttribute('spread');
        }

        $getAttrNode = new GetAttrNode($nodes, $attributes, $node->getTemplateLine());

        if ($isDefinedTest) {
            $getAttrNode->enableDefinedTest();
        }

        return $getAttrNode;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
