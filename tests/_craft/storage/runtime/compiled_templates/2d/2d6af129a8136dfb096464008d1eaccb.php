<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b7a33b00616a41c1995277504053b8da */
class __TwigTemplate_f668e019af83d5dc98c0670a3a10e76d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b7a33b00616a41c1995277504053b8da');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->percentageFilter('foo');
        craft\helpers\Template::endProfile('template', '__string_template__b7a33b00616a41c1995277504053b8da');
    }

    public function getTemplateName()
    {
        return '__string_template__b7a33b00616a41c1995277504053b8da';
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
        return new Source('{{ "foo"|percentage }}', '__string_template__b7a33b00616a41c1995277504053b8da', '');
    }
}
