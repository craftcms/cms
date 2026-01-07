<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* dashboard/_index.twig */
class __TwigTemplate_0ecb1d822c4ace9e7a2805f520c0e5ad extends Template
{
    private $source;

    private $macros = [];

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
    protected function doGetParent(array $context)
    {
        // line 1
        return '_layouts/cp';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', 'dashboard/_index.twig');
        // line 2
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Dashboard', 'app');
        // line 3
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', 'dashboard/_index.twig', 3)->unwrap();
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'dashboard/_index.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'dashboard/_index.twig');
    }

    // line 5
    public function block_actionButton($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 6
        echo '    <div class="buttons">
        <div class="newwidget btngroup">
            <button type="button" id="newwidgetmenubtn" class="btn menubtn add icon" aria-controls="new-widget-menu" data-disclosure-trigger>';
        // line 8
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('New widget', 'app'), 'html', null, true);
        echo '</button>
            <div id="new-widget-menu" class="menu menu--disclosure newwidgetmenu" data-disclosure-menu>
                <ul>
                    ';
        // line 11
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, (isset($context['widgetTypes']) || array_key_exists('widgetTypes', $context) ? $context['widgetTypes'] : (function () {
            throw new RuntimeError('Variable "widgetTypes" does not exist.', 11, $this->source);
        })()), function ($__t__) use ($context) {
            $context['t'] = $__t__;

            return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['t']) || array_key_exists('t', $context) ? $context['t'] : (function () {
                throw new RuntimeError('Variable "t" does not exist.', 11, $this->source);
            })()), 'selectable', []);
        }));
        foreach ($context['_seq'] as $context['type'] => $context['info']) {
            // line 12
            echo '                        <li>
                            <a role="button" type="button" class="menu-item" tabindex="0" data-type="';
            // line 13
            echo twig_escape_filter($this->env, $context['type'], 'html', null, true);
            echo '" data-name="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'name', []), 'html', null, true);
            echo '">
                                <span class="icon" aria-hidden="true">';
            // line 14
            echo $this->extensions['craft\web\twig\Extension']->svgFunction(craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'iconSvg', []), false);
            echo '</span>
                                ';
            // line 15
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'name', []), 'html', null, true);
            echo '
                            </a>
                        </li>
                    ';
        }
        unset($context['_seq'], $context['_iterated'], $context['type'], $context['info'], $context['_parent'], $context['loop']);
        // line 19
        echo '                </ul>
            </div>
        </div>

        <button type="button" id="widgetManagerBtn" class="btn settings icon" title="';
        // line 23
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'html', null, true);
        echo '" aria-label="';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'html', null, true);
        echo '" aria-expanded="false"></button>
    </div>
';
        craft\helpers\Template::endProfile('block', 'actionButton');
    }

    // line 28
    public function block_main($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'main');
        // line 29
        echo '    <div id="dashboard-grid" class="grid">
        ';
        // line 30
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['widgets']) || array_key_exists('widgets', $context) ? $context['widgets'] : (function () {
            throw new RuntimeError('Variable "widgets" does not exist.', 30, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['widget']) {
            // line 31
            echo '            ';
            $context['widgetHeadingId'] = ('widget-heading-'.craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', []));
            // line 32
            echo '            <div class="item" data-colspan="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'colspan', []), 'html', null, true);
            echo '">
                <div id="widget';
            // line 33
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', []), 'html', null, true);
            echo '" class="widget ';
            echo twig_escape_filter($this->env, twig_lower_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'type', [])), 'html', null, true);
            echo '" data-id="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', []), 'html', null, true);
            echo '" data-type="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'type', []), 'html', null, true);
            echo '" data-title="';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', []), 'html', null, true);
            echo '">
                    <div class="front">
                        <div class="pane">
                            <div class="spinner body-loading"></div>
                            ';
            // line 37
            if ((craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', []) || craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'subtitle', []))) {
                // line 38
                echo '                                <div id="';
                echo twig_escape_filter($this->env, (isset($context['widgetHeadingId']) || array_key_exists('widgetHeadingId', $context) ? $context['widgetHeadingId'] : (function () {
                    throw new RuntimeError('Variable "widgetHeadingId" does not exist.', 38, $this->source);
                })()), 'html', null, true);
                echo '" class="widget-heading">
                                    ';
                // line 39
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', [])) {
                    // line 40
                    echo '                                        <h2>';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'title', []), 'html', null, true);
                    echo '</h2>
                                    ';
                }
                // line 42
                echo '                                    ';
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'subtitle', [])) {
                    // line 43
                    echo '                                        <h5>';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'subtitle', []), 'html', null, true);
                    echo '</h5>
                                    ';
                }
                // line 45
                echo '                                </div>
                            ';
            }
            // line 47
            echo '                            <button role="button" class="settings icon hidden" aria-label="';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Widget settings', 'app'), 'html', null, true);
            echo '" aria-describedby="';
            echo twig_escape_filter($this->env, (isset($context['widgetHeadingId']) || array_key_exists('widgetHeadingId', $context) ? $context['widgetHeadingId'] : (function () {
                throw new RuntimeError('Variable "widgetHeadingId" does not exist.', 47, $this->source);
            })()), 'html', null, true);
            echo '" data-settings-toggle></button>
                            <div class="body">
                                ';
            // line 49
            echo craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'bodyHtml', []);
            echo '
                            </div>
                        </div>
                    </div>
                    <div class="back hidden">
                        <form class="pane">
                            ';
            // line 55
            echo craft\helpers\Html::hiddenInput('widgetId', craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'id', []));
            echo '
                            <h2 class="first">';
            // line 56
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('{type} Settings', 'app', ['type' => craft\helpers\Template::attribute($this->env, $this->source, $context['widget'], 'name', [])]), 'html', null, true);
            echo '</h2>
                            <div class="settings"></div>
                            <hr>
                            <div class="buttons clearafter">
                                ';
            // line 60
            echo twig_call_macro($macros['forms'], 'macro_submitButton', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Save', 'app'), 'spinner' => true]], 60, $context, $this->getSourceContext());
            echo '
                                ';
            // line 61
            echo twig_call_macro($macros['forms'], 'macro_button', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Cancel', 'app'), 'class' => 'cancel-btn']], 61, $context, $this->getSourceContext());
            echo '
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['widget'], $context['_parent'], $context['loop']);
        // line 68
        echo '    </div>
';
        craft\helpers\Template::endProfile('block', 'main');
    }

    public function getTemplateName()
    {
        return 'dashboard/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [220 => 68,  207 => 61,  203 => 60,  196 => 56,  192 => 55,  183 => 49,  175 => 47,  171 => 45,  165 => 43,  162 => 42,  156 => 40,  154 => 39,  149 => 38,  147 => 37,  132 => 33,  127 => 32,  124 => 31,  120 => 30,  117 => 29,  112 => 28,  102 => 23,  96 => 19,  86 => 15,  82 => 14,  76 => 13,  73 => 12,  69 => 11,  63 => 8,  59 => 6,  54 => 5,  48 => 1,  46 => 3,  44 => 2,  36 => 1];
    }

    public function getSourceContext()
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
", 'dashboard/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/dashboard/_index.twig');
    }
}
