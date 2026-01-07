<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ad004f80d2d29c661780f9887d04e1a3 */
class __TwigTemplate_d47e5fb98645df240a6b22476d959bd7 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ad004f80d2d29c661780f9887d04e1a3');
        // line 1
        echo craft\helpers\Html::redirectInput('A URL WITH CHARS !@#$%^*()😋');
        craft\helpers\Template::endProfile('template', '__string_template__ad004f80d2d29c661780f9887d04e1a3');
    }

    public function getTemplateName()
    {
        return '__string_template__ad004f80d2d29c661780f9887d04e1a3';
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
        return new Source('{{ redirectInput("A URL WITH CHARS !@#$%^*()😋") }}', '__string_template__ad004f80d2d29c661780f9887d04e1a3', '');
    }
}
