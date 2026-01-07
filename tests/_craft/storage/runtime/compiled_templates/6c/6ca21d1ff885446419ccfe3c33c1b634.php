<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__04b0696b8e0ae6a264177f49e4a5c8a5 */
class __TwigTemplate_5ccebba816cad2f9e1e66dc4f9fdad26 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->translateFilter('Source message with {var}', ['var' => (isset($context['myVar']) || array_key_exists('myVar', $context) ? $context['myVar'] : (function () {
            throw new RuntimeError('Variable "myVar" does not exist.', 1, $this->source);
        })())]);
        craft\helpers\Template::endProfile('template', '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5');
    }

    public function getTemplateName()
    {
        return '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5';
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
        return new Source('{{ "Source message with {var}"|t({var: myVar}) }}', '__string_template__04b0696b8e0ae6a264177f49e4a5c8a5', '');
    }
}
