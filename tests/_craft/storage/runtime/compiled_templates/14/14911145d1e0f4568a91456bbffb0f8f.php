<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__dfe2b52465ec487a1b9bb6ef903c5268 */
class __TwigTemplate_93a813bc9a711a0014e51cabfe2124d4 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__dfe2b52465ec487a1b9bb6ef903c5268');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->datetimeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'php:Y-m-d h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__dfe2b52465ec487a1b9bb6ef903c5268');
    }

    public function getTemplateName()
    {
        return '__string_template__dfe2b52465ec487a1b9bb6ef903c5268';
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
        return new Source('{{ d|datetime("php:Y-m-d h:i:s") }}', '__string_template__dfe2b52465ec487a1b9bb6ef903c5268', '');
    }
}
