<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0a199a41e5573950c20940247f3d137a */
class __TwigTemplate_9fad103ae1bdffb9b6ea94355d761bde extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0a199a41e5573950c20940247f3d137a');
        // line 1
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'id', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'id', []));
        craft\helpers\Template::endProfile('template', '__string_template__0a199a41e5573950c20940247f3d137a');
    }

    public function getTemplateName()
    {
        return '__string_template__0a199a41e5573950c20940247f3d137a';
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
        return new Source('{{ (_variables.id ?? object.id)|raw }}', '__string_template__0a199a41e5573950c20940247f3d137a', '');
    }
}
