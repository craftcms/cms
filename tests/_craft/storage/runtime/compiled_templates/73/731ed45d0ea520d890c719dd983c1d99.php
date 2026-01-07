<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ba6f171281e0c1134b70b04d1499e2fb */
class __TwigTemplate_50ca2147a796ac425aeac3a56d23af6f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ba6f171281e0c1134b70b04d1499e2fb');
        // line 1
        echo craft\helpers\Html::actionInput('A URL');
        craft\helpers\Template::endProfile('template', '__string_template__ba6f171281e0c1134b70b04d1499e2fb');
    }

    public function getTemplateName()
    {
        return '__string_template__ba6f171281e0c1134b70b04d1499e2fb';
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
        return new Source('{{ actionInput("A URL") }}', '__string_template__ba6f171281e0c1134b70b04d1499e2fb', '');
    }
}
