<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _includes/forms/selectize */
class __TwigTemplate_3435c49d5889d423ae37088c2902720c extends Template
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
        craft\helpers\Template::beginProfile('template', '_includes/forms/selectize');
        // line 1
        $context['id'] ??= 'selectize'.Twig\Extension\CoreExtension::random($this->env->getCharset());
        // line 2
        $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['dropdownParent' => 'body', 'plugins' => ['auto_position']], ((        // line 5
            $context['selectizeOptions']) ?? ([])));
        // line 6
        yield '
';
        // line 7
        $context['multi'] ??= false;
        // line 8
        if ((isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 8, $this->source);
        })())) {
            // line 9
            yield '    ';
            $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
                throw new RuntimeError('Variable "selectizeOptions" does not exist.', 9, $this->source);
            })()), ['plugins' => $this->extensions['craft\web\twig\Extension']->pushFilter((((craft\helpers\Template::attribute($this->env, $this->source,             // line 10
                ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', true, true, false, 10) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', false, false, false, 10) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', false, false, false, 10)) : ([])), 'remove_button')]);
        } else {
            // line 13
            yield '    ';
            $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
                throw new RuntimeError('Variable "selectizeOptions" does not exist.', 13, $this->source);
            })()), ['plugins' => $this->extensions['craft\web\twig\Extension']->pushFilter((((craft\helpers\Template::attribute($this->env, $this->source,             // line 14
                ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', true, true, false, 14) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', false, false, false, 14) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['selectizeOptions'] ?? null), 'plugins', [], 'any', false, false, false, 14)) : ([])), 'select_on_focus')]);
        }
        // line 17
        yield '
';
        // line 19
        $context['options'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (($context['options']) ?? ([])), function ($__o__, $__k__) use ($context) {
            $context['o'] = $__o__;
            $context['k'] = $__k__;

            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'optgroup', [], 'any', true, true, false, 19) || craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'value', [], 'any', true, true, false, 19))) ? ((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                throw new RuntimeError('Variable "o" does not exist.', 19, $this->source);
            })())) : (['value' =>         // line 20
(isset($context['k']) || array_key_exists('k', $context) ? $context['k'] : (function () {
    throw new RuntimeError('Variable "k" does not exist.', 20, $this->source);
})()), 'label' => ((craft\helpers\Template::attribute($this->env, $this->source,         // line 21
    ($context['o'] ?? null), 'label', [], 'any', true, true, false, 21)) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
        throw new RuntimeError('Variable "o" does not exist.', 21, $this->source);
    })()), 'label', [], 'any', false, false, false, 21)) : ((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
        throw new RuntimeError('Variable "o" does not exist.', 21, $this->source);
    })())))]);
        });
        // line 23
        $context['options'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
            throw new RuntimeError('Variable "options" does not exist.', 23, $this->source);
        })()), function ($__o__) use ($context) {
            $context['o'] = $__o__;

            return $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                throw new RuntimeError('Variable "o" does not exist.', 23, $this->source);
            })()), ['data' => $this->extensions['craft\web\twig\Extension']->mergeFilter((((craft\helpers\Template::attribute($this->env, $this->source,         // line 24
                ($context['o'] ?? null), 'data', [], 'any', true, true, false, 24) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, false, false, 24) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, false, false, 24)) : ([])), (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true, false, 24), 'data', [], 'any', true, true, false, 24) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true, false, 24), 'data', [], 'any', false, false, false, 24) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true, false, 24), 'data', [], 'any', false, false, false, 24)) : ([])))]);
        });
        // line 26
        yield '
';
        // line 27
        if ((($context['includeEnvVars']) ?? (false))) {
            // line 28
            yield '    ';
            if (! array_key_exists('allowedEnvValues', $context)) {
                // line 29
                yield '        ';
                $context['allowedEnvValues'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                    throw new RuntimeError('Variable "options" does not exist.', 29, $this->source);
                })()), function ($__o__) use ($context) {
                    $context['o'] = $__o__;

                    return ! craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'optgroup', [], 'any', true, true, false, 29);
                }), function ($__o__) use ($context) {
                    $context['o'] = $__o__;

                    return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                        throw new RuntimeError('Variable "o" does not exist.', 29, $this->source);
                    })()), 'value', [], 'any', false, false, false, 29);
                });
                // line 30
                yield '    ';
            }
            // line 31
            yield '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->mapFilter($this->env, (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 31, $this->source);
            })()), function ($__o__) use ($context) {
                $context['o'] = $__o__;

                return (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'data', [], 'any', false, true, false, 31), 'hint', [], 'any', true, true, false, 31)) ? ((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                    throw new RuntimeError('Variable "o" does not exist.', 31, $this->source);
                })())) : ($this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                    throw new RuntimeError('Variable "o" does not exist.', 31, $this->source);
                })()), ['data' => ['hint' => craft\helpers\Template::attribute($this->env, $this->source,             // line 33
                    (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                        throw new RuntimeError('Variable "o" does not exist.', 33, $this->source);
                    })()), 'value', [], 'any', false, false, false, 33)]], true));
            });
        }
        // line 37
        yield '
