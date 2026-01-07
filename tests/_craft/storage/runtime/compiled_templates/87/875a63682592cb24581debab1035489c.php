<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ac7e8dd5fa954dc19f6040b3f19a1920 */
class __TwigTemplate_b7e667548f539f18b3fb4239b14e58a7 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->withoutFilter([0 => 'foo', 1 => 'bar', 2 => 'baz'], [0 => 'bar', 1 => 'baz']), ',');
        craft\helpers\Template::endProfile('template', '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920');
    }

    public function getTemplateName()
    {
        return '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920';
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
        return new Source('{{ ["foo","bar","baz"]|without(["bar","baz"])|join(",") }}', '__string_template__ac7e8dd5fa954dc19f6040b3f19a1920', '');
    }
}
