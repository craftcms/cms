<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__c0a4362d86319a3593ea6322a6f9a0c4 */
class __TwigTemplate_00d09f23f175b8a99730bfb75edc1495 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__c0a4362d86319a3593ea6322a6f9a0c4');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->extensions['craft\web\twig\Extension']->gqlFunction('{ping}'));
        craft\helpers\Template::endProfile('template', '__string_template__c0a4362d86319a3593ea6322a6f9a0c4');
    }

    public function getTemplateName()
    {
        return '__string_template__c0a4362d86319a3593ea6322a6f9a0c4';
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
        return new Source('{{ gql("{ping}")|json_encode }}', '__string_template__c0a4362d86319a3593ea6322a6f9a0c4', '');
    }
}
