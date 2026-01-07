<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2493166636ea1d5fa5f2130064b3fd0f */
class __TwigTemplate_6fd920a7a579cec51c210c2b19846042 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2493166636ea1d5fa5f2130064b3fd0f');
        // line 1
        echo twig_join_filter($this->extensions['craft\web\twig\Extension']->withoutFilter([0 => 'foo', 1 => 'bar', 2 => 'baz'], 'baz'), ',');
        craft\helpers\Template::endProfile('template', '__string_template__2493166636ea1d5fa5f2130064b3fd0f');
    }

    public function getTemplateName()
    {
        return '__string_template__2493166636ea1d5fa5f2130064b3fd0f';
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
        return new Source('{{ ["foo","bar","baz"]|without("baz")|join(",") }}', '__string_template__2493166636ea1d5fa5f2130064b3fd0f', '');
    }
}
