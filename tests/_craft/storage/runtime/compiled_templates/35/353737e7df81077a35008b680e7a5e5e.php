<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6434ffa50fb41992da9a01f1099a9d41 */
class __TwigTemplate_6e306a75ada2a40b00754c213ddfc5f3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6434ffa50fb41992da9a01f1099a9d41');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->encencFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__6434ffa50fb41992da9a01f1099a9d41');
    }

    public function getTemplateName()
    {
        return '__string_template__6434ffa50fb41992da9a01f1099a9d41';
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
        return new Source('{{ "foo"|encenc }}', '__string_template__6434ffa50fb41992da9a01f1099a9d41', '');
    }
}
