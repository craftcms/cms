<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__78a1fc286ffb0bdec244a21000fc9d44 */
class __TwigTemplate_c6f46a69c43a2a9f1ddec789eed3dcd2 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__78a1fc286ffb0bdec244a21000fc9d44');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->currencyFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__78a1fc286ffb0bdec244a21000fc9d44');
    }

    public function getTemplateName()
    {
        return '__string_template__78a1fc286ffb0bdec244a21000fc9d44';
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
        return new Source('{{ "foo"|currency }}', '__string_template__78a1fc286ffb0bdec244a21000fc9d44', '');
    }
}
