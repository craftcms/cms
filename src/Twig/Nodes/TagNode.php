<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Html;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @since 6.0.0
 */
#[YieldReady]
class TagNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $var = $compiler->getVarName();

        $compiler
            ->addDebugInfo($this)
            ->write("\$$var = ")
            ->subcompile(new CaptureNode($this->getNode('content'), $this->getTemplateLine()))
            ->raw("\n")
            /** @see Html::tag() */
            ->write('yield '.Html::class.'::tag(')
            ->subcompile($this->getNode('name'))
            ->raw(", \$$var");

        if ($this->hasNode('options')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('options'));
        }

        $compiler
            ->raw(");\n")
            ->write("unset(\$$var);\n");
    }
}