';
        // line 38
        if ((array_key_exists('addOptionFn', $context) && array_key_exists('addOptionLabel', $context))) {
            // line 39
            yield '    ';
            if (Twig\Extension\CoreExtension::testEmpty((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 39, $this->source);
            })()))) {
                // line 40
                yield '        ';
                $context['selectizeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
                    throw new RuntimeError('Variable "selectizeOptions" does not exist.', 40, $this->source);
                })()), ['allowEmptyOption' => true]);
                // line 43
                yield '        ';
                $context['options'] = [['value' => '', 'label' => ' ']];
                // line 46
                yield '    ';
            }
            // line 47
            yield '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->pushFilter((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 47, $this->source);
            })()), ['label' =>             // line 48
(isset($context['addOptionLabel']) || array_key_exists('addOptionLabel', $context) ? $context['addOptionLabel'] : (function () {
    throw new RuntimeError('Variable "addOptionLabel" does not exist.', 48, $this->source);
})()), 'value' => '__add__', 'data' => ['addOption' => true]]);
        }
        // line 55
        yield '
';
        // line 56
        if ((($context['includeEnvVars']) ?? (false))) {
            // line 57
            yield '    ';
            $context['options'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 57, $this->source);
            })()), craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 57, $this->source);
            })()), 'cp', [], 'any', false, false, false, 57), 'getEnvOptions', [(isset($context['allowedEnvValues']) || array_key_exists('allowedEnvValues', $context) ? $context['allowedEnvValues'] : (function () {
                throw new RuntimeError('Variable "allowedEnvValues" does not exist.', 57, $this->source);
            })())], 'method', false, false, false, 57));
        }
        // line 59
        yield '
';
        // line 60
        yield from $this->loadTemplate((((isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 60, $this->source);
        })())) ? ('_includes/forms/multiselect.twig') : ('_includes/forms/select.twig')), '_includes/forms/selectize', 60)->unwrap()->yield(CoreExtension::merge($context, ['class' => array_unique($this->extensions['craft\web\twig\Extension']->pushFilter(craft\helpers\Html::explodeClass(((        // line 61
            $context['class']) ?? ([]))), 'selectize')), 'inputAttributes' => $this->extensions['craft\web\twig\Extension']->mergeFilter(['style' => ['display' => 'none'], 'autocomplete' => (((! // line 64
            (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
                throw new RuntimeError('Variable "multi" does not exist.', 64, $this->source);
            })()) && array_key_exists('autocomplete', $context))) ? ((isset($context['autocomplete']) || array_key_exists('autocomplete', $context) ? $context['autocomplete'] : (function () {
                throw new RuntimeError('Variable "autocomplete" does not exist.', 64, $this->source);
            })())) : (false))], ((        // line 65
                $context['inputAttributes']) ?? ([])), true)]));
        // line 67
        yield '
