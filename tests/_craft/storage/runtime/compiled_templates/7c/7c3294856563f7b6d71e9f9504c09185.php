<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__e0080c3712cbd7ac8de6a028c4d8273c */
class __TwigTemplate_4d28d99794290ab7a38d9af37821c8b5 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__e0080c3712cbd7ac8de6a028c4d8273c');
        // line 1
        $context['expression'] = $this->extensions['craft\web\twig\Extension']->expressionFunction('Im an expression', [0 => 'var']);
        echo isset($context['expression']) || array_key_exists('expression', $context) ? $context['expression'] : (function () {
            throw new RuntimeError('Variable "expression" does not exist.', 1, $this->source);
        })();
        echo ' | ';
        echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['expression']) || array_key_exists('expression', $context) ? $context['expression'] : (function () {
            throw new RuntimeError('Variable "expression" does not exist.', 1, $this->source);
        })()), 'params', []), 0, [], 'array');
        echo ' | ';
        echo craft\helpers\Template::attribute($this->env, $this->source, (isset($context['expression']) || array_key_exists('expression', $context) ? $context['expression'] : (function () {
            throw new RuntimeError('Variable "expression" does not exist.', 1, $this->source);
        })()), 'expression', []);
        craft\helpers\Template::endProfile('template', '__string_template__e0080c3712cbd7ac8de6a028c4d8273c');
    }

    public function getTemplateName()
    {
        return '__string_template__e0080c3712cbd7ac8de6a028c4d8273c';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('{% set expression =  expression("Im an expression", ["var"]) %}{{ expression }} | {{ expression.params[0] }} | {{ expression.expression }}', '__string_template__e0080c3712cbd7ac8de6a028c4d8273c', '');
    }
}
