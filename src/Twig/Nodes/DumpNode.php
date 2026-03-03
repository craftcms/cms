<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use craft\debug\DumpPanel;
use craft\helpers\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class DumpNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('%s::dump(', DumpPanel::class));

        if ($this->hasNode('var')) {
            $compiler->subcompile($this->getNode('var'));
        } else {
            $compiler->raw(sprintf('%s::contextWithoutTemplate($context)', Template::class));
        }

        $compiler
            ->raw(sprintf(", '%s', %s);\n", $this->getTemplateName(), $this->getTemplateLine()));
    }
}
