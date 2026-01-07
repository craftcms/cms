<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* template.twig */
class __TwigTemplate_1247845690b188d86a97fb136cca0b69 extends Template
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
        craft\helpers\Template::beginProfile('template', 'template.twig');
        // line 1
        echo 'Im a template!';
        craft\helpers\Template::endProfile('template', 'template.twig');
    }

    public function getTemplateName()
    {
        return 'template.twig';
    }

    public function getDebugInfo()
    {
        return [38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('Im a template!', 'template.twig', '/Users/brianhanson/Development/craft5/tests/_craft/templates/template.twig');
    }
}
