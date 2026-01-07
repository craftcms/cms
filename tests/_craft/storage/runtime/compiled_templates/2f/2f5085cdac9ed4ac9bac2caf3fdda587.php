<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/fieldLayoutDesigner */
class __TwigTemplate_986955ca31b45702639a67426973fdeb extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_includes/forms/fieldLayoutDesigner');
        // line 1
        $context['fieldLayout'] ??= Craft::createObject('craft\\models\\FieldLayout');
        // line 2
        yield craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 2, $this->source);
        })()), 'cp', [], 'any', false, false, false, 2), 'fieldLayoutDesigner', [(isset($context['fieldLayout']) || array_key_exists('fieldLayout', $context) ? $context['fieldLayout'] : (function () {
            throw new RuntimeError('Variable "fieldLayout" does not exist.', 2, $this->source);
        })()), $context], 'method', false, false, false, 2);
        yield '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/fieldLayoutDesigner');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/fieldLayoutDesigner';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set fieldLayout = fieldLayout ?? create('craft\\\\models\\\\FieldLayout') %}
{{ craft.cp.fieldLayoutDesigner(fieldLayout, _context)|raw }}
", '_includes/forms/fieldLayoutDesigner', '/tmp/packages/craft5/src/templates/_includes/forms/fieldLayoutDesigner.twig');
    }
}
