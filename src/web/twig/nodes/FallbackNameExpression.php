<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;
use Twig\Compiler;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

/**
 * Class NamespaceNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class FallbackNameExpression extends NameExpression
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
        // no special handling for _self/etc.,or always-defined variables
        if ($this->isSpecial() || $this->getAttribute('always_defined')) {
            parent::compile($compiler);
            return;
        }

        $name = $this->getAttribute('name');

        $compiler->addDebugInfo($this);

        if ($this->getAttribute('is_defined_test')) {
            $compiler
                ->raw('(array_key_exists(')
                ->string($name)
                ->raw(sprintf(', $context) || %s::fallbackExists(', Template::class))
                ->string($name)
                ->raw('))');
        } elseif ($this->getAttribute('ignore_strict_check') || !$compiler->getEnvironment()->isStrictVariables()) {
            $compiler
                ->raw('(isset($context[')
                ->string($name)
                ->raw(']) || array_key_exists(')
                ->string($name)
                ->raw(', $context) ? $context[')
                ->string($name)
                ->raw(sprintf('] : (%s::fallbackExists(', Template::class))
                ->string($name)
                ->raw(sprintf(') ? %s::fallback(', Template::class))
                ->string($name)
                ->raw(') : null))');
        } else {
            $compiler
                ->raw('(isset($context[')
                ->string($name)
                ->raw(']) || array_key_exists(')
                ->string($name)
                ->raw(', $context) ? $context[')
                ->string($name)
                ->raw(sprintf('] : (%s::fallbackExists(', Template::class))
                ->string($name)
                ->raw(sprintf(') ? %s::fallback(', Template::class))
                ->string($name)
                ->raw(') : (function () { throw new RuntimeError(\'Variable ')
                ->string($name)
                ->raw(' does not exist.\', ')
                ->repr($this->lineno)
                ->raw(', $this->source); })()')
                ->raw('))');
        }
    }
}
