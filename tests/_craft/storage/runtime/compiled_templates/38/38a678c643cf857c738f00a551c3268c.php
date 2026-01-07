<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__bf30e993c8afaea6c3b955dd6251b7d8 */
class __TwigTemplate_dbe0e17492f9965d5384006d3131ae6a extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__bf30e993c8afaea6c3b955dd6251b7d8');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->literalFilter('*foo*');
        craft\helpers\Template::endProfile('template', '__string_template__bf30e993c8afaea6c3b955dd6251b7d8');
    }

    public function getTemplateName()
    {
        return '__string_template__bf30e993c8afaea6c3b955dd6251b7d8';
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
        return new Source('{{ "*foo*"|literal }}', '__string_template__bf30e993c8afaea6c3b955dd6251b7d8', '');
    }
}
