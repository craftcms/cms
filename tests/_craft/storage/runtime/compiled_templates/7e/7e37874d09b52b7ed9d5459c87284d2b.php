<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__fd4f83460730af830e03599a58df1ffc */
class __TwigTemplate_0b82b6e1d970431ce73d75956f6a04ef extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fd4f83460730af830e03599a58df1ffc');
        // line 1
        echo (($this->extensions['craft\web\twig\Extension']->pluginFunction('no-a-real-plugin') === null)) ? ('invalid') : ('');
        craft\helpers\Template::endProfile('template', '__string_template__fd4f83460730af830e03599a58df1ffc');
    }

    public function getTemplateName()
    {
        return '__string_template__fd4f83460730af830e03599a58df1ffc';
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
        return new Source('{{ plugin("no-a-real-plugin") is same as(null) ? "invalid" }}', '__string_template__fd4f83460730af830e03599a58df1ffc', '');
    }
}
