<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2 */
class __TwigTemplate_44131ecb119545a45e1ae670167492d6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->pushFilter([0 => 'foo'], 'bar', 'baz'));
        craft\helpers\Template::endProfile('template', '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2');
    }

    public function getTemplateName()
    {
        return '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2';
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
        return new Source('{{ ["foo"]|push("bar", "baz")|json_encode }}', '__string_template__d2bdd073d8bae9eaf49fd6ef91d3c3c2', '');
    }
}
