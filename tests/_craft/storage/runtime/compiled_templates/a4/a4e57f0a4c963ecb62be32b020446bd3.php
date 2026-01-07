<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__216fbd80d0f5e88d84fdd5f9230a2d1a */
class __TwigTemplate_c7a400cd7ebe4d154202c3bedd5ac7a1 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->lcfirstFilter('Foo Bar');
        craft\helpers\Template::endProfile('template', '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a');
    }

    public function getTemplateName()
    {
        return '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a';
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
        return new Source('{{ "Foo Bar"|lcfirst }}', '__string_template__216fbd80d0f5e88d84fdd5f9230a2d1a', '');
    }
}
