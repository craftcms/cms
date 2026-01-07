<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _includes/forms/select */
class __TwigTemplate_eea0782121570ea1253204cf6c4a67ea extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/select');
        // line 1
        $context['class'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['class']) ?? ([]))), $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['select']));
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
})())], ((        // line 12
    $context['containerAttributes']) ?? ([])), true);
        // line 14
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 15
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 15, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 17
        yield '
';
        // line 18
        $context['inputAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' => ((        // line 19
            $context['id']) ?? (false)), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [((((        // line 21
                $context['toggle']) ?? (false))) ? ('fieldtoggle') : (null))]), 'name' => ((        // line 23
                    $context['name']) ?? (false)), 'autocomplete' => ((        // line 24
                        $context['autocomplete']) ?? (false)), 'autofocus' => (((        // line 25
                            $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                                throw new RuntimeError('Variable "craft" does not exist.', 25, $this->source);
                            })()), 'app', [], 'any', false, false, false, 25), 'request', [], 'any', false, false, false, 25), 'isMobileBrowser', [true], 'method', false, false, false, 25)), 'disabled' => ((        // line 26
                                $context['disabled']) ?? (false)), 'aria' => ['describedby' => ((        // line 28
                                    $context['describedBy']) ?? (false)), 'labelledby' => (((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 29
                                        ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true, false, 29), 'label', [], 'any', true, true, false, 29) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true, false, 29), 'label', [], 'any', false, false, false, 29) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['inputAttributes'] ?? null), 'aria', [], 'any', false, true, false, 29), 'label', [], 'any', false, false, false, 29)) : (false))) ? (false) : ((($context['labelledBy']) ?? (false))))], 'data' => ['target-prefix' => ((((        // line 32
                                            $context['toggle']) ?? (false))) ? ((($context['targetPrefix']) ?? ('#'))) : (false))]], ((        // line 34
                                                $context['inputAttributes']) ?? ([])), true);
        // line 35
        yield '
';
        // line 36
        ob_start();
        // line 37
        yield '    ';
        ob_start();
        // line 38
        yield '        ';
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
            throw new RuntimeError('Variable "options" does not exist.', 38, $this->source);
        })()));
        foreach ($context['_seq'] as $context['key'] => $context['option']) {
            // line 39
            yield '            ';
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'optgroup', [], 'any', true, true, false, 39)) {
                // line 40
                yield '                ';
                if ((isset($context['hasOptgroups']) || array_key_exists('hasOptgroups', $context) ? $context['hasOptgroups'] : (function () {
                    throw new RuntimeError('Variable "hasOptgroups" does not exist.', 40, $this->source);
                })())) {
                    // line 41
                    yield '                    </optgroup>
                ';
                } else {
                    // line 43
                    yield '                    ';
                    $context['hasOptgroups'] = true;
                    // line 44
                    yield '                ';
                }
                // line 45
                yield '                <optgroup label="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'optgroup', [], 'any', false, false, false, 45), 'html', null, true);
                yield '">
            ';
            } else {
                // line 47
                yield '                ';
                $context['optionValue'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', true, true, false, 47)) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', false, false, false, 47)) : ($context['key']));
                // line 48
                yield '                ';
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('option', ['value' => ((                // line 49
                    $context['optionValue']) ?? ('')), 'selected' => ((                // line 50
                        (isset($context['optionValue']) || array_key_exists('optionValue', $context) ? $context['optionValue'] : (function () {
                            throw new RuntimeError('Variable "optionValue" does not exist.', 50, $this->source);
                        })()).'') === ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                            throw new RuntimeError('Variable "value" does not exist.', 50, $this->source);
                        })()).'')), 'disabled' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 51
                            $context['option'], 'disabled', [], 'any', true, true, false, 51) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'disabled', [], 'any', false, false, false, 51) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'disabled', [], 'any', false, false, false, 51)) : (false)), 'hidden' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 52
                                $context['option'], 'hidden', [], 'any', true, true, false, 52) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'hidden', [], 'any', false, false, false, 52) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'hidden', [], 'any', false, false, false, 52)) : (false)), 'text' => ((craft\helpers\Template::attribute($this->env, $this->source,                 // line 53
                                    $context['option'], 'label', [], 'any', true, true, false, 53)) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'label', [], 'any', false, false, false, 53)) : ($context['option'])), 'data' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 54
                                        $context['option'], 'data', [], 'any', true, true, false, 54) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'data', [], 'any', false, false, false, 54) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'data', [], 'any', false, false, false, 54)) : (false))]);
                // line 55
                yield '
            ';
            }
            // line 57
            yield '        ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['key'], $context['option'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 58
        yield '        ';
        if ((isset($context['hasOptgroups']) || array_key_exists('hasOptgroups', $context) ? $context['hasOptgroups'] : (function () {
            throw new RuntimeError('Variable "hasOptgroups" does not exist.', 58, $this->source);
        })())) {
            // line 59
            yield '            </optgroup>
        ';
        }
        // line 61
        yield '    ';
        echo craft\helpers\Html::tag('select', ob_get_clean(),         // line 37
            (isset($context['inputAttributes']) || array_key_exists('inputAttributes', $context) ? $context['inputAttributes'] : (function () {
                throw new RuntimeError('Variable "inputAttributes" does not exist.', 37, $this->source);
            })()));
        echo craft\helpers\Html::tag('div', ob_get_clean(),         // line 36
            (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 36, $this->source);
            })()));
        craft\helpers\Template::endProfile('template', '_includes/forms/select');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/select';
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
        return [145 => 36,  143 => 37,  141 => 61,  137 => 59,  134 => 58,  128 => 57,  124 => 55,  122 => 54,  121 => 53,  120 => 52,  119 => 51,  118 => 50,  117 => 49,  115 => 48,  112 => 47,  106 => 45,  103 => 44,  100 => 43,  96 => 41,  93 => 40,  90 => 39,  85 => 38,  82 => 37,  80 => 36,  77 => 35,  75 => 34,  74 => 32,  73 => 29,  72 => 28,  71 => 26,  70 => 25,  69 => 24,  68 => 23,  67 => 21,  66 => 19,  65 => 18,  62 => 17,  59 => 15,  57 => 14,  55 => 12,  54 => 11,  53 => 10,  51 => 8,  49 => 7,  47 => 6,  45 => 5,  43 => 1];
    }

    public function getSourceContext(): Source
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
        'target-prefix': (toggle ?? false) ? (targetPrefix ?? '#') : false,
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
", '_includes/forms/select', '/tmp/packages/craft5/src/templates/_includes/forms/select.twig');
    }
}
