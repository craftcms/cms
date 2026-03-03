<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes\Expressions\Binaries;

use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use Twig\Compiler;
use Twig\Node\Expression\Binary\AbstractBinary;

final class HasEveryBinary extends AbstractBinary
{
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw(sprintf('%s::arrayEvery($this->env, ', CoreTwigExtension::class))
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')');
    }

    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('');
    }
}
