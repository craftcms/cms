<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class RequireLoginNode extends Node
{
    /**
     * Compiles a RequireLoginNode into PHP.
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class."::\$app->controller->requireLogin();\n");
    }
}
