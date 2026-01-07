<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0a24ef2567079e13d4ddb9adb76f8947 */
class __TwigTemplate_18ee2791641e7e3fa61e3f66bb39974e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0a24ef2567079e13d4ddb9adb76f8947');
        // line 1
        echo craft\helpers\Html::actionInput('A URL WITH CHARS !@#$%^&*()😋');
        craft\helpers\Template::endProfile('template', '__string_template__0a24ef2567079e13d4ddb9adb76f8947');
    }

    public function getTemplateName()
    {
        return '__string_template__0a24ef2567079e13d4ddb9adb76f8947';
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
        return new Source('{{ actionInput("A URL WITH CHARS !@#$%^&*()😋") }}', '__string_template__0a24ef2567079e13d4ddb9adb76f8947', '');
    }
}
