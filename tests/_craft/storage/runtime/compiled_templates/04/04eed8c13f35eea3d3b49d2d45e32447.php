<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/file */
class __TwigTemplate_7d0c90130b1183f0b2611253f2ccfd0e extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/file');
        // line 1
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['type' => 'file', 'id' => ((        // line 3
            $context['id']) ?? (false)), 'class' => craft\helpers\Html::explodeClass(((        // line 4
                $context['class']) ?? ([]))), 'name' => ((        // line 5
                    $context['name']) ?? (false)), 'autofocus' => (((        // line 6
                        $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                            throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
                        })()), 'app', [], 'any', false, false, false, 6), 'request', [], 'any', false, false, false, 6), 'isMobileBrowser', [true], 'method', false, false, false, 6)), 'disabled' => ((        // line 7
                            $context['disabled']) ?? (false)), 'aria' => ['describedby' => ((        // line 9
                                $context['describedBy']) ?? (false))]], ((        // line 11
                                    $context['inputAttributes']) ?? ([])), true);
        // line 13
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 14
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 14, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 16
        yield '
';
        // line 17
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('input', (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 17, $this->source);
        })()));
        yield '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/file');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/file';
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
        return [60 => 17,  57 => 16,  54 => 14,  52 => 13,  50 => 11,  49 => 9,  48 => 7,  47 => 6,  46 => 5,  45 => 4,  44 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set inputAttributes = {
    type: 'file',
    id: id ?? false,
    class: (class ?? [])|explodeClass,
    name: name ?? false,
    autofocus: (autofocus ?? false) and not craft.app.request.isMobileBrowser(true),
    disabled: disabled ?? false,
    aria: {
        describedby: describedBy ?? false,
    }
}|merge(inputAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set inputAttributes = inputAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{{ tag('input', inputAttributes) }}
", '_includes/forms/file', '/tmp/packages/craft5/src/templates/_includes/forms/file.twig');
    }
}
