<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__adc2412e60c5e1baaa51c94dab46c67c */
class __TwigTemplate_6251f16d01d278412d139d675f99c17c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__adc2412e60c5e1baaa51c94dab46c67c');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->atomFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__adc2412e60c5e1baaa51c94dab46c67c');
    }

    public function getTemplateName()
    {
        return '__string_template__adc2412e60c5e1baaa51c94dab46c67c';
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
        return new Source('{{ d|atom }}', '__string_template__adc2412e60c5e1baaa51c94dab46c67c', '');
    }
}
