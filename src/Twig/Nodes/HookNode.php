<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\View\TemplateHooks;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class HookNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('yield app(%s::class)->invoke(', TemplateHooks::class))
            ->subcompile($this->getNode('hook'))
            ->raw(", \$context);\n\n");
    }
}
