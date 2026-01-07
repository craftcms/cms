<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__e0080c3712cbd7ac8de6a028c4d8273c */
class __TwigTemplate_8a020137cdca7033f470bd986aa58fe3 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__e0080c3712cbd7ac8de6a028c4d8273c');
        // line 1
        $context['expression'] = $this->extensions['craft\web\twig\Extension']->expressionFunction('Im an expression', ['var']);
        yield isset($context['expression']) || array_key_exists('expression', $context) ? $context['expression'] : (function () {
            throw new RuntimeError('Variable "expression" does not exist.', 1, $this->source);
        })();
        yield ' | ';
        yield craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['expression']) || array_key_exists('expression', $context) ? $context['expression'] : (function () {
            throw new RuntimeError('Variable "expression" does not exist.', 1, $this->source);
        })()), 'params', [], 'any', false, false, false, 1), 0, [], 'array', false, false, false, 1);
        yield ' | ';
        yield craft\helpers\Template::attribute($this->env, $this->source, (isset($context['expression']) || array_key_exists('expression', $context) ? $context['expression'] : (function () {
            throw new RuntimeError('Variable "expression" does not exist.', 1, $this->source);
        })()), 'expression', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__e0080c3712cbd7ac8de6a028c4d8273c');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__e0080c3712cbd7ac8de6a028c4d8273c';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('{% set expression =  expression("Im an expression", ["var"]) %}{{ expression }} | {{ expression.params[0] }} | {{ expression.expression }}', '__string_template__e0080c3712cbd7ac8de6a028c4d8273c', '');
    }
}
