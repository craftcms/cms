<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
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
            ->write(Craft::class."::\$app->controller->requireGuest();\n");
    }
}
