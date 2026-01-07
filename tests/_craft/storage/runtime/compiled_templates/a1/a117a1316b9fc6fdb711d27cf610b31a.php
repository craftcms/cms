<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1c44e9b0bb8ae1567d41cf6591623186 */
class __TwigTemplate_3d24e1cbb2f0f13ed810561c1e2ebaa3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1c44e9b0bb8ae1567d41cf6591623186');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->removeClassFilter('<div class="foo bar">', 'foo');
        craft\helpers\Template::endProfile('template', '__string_template__1c44e9b0bb8ae1567d41cf6591623186');
    }

    public function getTemplateName()
    {
        return '__string_template__1c44e9b0bb8ae1567d41cf6591623186';
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
        return new Source("{{ '<div class=\"foo bar\">'|removeClass(\"foo\") }}", '__string_template__1c44e9b0bb8ae1567d41cf6591623186', '');
    }
}
