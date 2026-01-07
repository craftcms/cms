<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__e9f0015ddbe07ecbe9e77f646195ea3e */
class __TwigTemplate_afc456703cf912077ae24aa558d490ec extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->parseRefsFilter('{user:1:username}');
        craft\helpers\Template::endProfile('template', '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e');
    }

    public function getTemplateName()
    {
        return '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e';
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
        return new Source('{{ "{user:1:username}"|parseRefs }}', '__string_template__e9f0015ddbe07ecbe9e77f646195ea3e', '');
    }
}
