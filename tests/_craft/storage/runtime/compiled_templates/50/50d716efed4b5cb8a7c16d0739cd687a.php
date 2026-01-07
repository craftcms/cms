<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__fe8963dc71a2948dddc2ae0418d1f690 */
class __TwigTemplate_defe83e4ffc98accf7a7929f61873b3e extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fe8963dc71a2948dddc2ae0418d1f690');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->translateFilter('Source message', 'site');
        craft\helpers\Template::endProfile('template', '__string_template__fe8963dc71a2948dddc2ae0418d1f690');
    }

    public function getTemplateName()
    {
        return '__string_template__fe8963dc71a2948dddc2ae0418d1f690';
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
        return new Source('{{ "Source message"|t("site") }}', '__string_template__fe8963dc71a2948dddc2ae0418d1f690', '');
    }
}
