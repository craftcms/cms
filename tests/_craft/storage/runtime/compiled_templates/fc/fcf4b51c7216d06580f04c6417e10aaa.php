<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__95b8adfd3060e800daec6ff87f850525 */
class __TwigTemplate_9f198e6f51dc789bd5f7df71834a857c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__95b8adfd3060e800daec6ff87f850525');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->filesizeFilter(1000);
        craft\helpers\Template::endProfile('template', '__string_template__95b8adfd3060e800daec6ff87f850525');
    }

    public function getTemplateName()
    {
        return '__string_template__95b8adfd3060e800daec6ff87f850525';
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
        return new Source('{{ 1000|filesize }}', '__string_template__95b8adfd3060e800daec6ff87f850525', '');
    }
}
