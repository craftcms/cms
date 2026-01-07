<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ba911bff49bf8cd587015998e2d6577c */
class __TwigTemplate_38a7ef997052a461114ba8d95e61dc52 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ba911bff49bf8cd587015998e2d6577c');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->currencyFilter(299);
        craft\helpers\Template::endProfile('template', '__string_template__ba911bff49bf8cd587015998e2d6577c');
    }

    public function getTemplateName()
    {
        return '__string_template__ba911bff49bf8cd587015998e2d6577c';
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
        return new Source('{{ 299|currency }}', '__string_template__ba911bff49bf8cd587015998e2d6577c', '');
    }
}
