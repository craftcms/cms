<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
class PreloadSinglesNode extends Node
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        if (! $this->hasAttribute('handles')) {
            return;
        }

        $compiler
            ->write(sprintf(
                "%s::preloadSingles([%s], \$context);\n",
                Template::class,
                implode(', ', array_map(fn (string $handle) => "'$handle'", $this->getAttribute('handles'))),
            ));
    }
}
