<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0433b659fdd4bf33a594c609c2864924 */
class __TwigTemplate_d5dea977f61246835fed1c36daf967f9 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0433b659fdd4bf33a594c609c2864924');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->percentageFilter(0.8);
        craft\helpers\Template::endProfile('template', '__string_template__0433b659fdd4bf33a594c609c2864924');
    }

    public function getTemplateName()
    {
        return '__string_template__0433b659fdd4bf33a594c609c2864924';
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
        return new Source('{{ 0.8|percentage }}', '__string_template__0433b659fdd4bf33a594c609c2864924', '');
    }
}