';
        // line 68
        ob_start();
        // line 69
        yield '(() => {
    const id = ';
        // line 70
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter($this->env->getFilter('namespaceInputId')->getCallable()((isset($context['id']) || array_key_exists('id', $context) ? $context['id'] : (function () {
            throw new RuntimeError('Variable "id" does not exist.', 70, $this->source);
        })())));
        yield ";

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
            yield "            if (\$item.data('add-option')) {
                selectize.close();
                selectize.blur();

                (";
            // line 131
            yield isset($context['addOptionFn']) || array_key_exists('addOptionFn', $context) ? $context['addOptionFn'] : (function () {
                throw new RuntimeError('Variable "addOptionFn" does not exist.', 131, $this->source);
            })();
            yield ")(item => {
                    selectize.addOption(item);

                    // Remove the “Create” option and re-place it at the end
                    selectize.removeOption('__add__', true);
                    selectize.addOption({
                        text: ";
            // line 137
            yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['addOptionLabel']) || array_key_exists('addOptionLabel', $context) ? $context['addOptionLabel'] : (function () {
                throw new RuntimeError('Variable "addOptionLabel" does not exist.', 137, $this->source);
            })()));
            yield " ,
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
            yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((((craft\helpers\Template::attribute($this->env, $this->source, Twig\Extension\CoreExtension::first($this->env->getCharset(), (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 154, $this->source);
            })())), 'value', [], 'any', true, true, false, 154) && ! (craft\helpers\Template::attribute($this->env, $this->source, Twig\Extension\CoreExtension::first($this->env->getCharset(), (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 154, $this->source);
            })())), 'value', [], 'any', false, false, false, 154) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, Twig\Extension\CoreExtension::first($this->env->getCharset(), (isset($context['options']) || array_key_exists('options', $context) ? $context['options'] : (function () {
                throw new RuntimeError('Variable "options" does not exist.', 154, $this->source);
            })())), 'value', [], 'any', false, false, false, 154)) : ('')));
            yield ", true);
                    } else {
                        selectize.removeItem('__add__');
                    }
                });
            }
            ";
        }
        // line 161
        yield '        }
    };

    ';
        // line 164
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 164, $this->source);
        })())) {
            // line 165
            yield '        const selectizeDropdownOpenEvent = new Event("selectizedropdownopen");
        const selectizeDropdownCloseEvent = new Event("selectizedropdownclose");
    ';
        }
        // line 168
        yield "
    \$select.selectize(\$.extend({
        searchField: ['text', 'hint', 'value', 'keywords'],
        selectOnTab: false,
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

            ";
        // line 200
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 200, $this->source);
        })())) {
            // line 201
            yield "                // we need some sort of class here to be able to prevent Garnish.setFocusWithin()
                // from auto-focusing and therefore opening the selectized field if it's the first one in a slideout
                // see https://github.com/craftcms/cms/issues/15245
                this.\$control_input.addClass('prevent-autofocus');
            ";
        }
        // line 206
        yield "
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
        // line 220
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 220, $this->source);
        })())) {
            // line 221
            yield '                $select[0].dispatchEvent(selectizeDropdownOpenEvent);
            ';
        }
        // line 223
        yield '        },
        onDropdownClose: function() {
            ';
        // line 225
        if (! (isset($context['multi']) || array_key_exists('multi', $context) ? $context['multi'] : (function () {
            throw new RuntimeError('Variable "multi" does not exist.', 225, $this->source);
        })())) {
            // line 226
            yield '                $select[0].dispatchEvent(selectizeDropdownCloseEvent);
            ';
        }
        // line 228
        yield '        },
    }, ';
        // line 229
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((isset($context['selectizeOptions']) || array_key_exists('selectizeOptions', $context) ? $context['selectizeOptions'] : (function () {
            throw new RuntimeError('Variable "selectizeOptions" does not exist.', 229, $this->source);
        })()));
        yield '));

    onChange();
})()
';
        craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        craft\helpers\Template::endProfile('template', '_includes/forms/selectize');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/selectize';
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
        return [337 => 229,  334 => 228,  330 => 226,  328 => 225,  324 => 223,  320 => 221,  318 => 220,  302 => 206,  295 => 201,  293 => 200,  259 => 168,  254 => 165,  252 => 164,  247 => 161,  237 => 154,  217 => 137,  208 => 131,  202 => 127,  200 => 126,  141 => 70,  138 => 69,  136 => 68,  133 => 67,  131 => 65,  130 => 64,  129 => 61,  128 => 60,  125 => 59,  121 => 57,  119 => 56,  116 => 55,  113 => 48,  111 => 47,  108 => 46,  105 => 43,  102 => 40,  99 => 39,  97 => 38,  94 => 37,  91 => 33,  89 => 31,  86 => 30,  83 => 29,  80 => 28,  78 => 27,  75 => 26,  73 => 24,  72 => 23,  70 => 21,  69 => 20,  68 => 19,  65 => 17,  62 => 14,  60 => 13,  57 => 10,  55 => 9,  53 => 8,  51 => 7,  48 => 6,  46 => 5,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
        selectOnTab: false,
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

            {% if not multi %}
                // we need some sort of class here to be able to prevent Garnish.setFocusWithin()
                // from auto-focusing and therefore opening the selectized field if it's the first one in a slideout
                // see https://github.com/craftcms/cms/issues/15245
                this.\$control_input.addClass('prevent-autofocus');
            {% endif %}

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
", '_includes/forms/selectize', '/tmp/packages/craft5/src/templates/_includes/forms/selectize.twig');
    }
}
