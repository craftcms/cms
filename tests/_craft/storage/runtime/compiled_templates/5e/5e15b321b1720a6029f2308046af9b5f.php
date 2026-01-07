<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__f38551e939d3a5a942a8a873c0845c43 */
class __TwigTemplate_f4c5343c27c61802f6a9bc3bbeea52e3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f38551e939d3a5a942a8a873c0845c43');
        // line 1
        echo ' ';
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'exampleParam', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'exampleParam', []));
        craft\helpers\Template::endProfile('template', '__string_template__f38551e939d3a5a942a8a873c0845c43');
    }

    public function getTemplateName()
    {
        return '__string_template__f38551e939d3a5a942a8a873c0845c43';
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
        return new Source(' {{ (_variables.exampleParam ?? object.exampleParam)|raw }}', '__string_template__f38551e939d3a5a942a8a873c0845c43', '');
    }
}
