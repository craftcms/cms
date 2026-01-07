<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _includes/forms/selectize */
class __TwigTemplate_f63516a2471508f20389e6ad57725594 extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/selectize');
        // line 1
        $context['id'] ??= 'selectize'.twig_random($this->env);
        // line 2
        $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['dropdownParent' => 'body', 'plugins' => [0 => 'auto_position']], ((        // line 5
            $context['selectizeOptions']) ?? ([])));
        // line 6
        echo '
';
        // line 7
        $context['multi'] ??= false;
        // line 8
        if ((isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 8, $this->source);
        })())) {
            // line 9
            echo '    ';
            $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
                throw new RuntimeError('Variable "selectizeOptions" does not exist.', 9, $this->source);
            })()), ['plugins' => $this->extensions['craft\web\twig\Extension']->pushFilter((((craft\helpers\Template::attribute($this->env, $this->source,             // line 10
                ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', [])) : ([])), 'remove_button')]);
        } else {
            // line 13
            echo '    ';
            $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
                throw new RuntimeError('Variable "selectizeOptions" does not exist.', 13, $this->source);
            })()), ['plugins' => $this->extensions['craft\web\twig\Extension']->pushFilter((((craft\helpers\Template::attribute($this->env, $this->source,             // line 14
                ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', [])) : ([])), 'select_on_focus')]);
        }
        // line 17
        echo '
';
        // line 19
        $context['options'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (($context['options']) ?? ([])), function ($__o__, $__k__) use ($context) {
            $context['o'] = $__o__;
            $context['k'] = $__k__;

            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'optgroup', [], 'any', true, true) || craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'value', [], 'any', true, true))) ? ((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                throw new RuntimeError('Variable "o" does not exist.', 19, $this->source);
            })())) : (['value' =>         // line 20
(isset($context['k']) || array_key_exists('k', $context) ? $context['k'] : (function () {
    throw new RuntimeError('Variable "k" does not exist.', 20, $this->source);
})()), 'label' => ((craft\helpers\Template::attribute($this->env, $this->source,         // line 21
    ($context['o'] ?? null), 'label', [], 'any', true, true)) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
        throw new RuntimeError('Variable "o" does not exist.', 21, $this->source);
    })()), 'label', [])) : ((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
        throw new RuntimeError('Variable "o" does not exist.', 21, $this->source);
    })()))), ]);
        });
        // line 23
        $context['options'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
            throw new RuntimeError('Variable "options" does not exist.', 23, $this->source);
        })()), function ($__o__) use ($context) {
            $context['o'] = $__o__;

            return $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                throw new RuntimeError('Variable "o" does not exist.', 23, $this->source);
            })()), ['data' => $this->extensions['craft\web\twig\Extension']->mergeFilter((((craft\helpers\Template::attribute($this->env, $this->source,         // line 24
                ($context['o'] ?? null), 'data', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [])) : ([])), (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true), 'data', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true), 'data', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true), 'data', [])) : ([])))]);
        });
        // line 26
        echo '
';
        // line 27
        if ((($context['includeEnvVars']) ?? (false))) {
            // line 28
            echo '    ';
            if (! array_key_exists('allowedEnvValues', $context)) {
                // line 29
                echo '        ';
                $context['allowedEnvValues'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                    throw new RuntimeError('Variable "options" does not exist.', 29, $this->source);
                })()), function ($__o__) use ($context) {
                    $context['o'] = $__o__;

                    return ! craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'optgroup', [], 'any', true, true);
                }), function ($__o__) use ($context) {
                    $context['o'] = $__o__;

                    return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                        throw new RuntimeError('Variable "o" does not exist.', 29, $this->source);
                    })()), 'value', []);
                });
                // line 30
                echo '    ';
            }
            // line 31
            echo '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 31, $this->source);
            })()), function ($__o__) use ($context) {
                $context['o'] = $__o__;

                return (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true), 'hint', [], 'any', true, true)) ? ((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                    throw new RuntimeError('Variable "o" does not exist.', 31, $this->source);
                })())) : ($this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                    throw new RuntimeError('Variable "o" does not exist.', 31, $this->source);
                })()), ['data' => ['hint' => craft\helpers\Template::attribute($this->env, $this->source,             // line 33
                    (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                        throw new RuntimeError('Variable "o" does not exist.', 33, $this->source);
                    })()), 'value', [])]], true));
            });
        }
        // line 37
        echo '
