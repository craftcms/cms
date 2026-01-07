<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__a9d271a331c7c4c8bca3ebcba5488b11 */
class __TwigTemplate_b3f973107142008711fbcc1ecbd86d32 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__a9d271a331c7c4c8bca3ebcba5488b11');
        // line 1
        echo craft\helpers\Html::redirectInput('A URL');
        craft\helpers\Template::endProfile('template', '__string_template__a9d271a331c7c4c8bca3ebcba5488b11');
    }

    public function getTemplateName()
    {
        return '__string_template__a9d271a331c7c4c8bca3ebcba5488b11';
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
        return new Source('{{ redirectInput("A URL") }}', '__string_template__a9d271a331c7c4c8bca3ebcba5488b11', '');
    }
}
