<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\Binary\OrBinary;
use Twig\Node\Node;

#[YieldReady]
final class SwitchNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent();

        foreach ($this->getNode('cases') as $case) {
            /** @var Node $case */
            // The 'body' node may have been removed by Twig if it was an empty text node in a sub-template,
            // outside of any blocks
            if (! $case->hasNode('body')) {
                continue;
            }

            foreach ($case->getNode('values') as $value) {
                $this->compileCaseValues($value, $compiler);
            }

            $compiler
                ->write("{\n")
                ->indent()
                ->subcompile($case->getNode('body'))
                ->write("break;\n")
                ->outdent()
                ->write("}\n");
        }

        if ($this->hasNode('default')) {
            $compiler
                ->write("default:\n")
                ->write("{\n")
                ->indent()
                ->subcompile($this->getNode('default'))
                ->outdent()
                ->write("}\n");
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }

    private function compileCaseValues(Node $node, Compiler $compiler): void
    {
        if ($node instanceof OrBinary) {
            foreach ($node as $n) {
                $this->compileCaseValues($n, $compiler);
            }

            return;
        }

        $compiler
            ->write('case ')
            ->subcompile($node)
            ->raw(":\n");
    }
}
