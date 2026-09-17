<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Illuminate\Auth\AuthenticationException;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class RequireLoginNode extends Node
{
    /**
     * Compiles a RequireLoginNode into PHP.
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("if (\\CraftCms\\Cms\\craftAuth()->guest()) {\n")
            ->indent()
            ->write('throw new '.AuthenticationException::class."('Unauthenticated.');\n")
            ->outdent()
            ->write("}\n");
    }
}
