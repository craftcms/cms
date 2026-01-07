<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__71dfbbee3d283407f7aa932b956ea0b8 */
class __TwigTemplate_0067a337a3da45c9959b076a49a27c89 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__71dfbbee3d283407f7aa932b956ea0b8');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->markdownFilter('**Hello**', null, true);
        craft\helpers\Template::endProfile('template', '__string_template__71dfbbee3d283407f7aa932b956ea0b8');
    }

    public function getTemplateName()
    {
        return '__string_template__71dfbbee3d283407f7aa932b956ea0b8';
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
        return new Source('{{ "**Hello**"|md(inlineOnly=true) }}', '__string_template__71dfbbee3d283407f7aa932b956ea0b8', '');
    }
}
