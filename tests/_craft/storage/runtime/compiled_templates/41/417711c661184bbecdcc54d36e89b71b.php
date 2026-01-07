<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__74f311564a44ef94c09b7d683fe15a0e */
class __TwigTemplate_656d050b5b66e23a7b3b6a9ffebd8103 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__74f311564a44ef94c09b7d683fe15a0e');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->attrFilter('Hey', ['class' => 'foo']);
        craft\helpers\Template::endProfile('template', '__string_template__74f311564a44ef94c09b7d683fe15a0e');
    }

    public function getTemplateName()
    {
        return '__string_template__74f311564a44ef94c09b7d683fe15a0e';
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
        return new Source('{{ "Hey"|attr({class: "foo"}) }}', '__string_template__74f311564a44ef94c09b7d683fe15a0e', '');
    }
}
