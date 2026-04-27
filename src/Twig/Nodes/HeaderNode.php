<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Craft;
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
            ->write(Craft::class."::\$app->getResponse()->getHeaders()->set(\$_headerParts[0], \$_headerParts[1] ?? '');\n")
            ->write("unset(\$_headerParts);\n");
    }
}
