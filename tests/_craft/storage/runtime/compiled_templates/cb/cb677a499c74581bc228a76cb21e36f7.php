<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__4aaa475c995718b7eef34b3ae96aa7b7 */
class __TwigTemplate_316a0ceadc65e0c284ec65b67d769bd2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4aaa475c995718b7eef34b3ae96aa7b7');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->widontFilter('foo bar baz');
        craft\helpers\Template::endProfile('template', '__string_template__4aaa475c995718b7eef34b3ae96aa7b7');
    }

    public function getTemplateName()
    {
        return '__string_template__4aaa475c995718b7eef34b3ae96aa7b7';
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
        return new Source('{{ "foo bar baz"|widont }}', '__string_template__4aaa475c995718b7eef34b3ae96aa7b7', '');
    }
}
