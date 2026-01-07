<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cf1e513d1e164d0e39beb54bfe00013c */
class __TwigTemplate_125745b5b5490ca861756fe7b59232af extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__cf1e513d1e164d0e39beb54bfe00013c');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->currencyFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__cf1e513d1e164d0e39beb54bfe00013c');
    }

    public function getTemplateName()
    {
        return '__string_template__cf1e513d1e164d0e39beb54bfe00013c';
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
        return new Source('{{ null|currency }}', '__string_template__cf1e513d1e164d0e39beb54bfe00013c', '');
    }
}
