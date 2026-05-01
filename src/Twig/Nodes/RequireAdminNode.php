<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Cms;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class RequireAdminNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('if (! $user = '.Auth::class."::user()) {\n")
            ->indent()
            ->write('throw new '.AuthenticationException::class."('Unauthenticated.');\n")
            ->outdent()
            ->write("}\n")
            ->write("if (! \$user->isAdmin()) {\n")
            ->indent()
            ->write("abort(403, 'User is not permitted to perform this action.');\n")
            ->outdent()
            ->write("}\n");

        if ($this->hasNode('requireAdminChanges')) {
            $compiler
                ->write('if (')
                ->subcompile($this->getNode('requireAdminChanges'))
                ->raw(' && ! '.Cms::class."::config()->allowAdminChanges) {\n")
                ->indent()
                ->write("abort(403, 'Administrative changes are disallowed in this environment.');\n")
                ->outdent()
                ->write("}\n");
        } else {
            $compiler
                ->write('if (! '.Cms::class."::config()->allowAdminChanges) {\n")
                ->indent()
                ->write("abort(403, 'Administrative changes are disallowed in this environment.');\n")
                ->outdent()
                ->write("}\n");
        }
    }
}
