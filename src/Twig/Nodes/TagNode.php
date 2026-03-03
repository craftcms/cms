<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Html;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class TagNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write('yield '.Html::class.'::tag(')
            ->subcompile($this->getNode('name'))
            ->raw(', ob_get_clean()');

        if ($this->hasNode('options')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('options'));
        }

        $compiler->raw(");\n");
    }
}
