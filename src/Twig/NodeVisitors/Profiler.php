<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\NodeVisitors;

use craft\helpers\Template;
use CraftCms\Cms\Twig\Nodes\BaseNode;
use CraftCms\Cms\Twig\Nodes\ProfileNode;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\MacroNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Profiler adds profiling to template bodies, blocks, and macros.
 */
final class Profiler implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): \Twig\Node\Node
    {
        if ($node instanceof ModuleNode) {
            $name = $node->getTemplateName();
            $node->setNode('display_start', new BaseNode([
                new ProfileNode(Template::PROFILE_STAGE_BEGIN, Template::PROFILE_TYPE_TEMPLATE, $name),
                $node->getNode('display_start'),
            ]));
            $node->setNode('display_end', new BaseNode([
                new ProfileNode(Template::PROFILE_STAGE_END, Template::PROFILE_TYPE_TEMPLATE, $name),
                $node->getNode('display_end'),
            ]));
        } elseif ($node instanceof BlockNode) {
            $name = $node->getAttribute('name');
            $node->setNode('body', new BodyNode([
                new ProfileNode(Template::PROFILE_STAGE_BEGIN, Template::PROFILE_TYPE_BLOCK, $name),
                $node->getNode('body'),
                new ProfileNode(Template::PROFILE_STAGE_END, Template::PROFILE_TYPE_BLOCK, $name),
            ]));
        } elseif ($node instanceof MacroNode) {
            $name = $node->getAttribute('name');
            $node->setNode('body', new BodyNode([
                new ProfileNode(Template::PROFILE_STAGE_BEGIN, Template::PROFILE_TYPE_MACRO, $name),
                $node->getNode('body'),
                new ProfileNode(Template::PROFILE_STAGE_END, Template::PROFILE_TYPE_MACRO, $name),
            ]));
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
