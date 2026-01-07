<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__5dfd68c47647aaaeebfe95b7c393af11 */
class __TwigTemplate_b53f1793fe6d3109d86947fa0ec036dd extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__5dfd68c47647aaaeebfe95b7c393af11');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->translateFilter('Source message');
        craft\helpers\Template::endProfile('template', '__string_template__5dfd68c47647aaaeebfe95b7c393af11');
    }

    public function getTemplateName()
    {
        return '__string_template__5dfd68c47647aaaeebfe95b7c393af11';
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
        return new Source('{{ "Source message"|t }}', '__string_template__5dfd68c47647aaaeebfe95b7c393af11', '');
    }
}
