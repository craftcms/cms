<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ba97295cf0f737617ba6648f66acfbfd */
class __TwigTemplate_6ffade1e9ec4cc24c20774686464d0dc extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ba97295cf0f737617ba6648f66acfbfd');
        // line 1
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleParam', []));
        echo craft\helpers\Template::attribute($this->env, $this->source, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'object', []))), 'exampleParam', []);
        craft\helpers\Template::endProfile('template', '__string_template__ba97295cf0f737617ba6648f66acfbfd');
    }

    public function getTemplateName()
    {
        return '__string_template__ba97295cf0f737617ba6648f66acfbfd';
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
        return new Source('{{ (_variables.exampleParam ?? object.exampleParam) |raw }}{{ (_variables.object ?? object.object).exampleParam |raw }}', '__string_template__ba97295cf0f737617ba6648f66acfbfd', '');
    }
}
