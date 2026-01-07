<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8df0773aaeb61faca0f78f89dd72b447 */
class __TwigTemplate_f0fae5bc78aa1915b9431be8e4fe2bec extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8df0773aaeb61faca0f78f89dd72b447');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->parseAttrFilter('foo'));
        craft\helpers\Template::endProfile('template', '__string_template__8df0773aaeb61faca0f78f89dd72b447');
    }

    public function getTemplateName()
    {
        return '__string_template__8df0773aaeb61faca0f78f89dd72b447';
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
        return new Source('{{ "foo"|parseAttr|json_encode }}', '__string_template__8df0773aaeb61faca0f78f89dd72b447', '');
    }
}
