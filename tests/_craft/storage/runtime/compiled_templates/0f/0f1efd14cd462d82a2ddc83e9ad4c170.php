<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* settings/email/_index.twig */
class __TwigTemplate_6cc30a0f74d19e22b97b9b46f4bd0d28 extends Template
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
        // line 3
        return '_layouts/cp.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', 'settings/email/_index.twig');
        // line 1
        Craft::$app->controller->requireAdmin();
        // line 5
        $context['title'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Email Settings', 'app');
        // line 6
        $context['fullPageForm'] = true;
        // line 8
        $context['crumbs'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 12
        $context['formActions'] = [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Save and continue editing', 'app'), 'redirect' => $this->env->getFilter('hash')->getCallable()('settings/email'), 'shortcut' => true, 'retainScroll' => true]];
        // line 21
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms.twig', 'settings/email/_index.twig', 21)->unwrap();
        // line 23
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 23, $this->source);
        })()), 'registerTranslations', [0 => 'app', 1 => [0 => 'Email sent successfully! Check your inbox.']], 'method');
        // line 28
        if (! array_key_exists('settings', $context)) {
            // line 29
            $context['settings'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 29, $this->source);
            })()), 'app', []), 'projectConfig', []), 'get', [0 => 'email'], 'method');
            // line 30
            $context['freshSettings'] = true;
        } else {
            // line 32
            $context['freshSettings'] = false;
        }
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp.twig', 'settings/email/_index.twig', 3);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/email/_index.twig');
    }

    // line 36
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 37
        echo '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['customMailerFiles']) || array_key_exists('customMailerFiles', $context) ? $context['customMailerFiles'] : (function () {
            throw new RuntimeError('Variable "customMailerFiles" does not exist.', 37, $this->source);
        })()))) {
            // line 38
            echo '        <div class="readable">
            <blockquote class="note warning">
                <p>
                    ';
            // line 41
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('It looks like these settings are being overridden by {paths}.', 'app', ['paths' => twig_join_filter(            // line 42
                (isset($context['customMailerFiles']) || array_key_exists('customMailerFiles', $context) ? $context['customMailerFiles'] : (function () {
                    throw new RuntimeError('Variable "customMailerFiles" does not exist.', 42, $this->source);
                })()), ', ')]), 'html', null, true);
            // line 43
            echo '
                </p>
            </blockquote>
        </div>
        <hr>
    ';
        }
        // line 49
        echo '
    ';
        // line 50
        echo craft\helpers\Html::actionInput('system-settings/save-email-settings');
        echo '
    ';
        // line 51
        echo craft\helpers\Html::redirectInput('settings');
        echo '

    ';
        // line 53
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('System Email Address', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The email address Craft CMS will use when sending email.', 'app'), 'id' => 'fromEmail', 'name' => 'fromEmail', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 60
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 60, $this->source);
            })()), 'fromEmail', []), 'autofocus' => true, 'required' => true, 'errors' => ((        // line 63
                (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                    throw new RuntimeError('Variable "freshSettings" does not exist.', 63, $this->source);
                })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 63, $this->source);
                })()), 'getErrors', [0 => 'fromEmail'], 'method')))]], 53, $context, $this->getSourceContext());
        // line 64
        echo '

    ';
        // line 66
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Reply-To Address', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The Reply-To email address Craft CMS should use when sending email.', 'app'), 'id' => 'replyToEmail', 'name' => 'replyToEmail', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 73
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 73, $this->source);
            })()), 'replyToEmail', []), 'errors' => ((        // line 74
                (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                    throw new RuntimeError('Variable "freshSettings" does not exist.', 74, $this->source);
                })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 74, $this->source);
                })()), 'getErrors', [0 => 'replyToEmail'], 'method')))]], 66, $context, $this->getSourceContext());
        // line 75
        echo '

    ';
        // line 77
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Sender Name', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The “From” name Craft CMS will use when sending email.', 'app'), 'id' => 'fromName', 'name' => 'fromName', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 83
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 83, $this->source);
            })()), 'fromName', []), 'required' => true, 'errors' => ((        // line 85
                (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                    throw new RuntimeError('Variable "freshSettings" does not exist.', 85, $this->source);
                })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 85, $this->source);
                })()), 'getErrors', [0 => 'fromName'], 'method')))]], 77, $context, $this->getSourceContext());
        // line 86
        echo '

    ';
        // line 88
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 88, $this->source);
        })()) == (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 88, $this->source);
        })()))) {
            // line 89
            echo '        ';
            echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('HTML Email Template', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The template Craft CMS will use for HTML emails', 'app'), 'id' => 'template', 'name' => 'template', 'suggestions' => craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 94
                (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 94, $this->source);
                })()), 'cp', []), 'getTemplateSuggestions', [], 'method'), 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,             // line 96
                    (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                        throw new RuntimeError('Variable "settings" does not exist.', 96, $this->source);
                    })()), 'template', []), 'errors' => ((            // line 97
                        (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                            throw new RuntimeError('Variable "freshSettings" does not exist.', 97, $this->source);
                        })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                            throw new RuntimeError('Variable "settings" does not exist.', 97, $this->source);
                        })()), 'getErrors', [0 => 'template'], 'method')))]], 89, $context, $this->getSourceContext());
            // line 98
            echo '
    ';
        }
        // line 100
        echo '
    <hr>

    ';
        // line 103
        echo twig_call_macro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Transport Type', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('How should Craft CMS send the emails?', 'app'), 'id' => 'transportType', 'name' => 'transportType', 'options' =>         // line 108
