<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _layouts/components/form-action-menu */
class __TwigTemplate_429ca3b137e1c475f2396637a5522c06 extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_layouts/components/form-action-menu');
        // line 32
        yield '
';
        // line 33
        $context['safeActions'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['formActions']) || array_key_exists('formActions', $context) ? $context['formActions'] : (function () {
            throw new RuntimeError('Variable "formActions" does not exist.', 33, $this->source);
        })()), function ($__a__) use ($context) {
            $context['a'] = $__a__;

            return ! (((craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', true, true, false, 33) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', false, false, false, 33) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', false, false, false, 33)) : (false));
        });
        // line 34
        $context['destructiveActions'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['formActions']) || array_key_exists('formActions', $context) ? $context['formActions'] : (function () {
            throw new RuntimeError('Variable "formActions" does not exist.', 34, $this->source);
        })()), function ($__a__) use ($context) {
            $context['a'] = $__a__;

            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', true, true, false, 34) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', false, false, false, 34) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a'] ?? null), 'destructive', [], 'any', false, false, false, 34)) : (false);
        });
        // line 35
        yield '
<div id="form-action-menu" class="menu menu--disclosure" data-align="right">
    ';
        // line 37
        if ((isset($context['safeActions']) || array_key_exists('safeActions', $context) ? $context['safeActions'] : (function () {
            throw new RuntimeError('Variable "safeActions" does not exist.', 37, $this->source);
        })())) {
            // line 38
            yield '        ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_actionList', [(isset($context['safeActions']) || array_key_exists('safeActions', $context) ? $context['safeActions'] : (function () {
                throw new RuntimeError('Variable "safeActions" does not exist.', 38, $this->source);
            })()), false], 38, $context, $this->getSourceContext());
            yield '
    ';
        }
        // line 40
        yield '    ';
        if (((isset($context['safeActions']) || array_key_exists('safeActions', $context) ? $context['safeActions'] : (function () {
            throw new RuntimeError('Variable "safeActions" does not exist.', 40, $this->source);
        })()) && (isset($context['destructiveActions']) || array_key_exists('destructiveActions', $context) ? $context['destructiveActions'] : (function () {
            throw new RuntimeError('Variable "destructiveActions" does not exist.', 40, $this->source);
        })()))) {
            // line 41
            yield '        <hr>
    ';
        }
        // line 43
        yield '    ';
        if ((isset($context['destructiveActions']) || array_key_exists('destructiveActions', $context) ? $context['destructiveActions'] : (function () {
            throw new RuntimeError('Variable "destructiveActions" does not exist.', 43, $this->source);
        })())) {
            // line 44
            yield '        ';
            yield CoreExtension::callMacro($macros['_self'], 'macro_actionList', [(isset($context['destructiveActions']) || array_key_exists('destructiveActions', $context) ? $context['destructiveActions'] : (function () {
                throw new RuntimeError('Variable "destructiveActions" does not exist.', 44, $this->source);
            })()), true], 44, $context, $this->getSourceContext());
            yield '
    ';
        }
        // line 46
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/form-action-menu');
        yield from [];
    }

    // line 1
    public function macro_actionList($__actions__ = null, $__destructive__ = null, ...$__varargs__)
    {
        $macros = $this->macros;
        $context = [
            'actions' => $__actions__,
            'destructive' => $__destructive__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros) {
            craft\helpers\Template::beginProfile('macro', 'actionList');
            // line 2
            yield '    ';
            $macros['forms'] = $this->loadTemplate('_includes/forms', '_layouts/components/form-action-menu', 2)->unwrap();
            // line 3
            yield '    <ul>
        ';
            // line 4
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['actions']) || array_key_exists('actions', $context) ? $context['actions'] : (function () {
                throw new RuntimeError('Variable "actions" does not exist.', 4, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['action']) {
                // line 5
                yield '            <li>
                ';
                // line 6
                $context['linkAttributes'] = ['tabindex' => '0', 'role' => 'button', 'class' => ['formsubmit', ((((                // line 11
                    $context['destructive']) ?? (false))) ? ('error') : (''))], 'data' => ['action' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 14
                        $context['action'], 'action', [], 'any', true, true, false, 14) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'action', [], 'any', false, false, false, 14) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'action', [], 'any', false, false, false, 14)) : (false)), 'redirect' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 15
                            $context['action'], 'redirect', [], 'any', true, true, false, 15) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'redirect', [], 'any', false, false, false, 15) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'redirect', [], 'any', false, false, false, 15)) : (false)), 'confirm' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 16
                                $context['action'], 'confirm', [], 'any', true, true, false, 16) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'confirm', [], 'any', false, false, false, 16) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'confirm', [], 'any', false, false, false, 16)) : (false)), 'params' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 17
                                    $context['action'], 'params', [], 'any', true, true, false, 17) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'params', [], 'any', false, false, false, 17) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'params', [], 'any', false, false, false, 17)) : (false)), 'retain-scroll' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 18
                                        $context['action'], 'retainScroll', [], 'any', true, true, false, 18) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'retainScroll', [], 'any', false, false, false, 18) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'retainScroll', [], 'any', false, false, false, 18)) : (false)), 'event-data' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 19
                                            $context['action'], 'eventData', [], 'any', true, true, false, 19) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'eventData', [], 'any', false, false, false, 19) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'eventData', [], 'any', false, false, false, 19)) : (false))]];
                // line 22
                yield '                <a ';
                yield craft\helpers\Html::renderTagAttributes((isset($context['linkAttributes']) || array_key_exists('linkAttributes', $context) ? $context['linkAttributes'] : (function () {
                    throw new RuntimeError('Variable "linkAttributes" does not exist.', 22, $this->source);
                })()));
                yield '>
                    ';
                // line 23
                if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shortcut', [], 'any', true, true, false, 23) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shortcut', [], 'any', false, false, false, 23) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shortcut', [], 'any', false, false, false, 23)) : (false))) {
                    // line 24
                    yield '                        ';
                    yield CoreExtension::callMacro($macros['forms'], 'macro_optionShortcutLabel', ['S', (((craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shift', [], 'any', true, true, false, 24) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shift', [], 'any', false, false, false, 24) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'shift', [], 'any', false, false, false, 24)) : (false))], 24, $context, $this->getSourceContext());
                    yield '
                    ';
                }
                // line 26
                yield '                    ';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['action'], 'label', [], 'any', false, false, false, 26), 'html', null, true);
                yield '
                </a>
            </li>
        ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['action'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 30
            yield '    </ul>
';
            craft\helpers\Template::endProfile('macro', 'actionList');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/form-action-menu';
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
        return [146 => 30,  135 => 26,  129 => 24,  127 => 23,  122 => 22,  120 => 19,  119 => 18,  118 => 17,  117 => 16,  116 => 15,  115 => 14,  114 => 11,  113 => 6,  110 => 5,  106 => 4,  103 => 3,  100 => 2,  86 => 1,  79 => 46,  73 => 44,  70 => 43,  66 => 41,  63 => 40,  57 => 38,  55 => 37,  51 => 35,  49 => 34,  47 => 33,  44 => 32];
    }

    public function getSourceContext(): Source
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
                        'retain-scroll': action.retainScroll ?? false,
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
", '_layouts/components/form-action-menu', '/tmp/packages/craft5/src/templates/_layouts/components/form-action-menu.twig');
    }
}
