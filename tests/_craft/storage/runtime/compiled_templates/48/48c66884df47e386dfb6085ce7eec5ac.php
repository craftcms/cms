<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b0c7dc7a0cd27cfd4702bff30b04dedd */
class __TwigTemplate_604a47a845c02b655e0268dd10c0bca9 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'Y-m-d h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd');
    }

    public function getTemplateName()
    {
        return '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd';
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
        return new Source('{{ d|datetime("Y-m-d h:i:s") }}', '__string_template__b0c7dc7a0cd27cfd4702bff30b04dedd', '');
    }
}
