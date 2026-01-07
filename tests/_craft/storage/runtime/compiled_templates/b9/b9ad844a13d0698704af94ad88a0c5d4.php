<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/booleanMenu */
class __TwigTemplate_282edd9b0cc8b629d8d93eaa712d925a extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/booleanMenu');
        // line 1
        $context['id'] ??= 'booleanmenu'.twig_random($this->env);
        // line 2
        if (((array_key_exists('value', $context) && twig_test_empty((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
            throw new RuntimeError('Variable "value" does not exist.', 2, $this->source);
        })()))) && ! ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
            throw new RuntimeError('Variable "value" does not exist.', 2, $this->source);
        })()) === null))) {
            // line 3
            echo '    ';
            $context['value'] = '0';
        }
        // line 5
        echo '
';
        // line 6
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['data' => ['boolean-menu' => true, 'target' => ((        // line 9
            $context['toggle']) ?? (false)), 'reverse-target' => ((        // line 10
                $context['reverseToggle']) ?? (false))]], ((        // line 12
                    $context['inputAttributes']) ?? ([])), true);
        // line 13
        echo '
';
        // line 14
        $context['options'] = [0 => ['label' => ((        // line 16
            $context['yesLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('Yes', 'app'))), 'value' => '1', 'data' => ['status' => 'enabled']], 1 => ['label' => ((        // line 23
                $context['noLabel']) ?? ($this->extensions['craft\web\twig\Extension']->translateFilter('No', 'app'))), 'value' => '0', 'data' => ['status' => 'white']]];
        // line 30
        echo '
';
        // line 31
        if ((($context['includeEnvVars']) ?? (false))) {
            // line 32
            echo '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 32, $this->source);
            })()), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 32, $this->source);
            })()), 'cp', []), 'getBooleanEnvOptions', [], 'method'));
        }
        // line 34
        echo '
';
        // line 35
        $this->loadTemplate('_includes/forms/selectize', '_includes/forms/booleanMenu', 35)->display(twig_array_merge($context, ['includeEnvVars' => false, 'value' => ((        // line 37
            $context['value']) ?? ('0'))]));
        craft\helpers\Template::endProfile('template', '_includes/forms/booleanMenu');
    }

    public function getTemplateName()
    {
        return '_includes/forms/booleanMenu';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [74 => 37,  73 => 35,  70 => 34,  66 => 32,  64 => 31,  61 => 30,  59 => 23,  58 => 16,  57 => 14,  54 => 13,  52 => 12,  51 => 10,  50 => 9,  49 => 6,  46 => 5,  42 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% set id = id ?? \"booleanmenu#{random()}\" %}
{% if value is defined and value is empty and value is not same as(null) %}
    {% set value = '0' %}
{% endif %}

{% set inputAttributes = {
    data: {
        'boolean-menu': true,
        target: toggle ?? false,
        'reverse-target': reverseToggle ?? false,
    },
}|merge(inputAttributes ?? [], recursive=true) %}

{% set options = [
    {
        label: yesLabel ?? 'Yes'|t('app'),
        value: '1',
        data: {
            status: 'enabled',
        },
    },
    {
        label: noLabel ?? 'No'|t('app'),
        value: '0',
        data: {
            status: 'white',
        },
    },
] %}

{% if includeEnvVars ?? false %}
    {% set options = options|merge(craft.cp.getBooleanEnvOptions()) %}
{% endif %}

{% include '_includes/forms/selectize' with {
    includeEnvVars: false,
    value: value ?? '0',
}%}
", '_includes/forms/booleanMenu', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/booleanMenu.twig');
    }
}
