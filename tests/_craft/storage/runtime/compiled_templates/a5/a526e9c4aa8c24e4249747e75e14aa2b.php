<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__4f60c4785a42669631623c4422c42daf */
class __TwigTemplate_6fe48e3156902a0c36893c0edf72d4ad extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4f60c4785a42669631623c4422c42daf');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->percentageFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__4f60c4785a42669631623c4422c42daf');
    }

    public function getTemplateName()
    {
        return '__string_template__4f60c4785a42669631623c4422c42daf';
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
        return new Source('{{ null|percentage }}', '__string_template__4f60c4785a42669631623c4422c42daf', '');
    }
}
