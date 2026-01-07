<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* settings/general/_index.twig */
class __TwigTemplate_22348a2785c49b8d0c6065f38185141b extends Template
{
    private $source;

    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => $this->block_content(...),
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
        craft\helpers\Template::beginProfile('template', 'settings/general/_index.twig');
        // line 2
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', 'settings/general/_index.twig', 2)->unwrap();
        // line 3
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('General Settings', 'app');
        // line 4
        $context['fullPageForm'] = true;
        // line 6
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 10
        $context['formActions'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Save and continue editing', 'app'), 'redirect' => $this->env->getFilter('hash')->getCallable()('settings/general'), 'shortcut' => true, 'retainScroll' => true]];
        // line 20
        $context['system'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['name' => null, 'live' => false, 'retryDuration' => null, 'timeZone' => 'UTC'],         // line 25
            (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                throw new RuntimeError('Variable "system" does not exist.', 25, $this->source);
            })()));
        // line 34
        $macros['__internal_parse_0'] = $this->macros['__internal_parse_0'] = $this;
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp', 'settings/general/_index.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/general/_index.twig');
    }

    // line 37
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 38
        echo '    ';
        echo craft\helpers\Html::actionInput('system-settings/save-general-settings');
        echo '
    ';
        // line 39
        echo craft\helpers\Html::redirectInput('settings');
        echo '

    ';
        // line 41
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('System Name', 'app'), 'id' => 'name', 'suggestEnvVars' => true, 'name' => 'name', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 47
            (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                throw new RuntimeError('Variable "system" does not exist.', 47, $this->source);
            })()), 'name', [])]], 41, $context, $this->getSourceContext());
        // line 48
        echo '

    ';
        // line 50
        echo twig_call_macro($macros['forms'], 'macro_booleanMenuField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('System Status', 'app'), 'warning' => ((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 52
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 52, $this->source);
            })()), 'app', []), 'config', []), 'general', []), 'isSystemLive', []) === true) || (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 52, $this->source);
            })()), 'app', []), 'config', []), 'general', []), 'isSystemLive', []) === false))) ? (twig_call_macro($macros['__internal_parse_0'], 'macro_configWarning', ['isSystemLive'], 52, $context, $this->getSourceContext())) : ('')), 'id' => 'live', 'name' => 'live', 'yesLabel' => $this->extensions['craft\web\twig\Extension']->translateFilter('Online', 'app'), 'noLabel' => $this->extensions['craft\web\twig\Extension']->translateFilter('Offline', 'app'), 'includeEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 58
                (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                    throw new RuntimeError('Variable "system" does not exist.', 58, $this->source);
                })()), 'live', [])]], 50, $context, $this->getSourceContext());
        // line 59
        echo '

    ';
        // line 61
        echo twig_call_macro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Retry Duration', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The number of seconds that the `Retry-After` HTTP header should be set to for 503 responses when the system is offline.', 'app'), 'id' => 'retry-duration', 'name' => 'retryDuration', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 66
            (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                throw new RuntimeError('Variable "system" does not exist.', 66, $this->source);
            })()), 'retryDuration', []), 'inputmode' => 'numeric', 'size' => 4]], 61, $context, $this->getSourceContext());
        // line 69
        echo '

    ';
        // line 71
        echo twig_call_macro($macros['forms'], 'macro_timeZoneField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Time Zone', 'app'), 'warning' => ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 73
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 73, $this->source);
            })()), 'app', []), 'config', []), 'general', []), 'timezone', [])) ? (twig_call_macro($macros['__internal_parse_0'], 'macro_configWarning', ['timezone'], 73, $context, $this->getSourceContext())) : ('')), 'id' => 'time-zone', 'name' => 'timeZone', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 76
                (isset($context['system']) || array_key_exists('system', $context) ? $context['system'] : (function () {
                    throw new RuntimeError('Variable "system" does not exist.', 76, $this->source);
                })()), 'timeZone', []), 'includeEnvVars' => true]], 71, $context, $this->getSourceContext());
        // line 78
        echo '

    ';
        // line 80
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 80, $this->source);
        })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 80, $this->source);
        })()))) {
            // line 81
            echo '        <hr>

        ';
            // line 83
            craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                throw new RuntimeError('Variable "view" does not exist.', 83, $this->source);
            })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Are you sure you want to delete the logo?']], 'method');
            // line 86
            echo '
        ';
            // line 87
            craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
                throw new RuntimeError('Variable "view" does not exist.', 87, $this->source);
            })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\fileupload\\FileUploadAsset'], 'method');
            // line 88
            echo '
        ';
            // line 89
            echo twig_call_macro($macros['forms'], 'macro_field', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Login Page Logo', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('SVG file recommended. The logo will be displayed at {size} wide.', 'app', ['size' => '300px'])], twig_include($this->env, $context, 'settings/general/_images/logo.twig')], 89, $context, $this->getSourceContext());
            // line 92
            echo '

        ';
            // line 94
            echo twig_call_macro($macros['forms'], 'macro_field', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Site Icon', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Square SVG file recommended. The logo will be displayed at {size} by {size}.', 'app', ['size' => '32px'])], twig_include($this->env, $context, 'settings/general/_images/icon.twig')], 94, $context, $this->getSourceContext());
            // line 97
            echo '

        <div class="clear"></div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'content');
    }

    // line 28
    public function macro_configWarning($__setting__ = null, $__file__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'setting' => $__setting__,
            'file' => $__file__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'configWarning');
            // line 29
            echo $this->extensions['craft\web\twig\Extension']->translateFilter('This is being overridden by the {setting} config setting.', 'app', ['setting' => (((('<a href="https://craftcms.com/docs/4.x/config/config-settings.html#'.twig_lower_filter($this->env,             // line 30
                (isset($context['setting']) || array_key_exists('setting', $context) ? $context['setting'] : (function () {
                    throw new RuntimeError('Variable "setting" does not exist.', 30, $this->source);
                })()))).'" rel="noopener" target="_blank">').(isset($context['setting']) || array_key_exists('setting', $context) ? $context['setting'] : (function () {
                    throw new RuntimeError('Variable "setting" does not exist.', 30, $this->source);
                })())).'</a>')]);
            craft\helpers\Template::endProfile('macro', 'configWarning');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return 'settings/general/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [158 => 30,  157 => 29,  142 => 28,  133 => 97,  131 => 94,  127 => 92,  125 => 89,  122 => 88,  120 => 87,  117 => 86,  115 => 83,  111 => 81,  109 => 80,  105 => 78,  103 => 76,  102 => 73,  101 => 71,  97 => 69,  95 => 66,  94 => 61,  90 => 59,  88 => 58,  87 => 52,  86 => 50,  82 => 48,  80 => 47,  79 => 41,  74 => 39,  69 => 38,  64 => 37,  58 => 1,  56 => 34,  54 => 25,  53 => 20,  51 => 10,  49 => 6,  47 => 4,  45 => 3,  43 => 2,  35 => 1];
    }

    public function getSourceContext()
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
        setting: '<a href=\"https://craftcms.com/docs/4.x/config/config-settings.html#'~setting|lower~'\" rel=\"noopener\" target=\"_blank\">'~setting~'</a>'
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

    {% if CraftEdition == CraftPro %}
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
", 'settings/general/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/general/_index.twig');
    }
}
