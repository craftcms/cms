<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__f9915d5a292dc903e1043c121958e80e */
class __TwigTemplate_17421bc199e1b02164142368f2868cbe extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f9915d5a292dc903e1043c121958e80e');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->timeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'php:h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__f9915d5a292dc903e1043c121958e80e');
    }

    public function getTemplateName()
    {
        return '__string_template__f9915d5a292dc903e1043c121958e80e';
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
        return new Source('{{ d|time("php:h:i:s") }}', '__string_template__f9915d5a292dc903e1043c121958e80e', '');
    }
}
