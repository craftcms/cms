<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__93889955a11185d5f45536fb367753bb */
class __TwigTemplate_9937693c8f96bfd15ed195c471aa821d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__93889955a11185d5f45536fb367753bb');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->prependFilter('<p><span>bar</span></p>', '<span>foo</span>', 'replace');
        craft\helpers\Template::endProfile('template', '__string_template__93889955a11185d5f45536fb367753bb');
    }

    public function getTemplateName()
    {
        return '__string_template__93889955a11185d5f45536fb367753bb';
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
        return new Source('{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>", "replace") }}', '__string_template__93889955a11185d5f45536fb367753bb', '');
    }
}
