<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Illuminate\Support\Facades\Gate;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class RequirePermissionNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Gate::class.'::authorize(')
            ->subcompile($this->getNode('permissionName'))
            ->raw(");\n");
    }
}
