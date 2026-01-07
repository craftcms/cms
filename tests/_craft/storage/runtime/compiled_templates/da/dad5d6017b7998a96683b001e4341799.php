<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9956c5b969b3801f96827a0cfe63289b */
class __TwigTemplate_8d98a558067ac8bd5ac688221631c56a extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__9956c5b969b3801f96827a0cfe63289b');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->filesizeFilter(null);
        craft\helpers\Template::endProfile('template', '__string_template__9956c5b969b3801f96827a0cfe63289b');
    }

    public function getTemplateName()
    {
        return '__string_template__9956c5b969b3801f96827a0cfe63289b';
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
        return new Source('{{ null|filesize }}', '__string_template__9956c5b969b3801f96827a0cfe63289b', '');
    }
}
