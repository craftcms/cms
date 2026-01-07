<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__91cbba98dcb8ab083a15ef0bb53d5fbe */
class __TwigTemplate_0094df8fae16b6b43f101191fa0b19fb extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->indexOfFilter('Im a string', 'a');
        craft\helpers\Template::endProfile('template', '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe');
    }

    public function getTemplateName()
    {
        return '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe';
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
        return new Source('{{ "Im a string"|indexOf("a") }}', '__string_template__91cbba98dcb8ab083a15ef0bb53d5fbe', '');
    }
}
