<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cb3b92850811006e7714eebc3e0f9957 */
class __TwigTemplate_6d208564a0f6983f9e777ab807836677 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cb3b92850811006e7714eebc3e0f9957');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->pascalFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__cb3b92850811006e7714eebc3e0f9957');
    }

    public function getTemplateName()
    {
        return '__string_template__cb3b92850811006e7714eebc3e0f9957';
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
        return new Source('{{ "foo bar"|pascal }}', '__string_template__cb3b92850811006e7714eebc3e0f9957', '');
    }
}
