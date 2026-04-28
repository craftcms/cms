<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class RequireEditionNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("try {\n")
            ->indent()
            ->write(Edition::class.'::require(')
            ->subcompile($this->getNode('editionName'))
            ->raw(");\n")
            ->outdent()
            ->write('} catch ('.WrongEditionException::class.") {\n")
            ->indent()
            ->write("abort(404);\n")
            ->outdent()
            ->write("}\n");
    }
}
