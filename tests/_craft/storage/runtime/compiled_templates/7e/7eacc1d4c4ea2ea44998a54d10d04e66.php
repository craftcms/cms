<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71 */
class __TwigTemplate_409056c42b79230ce733a253b932936c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->markdownFilter('**Hello**');
        craft\helpers\Template::endProfile('template', '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71');
    }

    public function getTemplateName()
    {
        return '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71';
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
        return new Source('{{ "**Hello**"|md }}', '__string_template__dd3cead22e4bfbe2ce6ca8ad6d89cf71', '');
    }
}
