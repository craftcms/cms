<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd */
class __TwigTemplate_95042dd091f2e5dab9f4d56aab3335dc extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->translateFilter('Source message with {var}', 'site', ['var' => (isset($context['myVar']) || array_key_exists('myVar', $context) ? $context['myVar'] : (function () {
            throw new RuntimeError('Variable "myVar" does not exist.', 1, $this->source);
        })())]);
        craft\helpers\Template::endProfile('template', '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd');
    }

    public function getTemplateName()
    {
        return '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd';
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
        return new Source('{{ "Source message with {var}"|t("site", {var: myVar}) }}', '__string_template__6a3bdbcb47ab48c8f13d3ef9c32ff2cd', '');
    }
}
