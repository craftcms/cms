<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0255312c37aa8ecc41af4df7b083f1a8 */
class __TwigTemplate_3a7a84cd895b526d97712e8f7e1f31b4 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0255312c37aa8ecc41af4df7b083f1a8');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->dateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), '%d days');
        craft\helpers\Template::endProfile('template', '__string_template__0255312c37aa8ecc41af4df7b083f1a8');
    }

    public function getTemplateName()
    {
        return '__string_template__0255312c37aa8ecc41af4df7b083f1a8';
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
        return new Source('{{ d|date("%d days") }}', '__string_template__0255312c37aa8ecc41af4df7b083f1a8', '');
    }
}
