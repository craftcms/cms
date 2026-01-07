<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* dashboard/_index.twig */
class __TwigTemplate_f0d089fbba44e16dcbbc39d0274c25be extends Template
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

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
            'main' => $this->block_main(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', 'dashboard/_index.twig');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Dashboard', 'app');
        // line 3
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', 'dashboard/_index.twig', 3)->unwrap();
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'dashboard/_index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'dashboard/_index.twig');
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 6
        yield '    <div class="buttons">
        <div class="newwidget btngroup">
            <button type="button" id="newwidgetmenubtn" class="btn menubtn add icon" aria-controls="new-widget-menu" data-disclosure-trigger>';
        // line 8
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('New widget', 'app'), 'html', null, true);
        yield '</button>
            <div id="new-widget-menu" class="menu menu--disclosure newwidgetmenu" data-disclosure-menu>
                <ul>
                    ';
        // line 11
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['widgetTypes']) || array_key_exists('widgetTypes', $context) ? $context['widgetTypes'] : (function () {
            throw new RuntimeError('Variable "widgetTypes" does not exist.', 11, $this->source);
        })()), function ($__t__) use ($context) {
            $context['t'] = $__t__;

            return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['t']) || array_key_exists('t', $context) ? $context['t'] : (function () {
                throw new RuntimeError('Variable "t" does not exist.', 11, $this->source);
            })()), 'selectable', [], 'any', false, false, false, 11);
        }));
        foreach ($context['_seq'] as $context['type'] => $context['info']) {
            // line 12
            yield '                        <li>
                            <a role="button" type="button" class="menu-item" tabindex="0" data-type="';
            // line 13
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['type'], 'html', null, true);
            yield '" data-name="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'name', [], 'any', false, false, false, 13), 'html', null, true);
            yield '">
                                <span class="icon" aria-hidden="true">';
            // line 14
            yield $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'iconSvg', [], 'any', false, false, false, 14), false);
            yield '</span>
                                ';
            // line 15
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'name', [], 'any', false, false, false, 15), 'html', null, true);
            yield '
                            </a>
                        </li>
                    ';
        }
        unset($context['_seq'], $context['type'], $context['info'], $context['_parent']);
        // line 19
        yield '                </ul>
            </div>
        </div>

        <button type="button" id="widgetManagerBtn" class="btn settings icon" title="';
        // line 23
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'html', null, true);
        yield '" aria-label="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'html', null, true);
        yield '" aria-expanded="false"></button>
    </div>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    // line 28
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_main(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'main');
        // line 29
        yield '    <div id="dashboard-grid" class="grid">
        ';
        // line 30
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['widgets']) || array_key_exists('widgets', $context) ? $context['widgets'] : (function () {
            throw new RuntimeError('Variable "widgets" does not exist.', 30, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['widget']) {
            // line 31
            yield '            ';
            $context['widgetHeadingId'] = ('widget-heading-'.craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', [], 'any', false, false, false, 31));
            // line 32
            yield '            <div class="item" data-colspan="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'colspan', [], 'any', false, false, false, 32), 'html', null, true);
            yield '">
                <div id="widget';
            // line 33
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', [], 'any', false, false, false, 33), 'html', null, true);
            yield '" class="widget ';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(Twig\Extension\CoreExtension::lower($this->env->getCharset(), craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'type', [], 'any', false, false, false, 33)), 'html', null, true);
            yield '" data-id="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', [], 'any', false, false, false, 33), 'html', null, true);
            yield '" data-type="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'type', [], 'any', false, false, false, 33), 'html', null, true);
            yield '" data-title="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', [], 'any', false, false, false, 33), 'html', null, true);
            yield '">
                    <div class="front">
                        <div class="pane">
                            <div class="spinner body-loading"></div>
                            ';
            // line 37
            if ((craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', [], 'any', false, false, false, 37) || craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'subtitle', [], 'any', false, false, false, 37))) {
                // line 38
                yield '                                <div id="';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['widgetHeadingId']) || array_key_exists('widgetHeadingId', $context) ? $context['widgetHeadingId'] : (function () {
                    throw new RuntimeError('Variable "widgetHeadingId" does not exist.', 38, $this->source);
                })()), 'html', null, true);
                yield '" class="widget-heading">
                                    ';
                // line 39
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', [], 'any', false, false, false, 39)) {
                    // line 40
                    yield '                                        <h2>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', [], 'any', false, false, false, 40), 'html', null, true);
                    yield '</h2>
                                    ';
                }
                // line 42
                yield '                                    ';
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'subtitle', [], 'any', false, false, false, 42)) {
                    // line 43
                    yield '                                        <h5>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'subtitle', [], 'any', false, false, false, 43), 'html', null, true);
                    yield '</h5>
                                    ';
                }
                // line 45
                yield '                                </div>
                            ';
            }
            // line 47
            yield '                            <button role="button" class="settings icon hidden" aria-label="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Widget settings', 'app'), 'html', null, true);
            yield '" aria-describedby="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['widgetHeadingId']) || array_key_exists('widgetHeadingId', $context) ? $context['widgetHeadingId'] : (function () {
                throw new RuntimeError('Variable "widgetHeadingId" does not exist.', 47, $this->source);
            })()), 'html', null, true);
            yield '" data-settings-toggle></button>
                            <div class="body">
                                ';
            // line 49
            yield craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'bodyHtml', [], 'any', false, false, false, 49);
            yield '
                            </div>
                        </div>
                    </div>
                    <div class="back hidden">
                        <form class="pane">
                            ';
            // line 55
            yield craft\helpers\Html::hiddenInput('widgetId', craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', [], 'any', false, false, false, 55));
            yield '
                            <h2 class="first">';
            // line 56
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('{type} Settings', 'app', ['type' => craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'name', [], 'any', false, false, false, 56)]), 'html', null, true);
            yield '</h2>
                            <div class="settings"></div>
                            <hr>
                            <div class="buttons clearafter">
                                ';
            // line 60
            yield CoreExtension::callMacro($macros['forms'], 'macro_submitButton', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Save', 'app'), 'spinner' => true]], 60, $context, $this->getSourceContext());
            yield '
                                ';
            // line 61
            yield CoreExtension::callMacro($macros['forms'], 'macro_button', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Cancel', 'app'), 'class' => 'cancel-btn']], 61, $context, $this->getSourceContext());
            yield '
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        ';
        }
        unset($context['_seq'], $context['_key'], $context['widget'], $context['_parent']);
        // line 68
        yield '    </div>
';
        craft\helpers\Template::endProfile('block', 'main');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'dashboard/_index.twig';
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
        return [232 => 68,  219 => 61,  215 => 60,  208 => 56,  204 => 55,  195 => 49,  187 => 47,  183 => 45,  177 => 43,  174 => 42,  168 => 40,  166 => 39,  161 => 38,  159 => 37,  144 => 33,  139 => 32,  136 => 31,  132 => 30,  129 => 29,  121 => 28,  110 => 23,  104 => 19,  94 => 15,  90 => 14,  84 => 13,  81 => 12,  77 => 11,  71 => 8,  67 => 6,  59 => 5,  53 => 1,  51 => 3,  49 => 2,  41 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"_layouts/cp\" %}
{% set title = \"Dashboard\"|t('app') %}
{% import '_includes/forms' as forms %}

{% block actionButton %}
    <div class=\"buttons\">
        <div class=\"newwidget btngroup\">
            <button type=\"button\" id=\"newwidgetmenubtn\" class=\"btn menubtn add icon\" aria-controls=\"new-widget-menu\" data-disclosure-trigger>{{ 'New widget'|t('app') }}</button>
            <div id=\"new-widget-menu\" class=\"menu menu--disclosure newwidgetmenu\" data-disclosure-menu>
                <ul>
                    {% for type, info in widgetTypes|filter(t => t.selectable) %}
                        <li>
                            <a role=\"button\" type=\"button\" class=\"menu-item\" tabindex=\"0\" data-type=\"{{ type }}\" data-name=\"{{ info.name }}\">
                                <span class=\"icon\" aria-hidden=\"true\">{{ svg(info.iconSvg, sanitize=false) }}</span>
                                {{ info.name }}
                            </a>
                        </li>
                    {% endfor %}
                </ul>
            </div>
        </div>

        <button type=\"button\" id=\"widgetManagerBtn\" class=\"btn settings icon\" title=\"{{ 'Settings'|t('app') }}\" aria-label=\"{{ 'Settings'|t('app') }}\" aria-expanded=\"false\"></button>
    </div>
{% endblock %}


{% block main %}
    <div id=\"dashboard-grid\" class=\"grid\">
        {% for widget in widgets %}
            {% set widgetHeadingId = \"widget-heading-#{widget.id}\" %}
            <div class=\"item\" data-colspan=\"{{ widget.colspan }}\">
                <div id=\"widget{{ widget.id }}\" class=\"widget {{ widget.type|lower }}\" data-id=\"{{ widget.id }}\" data-type=\"{{ widget.type }}\" data-title=\"{{ widget.title }}\">
                    <div class=\"front\">
                        <div class=\"pane\">
                            <div class=\"spinner body-loading\"></div>
                            {% if widget.title or widget.subtitle %}
                                <div id=\"{{ widgetHeadingId }}\" class=\"widget-heading\">
                                    {% if widget.title %}
                                        <h2>{{ widget.title }}</h2>
                                    {% endif %}
                                    {% if widget.subtitle %}
                                        <h5>{{ widget.subtitle }}</h5>
                                    {% endif %}
                                </div>
                            {% endif %}
                            <button role=\"button\" class=\"settings icon hidden\" aria-label=\"{{ 'Widget settings'|t('app') }}\" aria-describedby=\"{{ widgetHeadingId }}\" data-settings-toggle></button>
                            <div class=\"body\">
                                {{ widget.bodyHtml|raw }}
                            </div>
                        </div>
                    </div>
                    <div class=\"back hidden\">
                        <form class=\"pane\">
                            {{ hiddenInput('widgetId', widget.id) }}
                            <h2 class=\"first\">{{ \"{type} Settings\"|t('app', { type: widget.name }) }}</h2>
                            <div class=\"settings\"></div>
                            <hr>
                            <div class=\"buttons clearafter\">
                                {{ forms.submitButton({label: 'Save'|t('app'), spinner: true}) }}
                                {{ forms.button({label: 'Cancel'|t('app'), class: 'cancel-btn'}) }}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        {% endfor %}
    </div>
{% endblock %}
", 'dashboard/_index.twig', '/tmp/packages/craft5/src/templates/dashboard/_index.twig');
    }
}
