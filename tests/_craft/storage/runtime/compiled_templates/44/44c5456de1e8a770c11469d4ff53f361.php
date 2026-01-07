<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__f9815be4229bd644754d1612c5352cca */
class __TwigTemplate_5dd599dc510fae777fddac9efdd48c6a extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f9815be4229bd644754d1612c5352cca');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->currencyFilter(299, null, [], [], true);
        craft\helpers\Template::endProfile('template', '__string_template__f9815be4229bd644754d1612c5352cca');
    }

    public function getTemplateName()
    {
        return '__string_template__f9815be4229bd644754d1612c5352cca';
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
        return new Source('{{ 299|currency(stripZeros=true) }}', '__string_template__f9815be4229bd644754d1612c5352cca', '');
    }
}
