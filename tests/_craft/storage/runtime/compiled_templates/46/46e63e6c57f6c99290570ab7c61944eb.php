<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/general/_index.twig */
class __TwigTemplate_1f6eeca970d601a421e051ba6b0bf94a extends Template
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
            'content' => $this->block_content(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/general/_index.twig');
        // line 2
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', 'settings/general/_index.twig', 2)->unwrap();
        // line 3
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('General Settings', 'app');
        // line 4
        $context['fullPageForm'] = true;
        // line 6
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 10
        $context['formActions'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Save and continue editing', 'app'), 'redirect' => $this->env->getFilter('hash')->getCallable()('settings/general'), 'shortcut' => true, 'retainScroll' => true]];
        // line 20
        $context['system'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['name' => null, 'live' => false, 'retryDuration' => null, 'timeZone' => 'UTC'],         // line 25
            (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                throw new RuntimeError('Variable "system" does not exist.', 25, $this->source);
            })()));
        // line 34
        $macros['__internal_parse_0'] = $this->macros['__internal_parse_0'] = $this;
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/general/_index.twig', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/general/_index.twig');
    }

    // line 37
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 38
        yield '    ';
        yield craft\helpers\Html::actionInput('system-settings/save-general-settings');
        yield '
    ';
        // line 39
        yield craft\helpers\Html::redirectInput('settings');
        yield '

    ';
        // line 41
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('System Name', 'app'), 'id' => 'name', 'suggestEnvVars' => true, 'name' => 'name', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 47
            (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                throw new RuntimeError('Variable "system" does not exist.', 47, $this->source);
            })()), 'name', [], 'any', false, false, false, 47)]], 41, $context, $this->getSourceContext());
        // line 48
        yield '

    ';
        // line 50
        yield CoreExtension::callMacro($macros['forms'], 'macro_booleanMenuField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('System Status', 'app'), 'warning' => ((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 52
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 52, $this->source);
            })()), 'app', [], 'any', false, false, false, 52), 'config', [], 'any', false, false, false, 52), 'general', [], 'any', false, false, false, 52), 'isSystemLive', [], 'any', false, false, false, 52) === true) || (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 52, $this->source);
            })()), 'app', [], 'any', false, false, false, 52), 'config', [], 'any', false, false, false, 52), 'general', [], 'any', false, false, false, 52), 'isSystemLive', [], 'any', false, false, false, 52) === false))) ? (CoreExtension::callMacro($macros['__internal_parse_0'], 'macro_configWarning', ['isSystemLive'], 52, $context, $this->getSourceContext())) : ('')), 'id' => 'live', 'name' => 'live', 'yesLabel' => $this->extensions['craft\web\twig\Extension']->translateFilter('Online', 'app'), 'noLabel' => $this->extensions['craft\web\twig\Extension']->translateFilter('Offline', 'app'), 'includeEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 58
                (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                    throw new RuntimeError('Variable "system" does not exist.', 58, $this->source);
                })()), 'live', [], 'any', false, false, false, 58)]], 50, $context, $this->getSourceContext());
        // line 59
        yield '

    ';
        // line 61
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Retry Duration', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The number of seconds that the `Retry-After` HTTP header should be set to for 503 responses when the system is offline.', 'app'), 'id' => 'retry-duration', 'name' => 'retryDuration', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 66
            (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                throw new RuntimeError('Variable "system" does not exist.', 66, $this->source);
            })()), 'retryDuration', [], 'any', false, false, false, 66), 'inputmode' => 'numeric', 'size' => 4]], 61, $context, $this->getSourceContext());
        // line 69
        yield '

    ';
        // line 71
        yield CoreExtension::callMacro($macros['forms'], 'macro_timeZoneField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Time Zone', 'app'), 'warning' => ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 73
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 73, $this->source);
            })()), 'app', [], 'any', false, false, false, 73), 'config', [], 'any', false, false, false, 73), 'general', [], 'any', false, false, false, 73), 'timezone', [], 'any', false, false, false, 73)) ? (CoreExtension::callMacro($macros['__internal_parse_0'], 'macro_configWarning', ['timezone'], 73, $context, $this->getSourceContext())) : ('')), 'id' => 'time-zone', 'name' => 'timeZone', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 76
                (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                    throw new RuntimeError('Variable "system" does not exist.', 76, $this->source);
                })()), 'timeZone', [], 'any', false, false, false, 76), 'includeEnvVars' => true]], 71, $context, $this->getSourceContext());
        // line 78
        yield '

    ';
        // line 80
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 80, $this->source);
        })()) >= (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 80, $this->source);
        })()))) {
            // line 81
            yield '        <hr>

        ';
            // line 83
            craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                throw new RuntimeError('Variable "view" does not exist.', 83, $this->source);
            })()), 'registerTranslations', ['app', ['Are you sure you want to delete the logo?']], 'method', false, false, false, 83);
            // line 86
            yield '
        ';
            // line 87
            craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                throw new RuntimeError('Variable "view" does not exist.', 87, $this->source);
            })()), 'registerAssetBundle', ['craft\\web\\assets\\fileupload\\FileUploadAsset'], 'method', false, false, false, 87);
            // line 88
            yield '
        ';
            // line 89
            yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Login Page Logo', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('SVG file recommended. The logo will be displayed at {size} wide.', 'app', ['size' => '300px'])], Twig\Extension\CoreExtension::include($this->env, $context, 'settings/general/_images/logo.twig')], 89, $context, $this->getSourceContext());
            // line 92
            yield '

        ';
            // line 94
            yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Site Icon', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Square SVG file recommended. The logo will be displayed at {size} by {size}.', 'app', ['size' => '32px'])], Twig\Extension\CoreExtension::include($this->env, $context, 'settings/general/_images/icon.twig')], 94, $context, $this->getSourceContext());
            // line 97
            yield '

        <div class="clear"></div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    // line 28
    public function macro_configWarning($__setting__ = null, $__file__ = null, ...$__varargs__)
    {
        $context = [
            'setting' => $__setting__,
            'file' => $__file__,
            'varargs' => $__varargs__,
        ] + $this->env->getGlobals();

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            craft\helpers\Template::beginProfile('macro', 'configWarning');
            // line 29
            yield $this->extensions['craft\web\twig\Extension']->translateFilter('This is being overridden by the {setting} config setting.', 'app', ['setting' => (((('<a href="https://craftcms.com/docs/5.x/reference/config/general.html#'.Twig\Extension\CoreExtension::lower($this->env->getCharset(),             // line 30
                (isset($context['setting']) || array_key_exists('setting', $context) ? $context['setting'] : (function () {
                    throw new RuntimeError('Variable "setting" does not exist.', 30, $this->source);
                })()))).'" rel="noopener" target="_blank">').(isset($context['setting']) || array_key_exists('setting', $context) ? $context['setting'] : (function () {
                    throw new RuntimeError('Variable "setting" does not exist.', 30, $this->source);
                })())).'</a>')]);
            craft\helpers\Template::endProfile('macro', 'configWarning');
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/general/_index.twig';
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
        return [166 => 30,  165 => 29,  151 => 28,  141 => 97,  139 => 94,  135 => 92,  133 => 89,  130 => 88,  128 => 87,  125 => 86,  123 => 83,  119 => 81,  117 => 80,  113 => 78,  111 => 76,  110 => 73,  109 => 71,  105 => 69,  103 => 66,  102 => 61,  98 => 59,  96 => 58,  95 => 52,  94 => 50,  90 => 48,  88 => 47,  87 => 41,  82 => 39,  77 => 38,  69 => 37,  63 => 1,  61 => 34,  59 => 25,  58 => 20,  56 => 10,  54 => 6,  52 => 4,  50 => 3,  48 => 2,  40 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \"_layouts/cp\" %}
{% import \"_includes/forms\" as forms %}
{% set title = \"General Settings\"|t('app') %}
{% set fullPageForm = true %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set formActions = [
    {
        label: 'Save and continue editing'|t('app'),
        redirect: 'settings/general'|hash,
        shortcut: true,
        retainScroll: true,
    },
] %}

{# set defaults #}
{% set system = {
    name: null,
    live: false,
    retryDuration: null,
    timeZone: 'UTC',
}|merge(system) %}


{% macro configWarning(setting, file) -%}
    {{ \"This is being overridden by the {setting} config setting.\"|t('app', {
        setting: '<a href=\"https://craftcms.com/docs/5.x/reference/config/general.html#'~setting|lower~'\" rel=\"noopener\" target=\"_blank\">'~setting~'</a>'
    })|raw }}
{%- endmacro %}

{% from _self import configWarning %}


{% block content %}
    {{ actionInput('system-settings/save-general-settings') }}
    {{ redirectInput('settings') }}

    {{ forms.autosuggestField({
        first: true,
        label: \"System Name\"|t('app'),
        id: 'name',
        suggestEnvVars: true,
        name: 'name',
        value: system.name
    }) }}

    {{ forms.booleanMenuField({
        label: \"System Status\"|t('app'),
        warning: (craft.app.config.general.isSystemLive is same as(true) or craft.app.config.general.isSystemLive is same as(false) ? configWarning('isSystemLive')),
        id: 'live',
        name: 'live',
        yesLabel: 'Online'|t('app'),
        noLabel: 'Offline'|t('app'),
        includeEnvVars: true,
        value: system.live
    }) }}

    {{ forms.textField({
        label: 'Retry Duration'|t('app'),
        instructions: 'The number of seconds that the `Retry-After` HTTP header should be set to for 503 responses when the system is offline.'|t('app'),
        id: 'retry-duration',
        name: 'retryDuration',
        value: system.retryDuration,
        inputmode: 'numeric',
        size: 4,
    }) }}

    {{ forms.timeZoneField({
        label: \"Time Zone\"|t('app'),
        warning: (craft.app.config.general.timezone ? configWarning('timezone')),
        id: 'time-zone',
        name: 'timeZone',
        value: system.timeZone,
        includeEnvVars: true,
    }) }}

    {% if CraftEdition >= CraftPro %}
        <hr>

        {% do view.registerTranslations('app', [
            \"Are you sure you want to delete the logo?\",
        ]) %}

        {% do view.registerAssetBundle(\"craft\\\\web\\\\assets\\\\fileupload\\\\FileUploadAsset\") %}

        {{ forms.field({
            label: \"Login Page Logo\"|t('app'),
            instructions: \"SVG file recommended. The logo will be displayed at {size} wide.\"|t('app', { size: '300px' })
        }, include('settings/general/_images/logo.twig')) }}

        {{ forms.field({
            label: \"Site Icon\"|t('app'),
            instructions: \"Square SVG file recommended. The logo will be displayed at {size} by {size}.\"|t('app', { size: '32px' })
        }, include('settings/general/_images/icon.twig')) }}

        <div class=\"clear\"></div>
    {% endif %}
{% endblock %}
", 'settings/general/_index.twig', '/tmp/packages/craft5/src/templates/settings/general/_index.twig');
    }
}
