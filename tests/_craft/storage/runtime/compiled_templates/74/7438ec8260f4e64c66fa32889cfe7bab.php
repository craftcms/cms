<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__28911656061cf07ad2969da5f5aca6b2 */
class __TwigTemplate_8e0687f646fe1863e7a2532377f8ca15 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__28911656061cf07ad2969da5f5aca6b2');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->prependFilter('<p><span>bar</span></p>', '<span>foo</span>');
        craft\helpers\Template::endProfile('template', '__string_template__28911656061cf07ad2969da5f5aca6b2');
    }

    public function getTemplateName()
    {
        return '__string_template__28911656061cf07ad2969da5f5aca6b2';
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
        return new Source('{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>") }}', '__string_template__28911656061cf07ad2969da5f5aca6b2', '');
    }
}
