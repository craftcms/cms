<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/select */
class __TwigTemplate_97d31a8a1c3c816e73ad2dacdde5f6f7 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/select');
        // line 1
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'select']));
        // line 5
        $context['options'] ??= [];
        // line 6
        $context['value'] ??= null;
        // line 7
        $context['hasOptgroups'] = false;
        // line 8
        $context['labelledBy'] ??= null;
        // line 10
        $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' =>         // line 11
(isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
    throw new RuntimeError('Variable "class" does not exist.', 11, $this->source);
})()), ], ((        // line 12
    $context['containerAttributes']) ?? ([])), true);
        // line 14
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 15
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 15, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 17
        echo '
';
        // line 18
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' => ((        // line 19
            $context['id']) ?? (false)), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => ((((        // line 21
                $context['toggle']) ?? (false))) ? ('fieldtoggle') : (null))]), 'name' => ((        // line 23
                    $context['name']) ?? (false)), 'autocomplete' => ((        // line 24
                        $context['autocomplete']) ?? (false)), 'autofocus' => (((        // line 25
                            $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 25, $this->source);
                            })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), 'disabled' => ((        // line 26
                                $context['disabled']) ?? (false)), 'aria' => ['describedby' => ((        // line 28
                                    $context['describedBy']) ?? (false)), 'labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 29
                                        ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true), 'label', [])) : (false))) ? (false) : ((($context['labelledBy']) ?? (false))))], 'data' => ['target-prefix' => ((((        // line 32
                                            $context['toggle']) ?? (false))) ? ((($context['targetPrefix']) ?? (''))) : (false))]], ((        // line 34
                                                $context['inputAttributes']) ?? ([])), true);
        // line 35
        echo '
';
        // line 36
        ob_start();
        // line 37
        echo '    ';
        ob_start();
        // line 38
        echo '        ';
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
            throw new RuntimeError('Variable "options" does not exist.', 38, $this->source);
        })()));
        foreach ($context['_seq'] as $context['key'] => $context['option']) {
            // line 39
            echo '            ';
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'optgroup', [], 'any', true, true)) {
                // line 40
                echo '                ';
                if ((isset($context['hasOptgroups']) || array_key_exists('hasOptgroups', $context) ? $context['hasOptgroups'] : (function () {
                    throw new RuntimeError('Variable "hasOptgroups" does not exist.', 40, $this->source);
                })())) {
                    // line 41
                    echo '                    </optgroup>
                ';
                } else {
                    // line 43
                    echo '                    ';
                    $context['hasOptgroups'] = true;
                    // line 44
                    echo '                ';
                }
                // line 45
                echo '                <optgroup label="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'optgroup', []), 'html', null, true);
                echo '">
            ';
            } else {
                // line 47
                echo '                ';
                $context['optionValue'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', true, true)) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [])) : ($context['key']));
                // line 48
                echo '                ';
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('option', ['value' => ((                // line 49
                    $context['optionValue']) ?? ('')), 'selected' => ((                // line 50
                        (isset($context['optionValue']) || array_key_exists('optionValue', $context) ? $context['optionValue'] : (function () {
                            throw new RuntimeError('Variable "optionValue" does not exist.', 50, $this->source);
                        })()).'') === ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                            throw new RuntimeError('Variable "value" does not exist.', 50, $this->source);
                        })()).'')), 'disabled' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 51
                            $context['option'], 'disabled', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'disabled', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'disabled', [])) : (false)), 'hidden' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 52
                                $context['option'], 'hidden', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'hidden', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'hidden', [])) : (false)), 'text' => ((craft\helpers\Template::attribute($this->env, $this->source,                 // line 53
                                    $context['option'], 'label', [], 'any', true, true)) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'label', [])) : ($context['option'])), 'data' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 54
                                        $context['option'], 'data', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'data', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'data', [])) : (false))]);
                // line 55
                echo '
            ';
            }
            // line 57
            echo '        ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['key'], $context['option'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 58
        echo '        ';
        if ((isset($context['hasOptgroups']) || array_key_exists('hasOptgroups', $context) ? $context['hasOptgroups'] : (function () {
            throw new RuntimeError('Variable "hasOptgroups" does not exist.', 58, $this->source);
        })())) {
            // line 59
            echo '            </optgroup>
        ';
        }
        // line 61
        echo '    ';
        echo craft\helpers\Html::tag('select', ob_get_clean(),         // line 37
            (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 37, $this->source);
            })()));
        echo craft\helpers\Html::tag('div', ob_get_clean(),         // line 36
            (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 36, $this->source);
            })()));
        craft\helpers\Template::endProfile('template', '_includes/forms/select');
    }

    public function getTemplateName()
    {
        return '_includes/forms/select';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [140 => 36,  138 => 37,  136 => 61,  132 => 59,  129 => 58,  123 => 57,  119 => 55,  117 => 54,  116 => 53,  115 => 52,  114 => 51,  113 => 50,  112 => 49,  110 => 48,  107 => 47,  101 => 45,  98 => 44,  95 => 43,  91 => 41,  88 => 40,  85 => 39,  80 => 38,  77 => 37,  75 => 36,  72 => 35,  70 => 34,  69 => 32,  68 => 29,  67 => 28,  66 => 26,  65 => 25,  64 => 24,  63 => 23,  62 => 21,  61 => 19,  60 => 18,  57 => 17,  54 => 15,  52 => 14,  50 => 12,  49 => 11,  48 => 10,  46 => 8,  44 => 7,  42 => 6,  40 => 5,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{%- set class = (class ?? [])|explodeClass|merge([
    'select',
]|filter) %}

{%- set options = options ?? [] %}
{%- set value = value ?? null %}
{%- set hasOptgroups = false -%}
{% set labelledBy = labelledBy ?? null %}

{%- set containerAttributes = {
    class: class,
}|merge(containerAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set containerAttributes = containerAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{% set inputAttributes = {
    id: id ?? false,
    class: [
        (toggle ?? false) ? 'fieldtoggle' : null,
    ]|filter,
    name: name ?? false,
    autocomplete: (autocomplete ?? false),
    autofocus: (autofocus ?? false) and not craft.app.request.isMobileBrowser(true),
    disabled: disabled ?? false,
    aria: {
        describedby: describedBy ?? false,
        labelledby: (inputAttributes.aria.label ?? false) ? false : (labelledBy ?? false),
    },
    data: {
        'target-prefix': (toggle ?? false) ? (targetPrefix ?? '') : false,
    },
}|merge(inputAttributes ?? [], recursive=true) %}

{% tag 'div' with containerAttributes %}
    {% tag 'select' with inputAttributes %}
        {% for key, option in options %}
            {% if option.optgroup is defined %}
                {% if hasOptgroups %}
                    </optgroup>
                {% else %}
                    {% set hasOptgroups = true %}
                {% endif %}
                <optgroup label=\"{{ option.optgroup }}\">
            {% else %}
                {% set optionValue = option.value is defined ? option.value : key %}
                {{ tag('option', {
                    value: optionValue ?? '',
                    selected: (optionValue~'') is same as (value~''),
                    disabled: option.disabled ?? false,
                    hidden: option.hidden ?? false,
                    text: option.label is defined ? option.label : option,
                    data: option.data ?? false,
                }) }}
            {% endif %}
        {% endfor %}
        {% if hasOptgroups %}
            </optgroup>
        {% endif %}
    {% endtag %}
{% endtag %}
", '_includes/forms/select', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/select.twig');
    }
}
