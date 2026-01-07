<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2 */
class __TwigTemplate_45d1f756b9a7cca07c56390b60d7f884 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->parseAttrFilter('<p id="foo" class="bar baz">Hello</p>'));
        craft\helpers\Template::endProfile('template', '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2');
    }

    public function getTemplateName()
    {
        return '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2';
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
        return new Source("{{ '<p id=\"foo\" class=\"bar baz\">Hello</p>'|parseAttr|json_encode }}", '__string_template__4f3e168afaf7f1e8de0cc5ccf0eaeaf2', '');
    }
}
