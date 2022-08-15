<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use craft\helpers\Template;
use craft\web\twig\nodes\ProfileNode;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\MacroNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Profiler adds profiling to template bodies, blocks, and macros.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Profiler implements NodeVisitorInterface
{
    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $name = $node->getTemplateName();
            $node->setNode('display_start', new Node([
                new ProfileNode(Template::PROFILE_STAGE_BEGIN, Template::PROFILE_TYPE_TEMPLATE, $name),
                $node->getNode('display_start'),
            ]));
            $node->setNode('display_end', new Node([
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

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return 0;
    }
}
