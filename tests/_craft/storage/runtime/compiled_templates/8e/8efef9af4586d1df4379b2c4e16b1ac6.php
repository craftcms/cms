<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__c8718136efe06d75ea00c7619cd842c2 */
class __TwigTemplate_a54700ad9ac1d2ece89288299df2cd19 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__c8718136efe06d75ea00c7619cd842c2');
        // line 1
        echo $this->extensions['craft\web\twig\Extension']->svgFunction((isset($context['path']) || array_key_exists('path', $context) ? $context['path'] : (function () {
            throw new RuntimeError('Variable "path" does not exist.', 1, $this->source);
        })()));
        craft\helpers\Template::endProfile('template', '__string_template__c8718136efe06d75ea00c7619cd842c2');
    }

    public function getTemplateName()
    {
        return '__string_template__c8718136efe06d75ea00c7619cd842c2';
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
        return new Source('{{ svg(path) }}', '__string_template__c8718136efe06d75ea00c7619cd842c2', '');
    }
}
