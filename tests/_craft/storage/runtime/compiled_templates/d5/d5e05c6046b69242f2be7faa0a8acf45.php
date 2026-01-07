<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__ddc287aeeddb1e35d30840319cc6faa3 */
class __TwigTemplate_c137c531788bc72c991e69c9fd41c0ae extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__ddc287aeeddb1e35d30840319cc6faa3');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->ucwordsFilter($this->env, 'foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__ddc287aeeddb1e35d30840319cc6faa3');
    }

    public function getTemplateName()
    {
        return '__string_template__ddc287aeeddb1e35d30840319cc6faa3';
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
        return new Source('{{ "foo bar"|ucwords }}', '__string_template__ddc287aeeddb1e35d30840319cc6faa3', '');
    }
}
