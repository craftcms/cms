<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use craft\helpers\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

#[YieldReady]
final class PreloadSinglesNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        if (! $this->hasAttribute('handles')) {
            return;
        }

        $compiler
            ->write(sprintf(
                "%s::preloadSingles([%s]);\n",
                Template::class,
                implode(', ', array_map(fn (string $handle) => "'$handle'", $this->getAttribute('handles'))),
            ));
    }
}
