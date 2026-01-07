<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/file */
class __TwigTemplate_37f907eea1b2bc0db3c180b570799a89 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/file');
        // line 1
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['type' => 'file', 'id' => ((        // line 3
            $context['id']) ?? (false)), 'class' => craft\helpers\Html::explodeClass(((        // line 4
                $context['class']) ?? ([]))), 'name' => ((        // line 5
                    $context['name']) ?? (false)), 'autofocus' => (((        // line 6
                        $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                            throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
                        })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), 'disabled' => ((        // line 7
                            $context['disabled']) ?? (false)), 'aria' => ['describedby' => ((        // line 9
                                $context['describedBy']) ?? (false))]], ((        // line 11
                                    $context['inputAttributes']) ?? ([])), true);
        // line 13
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 14
            $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 14, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 16
        echo '
';
        // line 17
        echo $this->extensions['craft\web\twig\Extension']->tagFunction('input', (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
            throw new RuntimeError('Variable "inputAttributes" does not exist.', 17, $this->source);
        })()));
        echo '
';
        craft\helpers\Template::endProfile('template', '_includes/forms/file');
    }

    public function getTemplateName()
    {
        return '_includes/forms/file';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [55 => 17,  52 => 16,  49 => 14,  47 => 13,  45 => 11,  44 => 9,  43 => 7,  42 => 6,  41 => 5,  40 => 4,  39 => 3,  38 => 1];
    }

    public function getSourceContext()
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
", '_includes/forms/file', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/file.twig');
    }
}
