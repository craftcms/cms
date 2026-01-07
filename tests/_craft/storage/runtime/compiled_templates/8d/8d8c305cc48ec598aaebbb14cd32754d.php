<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ced260b6069fc8d288ffda7b1ed7ef83 */
class __TwigTemplate_765144094eab78b8dd34e246d538ebb9 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__ced260b6069fc8d288ffda7b1ed7ef83');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->tagFunction('p', ['text' => "<script>alert('Hello');</script>"]);
        craft\helpers\Template::endProfile('template', '__string_template__ced260b6069fc8d288ffda7b1ed7ef83');
    }

    public function getTemplateName()
    {
        return '__string_template__ced260b6069fc8d288ffda7b1ed7ef83';
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
        return new Source("{{ tag(\"p\", {text: \"<script>alert('Hello');</script>\"}) }}", '__string_template__ced260b6069fc8d288ffda7b1ed7ef83', '');
    }
}
