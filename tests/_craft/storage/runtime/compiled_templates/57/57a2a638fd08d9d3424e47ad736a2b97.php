<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2 */
class __TwigTemplate_150f3626250e1165a1e0bab5d61e653f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->mergeFilter([0 => 'foo'], [0 => 'bar', 1 => 'baz']), ' ');
        craft\helpers\Template::endProfile('template', '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2');
    }

    public function getTemplateName()
    {
        return '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2';
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
        return new Source('{{ ["foo"]|merge(["bar", "baz"])|join(" ") }}', '__string_template__baa4f04a5d0e0d89aa0c05bb8b0b38c2', '');
    }
}
