<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _includes/forms/lightswitch.twig */
class __TwigTemplate_aabf0774f9d78a43310ba17860fb88f9 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/lightswitch.twig');
        // line 1
        $context['id'] ??= 'lightswitch'.twig_random($this->env);
        // line 2
        $context['on'] ??= false;
        // line 3
        $context['indeterminate'] = (! (isset($context['on']) || array_key_exists('on', $context) ? $context['on'] : (function () {
            throw new RuntimeError('Variable "on" does not exist.', 3, $this->source);
        })()) && (($context['indeterminate']) ?? (false)));
        // line 4
        $context['value'] ??= '1';
        // line 5
        $context['indeterminateValue'] ??= '-';
        // line 6
        $context['small'] ??= false;
        // line 7
        $context['toggle'] ??= null;
        // line 8
        $context['reverseToggle'] ??= null;
        // line 9
        $context['disabled'] = (((($context['disabled']) ?? (false))) ? (true) : (false));
        // line 10
        $context['onLabel'] ??= ($context['label']) ?? (false);
        // line 11
        $context['offLabel'] ??= false;
        // line 12
        $context['hasOnLabel'] = ! ((isset($context['onLabel']) || array_key_exists('onLabel', $context) ? $context['onLabel'] : (function () {
            throw new RuntimeError('Variable "onLabel" does not exist.', 12, $this->source);
        })()) === false);
        // line 13
        $context['hasOffLabel'] = ! ((isset($context['offLabel']) || array_key_exists('offLabel', $context) ? $context['offLabel'] : (function () {
            throw new RuntimeError('Variable "offLabel" does not exist.', 13, $this->source);
        })()) === false);
        // line 14
        $context['hasLabels'] = ((isset($context['hasOnLabel']) || array_key_exists('hasOnLabel', $context) ? $context['hasOnLabel'] : (function () {
            throw new RuntimeError('Variable "hasOnLabel" does not exist.', 14, $this->source);
        })()) || (isset($context['hasOffLabel']) || array_key_exists('hasOffLabel', $context) ? $context['hasOffLabel'] : (function () {
            throw new RuntimeError('Variable "hasOffLabel" does not exist.', 14, $this->source);
        })()));
        // line 15
        $context['descriptionId'] ??= (isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 15, $this->source);
        })()).'-desc';
        // line 17
        $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['id' =>         // line 18
(isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
    throw new RuntimeError('Variable "id" does not exist.', 18, $this->source);
})()), 'class' => $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => 'lightswitch', 1 => ((        // line 21
    (isset($context['on']) || array_key_exists('on', $context) ? $context['on'] : (function () {
        throw new RuntimeError('Variable "on" does not exist.', 21, $this->source);
    })())) ? ('on') : (null)), 2 => ((        // line 22
        (isset($context['hasLabels']) || array_key_exists('hasLabels', $context) ? $context['hasLabels'] : (function () {
            throw new RuntimeError('Variable "hasLabels" does not exist.', 22, $this->source);
        })())) ? ('has-labels') : (null)), 3 => ((        // line 23
            (isset($context['indeterminate']) || array_key_exists('indeterminate', $context) ? $context['indeterminate'] : (function () {
                throw new RuntimeError('Variable "indeterminate" does not exist.', 23, $this->source);
            })())) ? ('indeterminate') : (null)), 4 => ((        // line 24
                (isset($context['small']) || array_key_exists('small', $context) ? $context['small'] : (function () {
                    throw new RuntimeError('Variable "small" does not exist.', 24, $this->source);
                })())) ? ('small') : (null)), 5 => (((        // line 25
                    (isset($context['toggle']) || array_key_exists('toggle', $context) ? $context['toggle'] : (function () {
                        throw new RuntimeError('Variable "toggle" does not exist.', 25, $this->source);
                    })()) || (isset($context['reverseToggle']) || array_key_exists('reverseToggle', $context) ? $context['reverseToggle'] : (function () {
                        throw new RuntimeError('Variable "reverseToggle" does not exist.', 25, $this->source);
                    })()))) ? ('fieldtoggle') : (null)), 6 => ((        // line 26
                        (isset($context['disabled']) || array_key_exists('disabled', $context) ? $context['disabled'] : (function () {
                            throw new RuntimeError('Variable "disabled" does not exist.', 26, $this->source);
                        })())) ? ('noteditable') : (null))]), 'data' => ['value' => (((        // line 29
                            (isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                                throw new RuntimeError('Variable "value" does not exist.', 29, $this->source);
                            })()) != '1')) ? ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                                throw new RuntimeError('Variable "value" does not exist.', 29, $this->source);
                            })())) : (false)), 'indeterminate-value' => (((        // line 30
                                (isset($context['indeterminateValue']) || array_key_exists('indeterminateValue', $context) ? $context['indeterminateValue'] : (function () {
                                    throw new RuntimeError('Variable "indeterminateValue" does not exist.', 30, $this->source);
                                })()) != '-')) ? ((isset($context['indeterminateValue']) || array_key_exists('indeterminateValue', $context) ? $context['indeterminateValue'] : (function () {
                                    throw new RuntimeError('Variable "indeterminateValue" does not exist.', 30, $this->source);
                                })())) : (false)), 'target' => ((isset($context['toggle']) || array_key_exists('toggle', $context) ? $context['toggle'] : (function () {
                                    throw new RuntimeError('Variable "toggle" does not exist.', 31, $this->source);
                                })()) ?: false), 'reverse-target' => ((isset($context['reverseToggle']) || array_key_exists('reverseToggle', $context) ? $context['reverseToggle'] : (function () {
                                    throw new RuntimeError('Variable "reverseToggle" does not exist.', 32, $this->source);
                                })()) ?: false)], 'type' => 'button', 'role' => 'switch', 'aria' => ['checked' => ((        // line 37
                                    (isset($context['on']) || array_key_exists('on', $context) ? $context['on'] : (function () {
                                        throw new RuntimeError('Variable "on" does not exist.', 37, $this->source);
                                    })())) ? ('true') : ((((isset($context['indeterminate']) || array_key_exists('indeterminate', $context) ? $context['indeterminate'] : (function () {
                                        throw new RuntimeError('Variable "indeterminate" does not exist.', 37, $this->source);
                                    })())) ? ('mixed') : ('false')))), 'labelledby' => ((        // line 38
                                        $context['labelledBy']) ?? ((($context['labelId']) ?? (false)))), 'describedby' => (twig_join_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => ((        // line 39
                                            $context['describedBy']) ?? (null)), 1 => (((isset($context['hasLabels']) || array_key_exists('hasLabels', $context) ? $context['hasLabels'] : (function () {
                                                throw new RuntimeError('Variable "hasLabels" does not exist.', 39, $this->source);
                                            })())) ? ((isset($context['descriptionId']) || array_key_exists('descriptionId', $context) ? $context['descriptionId'] : (function () {
                                                throw new RuntimeError('Variable "descriptionId" does not exist.', 39, $this->source);
                                            })())) : (null))]), ' ') ?: false)], ], ((        // line 41
                                                $context['containerAttributes']) ?? ([])), true);
        // line 43
        if ($this->hasBlock('attr', $context, $blocks)) {
            // line 44
            $context['containerAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 44, $this->source);
            })()), $this->extensions['craft\web\twig\Extension']->parseAttrFilter((('<div '.$this->renderBlock('attr', $context, $blocks)).'>')), true);
        }
        // line 46
        echo '
