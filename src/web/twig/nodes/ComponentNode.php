<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\web\twig\ComponentExtension;
use Twig\Compiler;
use Twig\Node\EmbedNode;
use Twig\Node\Expression\ArrayExpression;

class ComponentNode extends EmbedNode
{
    public function __construct(string $component, string $template, int $index, ArrayExpression $variables, bool $only, int $lineno, string $tag)
    {
        parent::__construct($template, $index, $variables, $only, false, $lineno, $tag);

        $this->setAttribute('component', $component);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->raw('$props = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->embeddedContext(')
            ->string($this->getAttribute('component'))
            ->raw(', ')
            ->raw('twig_to_array(')
            ->subcompile($this->getNode('variables'))
            ->raw('), ')
            ->raw($this->getAttribute('only') ? '[]' : '$context')
            ->raw(");\n");

        $this->addGetTemplate($compiler);

        $compiler->raw('->display($props);');
        $compiler->raw("\n");
    }
}
