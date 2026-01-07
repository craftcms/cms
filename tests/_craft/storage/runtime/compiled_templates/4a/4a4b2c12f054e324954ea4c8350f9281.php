<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__06b42c9af74c277ac0652a600546e4aa */
class __TwigTemplate_1490e84b91abecdcdfb670f52164a508 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__06b42c9af74c277ac0652a600546e4aa');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->numberFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__06b42c9af74c277ac0652a600546e4aa');
    }

    public function getTemplateName()
    {
        return '__string_template__06b42c9af74c277ac0652a600546e4aa';
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
        return new Source('{{ "foo"|number }}', '__string_template__06b42c9af74c277ac0652a600546e4aa', '');
    }
}
