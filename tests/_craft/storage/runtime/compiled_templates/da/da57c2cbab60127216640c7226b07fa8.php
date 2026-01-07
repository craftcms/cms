<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__8d5089cfff73fc686f08498a6e455c16 */
class __TwigTemplate_f27b4d53cc50a53435cdd0b3963f89e4 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8d5089cfff73fc686f08498a6e455c16');
        // line 1
        echo ((((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()) != $this->extensions['craft\web\twig\Extension']->shuffleFunction((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()))) || ((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()) != $this->extensions['craft\web\twig\Extension']->shuffleFunction((isset($context['array']) || array_key_exists('array', $context) ? $context['array'] : (function () {
            throw new RuntimeError('Variable "array" does not exist.', 1, $this->source);
        })()))))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__8d5089cfff73fc686f08498a6e455c16');
    }

    public function getTemplateName()
    {
        return '__string_template__8d5089cfff73fc686f08498a6e455c16';
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
        return new Source('{{ array != shuffle(array) or array != shuffle(array) ? "yes" : "no" }}', '__string_template__8d5089cfff73fc686f08498a6e455c16', '');
    }
}
