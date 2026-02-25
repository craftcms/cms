<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use craft\helpers\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class PaginateNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('[')
            ->subcompile($this->getNode('infoVariable'))
            ->raw(', ')
            ->subcompile($this->getNode('resultVariable'))
            ->raw(sprintf('] = %s::paginateQuery(', Template::class))
            ->subcompile($this->getNode('query'))
            ->raw(");\n");
    }
}
