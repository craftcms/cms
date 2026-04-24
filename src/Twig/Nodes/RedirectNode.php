<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Url;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class RedirectNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        if ($this->hasNode('error')) {
            $compiler
                ->write(Flash::class.'::fail(')
                ->subcompile($this->getNode('error'))
                ->raw(");\n");
        }

        if ($this->hasNode('notice')) {
            $compiler
                ->write(Flash::class.'::notice(')
                ->subcompile($this->getNode('notice'))
                ->raw(");\n");
        }

        $compiler
            ->write('redirect('.Url::class.'::url(')
            ->subcompile($this->getNode('path'))
            ->raw('), ')
            ->subcompile($this->getNode('httpStatusCode'))
            ->raw(")->throwResponse();\n");
    }
}
