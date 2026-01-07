<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _includes/forms/checkboxSelect */
class __TwigTemplate_2af99cccaae7aa36d7142c396b630fdd extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/checkboxSelect');
        // line 1
        $context['options'] ??= [];
        // line 2
        $context['values'] ??= [];
        // line 4
        $context['showAllOption'] ??= false;
        // line 5
        if ((isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
            throw new RuntimeError('Variable "showAllOption" does not exist.', 5, $this->source);
        })())) {
            // line 6
            $context['allLabel'] ??= $this->extensions['craft\web\twig\Extension']->translateFilter('All', 'app');
            // line 7
            $context['allValue'] ??= '*';
            // line 8
            $context['allChecked'] = ((isset($context['values']) || array_key_exists('values', $context) ? $context['values'] : (function () {
                throw new RuntimeError('Variable "values" does not exist.', 8, $this->source);
            })()) == (isset($context['allValue']) || array_key_exists('allValue', $context) ? $context['allValue'] : (function () {
                throw new RuntimeError('Variable "allValue" does not exist.', 8, $this->source);
            })()));
        }
        // line 10
        yield '
';
        // line 11
        $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['checkbox-select'], craft\helpers\Html::explodeClass(((        // line 12
            $context['class']) ?? ([]))))], ((        // line 13
                $context['containerAttributes']) ?? ([])), true);
        // line 15
        if ($this->unwrap()->hasBlock('attr', $context, $blocks)) {
            // line 16
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 16, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->unwrap()->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 18
        yield '
';
        // line 19
        ob_start();
        // line 20
        if ((isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
            throw new RuntimeError('Variable "showAllOption" does not exist.', 20, $this->source);
        })())) {
            // line 21
            yield '        <div>
            ';
            // line 22
            yield from $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/checkboxSelect', 22)->unwrap()->yield(CoreExtension::toArray(['describedBy' => ((            // line 23
                $context['describedBy']) ?? (false)), 'class' => 'all', 'label' => craft\helpers\Template::raw((('<b>'.             // line 25
                (isset($context['allLabel']) || array_key_exists('allLabel', $context) ? $context['allLabel'] : (function () {
                    throw new RuntimeError('Variable "allLabel" does not exist.', 25, $this->source);
                })())).'</b>')), 'name' => ((            // line 26
                    $context['name']) ?? (null)), 'value' =>             // line 27
