<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _layouts/components/form-action-menu */
class __TwigTemplate_9c585d8cb7ddeb098e4e316e62f49f50 extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_layouts/components/form-action-menu');
        // line 31
        echo '
';
        // line 32
        $context['safeActions'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['formActions']) || array_key_exists('formActions', $context) ? $context['formActions'] : (function () {
            throw new RuntimeError('Variable "formActions" does not exist.', 32, $this->source);
        })()), function ($__a__) use ($context) {
            $context['a'] = $__a__;

            return ! (((craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [])) : (false));
        });
        // line 33
        $context['destructiveActions'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['formActions']) || array_key_exists('formActions', $context) ? $context['formActions'] : (function () {
            throw new RuntimeError('Variable "formActions" does not exist.', 33, $this->source);
        })()), function ($__a__) use ($context) {
            $context['a'] = $__a__;

            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [])) : (false);
        });
        // line 34
        echo '
<div id="form-action-menu" class="menu menu--disclosure" data-align="right">
    ';
        // line 36
        if ((isset($context['safeActions']) || array_key_exists('safeActions', $context) ? $context['safeActions'] : (function () {
            throw new RuntimeError('Variable "safeActions" does not exist.', 36, $this->source);
        })())) {
            // line 37
            echo '        ';
            echo twig_call_macro($macros['_self'], 'macro_actionList', [(isset($context['safeActions']) || array_key_exists('safeActions', $context) ? $context['safeActions'] : (function () {
                throw new RuntimeError('Variable "safeActions" does not exist.', 37, $this->source);
            })()), false], 37, $context, $this->getSourceContext());
            echo '
    ';
        }
        // line 39
        echo '    ';
        if (((isset($context['safeActions']) || array_key_exists('safeActions', $context) ? $context['safeActions'] : (function () {
            throw new RuntimeError('Variable "safeActions" does not exist.', 39, $this->source);
        })()) && (isset($context['destructiveActions']) || array_key_exists('destructiveActions', $context) ? $context['destructiveActions'] : (function () {
            throw new RuntimeError('Variable "destructiveActions" does not exist.', 39, $this->source);
        })()))) {
            // line 40
            echo '        <hr>
    ';
        }
        // line 42
        echo '    ';
        if ((isset($context['destructiveActions']) || array_key_exists('destructiveActions', $context) ? $context['destructiveActions'] : (function () {
            throw new RuntimeError('Variable "destructiveActions" does not exist.', 42, $this->source);
        })())) {
            // line 43
            echo '        ';
            echo twig_call_macro($macros['_self'], 'macro_actionList', [(isset($context['destructiveActions']) || array_key_exists('destructiveActions', $context) ? $context['destructiveActions'] : (function () {
                throw new RuntimeError('Variable "destructiveActions" does not exist.', 43, $this->source);
            })()), true], 43, $context, $this->getSourceContext());
            echo '
    ';
        }
        // line 45
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/form-action-menu');
    }

    // line 1
    public function macro_actionList($__actions__ = null, $__destructive__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = $this->env->mergeGlobals([
            'actions' => $__actions__,
            'destructive' => $__destructive__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'actionList');
            // line 2
            echo '    ';
            $macros['forms'] = $this->loadTemplate('_includes/forms', '_layouts/components/form-action-menu', 2)->unwrap();
            // line 3
            echo '    <ul>
        ';
            // line 4
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['actions']) || array_key_exists('actions', $context) ? $context['actions'] : (function () {
                throw new RuntimeError('Variable "actions" does not exist.', 4, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['action']) {
                // line 5
                echo '            <li>
                ';
                // line 6
                $context['linkAttributes'] = ['tabindex' => '0', 'role' => 'button', 'class' => [0 => 'formsubmit', 1 => ((((                // line 11
                    $context['destructive']) ?? (false))) ? ('error') : (''))], 'data' => ['action' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 14
                        $context['action'], 'action', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'action', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'action', [])) : (false)), 'redirect' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 15
                            $context['action'], 'redirect', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'redirect', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'redirect', [])) : (false)), 'confirm' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 16
                                $context['action'], 'confirm', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'confirm', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'confirm', [])) : (false)), 'params' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 17
                                    $context['action'], 'params', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'params', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'params', [])) : (false)), 'event-data' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 18
                                        $context['action'], 'eventData', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'eventData', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'eventData', [])) : (false))]];
                // line 21
                echo '                <a ';
                echo craft\helpers\Html::renderTagAttributes((isset($context['linkAttributes']) || array_key_exists('linkAttributes', $context) ? $context['linkAttributes'] : (function () {
                    throw new RuntimeError('Variable "linkAttributes" does not exist.', 21, $this->source);
                })()));
                echo '>
                    ';
                // line 22
                if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shortcut', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shortcut', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shortcut', [])) : (false))) {
                    // line 23
                    echo '                        ';
                    echo twig_call_macro($macros['forms'], 'macro_optionShortcutLabel', ['S', (((craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shift', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shift', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shift', [])) : (false))], 23, $context, $this->getSourceContext());
                    echo '
                    ';
                }
                // line 25
                echo '                    ';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'label', []), 'html', null, true);
                echo '
                </a>
            </li>
        ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['action'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 29
            echo '    </ul>
';
            craft\helpers\Template::endProfile('macro', 'actionList');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_layouts/components/form-action-menu';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [140 => 29,  129 => 25,  123 => 23,  121 => 22,  116 => 21,  114 => 18,  113 => 17,  112 => 16,  111 => 15,  110 => 14,  109 => 11,  108 => 6,  105 => 5,  101 => 4,  98 => 3,  95 => 2,  80 => 1,  74 => 45,  68 => 43,  65 => 42,  61 => 40,  58 => 39,  52 => 37,  50 => 36,  46 => 34,  44 => 33,  42 => 32,  39 => 31];
    }

    public function getSourceContext()
    {
        return new Source("{% macro actionList(actions, destructive) %}
    {% import '_includes/forms' as forms %}
    <ul>
        {% for action in actions %}
            <li>
                {% set linkAttributes = {
                    tabindex: '0',
                    role: 'button',
                    class: [
                        'formsubmit',
                        (destructive ?? false) ? 'error',
                    ],
                    data: {
                        action: action.action ?? false,
                        redirect: action.redirect ?? false,
                        confirm: action.confirm ?? false,
                        params: action.params ?? false,
                        'event-data': action.eventData ?? false,
                    },
                } %}
                <a {{ attr(linkAttributes) }}>
                    {% if action.shortcut ?? false %}
                        {{ forms.optionShortcutLabel('S', action.shift ?? false) }}
                    {% endif %}
                    {{ action.label }}
                </a>
            </li>
        {% endfor %}
    </ul>
{% endmacro %}

{% set safeActions = formActions|filter(a => not (a.destructive ?? false)) %}
{% set destructiveActions = formActions|filter(a => a.destructive ?? false) %}

<div id=\"form-action-menu\" class=\"menu menu--disclosure\" data-align=\"right\">
    {% if safeActions %}
        {{ _self.actionList(safeActions, false) }}
    {% endif %}
    {% if safeActions and destructiveActions %}
        <hr>
    {% endif %}
    {% if destructiveActions %}
        {{ _self.actionList(destructiveActions, true) }}
    {% endif %}
</div>
", '_layouts/components/form-action-menu', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/form-action-menu.twig');
    }
}