';
        // line 47
        ob_start();
        // line 48
        echo '    ';
        ob_start();
        // line 49
        echo '        <div class="lightswitch-container">
            <div class="handle"></div>
        </div>
        ';
        // line 52
        if (array_key_exists('name', $context)) {
            // line 53
            echo craft\helpers\Html::hiddenInput((isset($context['name']) || array_key_exists('name', $context) ? $context['name'] : (function () {
                throw new RuntimeError('Variable "name" does not exist.', 53, $this->source);
            })()), (((isset($context['on']) || array_key_exists('on', $context) ? $context['on'] : (function () {
                throw new RuntimeError('Variable "on" does not exist.', 53, $this->source);
            })())) ? ((isset($context['value']) || array_key_exists('value', $context) ? $context['value'] : (function () {
                throw new RuntimeError('Variable "value" does not exist.', 53, $this->source);
            })())) : ((((isset($context['indeterminate']) || array_key_exists('indeterminate', $context) ? $context['indeterminate'] : (function () {
                throw new RuntimeError('Variable "indeterminate" does not exist.', 53, $this->source);
            })())) ? ((isset($context['indeterminateValue']) || array_key_exists('indeterminateValue', $context) ? $context['indeterminateValue'] : (function () {
                throw new RuntimeError('Variable "indeterminateValue" does not exist.', 53, $this->source);
            })())) : ('')))), ['disabled' => (isset($context['disabled']) || array_key_exists('disabled', $context) ? $context['disabled'] : (function () {
                throw new RuntimeError('Variable "disabled" does not exist.', 53, $this->source);
            })())]);
        }
        // line 55
        echo '    ';
        echo craft\helpers\Html::tag('button', ob_get_clean(),         // line 48
            (isset($context['containerAttributes']) || array_key_exists('containerAttributes', $context) ? $context['containerAttributes'] : (function () {
                throw new RuntimeError('Variable "containerAttributes" does not exist.', 48, $this->source);
            })()));
        $context['input'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 57
        echo '
';
        // line 58
        if ((isset($context['hasLabels']) || array_key_exists('hasLabels', $context) ? $context['hasLabels'] : (function () {
            throw new RuntimeError('Variable "hasLabels" does not exist.', 58, $this->source);
        })())) {
            // line 59
            echo '<div class="lightswitch-outer-container">
        ';
            // line 60
            if ((isset($context['hasLabels']) || array_key_exists('hasLabels', $context) ? $context['hasLabels'] : (function () {
                throw new RuntimeError('Variable "hasLabels" does not exist.', 60, $this->source);
            })())) {
                // line 61
                echo '            ';
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['id' =>                 // line 62
(isset($context['descriptionId']) || array_key_exists('descriptionId', $context) ? $context['descriptionId'] : (function () {
    throw new RuntimeError('Variable "descriptionId" does not exist.', 62, $this->source);
})()), 'class' => 'visually-hidden', 'text' => twig_join_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, [0 => ((                // line 65
    (isset($context['onLabel']) || array_key_exists('onLabel', $context) ? $context['onLabel'] : (function () {
        throw new RuntimeError('Variable "onLabel" does not exist.', 65, $this->source);
    })())) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('Check for {onLabel}.', 'app', ['onLabel' => (isset($context['onLabel']) || array_key_exists('onLabel', $context) ? $context['onLabel'] : (function () {
        throw new RuntimeError('Variable "onLabel" does not exist.', 65, $this->source);
    })())])) : ('')), 1 => ((                // line 66
        (isset($context['offLabel']) || array_key_exists('offLabel', $context) ? $context['offLabel'] : (function () {
            throw new RuntimeError('Variable "offLabel" does not exist.', 66, $this->source);
        })())) ? ($this->extensions['craft\web\twig\Extension']->translateFilter('Uncheck for {offLabel}.', 'app', ['offLabel' => (isset($context['offLabel']) || array_key_exists('offLabel', $context) ? $context['offLabel'] : (function () {
            throw new RuntimeError('Variable "offLabel" does not exist.', 66, $this->source);
        })())])) : (''))]), ' '), ]);
                // line 68
                echo '
        ';
            }
            // line 70
            echo '        <div class="lightswitch-inner-container">
            ';
            // line 71
            if ((isset($context['hasOffLabel']) || array_key_exists('hasOffLabel', $context) ? $context['hasOffLabel'] : (function () {
                throw new RuntimeError('Variable "hasOffLabel" does not exist.', 71, $this->source);
            })())) {
                // line 72
                echo '                <span data-toggle="off" aria-hidden="true">';
                echo twig_escape_filter($this->env, (isset($context['offLabel']) || array_key_exists('offLabel', $context) ? $context['offLabel'] : (function () {
                    throw new RuntimeError('Variable "offLabel" does not exist.', 72, $this->source);
                })()), 'html', null, true);
                echo '</span>
            ';
            }
            // line 74
            echo '            ';
            echo twig_escape_filter($this->env, (isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 74, $this->source);
            })()), 'html', null, true);
            echo '
            ';
            // line 75
            if ((isset($context['hasOnLabel']) || array_key_exists('hasOnLabel', $context) ? $context['hasOnLabel'] : (function () {
                throw new RuntimeError('Variable "hasOnLabel" does not exist.', 75, $this->source);
            })())) {
                // line 76
                echo '                <span data-toggle="on" aria-hidden="true">';
                echo twig_escape_filter($this->env, (isset($context['onLabel']) || array_key_exists('onLabel', $context) ? $context['onLabel'] : (function () {
                    throw new RuntimeError('Variable "onLabel" does not exist.', 76, $this->source);
                })()), 'html', null, true);
                echo '</span>
            ';
            }
            // line 78
            echo '        </div>
    </div>
