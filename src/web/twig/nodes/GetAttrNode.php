<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template as TemplateHelper;
use Twig\Compiler;
use Twig\Extension\SandboxExtension;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\Template;

/**
 * GetAttrNode is an alternative to [[\Twig\Node\Expression\GetAttrExpression]], which sends attribute calls to [[TemplateHelper::attribute()]] rather than twig_get_attribute().
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GetAttrNode extends GetAttrExpression
{
    /**
     * @param array $nodes An array of named nodes
     * @param array $attributes An array of attributes (should not be nodes)
     * @param int $lineno The line number
     * @param string|null $tag The tag name associated with the Node
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0, ?string $tag = null)
    {
        // Skip parent::__construct()
        Node::__construct($nodes, $attributes, $lineno, $tag);
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $env = $compiler->getEnvironment();

        // optimize array calls
        if (
            $this->getAttribute('optimizable')
            && (!$env->isStrictVariables() || $this->getAttribute('ignore_strict_check'))
            && !$this->getAttribute('is_defined_test')
            && Template::ARRAY_CALL === $this->getAttribute('type')
        ) {
            $var = '$' . $compiler->getVarName();
            $compiler
                ->raw('((' . $var . ' = ')
                ->subcompile($this->getNode('node'))
                ->raw(') && is_array(')
                ->raw($var)
                ->raw(') || ')
                ->raw($var)
                ->raw(' instanceof ArrayAccess ? (')
                ->raw($var)
                ->raw('[')
                ->subcompile($this->getNode('attribute'))
                ->raw('] ?? null) : null)');

            return;
        }

        // This is the only line that should be different from GetAttrExpression::compile()
        $compiler->raw(TemplateHelper::class . '::attribute($this->env, $this->source, ');

        if ($this->getAttribute('ignore_strict_check')) {
            $this->getNode('node')->setAttribute('ignore_strict_check', true);
        }

        $compiler->subcompile($this->getNode('node'));

        $compiler->raw(', ')->subcompile($this->getNode('attribute'));

        // only generate optional arguments when needed (to make generated code more readable)
        $needFifth = $env->hasExtension(SandboxExtension::class);
        $needFourth = $needFifth || $this->getAttribute('ignore_strict_check');
        $needThird = $needFourth || $this->getAttribute('is_defined_test');
        $needSecond = $needThird || Template::ANY_CALL !== $this->getAttribute('type');
        $needFirst = $needSecond || $this->hasNode('arguments');

        if ($needFirst) {
            if ($this->hasNode('arguments')) {
                $compiler->raw(', ')->subcompile($this->getNode('arguments'));
            } else {
                $compiler->raw(', []');
            }
        }

        if ($needSecond) {
            $compiler->raw(', ')->repr($this->getAttribute('type'));
        }

        if ($needThird) {
            $compiler->raw(', ')->repr($this->getAttribute('is_defined_test'));
        }

        if ($needFourth) {
            $compiler->raw(', ')->repr($this->getAttribute('ignore_strict_check'));
        }

        if ($needFifth) {
            $compiler->raw(', ')->repr($env->hasExtension(SandboxExtension::class));
        }

        $compiler->raw(')');
    }
}
