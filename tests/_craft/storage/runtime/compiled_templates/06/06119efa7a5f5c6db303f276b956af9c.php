<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__2cccfeaf03117b4173238e2657c83635 */
class __TwigTemplate_f43dba199d977b03fa9d93b6d5c131c8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__2cccfeaf03117b4173238e2657c83635');
        // line 1
        echo craft\helpers\App::env('FROM_EMAIL_NAME');
        echo ' | ';
        echo craft\helpers\App::env('FROM_EMAIL_ADDRESS');
        craft\helpers\Template::endProfile('template', '__string_template__2cccfeaf03117b4173238e2657c83635');
    }

    public function getTemplateName()
    {
        return '__string_template__2cccfeaf03117b4173238e2657c83635';
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
        return new Source('{{ getenv("FROM_EMAIL_NAME") }} | {{ getenv("FROM_EMAIL_ADDRESS") }}', '__string_template__2cccfeaf03117b4173238e2657c83635', '');
    }
}
