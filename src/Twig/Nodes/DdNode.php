<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
use craft\helpers\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class DdNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write(Craft::class.'::dd(');

        if ($this->hasNode('var')) {
            $compiler->subcompile($this->getNode('var'));
        } else {
            $compiler->raw(sprintf('%s::contextWithoutTemplate($context)', Template::class));
        }

        $compiler->raw(");\n");
    }
}
