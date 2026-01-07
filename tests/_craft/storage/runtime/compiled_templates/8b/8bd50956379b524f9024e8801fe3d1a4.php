<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6d957a785af9f868297c40c48e40b3fb */
class __TwigTemplate_6e73853757eac9ed90f7d6fc022ec741 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6d957a785af9f868297c40c48e40b3fb');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->snakeFilter('foo bar');
        craft\helpers\Template::endProfile('template', '__string_template__6d957a785af9f868297c40c48e40b3fb');
    }

    public function getTemplateName()
    {
        return '__string_template__6d957a785af9f868297c40c48e40b3fb';
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
        return new Source('{{ "foo bar"|snake }}', '__string_template__6d957a785af9f868297c40c48e40b3fb', '');
    }
}
