<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__0a163c3b27a50144ff02ad6a054ef381 */
class __TwigTemplate_393d69700a92002f901fea0b1e34d38f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0a163c3b27a50144ff02ad6a054ef381');
        // line 1
        $this->extensions['craft\web\twig\Extension']->groupFilter('foo', 'bar');
        craft\helpers\Template::endProfile('template', '__string_template__0a163c3b27a50144ff02ad6a054ef381');
    }

    public function getTemplateName()
    {
        return '__string_template__0a163c3b27a50144ff02ad6a054ef381';
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
        return new Source('{% do "foo"|group("bar") %}', '__string_template__0a163c3b27a50144ff02ad6a054ef381', '');
    }
}
