<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\View\TemplateHooks;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class HookNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
#[YieldReady]
class HookNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('yield app(%s::class)->invoke(', TemplateHooks::class))
            ->subcompile($this->getNode('hook'))
            ->raw(", \$context);\n\n");
    }
}
