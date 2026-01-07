<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__853924542caa1c6d05cc319ca013c2d3 */
class __TwigTemplate_4cd4298c0af31d16ea51c867eb983ac6 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__853924542caa1c6d05cc319ca013c2d3');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->removeClassFilter('<div class="foo bar baz">', [0 => 'foo', 1 => 'bar']);
        craft\helpers\Template::endProfile('template', '__string_template__853924542caa1c6d05cc319ca013c2d3');
    }

    public function getTemplateName()
    {
        return '__string_template__853924542caa1c6d05cc319ca013c2d3';
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
        return new Source("{{ '<div class=\"foo bar baz\">'|removeClass([\"foo\", \"bar\"]) }}", '__string_template__853924542caa1c6d05cc319ca013c2d3', '');
    }
}
