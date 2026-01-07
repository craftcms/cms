<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__63836adee2c3bdbb414dea280bd717e5 */
class __TwigTemplate_932287cc716fb231a340913ff1f88bdd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__63836adee2c3bdbb414dea280bd717e5');
        // line 1
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'extraField', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'extraField', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'extraField', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'extraField', []));
        echo craft\helpers\Template::attribute($this->env, $this->source, (((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'object', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'object', []))), 'extraField', []);
        craft\helpers\Template::endProfile('template', '__string_template__63836adee2c3bdbb414dea280bd717e5');
    }

    public function getTemplateName()
    {
        return '__string_template__63836adee2c3bdbb414dea280bd717e5';
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
        return new Source('{{ (_variables.extraField ?? object.extraField) |raw }}{{ (_variables.object ?? object.object).extraField |raw }}', '__string_template__63836adee2c3bdbb414dea280bd717e5', '');
    }
}
