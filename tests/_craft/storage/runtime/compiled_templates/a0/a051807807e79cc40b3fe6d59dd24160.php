<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6c10341b34cac0d3b5a5e250a9759019 */
class __TwigTemplate_94bbbe5169231816232be446670a3ecd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6c10341b34cac0d3b5a5e250a9759019');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->markdownFilter('**Hello**');
        craft\helpers\Template::endProfile('template', '__string_template__6c10341b34cac0d3b5a5e250a9759019');
    }

    public function getTemplateName()
    {
        return '__string_template__6c10341b34cac0d3b5a5e250a9759019';
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
        return new Source('{{ "**Hello**"|markdown }}', '__string_template__6c10341b34cac0d3b5a5e250a9759019', '');
    }
}
