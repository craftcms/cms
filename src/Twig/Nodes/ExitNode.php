<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Twig\Exceptions\TemplateExitException;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class ExitNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        if ($this->hasNode('status')) {
            $status = $this->getNode('status')->getAttribute('value');

            $compiler
                ->write(sprintf('abort(%d', $status));

            if ($this->hasNode('message')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($this->getNode('message'));
            }

            $compiler->raw(");\n");
        } else {
            $compiler->write(sprintf("throw new \\%s;\n", TemplateExitException::class));
        }
    }
}