(isset($context['allValue']) || array_key_exists('allValue', $context) ? $context['allValue'] : (function () {
    throw new RuntimeError('Variable "allValue" does not exist.', 27, $this->source);
})()), 'checked' =>             // line 28
(isset($context['allChecked']) || array_key_exists('allChecked', $context) ? $context['allChecked'] : (function () {
    throw new RuntimeError('Variable "allChecked" does not exist.', 28, $this->source);
})()), 'autofocus' => (((            // line 29
    $context['autofocus']) ?? (false)) && ! craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
        throw new RuntimeError('Variable "craft" does not exist.', 29, $this->source);
    })()), 'app', [], 'any', false, false, false, 29), 'request', [], 'any', false, false, false, 29), 'isMobileBrowser', [true], 'method', false, false, false, 29)), 'targetPrefix' => ((            // line 30
        $context['targetPrefix']) ?? (null))]));
            // line 32
            yield '        </div>';
        } elseif ((        // line 33
            array_key_exists('name', $context) && (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 33, $this->source);
            })())) < 3) || (Twig\Extension\CoreExtension::slice($this->env->getCharset(), (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 33, $this->source);
            })()), -2) != '[]')))) {
            // line 34
            yield craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 34, $this->source);
            })()), '');
        }
        // line 36
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
            throw new RuntimeError('Variable "options" does not exist.', 36, $this->source);
        })()));
        foreach ($context['_seq'] as $context['key'] => $context['option']) {
            // line 37
            if (! is_iterable($context['option'])) {
                // line 38
                $context['option'] = ['label' => $context['option'], 'value' => $context['key']];
            }
            // line 40
            yield '        ';
            if (((! (isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
                throw new RuntimeError('Variable "showAllOption" does not exist.', 40, $this->source);
            })()) || ! craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', true, true, false, 40)) || (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', false, false, false, 40) != (isset($context['allValue']) || array_key_exists('allValue', $context) ? $context['allValue'] : (function () {
                throw new RuntimeError('Variable "allValue" does not exist.', 40, $this->source);
            })())))) {
                // line 41
                yield '            <div>
                ';
                // line 42
                yield from $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/checkboxSelect', 42)->unwrap()->yield(CoreExtension::toArray($this->extensions['craft\web\twig\Extension']->mergeFilter(['name' => ((((                // line 43
                    $context['name']) ?? (false))) ? (((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                        throw new RuntimeError('Variable "name" does not exist.', 43, $this->source);
                    })()).'[]')) : (null)), 'checked' => ((                // line 44
                        (isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
                            throw new RuntimeError('Variable "showAllOption" does not exist.', 44, $this->source);
                        })()) && (isset($context['allChecked']) || array_key_exists('allChecked', $context) ? $context['allChecked'] : (function () {
                            throw new RuntimeError('Variable "allChecked" does not exist.', 44, $this->source);
                        })())) || (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', true, true, false, 44) && CoreExtension::inFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', false, false, false, 44), (isset($context['values']) || array_key_exists('values', $context) ? $context['values'] : (function () {
                            throw new RuntimeError('Variable "values" does not exist.', 44, $this->source);
                        })())))), 'disabled' => (                // line 45
                            (isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
                                throw new RuntimeError('Variable "showAllOption" does not exist.', 45, $this->source);
                            })()) && (isset($context['allChecked']) || array_key_exists('allChecked', $context) ? $context['allChecked'] : (function () {
                                throw new RuntimeError('Variable "allChecked" does not exist.', 45, $this->source);
                            })())), 'targetPrefix' => ((                // line 46
                                $context['targetPrefix']) ?? (null))],                 // line 47
                    $context['option'])));
                // line 48
                yield '            </div>
        ';
            }
            // line 50
            yield '    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['key'], $context['option'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        echo craft\helpers\Html::tag('fieldset', ob_get_clean(),         // line 19
            (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 19, $this->source);
            })()));
        craft\helpers\Template::endProfile('template', '_includes/forms/checkboxSelect');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/checkboxSelect';
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
        return [128 => 19,  122 => 50,  118 => 48,  116 => 47,  115 => 46,  114 => 45,  113 => 44,  112 => 43,  111 => 42,  108 => 41,  105 => 40,  102 => 38,  100 => 37,  96 => 36,  93 => 34,  91 => 33,  89 => 32,  87 => 30,  86 => 29,  85 => 28,  84 => 27,  83 => 26,  82 => 25,  81 => 23,  80 => 22,  77 => 21,  75 => 20,  73 => 19,  70 => 18,  67 => 16,  65 => 15,  63 => 13,  62 => 12,  61 => 11,  58 => 10,  55 => 8,  53 => 7,  51 => 6,  49 => 5,  47 => 4,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{%- set options = options ?? [] %}
{%- set values = values ?? [] -%}

{%- set showAllOption = showAllOption ?? false %}
{%- if showAllOption %}
    {%- set allLabel = allLabel ?? \"All\"|t('app') %}
    {%- set allValue = allValue ?? '*' %}
    {%- set allChecked = (values == allValue) %}
{%- endif %}

{% set containerAttributes = {
    class: ['checkbox-select']|merge((class ?? [])|explodeClass),
}|merge(containerAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set containerAttributes = containerAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{% tag 'fieldset' with containerAttributes %}
    {%- if showAllOption %}
        <div>
            {% include \"_includes/forms/checkbox\" with {
                describedBy: describedBy ?? false,
                class: 'all',
                label: raw(\"<b>#{allLabel}</b>\"),
                name: name ?? null,
                value: allValue,
                checked: allChecked,
                autofocus: (autofocus ?? false) and not craft.app.request.isMobileBrowser(true),
                targetPrefix: targetPrefix ?? null,
            } only %}
        </div>
    {%- elseif name is defined and (name|length < 3 or name|slice(-2) != '[]') %}
        {{- hiddenInput(name, '') }}
    {%- endif %}
    {%- for key, option in options %}
        {%- if option is not iterable %}
            {%- set option = {label: option, value: key} %}
        {%- endif %}
        {% if not showAllOption or option.value is not defined or option.value != allValue %}
            <div>
                {% include \"_includes/forms/checkbox\" with {
                    name: (name ?? false) ? \"#{name}[]\" : null,
                    checked: ((showAllOption and allChecked) or (option.value is defined and option.value in values)),
                    disabled: (showAllOption and allChecked),
                    targetPrefix: targetPrefix ?? null,
                }|merge(option) only %}
            </div>
        {% endif %}
    {% endfor %}
{% endtag %}
", '_includes/forms/checkboxSelect', '/tmp/packages/craft5/src/templates/_includes/forms/checkboxSelect.twig');
    }
}
