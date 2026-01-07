<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b956ce0908ac648172b1e755f20ad818 */
class __TwigTemplate_22e3e3c29e3c2fcce2d88aa9ef7c7140 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b956ce0908ac648172b1e755f20ad818');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->renderObjectTemplate('{{ object.firstName}}', ['firstName' => 'John']);
        craft\helpers\Template::endProfile('template', '__string_template__b956ce0908ac648172b1e755f20ad818');
    }

    public function getTemplateName()
    {
        return '__string_template__b956ce0908ac648172b1e755f20ad818';
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
        return new Source('{{ renderObjectTemplate("{{ object.firstName}}", {firstName: "John"}) }}', '__string_template__b956ce0908ac648172b1e755f20ad818', '');
    }
}
