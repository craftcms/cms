<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* novar.twig */
class __TwigTemplate_92f4029d42af63fe50c1dcaaba758490 extends Template
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
        craft\helpers\Template::beginProfile('template', 'novar.twig');
        // line 1
        echo 'I have no vars';
        craft\helpers\Template::endProfile('template', 'novar.twig');
    }

    public function getTemplateName()
    {
        return 'novar.twig';
    }

    public function getDebugInfo()
    {
        return [38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('I have no vars', 'novar.twig', '/Users/brianhanson/Development/craft5/tests/_craft/templates/novar.twig');
    }
}
