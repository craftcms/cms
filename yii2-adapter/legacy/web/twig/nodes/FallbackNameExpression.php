<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;
use Twig\Compiler;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;

/**
 * Class FallbackNameExpression
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class FallbackNameExpression extends ContextVariable
{
    public function __construct(string $name, array $attributes = [], int $lineno = 0)
    {
        $attributes += [
            'name' => $name,
            'is_defined_test' => false,
            'ignore_strict_check' => false,
            'always_defined' => false,
        ];
        Node::__construct([], $attributes, $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        // No special handling for _self/etc. or always-defined variables
        if (str_starts_with($this->getAttribute('name'), '_') || $this->getAttribute('always_defined')) {
            parent::compile($compiler);
            return;
        }

        $name = $this->getAttribute('name');

        $compiler->addDebugInfo($this);

        if ($this->isDefinedTestEnabled()) {
            $compiler
                ->raw(sprintf('%s::variableExists(', Template::class))
                ->string($name)
                ->raw(', $context)');

            return;
        }

        $compiler
            ->raw(sprintf('%s::resolveVariable(', Template::class))
            ->string($name)
            ->raw(', $context, ')
            ->raw(!$this->getAttribute('ignore_strict_check') && $compiler->getEnvironment()->isStrictVariables() ? 'true' : 'false')
            ->raw(', ')
            ->repr($this->lineno)
            ->raw(', $this->source)');
    }
}
