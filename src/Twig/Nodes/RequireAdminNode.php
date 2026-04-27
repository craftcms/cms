<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
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
            ->write(Craft::class.'::$app->controller->requireAdmin(');

        if ($this->hasNode('requireAdminChanges')) {
            $compiler->subcompile($this->getNode('requireAdminChanges'));
        }

        $compiler->raw(");\n");
    }
}
