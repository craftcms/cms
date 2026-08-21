<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Facades\ResponseHeaders;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class HeaderNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write('$_headerParts = array_map(\'trim\', explode(\':\', ')
            ->subcompile($this->getNode('header'))
            ->raw(", 2));\n")
            ->write(ResponseHeaders::class."::add(\$_headerParts[0], \$_headerParts[1] ?? '');\n")
            ->write("unset(\$_headerParts);\n");
    }
}
