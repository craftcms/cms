<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b93f6074a306e1db3eb0582839a224e1 */
class __TwigTemplate_04123ac3dc298d7d9c79b06bcb6b116a extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b93f6074a306e1db3eb0582839a224e1');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->removeClassFilter('foo', 'foo');
        craft\helpers\Template::endProfile('template', '__string_template__b93f6074a306e1db3eb0582839a224e1');
    }

    public function getTemplateName()
    {
        return '__string_template__b93f6074a306e1db3eb0582839a224e1';
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
        return new Source("{{ 'foo'|removeClass(\"foo\") }}", '__string_template__b93f6074a306e1db3eb0582839a224e1', '');
    }
}
