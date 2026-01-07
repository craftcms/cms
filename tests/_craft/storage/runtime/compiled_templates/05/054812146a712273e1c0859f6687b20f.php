<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b512df1cfcd5e613033cad0039b2ab28 */
class __TwigTemplate_28dd70498bfa0522038430ab7fd7418f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b512df1cfcd5e613033cad0039b2ab28');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->httpdateFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__b512df1cfcd5e613033cad0039b2ab28');
    }

    public function getTemplateName()
    {
        return '__string_template__b512df1cfcd5e613033cad0039b2ab28';
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
        return new Source('{{ d|httpdate }}', '__string_template__b512df1cfcd5e613033cad0039b2ab28', '');
    }
}
