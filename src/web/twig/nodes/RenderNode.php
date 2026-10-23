<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;
use Twig\Compiler;
use Twig\Extension\CoreExtension;
use Twig\Node\IncludeNode;

/**
 * Class RenderNode
 *
 * Extends [[IncludeNode]] so Twig treats it like an `{% include %}` tag, e.g. by keeping the
 * `loop` variable available when it’s used within `{% for %}` loops.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class RenderNode extends IncludeNode
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('echo %s::render(', Template::class))
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

    /**
     * @inheritdoc
     */
    public function getStringCoercedChildNames(): array
    {
        // the objects get rendered via render(), not string-coerced
        return [];
    }
}
