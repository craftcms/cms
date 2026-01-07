<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__59d2c8b08ec6e7495726f67a831597d3 */
class __TwigTemplate_9a54356a23bf3ad1e52f8cf3762b2e07 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__59d2c8b08ec6e7495726f67a831597d3');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['myVar']) || array_key_exists('myVar', $context) ? $context['myVar'] : (function () {
            throw new RuntimeError('Variable "myVar" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__59d2c8b08ec6e7495726f67a831597d3');
    }

    public function getTemplateName()
    {
        return '__string_template__59d2c8b08ec6e7495726f67a831597d3';
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
        return new Source('{{ myVar|json_encode }}', '__string_template__59d2c8b08ec6e7495726f67a831597d3', '');
    }
}
