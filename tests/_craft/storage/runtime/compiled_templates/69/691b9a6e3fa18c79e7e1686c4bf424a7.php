<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__1b6393b0240af0cc43453fe6f16ade37 */
class __TwigTemplate_7c0a455e37fbbec875c19b64789d3116 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1b6393b0240af0cc43453fe6f16ade37');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->appendFilter('<p><span>bar</span></p>', '<span>foo</span>', 'replace');
        craft\helpers\Template::endProfile('template', '__string_template__1b6393b0240af0cc43453fe6f16ade37');
    }

    public function getTemplateName()
    {
        return '__string_template__1b6393b0240af0cc43453fe6f16ade37';
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
        return new Source('{{ "<p><span>bar</span></p>"|append("<span>foo</span>", "replace") }}', '__string_template__1b6393b0240af0cc43453fe6f16ade37', '');
    }
}