';
        // line 38
        if ((array_key_exists('addOptionFn', $context) && array_key_exists('addOptionLabel', $context))) {
            // line 39
            echo '    ';
            if (twig_test_empty((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 39, $this->source);
            })()))) {
                // line 40
                echo '        ';
                $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
                    throw new RuntimeError('Variable "selectizeOptions" does not exist.', 40, $this->source);
                })()), ['allowEmptyOption' => true]);
                // line 43
                echo '        ';
                $context['options'] = [0 => ['value' => '', 'label' => ' ']];
                // line 46
                echo '    ';
            }
            // line 47
            echo '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 47, $this->source);
            })()), ['label' =>             // line 48
(isset($context['addOptionLabel']) || array_key_exists('addOptionLabel', $context) ? $context['addOptionLabel'] : (function () {
    throw new RuntimeError('Variable "addOptionLabel" does not exist.', 48, $this->source);
})()), 'value' => '__add__', 'data' => ['addOption' => true], ]);
        }
        // line 55
        echo '
';
        // line 56
        if ((($context['includeEnvVars']) ?? (false))) {
            // line 57
            echo '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 57, $this->source);
            })()), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 57, $this->source);
            })()), 'cp', []), 'getEnvOptions', [0 => (isset($context['allowedEnvValues']) || array_key_exists('allowedEnvValues', $context) ? $context['allowedEnvValues'] : (function () {
                throw new RuntimeError('Variable "allowedEnvValues" does not exist.', 57, $this->source);
            })())], 'method'));
        }
        // line 59
        echo '
';
        // line 60
        $this->loadTemplate((((isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 60, $this->source);
        })())) ? ('_includes/forms/multiselect.twig') : ('_includes/forms/select.twig')), '_includes/forms/selectize', 60)->display(twig_array_merge($context, ['class' => array_unique($this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass(((        // line 61
            $context['class']) ?? ([]))), 'selectize')), 'inputAttributes' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['style' => ['display' => 'none'], 'autocomplete' => (((! // line 64
            (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
                throw new RuntimeError('Variable "multi" does not exist.', 64, $this->source);
            })()) && array_key_exists('autocomplete', $context))) ? ((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                throw new RuntimeError('Variable "autocomplete" does not exist.', 64, $this->source);
            })())) : (false))], ((        // line 65
                $context['inputAttributes']) ?? ([])), true)]));
        // line 67
        echo '
