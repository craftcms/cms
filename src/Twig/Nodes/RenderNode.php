<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Template;
use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Extension\CoreExtension;
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

        // Pass the `with` variables and the template context separately, so Template::render() can tell whether
        // `loop` was explicitly passed in
        if ($this->hasNode('variables')) {
            $compiler
                ->raw(sprintf('%s::toArray(', CoreExtension::class))
                ->subcompile($this->getNode('variables'))
                ->raw(')');
        } else {
            $compiler->raw('[]');
        }

        $compiler
            ->raw(', ')
            ->raw($this->getAttribute('only') ? '[]' : '$context')
            ->raw(");\n");
    }

    #[Override]
    public function getStringCoercedChildNames(): array
    {
        // The objects are rendered via render(), not string-coerced.
        return [];
    }
}
