<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2ec074eceab767462c72d66d27212907 */
class __TwigTemplate_c78aa349be02ccb906057eadae13aa0b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2ec074eceab767462c72d66d27212907');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->truncateFilter('Test foo bar', 8, '...');
        craft\helpers\Template::endProfile('template', '__string_template__2ec074eceab767462c72d66d27212907');
    }

    public function getTemplateName()
    {
        return '__string_template__2ec074eceab767462c72d66d27212907';
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
        return new Source('{{ "Test foo bar"|truncate(8, "...") }}', '__string_template__2ec074eceab767462c72d66d27212907', '');
    }
}
