<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__efe4b42648e98daad5b21fe724aa7a08 */
class __TwigTemplate_1cb3820a785f51d0a0385c087bda9225 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__efe4b42648e98daad5b21fe724aa7a08');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->timeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'icu:HH:mm:ss');
        craft\helpers\Template::endProfile('template', '__string_template__efe4b42648e98daad5b21fe724aa7a08');
    }

    public function getTemplateName()
    {
        return '__string_template__efe4b42648e98daad5b21fe724aa7a08';
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
        return new Source('{{ d|time("icu:HH:mm:ss") }}', '__string_template__efe4b42648e98daad5b21fe724aa7a08', '');
    }
}
