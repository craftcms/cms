<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class RequireGuestNode extends Node
{
    /**
     * Compiles a RequireGuestNode into PHP.
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('if ('.Auth::class."::check()) {\n")
            ->indent()
            ->write('redirect('.URL::class."::returnUrl())->throwResponse();\n")
            ->outdent()
            ->write("}\n");
    }
}
