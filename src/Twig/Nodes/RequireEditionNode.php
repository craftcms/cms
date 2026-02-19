<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Edition;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class RequireEditionNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('if ('.Edition::class.'::get() < ')
            ->subcompile($this->getNode('editionName'))
            ->raw(")\n")
            ->write("{\n")
            ->indent()
            ->write("abort(404);\n")
            ->outdent()
            ->write("}\n");
    }
}
