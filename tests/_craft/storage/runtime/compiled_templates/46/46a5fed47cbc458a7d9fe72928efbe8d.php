<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__33b56c77e04afa9204f960df374147ac */
class __TwigTemplate_c0e071639de66f6a278f575a600501d3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__33b56c77e04afa9204f960df374147ac');
        // line 1
        echo ((craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['_variables'] ?? null), 'slug', [])) : (craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'slug', []));
        craft\helpers\Template::endProfile('template', '__string_template__33b56c77e04afa9204f960df374147ac');
    }

    public function getTemplateName()
    {
        return '__string_template__33b56c77e04afa9204f960df374147ac';
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
        return new Source('{{ (_variables.slug ?? object.slug)|raw }}', '__string_template__33b56c77e04afa9204f960df374147ac', '');
    }
}