';
        } else {
            // line 81
            echo '    ';
            echo twig_escape_filter($this->env, (isset($context['input']) || array_key_exists('input', $context) ? $context['input'] : (function () {
                throw new RuntimeError('Variable "input" does not exist.', 81, $this->source);
            })()), 'html', null, true);
        }
        craft\helpers\Template::endProfile('template', '_includes/forms/lightswitch.twig');
    }

    public function getTemplateName()
    {
        return '_includes/forms/lightswitch.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [162 => 81,  157 => 78,  151 => 76,  149 => 75,  144 => 74,  138 => 72,  136 => 71,  133 => 70,  129 => 68,  127 => 66,  126 => 65,  125 => 62,  123 => 61,  121 => 60,  118 => 59,  116 => 58,  113 => 57,  110 => 48,  108 => 55,  105 => 53,  103 => 52,  98 => 49,  95 => 48,  93 => 47,  90 => 46,  87 => 44,  85 => 43,  83 => 41,  82 => 39,  81 => 38,  80 => 37,  79 => 32,  78 => 31,  77 => 30,  76 => 29,  75 => 26,  74 => 25,  73 => 24,  72 => 23,  71 => 22,  70 => 21,  69 => 18,  68 => 17,  66 => 15,  64 => 14,  62 => 13,  60 => 12,  58 => 11,  56 => 10,  54 => 9,  52 => 8,  50 => 7,  48 => 6,  46 => 5,  44 => 4,  42 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{%- set id = id ?? \"lightswitch#{random()}\" %}
{%- set on = on ?? false %}
{%- set indeterminate = not on and (indeterminate ?? false) %}
{%- set value = value ?? '1' %}
{%- set indeterminateValue = indeterminateValue ?? '-' %}
{%- set small = small ?? false %}
{%- set toggle = toggle ?? null %}
{%- set reverseToggle = reverseToggle ?? null %}
{%- set disabled = (disabled ?? false) ? true : false %}
{%- set onLabel = onLabel ?? label ?? false %}
{%- set offLabel = offLabel ?? false %}
{%- set hasOnLabel = onLabel is not same as(false) %}
{%- set hasOffLabel = offLabel is not same as(false) %}
{%- set hasLabels = hasOnLabel or hasOffLabel %}
{%- set descriptionId = descriptionId ?? \"#{id}-desc\" %}

{%- set containerAttributes = {
    id: id,
    class: [
        'lightswitch',
        on ? 'on' : null,
        hasLabels ? 'has-labels' : null,
        indeterminate ? 'indeterminate' : null,
        small ? 'small' : null,
        toggle or reverseToggle ? 'fieldtoggle' : null,
        disabled ? 'noteditable' : null,
    ]|filter,
    data: {
        value: value != '1' ? value : false,
        'indeterminate-value': indeterminateValue != '-' ? indeterminateValue : false,
        target: toggle ?: false,
        'reverse-target': reverseToggle ?: false,
    },
    type: 'button',
    role: 'switch',
    aria: {
        checked: on ? 'true' : (indeterminate ? 'mixed' : 'false'),
        labelledby: labelledBy ?? labelId ?? false,
        describedby: [describedBy ?? null, hasLabels ? descriptionId : null]|filter|join(' ') ?: false,
    },
}|merge(containerAttributes ?? [], recursive=true) %}

{%- if block('attr') is defined %}
    {%- set containerAttributes = containerAttributes|merge(('<div ' ~ block('attr') ~ '>')|parseAttr, recursive=true) %}
{% endif %}

{% set input %}
    {% tag 'button' with containerAttributes %}
        <div class=\"lightswitch-container\">
            <div class=\"handle\"></div>
        </div>
        {% if name is defined -%}
            {{ hiddenInput(name, on ? value : (indeterminate ? indeterminateValue : ''), {disabled: disabled}) }}
        {%- endif %}
    {% endtag %}
{% endset %}

{% if hasLabels -%}
    <div class=\"lightswitch-outer-container\">
        {% if hasLabels %}
            {{ tag('span', {
                id: descriptionId,
                class: 'visually-hidden',
                text: [
                    onLabel ? 'Check for {onLabel}.'|t('app', {onLabel: onLabel}),
                    offLabel ? 'Uncheck for {offLabel}.'|t('app', {offLabel: offLabel}),
                ]|filter|join(' '),
            }) }}
        {% endif %}
        <div class=\"lightswitch-inner-container\">
            {% if hasOffLabel %}
                <span data-toggle=\"off\" aria-hidden=\"true\">{{ offLabel }}</span>
            {% endif %}
            {{ input }}
            {% if hasOnLabel %}
                <span data-toggle=\"on\" aria-hidden=\"true\">{{ onLabel }}</span>
            {% endif %}
        </div>
    </div>
{% else %}
    {{ input }}
{%- endif %}
", '_includes/forms/lightswitch.twig', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/lightswitch.twig');
    }
}
