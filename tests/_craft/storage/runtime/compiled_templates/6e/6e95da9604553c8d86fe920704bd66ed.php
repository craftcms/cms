<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__955216ba2eb382e000209944259eda8a */
class __TwigTemplate_631d872742350307339842be8fb7456f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__955216ba2eb382e000209944259eda8a');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->kebabFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__955216ba2eb382e000209944259eda8a');
    }

    public function getTemplateName()
    {
        return '__string_template__955216ba2eb382e000209944259eda8a';
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
        return new Source('{{ "foo bar"|kebab }}', '__string_template__955216ba2eb382e000209944259eda8a', '');
    }
}
