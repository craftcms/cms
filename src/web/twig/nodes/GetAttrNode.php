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
 * GetAttrNode is an alternative to [[\Twig\Node\Expression\GetAttrExpression]], which sends attribute calls to
 * [[TemplateHelper::attribute()]] rather than CoreExtension::getAttribute().
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
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0)
    {
        // Skip parent::__construct()
        Node::__construct($nodes, $attributes, $lineno);
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        // The following code is based on GetAttrExpression::compile().
        // Differences noted below with `DIFF` comments.

        $env = $compiler->getEnvironment();
        $arrayAccessSandbox = false;

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
                ->raw($var);

            if (!$env->hasExtension(SandboxExtension::class)) {
                $compiler
                    ->raw(') || ')
                    ->raw($var)
                    ->raw(' instanceof ArrayAccess ? (')
                    ->raw($var)
                    ->raw('[(string)') // DIFF: `(string)` added
                    ->subcompile($this->getNode('attribute'))
                    ->raw('] ?? null) : null)')
                ;

                return;
            }

            $arrayAccessSandbox = true;

            $compiler
                ->raw(') || ')
                ->raw($var)
                ->raw(' instanceof ArrayAccess && in_array(')
                ->raw($var . '::class')
                ->raw(', CoreExtension::ARRAY_LIKE_CLASSES, true) ? (')
                ->raw($var)
                ->raw('[(string)') // DIFF: `(string)` added
                ->subcompile($this->getNode('attribute'))
                ->raw('] ?? null) : ')
            ;
        }

        // DIFF: TemplateHelper::attribute() used instead of CoreExtension::getAttribute()
        $compiler->raw(TemplateHelper::class . '::attribute($this->env, $this->source, ');

        if ($this->getAttribute('ignore_strict_check')) {
            $this->getNode('node')->setAttribute('ignore_strict_check', true);
        }

        $compiler
            ->subcompile($this->getNode('node'))
            ->raw(', ')
            ->subcompile($this->getNode('attribute'))
        ;

        if ($this->hasNode('arguments')) {
            $compiler->raw(', ')->subcompile($this->getNode('arguments'));
        } else {
            $compiler->raw(', []');
        }

        $compiler->raw(', ')
            ->repr($this->getAttribute('type'))
            ->raw(', ')->repr($this->getAttribute('is_defined_test'))
            ->raw(', ')->repr($this->getAttribute('ignore_strict_check'))
            ->raw(', ')->repr($env->hasExtension(SandboxExtension::class))
            ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
            ->raw(')')
        ;

        if ($arrayAccessSandbox) {
            $compiler->raw(')');
        }
    }
}
