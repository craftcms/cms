<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0e993abc2a8fe0a1bcb766f6d024d7af */
class __TwigTemplate_c6ade2e6198e6a8eaadc4fbb752d3ad8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->dateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'Y-m-d');
        craft\helpers\Template::endProfile('template', '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af');
    }

    public function getTemplateName()
    {
        return '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af';
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
        return new Source('{{ d|date("Y-m-d") }}', '__string_template__0e993abc2a8fe0a1bcb766f6d024d7af', '');
    }
}
