<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__44f5b0d0083c72c010c63803251c4235 */
class __TwigTemplate_6eb4b1dee265d5493b778e43b1a4280d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__44f5b0d0083c72c010c63803251c4235');
        // line 1
        echo 'foo=';
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'foo', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'foo', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'foo', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'foo', []));
        craft\helpers\Template::endProfile('template', '__string_template__44f5b0d0083c72c010c63803251c4235');
    }

    public function getTemplateName()
    {
        return '__string_template__44f5b0d0083c72c010c63803251c4235';
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
        return new Source('foo={{ (_variables.foo ?? object.foo)|raw }}', '__string_template__44f5b0d0083c72c010c63803251c4235', '');
    }
}
