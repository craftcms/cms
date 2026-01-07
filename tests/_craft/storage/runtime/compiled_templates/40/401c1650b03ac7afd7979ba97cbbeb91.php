<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__7762e12e03b67945dd7c71501ad9526a */
class __TwigTemplate_313242c4d3f13a22b0af5f3b6f858f20 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__7762e12e03b67945dd7c71501ad9526a');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->indexOfFilter([0 => 2, 1 => 3, 2 => 4, 3 => 5], 3);
        craft\helpers\Template::endProfile('template', '__string_template__7762e12e03b67945dd7c71501ad9526a');
    }

    public function getTemplateName()
    {
        return '__string_template__7762e12e03b67945dd7c71501ad9526a';
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
        return new Source('{{ [2, 3, 4, 5]|indexOf(3) }}', '__string_template__7762e12e03b67945dd7c71501ad9526a', '');
    }
}
