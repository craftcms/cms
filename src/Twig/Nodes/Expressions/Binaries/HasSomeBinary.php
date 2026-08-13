<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes\Expressions\Binaries;

use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use Twig\Compiler;
use Twig\Node\Expression\Binary\AbstractBinary;

class HasSomeBinary extends AbstractBinary
{
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw(sprintf('%s::arraySome($this->env, ', CoreTwigExtension::class))
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(', $this->env->hasExtension(\Twig\Extension\SandboxExtension::class) && $this->env->getExtension(\Twig\Extension\SandboxExtension::class)->isSandboxed($this->source))');
    }

    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('');
    }
}
