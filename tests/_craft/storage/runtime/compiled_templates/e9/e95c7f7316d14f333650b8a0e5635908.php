<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__cf7d176858c7d56f36824eb9a2508c45 */
class __TwigTemplate_160ff6a6bfebdca8ae9d66d3af7660db extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__cf7d176858c7d56f36824eb9a2508c45');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->removeClassFilter('<div class="foo">', 'foo');
        craft\helpers\Template::endProfile('template', '__string_template__cf7d176858c7d56f36824eb9a2508c45');
    }

    public function getTemplateName()
    {
        return '__string_template__cf7d176858c7d56f36824eb9a2508c45';
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
        return new Source("{{ '<div class=\"foo\">'|removeClass(\"foo\") }}", '__string_template__cf7d176858c7d56f36824eb9a2508c45', '');
    }
}
