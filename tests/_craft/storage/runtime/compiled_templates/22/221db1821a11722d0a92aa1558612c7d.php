<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7f8712fff11b58db42727f737c1c3c5a */
class __TwigTemplate_2477bed7b7a4e2d6a033acf5249b6273 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7f8712fff11b58db42727f737c1c3c5a');
        // line 1
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleArrayableParam', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleArrayableParam', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleArrayableParam', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleArrayableParam', []));
        echo craft\helpers\Template::attribute($this->env, $this->source, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'object', []))), 'exampleArrayableParam', []);
        craft\helpers\Template::endProfile('template', '__string_template__7f8712fff11b58db42727f737c1c3c5a');
    }

    public function getTemplateName()
    {
        return '__string_template__7f8712fff11b58db42727f737c1c3c5a';
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
        return new Source('{{ (_variables.exampleArrayableParam ?? object.exampleArrayableParam) |raw }}{{ (_variables.object ?? object.object).exampleArrayableParam |raw }}', '__string_template__7f8712fff11b58db42727f737c1c3c5a', '');
    }
}
