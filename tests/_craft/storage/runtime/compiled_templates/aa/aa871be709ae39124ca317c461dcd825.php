<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/fieldLayoutDesigner */
class __TwigTemplate_6da7da5e9eaf85bfacaffc0d20ad0c28 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/fieldLayoutDesigner');
        // line 1
        $context['fieldLayout'] ??= Craft::createObject('craft\\models\\FieldLayout');
        // line 2
        echo craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 2, $this->source);
        })()), 'cp', []), 'fieldLayoutDesigner', [0 => (isset($context['fieldLayout']) || array_key_exists('fieldLayout', $context) ? $context['fieldLayout'] : (function () {
            throw new RuntimeError('Variable "fieldLayout" does not exist.', 2, $this->source);
        })()), 1 => $context], 'method');
        echo '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/fieldLayoutDesigner');
    }

    public function getTemplateName()
    {
        return '_includes/forms/fieldLayoutDesigner';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% set fieldLayout = fieldLayout ?? create('craft\\\\models\\\\FieldLayout') %}
{{ craft.cp.fieldLayoutDesigner(fieldLayout, _context)|raw }}
", '_includes/forms/fieldLayoutDesigner', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/fieldLayoutDesigner.twig');
    }
}
