<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1 */
class __TwigTemplate_02eea2420bec555a8ddd406195d648bb extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->percentageFilter(0.8, 1);
        craft\helpers\Template::endProfile('template', '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1');
    }

    public function getTemplateName()
    {
        return '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1';
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
        return new Source('{{ 0.8|percentage(decimals=1) }}', '__string_template__6183f3fa3d308ac0bc2b734fc3b3e7e1', '');
    }
}
