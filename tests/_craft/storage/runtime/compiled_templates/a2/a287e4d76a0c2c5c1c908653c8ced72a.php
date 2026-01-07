<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__dd78f12527644088c2a1a274e6fe24ab */
class __TwigTemplate_9d0dd9a699d3be87b48973b9252c5a8c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__dd78f12527644088c2a1a274e6fe24ab');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->filesizeFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__dd78f12527644088c2a1a274e6fe24ab');
    }

    public function getTemplateName()
    {
        return '__string_template__dd78f12527644088c2a1a274e6fe24ab';
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
        return new Source('{{ "foo"|filesize }}', '__string_template__dd78f12527644088c2a1a274e6fe24ab', '');
    }
}
