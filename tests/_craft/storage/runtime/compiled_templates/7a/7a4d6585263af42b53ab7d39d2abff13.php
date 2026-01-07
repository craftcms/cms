<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__c6f1932fd35b1c006235e309f2d7aa69 */
class __TwigTemplate_5da9d27c3626e969b150f9de117f1ba7 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__c6f1932fd35b1c006235e309f2d7aa69');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->appendFilter('<p><span>foo</span></p>', '<span>bar</span>');
        craft\helpers\Template::endProfile('template', '__string_template__c6f1932fd35b1c006235e309f2d7aa69');
    }

    public function getTemplateName()
    {
        return '__string_template__c6f1932fd35b1c006235e309f2d7aa69';
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
        return new Source('{{ "<p><span>foo</span></p>"|append("<span>bar</span>") }}', '__string_template__c6f1932fd35b1c006235e309f2d7aa69', '');
    }
}
