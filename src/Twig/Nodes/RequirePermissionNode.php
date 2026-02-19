<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class RequirePermissionNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class.'::$app->controller->requirePermission(')
            ->subcompile($this->getNode('permissionName'))
            ->raw(");\n");
    }
}
