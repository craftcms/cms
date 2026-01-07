<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8850fad039b57c44192c36bdd0ad3d9c */
class __TwigTemplate_ad96ff27da8555484260b3992b0ed510 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8850fad039b57c44192c36bdd0ad3d9c');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->numberFilter(1000);
        craft\helpers\Template::endProfile('template', '__string_template__8850fad039b57c44192c36bdd0ad3d9c');
    }

    public function getTemplateName()
    {
        return '__string_template__8850fad039b57c44192c36bdd0ad3d9c';
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
        return new Source('{{ 1000|number }}', '__string_template__8850fad039b57c44192c36bdd0ad3d9c', '');
    }
}
