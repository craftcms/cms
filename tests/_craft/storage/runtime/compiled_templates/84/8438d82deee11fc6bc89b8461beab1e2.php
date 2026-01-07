<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__a2970a215e231d1347ef31a53ab2b1b4 */
class __TwigTemplate_0875f93ad68e5fdadaabd76633c86dd3 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__a2970a215e231d1347ef31a53ab2b1b4');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->truncateFilter('', 8);
        craft\helpers\Template::endProfile('template', '__string_template__a2970a215e231d1347ef31a53ab2b1b4');
    }

    public function getTemplateName()
    {
        return '__string_template__a2970a215e231d1347ef31a53ab2b1b4';
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
        return new Source('{{ ""|truncate(8) }}', '__string_template__a2970a215e231d1347ef31a53ab2b1b4', '');
    }
}
