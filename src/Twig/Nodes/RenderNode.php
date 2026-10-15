<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\IncludeNode;

/**
 * Compiles the `{% render %}` tag.
 *
 * Extends {@see IncludeNode} so Twig treats it like an `{% include %}` tag, e.g. by keeping the
 * `loop` variable available when it’s used within `{% for %}` loops.
 *
 * @since 6.0.0
 */
#[YieldReady]
class RenderNode extends IncludeNode
{
    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('yield (string) %s::render(', Template::class))
            ->subcompile($this->getNode('expr'))
            ->raw(', ');

        $this->addTemplateArguments($compiler);

        $compiler->raw(");\n");
    }

    #[Override]
    public function getStringCoercedChildNames(): array
    {
        // The objects are rendered via render(), not string-coerced.
        return [];
    }
}
