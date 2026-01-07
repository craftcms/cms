<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__8de31afd956555f12fd8a9fee569962b */
class __TwigTemplate_50423ab06679aebc4c23f99fdefefe96 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__8de31afd956555f12fd8a9fee569962b');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->numberFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__8de31afd956555f12fd8a9fee569962b');
    }

    public function getTemplateName()
    {
        return '__string_template__8de31afd956555f12fd8a9fee569962b';
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
        return new Source('{{ null|number }}', '__string_template__8de31afd956555f12fd8a9fee569962b', '');
    }
}