';
        // line 68
        ob_start();
        // line 69
        echo '(() => {
    const id = ';
        // line 70
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 70, $this->source);
        })())));
        echo ";

    const hasData = (data, option) => {
        return typeof data[option] !== 'undefined' || typeof data[option.toLowerCase()] !== 'undefined';
    };
    const getData = (data, option) => {
        if (typeof data[option] !== 'undefined') {
            return data[option];
        }
        return data[option.toLowerCase()];
    };
    const label = (data, showHint) => {
        let label = '';
        if (hasData(data, 'addOption')) {
            label += '<span class=\"icon add\"></span> ';
        }
        const status = (() => {
            if (hasData(data, 'status')) {
                return getData(data, 'status');
            }
            if (hasData(data, 'boolean')) {
                return getData(data, 'boolean') ? 'enabled' : 'white';
            }
            return null;
        })();
        if (status) {
            label += `<span class=\"status \${status}\"></span>`;
        }
        label += `<span>\${Craft.escapeHtml(getData(data, 'text'))}</span>`;
        if (showHint && hasData(data, 'hint') && getData(data, 'hint') !== '') {
            const hintLang = getData(data, 'hintLang');
            const langAttr = hintLang ? ` lang=\"\${hintLang}\"` : '';
            label += `<span class=\"light\"\${langAttr}>– \${Craft.escapeHtml(getData(data, 'hint'))}</span>`;
        }
        return `<div class=\"flex flex-nowrap\">\${label}</div>`;
    };

    const \$select = \$(`#\${id}`);

    const onChange = () => {
        const selectize = \$select.data('selectize');
        const \$items = selectize.\$wrapper.find('.item');
        const isSelect = \$select.is('select');

        for (let i = 0; i < \$items.length; i++) {
            const \$item = \$items.eq(i);

            if (isSelect) {
                const boolean = \$item.data('boolean');
                if (typeof boolean !== 'undefined') {
                    \$select.data('boolean', !!boolean);
                } else {
                    \$select.removeData('boolean');
                }
            }

            ";
        // line 126
        if ((array_key_exists('addOptionFn', $context) && array_key_exists('addOptionLabel', $context))) {
            // line 127
            echo "            if (\$item.data('add-option')) {
                selectize.close();
                selectize.blur();

                (";
            // line 131
            echo isset($context['addOptionFn']) || array_key_exists('addOptionFn', $context) ? $context['addOptionFn'] : (function () {
                throw new RuntimeError('Variable "addOptionFn" does not exist.', 131, $this->source);
            })();
            echo ")(item => {
                    selectize.addOption(item);

                    // Remove the “Create” option and re-place it at the end
                    selectize.removeOption('__add__', true);
                    selectize.addOption({
                        text: ";
            // line 137
            echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['addOptionLabel']) || array_key_exists('addOptionLabel', $context) ? $context['addOptionLabel'] : (function () {
                throw new RuntimeError('Variable "addOptionLabel" does not exist.', 137, $this->source);
            })()));
            echo " ,
                        value: '__add__',
                        addOption: true,
                        hint: null,
                    });

                    selectize.refreshOptions(false);

                    if (isSelect) {
                        selectize.setValue(item.value, true);
                    } else {
                        selectize.addItem(item.value, true);
                    }
                }, selectize);

                Garnish.requestAnimationFrame(() => {
                    if (isSelect) {
                        selectize.setValue(";
            // line 154
            echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((((craft\helpers\Template::attribute($this->env, $this->source, twig_first($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 154, $this->source);
            })())), 'value', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, twig_first($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 154, $this->source);
            })())), 'value', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, twig_first($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 154, $this->source);
            })())), 'value', [])) : ('')));
            echo ", true);
                    } else {
                        selectize.removeItem('__add__');
                    }
                });
            }
            ";
        }
        // line 161
        echo '        }
    };

    ';
        // line 164
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 164, $this->source);
        })())) {
            // line 165
            echo '        const selectizeDropdownOpenEvent = new Event("selectizedropdownopen");
        const selectizeDropdownCloseEvent = new Event("selectizedropdownclose");
    ';
        }
        // line 168
        echo "
    \$select.selectize(\$.extend({
        searchField: ['text', 'hint', 'value', 'keywords'],
        render: {
            option: data => {
                const classes = ['option'];
                if (data.value === '') {
                    classes.push('selectize-dropdown-emptyoptionlabel');
                }
                return `<div class=\"\${classes.join(' ')}\">\${label(data, true)}</div>`;
            },
            item: data => {
                const attrs = ['class=\"item\"'];
                if (hasData(data, 'boolean')) {
                    attrs.push(`data-boolean=\"\${getData(data, 'boolean') ? '1' : ''}\"`);
                }
                if (hasData(data, 'addOption')) {
                    attrs.push('data-add-option=\"1\"');
                }
                return `<div \${attrs.join(' ')}>\${label(data, false)}</div>`;
            },
        },
        onChange: onChange,
        onInitialize: function () {
            // Copy all ARIA attributes from initial select to selectize
            [...\$select[0].attributes]
                .filter(attr => /^aria-/.test(attr.name))
                .forEach((attr) => {
                    this.\$control_input.attr(attr.name, attr.value);
                });

            // allow autocomplete;
            // despite what the documentation says, the \"autofill_disable\" seems to be ON by default,
            // and there's no \"proper\" way to disable it
            // more info: https://github.com/selectize/selectize.js/issues/1535
            const autocomplete = \$select.attr('autocomplete');
            if (autocomplete) {
                const selectize = \$select.data('selectize');
                selectize.\$control_input
                    .removeAttr('autofill')
                    .attr('autocomplete', autocomplete);
            }
        },
        onDropdownOpen: function() {
            ";
        // line 212
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 212, $this->source);
        })())) {
            // line 213
            echo '                $select[0].dispatchEvent(selectizeDropdownOpenEvent);
            ';
        }
        // line 215
        echo '        },
        onDropdownClose: function() {
            ';
        // line 217
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 217, $this->source);
        })())) {
            // line 218
            echo '                $select[0].dispatchEvent(selectizeDropdownCloseEvent);
            ';
        }
        // line 220
        echo '        },
    }, ';
        // line 221
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
            throw new RuntimeError('Variable "selectizeOptions" does not exist.', 221, $this->source);
        })()));
        echo '));

    onChange();
})()
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        craft\helpers\Template::endProfile('template', '_includes/forms/selectize');
    }

    public function getTemplateName()
    {
        return '_includes/forms/selectize';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [319 => 221,  316 => 220,  312 => 218,  310 => 217,  306 => 215,  302 => 213,  300 => 212,  254 => 168,  249 => 165,  247 => 164,  242 => 161,  232 => 154,  212 => 137,  203 => 131,  197 => 127,  195 => 126,  136 => 70,  133 => 69,  131 => 68,  128 => 67,  126 => 65,  125 => 64,  124 => 61,  123 => 60,  120 => 59,  116 => 57,  114 => 56,  111 => 55,  108 => 48,  106 => 47,  103 => 46,  100 => 43,  97 => 40,  94 => 39,  92 => 38,  89 => 37,  86 => 33,  84 => 31,  81 => 30,  78 => 29,  75 => 28,  73 => 27,  70 => 26,  68 => 24,  67 => 23,  65 => 21,  64 => 20,  63 => 19,  60 => 17,  57 => 14,  55 => 13,  52 => 10,  50 => 9,  48 => 8,  46 => 7,  43 => 6,  41 => 5,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% set id = id ?? \"selectize#{random()}\" %}
{% set selectizeOptions = {
    dropdownParent: 'body',
    plugins: ['auto_position'],
}|merge(selectizeOptions ?? []) %}

{% set multi = multi ?? false %}
{% if multi %}
    {% set selectizeOptions = selectizeOptions|merge({
        plugins: (selectizeOptions.plugins ?? [])|push('remove_button')
    }) %}
{% else %}
    {% set selectizeOptions = selectizeOptions|merge({
        plugins: (selectizeOptions.plugins ?? [])|push('select_on_focus')
    }) %}
{% endif %}

{# Normalize the options #}
{% set options = (options ?? [])|map((o, k) => (o.optgroup is defined or o.value is defined) ? o : {
    value: k,
    label: o.label is defined ? o.label : o,
}) %}
{% set options = options|map(o => o|merge({
    data: (o.data ?? {})|merge(o.data.data ?? {})
})) %}

{% if includeEnvVars ?? false %}
    {% if allowedEnvValues is not defined %}
        {% set allowedEnvValues = options|filter(o => o.optgroup is not defined)|map(o => o.value) %}
    {% endif %}
    {% set options = options|map(o => o.data.hint is defined ? o : o|merge({
        data: {
            hint: o.value,
        },
    }, recursive=true)) %}
{% endif %}

{% if addOptionFn is defined and addOptionLabel is defined %}
    {% if options is empty %}
        {% set selectizeOptions = selectizeOptions|merge({
            allowEmptyOption: true,
        }) %}
        {% set options = [
            {value: '', label: ' '},
        ] %}
    {% endif %}
    {% set options = options|push({
        label: addOptionLabel ,
        value: '__add__',
        data: {
            addOption: true,
        },
    }) %}
{% endif %}

{% if includeEnvVars ?? false %}
    {% set options = options|merge(craft.cp.getEnvOptions(allowedEnvValues)) %}
{% endif %}

{% include (multi ? '_includes/forms/multiselect.twig' : '_includes/forms/select.twig') with {
    class: (class ?? [])|explodeClass|push('selectize')|unique,
    inputAttributes: {
        style: {display: 'none'},
        autocomplete: (not multi and autocomplete is defined) ? autocomplete : false
    }|merge(inputAttributes ?? [], recursive=true),
} %}

{% js %}
(() => {
    const id = {{ id|namespaceInputId|json_encode|raw }};

    const hasData = (data, option) => {
        return typeof data[option] !== 'undefined' || typeof data[option.toLowerCase()] !== 'undefined';
    };
    const getData = (data, option) => {
        if (typeof data[option] !== 'undefined') {
            return data[option];
        }
        return data[option.toLowerCase()];
    };
    const label = (data, showHint) => {
        let label = '';
        if (hasData(data, 'addOption')) {
            label += '<span class=\"icon add\"></span> ';
        }
        const status = (() => {
            if (hasData(data, 'status')) {
                return getData(data, 'status');
            }
            if (hasData(data, 'boolean')) {
                return getData(data, 'boolean') ? 'enabled' : 'white';
            }
            return null;
        })();
        if (status) {
            label += `<span class=\"status \${status}\"></span>`;
        }
        label += `<span>\${Craft.escapeHtml(getData(data, 'text'))}</span>`;
        if (showHint && hasData(data, 'hint') && getData(data, 'hint') !== '') {
            const hintLang = getData(data, 'hintLang');
            const langAttr = hintLang ? ` lang=\"\${hintLang}\"` : '';
            label += `<span class=\"light\"\${langAttr}>– \${Craft.escapeHtml(getData(data, 'hint'))}</span>`;
        }
        return `<div class=\"flex flex-nowrap\">\${label}</div>`;
    };

    const \$select = \$(`#\${id}`);

    const onChange = () => {
        const selectize = \$select.data('selectize');
        const \$items = selectize.\$wrapper.find('.item');
        const isSelect = \$select.is('select');

        for (let i = 0; i < \$items.length; i++) {
            const \$item = \$items.eq(i);

            if (isSelect) {
                const boolean = \$item.data('boolean');
                if (typeof boolean !== 'undefined') {
                    \$select.data('boolean', !!boolean);
                } else {
                    \$select.removeData('boolean');
                }
            }

            {% if addOptionFn is defined and addOptionLabel is defined %}
            if (\$item.data('add-option')) {
                selectize.close();
                selectize.blur();

                ({{ addOptionFn|raw }})(item => {
                    selectize.addOption(item);

                    // Remove the “Create” option and re-place it at the end
                    selectize.removeOption('__add__', true);
                    selectize.addOption({
                        text: {{ addOptionLabel|json_encode|raw }} ,
                        value: '__add__',
                        addOption: true,
                        hint: null,
                    });

                    selectize.refreshOptions(false);

                    if (isSelect) {
                        selectize.setValue(item.value, true);
                    } else {
                        selectize.addItem(item.value, true);
                    }
                }, selectize);

                Garnish.requestAnimationFrame(() => {
                    if (isSelect) {
                        selectize.setValue({{ ((options|first).value ?? '')|json_encode|raw }}, true);
                    } else {
                        selectize.removeItem('__add__');
                    }
                });
            }
            {% endif %}
        }
    };

    {% if not multi %}
        const selectizeDropdownOpenEvent = new Event(\"selectizedropdownopen\");
        const selectizeDropdownCloseEvent = new Event(\"selectizedropdownclose\");
    {% endif %}

    \$select.selectize(\$.extend({
        searchField: ['text', 'hint', 'value', 'keywords'],
        render: {
            option: data => {
                const classes = ['option'];
                if (data.value === '') {
                    classes.push('selectize-dropdown-emptyoptionlabel');
                }
                return `<div class=\"\${classes.join(' ')}\">\${label(data, true)}</div>`;
            },
            item: data => {
                const attrs = ['class=\"item\"'];
                if (hasData(data, 'boolean')) {
                    attrs.push(`data-boolean=\"\${getData(data, 'boolean') ? '1' : ''}\"`);
                }
                if (hasData(data, 'addOption')) {
                    attrs.push('data-add-option=\"1\"');
                }
                return `<div \${attrs.join(' ')}>\${label(data, false)}</div>`;
            },
        },
        onChange: onChange,
        onInitialize: function () {
            // Copy all ARIA attributes from initial select to selectize
            [...\$select[0].attributes]
                .filter(attr => /^aria-/.test(attr.name))
                .forEach((attr) => {
                    this.\$control_input.attr(attr.name, attr.value);
                });

            // allow autocomplete;
            // despite what the documentation says, the \"autofill_disable\" seems to be ON by default,
            // and there's no \"proper\" way to disable it
            // more info: https://github.com/selectize/selectize.js/issues/1535
            const autocomplete = \$select.attr('autocomplete');
            if (autocomplete) {
                const selectize = \$select.data('selectize');
                selectize.\$control_input
                    .removeAttr('autofill')
                    .attr('autocomplete', autocomplete);
            }
        },
        onDropdownOpen: function() {
            {% if not multi %}
                \$select[0].dispatchEvent(selectizeDropdownOpenEvent);
            {% endif %}
        },
        onDropdownClose: function() {
            {% if not multi %}
                \$select[0].dispatchEvent(selectizeDropdownCloseEvent);
            {% endif %}
        },
    }, {{ selectizeOptions|json_encode|raw }}));

    onChange();
})()
{% endjs %}
", '_includes/forms/selectize', '/Users/brianhanson/Development/craft5/src/templates/_includes/forms/selectize.twig');
    }
}
