<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use craft\helpers\Template;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class DdNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.2.0
 */
#[YieldReady]
class DdNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write(Craft::class.'::dd(');

        if ($this->hasNode('var')) {
            $compiler->subcompile($this->getNode('var'));
        } else {
            $compiler->raw(sprintf('%s::contextWithoutTemplate($context)', Template::class));
        }

        $compiler->raw(");\n");
    }
}