(isset($context['transportTypeOptions']) || array_key_exists('transportTypeOptions', $context) ? $context['transportTypeOptions'] : (function () {
    throw new RuntimeError('Variable "transportTypeOptions" does not exist.', 108, $this->source);
})()), 'value' => (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
    throw new RuntimeError('Variable "adapter" does not exist.', 109, $this->source);
})())::class, 'errors' => (((craft\helpers\Template::attribute($this->env, $this->source,         // line 110
    ($context['adapter'] ?? null), 'getErrors', [0 => 'type'], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['adapter'] ?? null), 'getErrors', [0 => 'type'], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['adapter'] ?? null), 'getErrors', [0 => 'type'], 'method')) : (null)), 'toggle' => true, ]], 103, $context, $this->getSourceContext());
        // line 112
        echo '


    ';
        // line 115
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['allTransportAdapters']) || array_key_exists('allTransportAdapters', $context) ? $context['allTransportAdapters'] : (function () {
            throw new RuntimeError('Variable "allTransportAdapters" does not exist.', 115, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['_adapter']) {
            // line 116
            echo '        ';
            $context['isCurrent'] = ($context['_adapter']::class == (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 116, $this->source);
            })())::class);
            // line 117
            echo '        <div id="';
            echo twig_escape_filter($this->env, craft\helpers\Html::id($context['_adapter']::class), 'html', null, true);
            echo '"';
            if (! (isset($context['isCurrent']) || array_key_exists('isCurrent', $context) ? $context['isCurrent'] : (function () {
                throw new RuntimeError('Variable "isCurrent" does not exist.', 117, $this->source);
            })())) {
                echo ' class="hidden"';
            }
            echo '>
            ';
            // line 118
            $_namespace = (('transportTypes['.craft\helpers\Html::id($context['_adapter']::class)).']');
            if ($_namespace !== '') {
                $_originalNamespace = Craft::$app->getView()->getNamespace();
                Craft::$app->getView()->setNamespace(Craft::$app->getView()->namespaceInputName($_namespace));
                ob_start();
                try {
                    // line 119
                    echo '                ';
                    echo craft\helpers\Template::attribute($this->env, $this->source, (((isset($context['isCurrent']) || array_key_exists('isCurrent', $context) ? $context['isCurrent'] : (function () {
                        throw new RuntimeError('Variable "isCurrent" does not exist.', 119, $this->source);
                    })())) ? ((isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                        throw new RuntimeError('Variable "adapter" does not exist.', 119, $this->source);
                    })())) : ($context['_adapter'])), 'getSettingsHtml', [], 'method');
                    echo '
            ';
                } catch (Exception $e) {
                    ob_end_clean();

                    throw $e;
                }
                echo craft\helpers\Html::namespaceHtml(ob_get_clean(), $_namespace, false);
                Craft::$app->getView()->setNamespace($_originalNamespace);
            } else {
                echo '                ';
                echo craft\helpers\Template::attribute($this->env, $this->source, (((isset($context['isCurrent']) || array_key_exists('isCurrent', $context) ? $context['isCurrent'] : (function () {
                    throw new RuntimeError('Variable "isCurrent" does not exist.', 119, $this->source);
                })())) ? ((isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 119, $this->source);
                })())) : ($context['_adapter'])), 'getSettingsHtml', [], 'method');
                echo '
            ';
            }
            unset($_originalNamespace, $_namespace);
            // line 121
            echo '        </div>
    ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['_adapter'], $context['_parent'], $context['loop']);
        // line 123
        echo '
    <hr>

    <div class="buttons">
        <button type="button" id="test" class="btn formsubmit" data-action="system-settings/test-email-settings">';
        // line 127
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Test', 'app'), 'html', null, true);
        echo '</button>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    public function getTemplateName()
    {
        return 'settings/email/_index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [215 => 127,  209 => 123,  202 => 121,  183 => 119,  176 => 118,  167 => 117,  164 => 116,  160 => 115,  155 => 112,  153 => 110,  152 => 109,  151 => 108,  150 => 103,  145 => 100,  141 => 98,  139 => 97,  138 => 96,  137 => 94,  135 => 89,  133 => 88,  129 => 86,  127 => 85,  126 => 83,  125 => 77,  121 => 75,  119 => 74,  118 => 73,  117 => 66,  113 => 64,  111 => 63,  110 => 60,  109 => 53,  104 => 51,  100 => 50,  97 => 49,  89 => 43,  87 => 42,  86 => 41,  81 => 38,  78 => 37,  73 => 36,  67 => 3,  64 => 32,  61 => 30,  59 => 29,  57 => 28,  55 => 23,  53 => 21,  51 => 12,  49 => 8,  47 => 6,  45 => 5,  43 => 1,  35 => 3];
    }

    public function getSourceContext()
    {
        return new Source("{% requireAdmin %}

{% extends '_layouts/cp.twig' %}

{% set title = 'Email Settings'|t('app') %}
{% set fullPageForm = true %}

{% set crumbs = [
    { label: \"Settings\"|t('app'), url: url('settings') }
] %}

{% set formActions = [
    {
        label: 'Save and continue editing'|t('app'),
        redirect: 'settings/email'|hash,
        shortcut: true,
        retainScroll: true,
    },
] %}

{% import '_includes/forms.twig' as forms %}

{% do view.registerTranslations('app', [
    \"Email sent successfully! Check your inbox.\",
]) %}


{% if settings is not defined %}
    {% set settings = craft.app.projectConfig.get('email') %}
    {% set freshSettings = true %}
{% else %}
    {% set freshSettings = false %}
{% endif %}


{% block content %}
    {% if customMailerFiles|length %}
        <div class=\"readable\">
            <blockquote class=\"note warning\">
                <p>
                    {{ 'It looks like these settings are being overridden by {paths}.'|t('app', {
                        paths: customMailerFiles|join(', ')
                    }) }}
                </p>
            </blockquote>
        </div>
        <hr>
    {% endif %}

    {{ actionInput('system-settings/save-email-settings') }}
    {{ redirectInput('settings') }}

    {{ forms.autosuggestField({
        first: true,
        label: \"System Email Address\"|t('app'),
        instructions: \"The email address Craft CMS will use when sending email.\"|t('app'),
        id: 'fromEmail',
        name: 'fromEmail',
        suggestEnvVars: true,
        value: settings.fromEmail,
        autofocus: true,
        required: true,
        errors: (freshSettings ? null : settings.getErrors('fromEmail'))
    }) }}

    {{ forms.autosuggestField({
        first: true,
        label: 'Reply-To Address'|t('app'),
        instructions: 'The Reply-To email address Craft CMS should use when sending email.'|t('app'),
        id: 'replyToEmail',
        name: 'replyToEmail',
        suggestEnvVars: true,
        value: settings.replyToEmail,
        errors: (freshSettings ? null : settings.getErrors('replyToEmail'))
    }) }}

    {{ forms.autosuggestField({
        label: \"Sender Name\"|t('app'),
        instructions: \"The “From” name Craft CMS will use when sending email.\"|t('app'),
        id: 'fromName',
        name: 'fromName',
        suggestEnvVars: true,
        value: settings.fromName,
        required: true,
        errors: (freshSettings ? null : settings.getErrors('fromName'))
    }) }}

    {% if CraftEdition == CraftPro %}
        {{ forms.autosuggestField({
            label: \"HTML Email Template\"|t('app'),
            instructions: \"The template Craft CMS will use for HTML emails\"|t('app'),
            id: 'template',
            name: 'template',
            suggestions: craft.cp.getTemplateSuggestions(),
            suggestEnvVars: true,
            value: settings.template,
            errors: (freshSettings ? null : settings.getErrors('template'))
        }) }}
    {% endif %}

    <hr>

    {{ forms.selectField({
        label: \"Transport Type\"|t('app'),
        instructions: \"How should Craft CMS send the emails?\"|t('app'),
        id: 'transportType',
        name: 'transportType',
        options: transportTypeOptions,
        value: className(adapter),
        errors: adapter.getErrors('type') ?? null,
        toggle: true
    }) }}


    {% for _adapter in allTransportAdapters %}
        {% set isCurrent = (className(_adapter) == className(adapter)) %}
        <div id=\"{{ className(_adapter)|id }}\"{% if not isCurrent %} class=\"hidden\"{% endif %}>
            {% namespace 'transportTypes['~className(_adapter)|id~']' %}
                {{ (isCurrent ? adapter : _adapter).getSettingsHtml()|raw }}
            {% endnamespace %}
        </div>
    {% endfor %}

    <hr>

    <div class=\"buttons\">
        <button type=\"button\" id=\"test\" class=\"btn formsubmit\" data-action=\"system-settings/test-email-settings\">{{ \"Test\"|t('app') }}</button>
    </div>
{% endblock %}
", 'settings/email/_index.twig', '/Users/brianhanson/Development/craft5/src/templates/settings/email/_index.twig');
    }
}
