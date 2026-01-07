<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/checkboxSelect */
class __TwigTemplate_5c50e5f8a63248c798d75f4902b3fdb5 extends Template
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
        echo '
';
        // line 11
        $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' => $this->extensions['craft\web\twig\Extension']->mergeFilter([0 => 'checkbox-select'], craft\helpers\Html::explodeClass(((        // line 12
            $context['class']) ?? ([]))))], ((        // line 13
                $context['containerAttributes']) ?? ([])), true);
        // line 15
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 16
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 16, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 18
        echo '
';
        // line 19
        ob_start();
        // line 20
        if ((isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
            throw new RuntimeError('Variable "showAllOption" does not exist.', 20, $this->source);
        })())) {
            // line 21
            echo '        <div>
            ';
            // line 22
            $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/checkboxSelect', 22)->display(twig_to_array(['describedBy' => ((            // line 23
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
    })()), 'app', []), 'request', []), 'isMobileBrowser', [0 => true], 'method')), ]));
            // line 31
            echo '        </div>';
        } elseif ((        // line 32
            array_key_exists('name', $context) && (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 32, $this->source);
            })())) < 3) || (twig_slice($this->env, (isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 32, $this->source);
            })()), -2) != '[]')))) {
            // line 33
            echo craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 33, $this->source);
            })()), '');
        }
        // line 35
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
            throw new RuntimeError('Variable "options" does not exist.', 35, $this->source);
        })()));
        foreach ($context['_seq'] as $context['key'] => $context['option']) {
            // line 36
            if (! twig_test_iterable($context['option'])) {
                // line 37
                $context['option'] = ['label' => $context['option'], 'value' => $context['key']];
            }
            // line 39
            echo '        ';
            if (((! (isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
                throw new RuntimeError('Variable "showAllOption" does not exist.', 39, $this->source);
            })()) || ! craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', true, true)) || (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', []) != (isset($context['allValue']) || array_key_exists('allValue', $context) ? $context['allValue'] : (function () {
                throw new RuntimeError('Variable "allValue" does not exist.', 39, $this->source);
            })())))) {
                // line 40
                echo '            <div>
                ';
                // line 41
                $this->loadTemplate('_includes/forms/checkbox', '_includes/forms/checkboxSelect', 41)->display(twig_to_array($this->extensions['craft\web\twig\Extension']->mergeFilter(['name' => ((((                // line 42
                    $context['name']) ?? (false))) ? (((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                        throw new RuntimeError('Variable "name" does not exist.', 42, $this->source);
                    })()).'[]')) : (null)), 'checked' => ((                // line 43
                        (isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
                            throw new RuntimeError('Variable "showAllOption" does not exist.', 43, $this->source);
                        })()) && (isset($context['allChecked']) || array_key_exists('allChecked', $context) ? $context['allChecked'] : (function () {
                            throw new RuntimeError('Variable "allChecked" does not exist.', 43, $this->source);
                        })())) || (craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', [], 'any', true, true) && twig_in_filter(craft\helpers\Template::attribute($this->env, $this->source, $context['option'], 'value', []), (isset($context['values']) || array_key_exists('values', $context) ? $context['values'] : (function () {
                            throw new RuntimeError('Variable "values" does not exist.', 43, $this->source);
                        })())))), 'disabled' => (                // line 44
                            (isset($context['showAllOption']) || array_key_exists('showAllOption', $context) ? $context['showAllOption'] : (function () {
                                throw new RuntimeError('Variable "showAllOption" does not exist.', 44, $this->source);
                            })()) && (isset($context['allChecked']) || array_key_exists('allChecked', $context) ? $context['allChecked'] : (function () {
                                throw new RuntimeError('Variable "allChecked" does not exist.', 44, $this->source);
                            })()))],                 // line 45
                    $context['option'])));
                // line 46
                echo '            </div>
        ';
            }
            // line 48
            echo '    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['key'], $context['option'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        echo craft\helpers\Html::tag('fieldset', ob_get_clean(),         // line 19
            (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 19, $this->source);
            })()));
        craft\helpers\Template::endProfile('template', '_includes/forms/checkboxSelect');
    }

    public function getTemplateName()
    {
        return '_includes/forms/checkboxSelect';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [121 => 19,  115 => 48,  111 => 46,  109 => 45,  108 => 44,  107 => 43,  106 => 42,  105 => 41,  102 => 40,  99 => 39,  96 => 37,  94 => 36,  90 => 35,  87 => 33,  85 => 32,  83 => 31,  81 => 29,  80 => 28,  79 => 27,  78 => 26,  77 => 25,  76 => 23,  75 => 22,  72 => 21,  70 => 20,  68 => 19,  65 => 18,  62 => 16,  60 => 15,  58 => 13,  57 => 12,  56 => 11,  53 => 10,  50 => 8,  48 => 7,  46 => 6,  44 => 5,  42 => 4,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
                    disabled: (showAllOption and allChecked)
                }|merge(option) only %}
            </div>
        {% endif %}
    {% endfor %}
{% endtag %}
", '_includes/forms/checkboxSelect', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/checkboxSelect.twig');
    }
}
