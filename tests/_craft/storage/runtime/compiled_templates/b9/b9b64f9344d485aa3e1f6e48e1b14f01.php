<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__54651f9a9c737e95cf15868c5aa0a444 */
class __TwigTemplate_4ead591f4252a8f4e7ab28aaa8f31121 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__54651f9a9c737e95cf15868c5aa0a444');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->purifyFilter('<p bad-attr="bad-value">foo</p>');
        craft\helpers\Template::endProfile('template', '__string_template__54651f9a9c737e95cf15868c5aa0a444');
    }

    public function getTemplateName()
    {
        return '__string_template__54651f9a9c737e95cf15868c5aa0a444';
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
        return new Source("{{ '<p bad-attr=\"bad-value\">foo</p>'|purify }}", '__string_template__54651f9a9c737e95cf15868c5aa0a444', '');
    }
}
