<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* settings/email/_index.twig */
class __TwigTemplate_606c09d1a11da0bb3c58e4960f0ca1d2 extends Template
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
        // line 3
        return '_layouts/cp.twig';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
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
        $context['crumbs'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Settings', 'app'), 'url' => craft\helpers\UrlHelper::url('settings')]];
        // line 12
        $context['formActions'] = [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Save and continue editing', 'app'), 'redirect' => $this->env->getFilter('hash')->getCallable()('settings/email'), 'shortcut' => true, 'retainScroll' => true]];
        // line 21
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms.twig', 'settings/email/_index.twig', 21)->unwrap();
        // line 23
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 23, $this->source);
        })()), 'registerTranslations', ['app', ['Email sent successfully! Check your inbox.']], 'method', false, false, false, 23);
        // line 28
        if (! array_key_exists('settings', $context)) {
            // line 29
            $context['settings'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 29, $this->source);
            })()), 'app', [], 'any', false, false, false, 29), 'projectConfig', [], 'any', false, false, false, 29), 'get', ['email'], 'method', false, false, false, 29);
            // line 30
            $context['freshSettings'] = true;
        } else {
            // line 32
            $context['freshSettings'] = false;
        }
        // line 3
        $this->parent = $this->loadTemplate('_layouts/cp.twig', 'settings/email/_index.twig', 3);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'settings/email/_index.twig');
    }

    // line 36
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('block', 'content');
        // line 37
        yield '    ';
        if ($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['customMailerFiles']) || array_key_exists('customMailerFiles', $context) ? $context['customMailerFiles'] : (function () {
            throw new RuntimeError('Variable "customMailerFiles" does not exist.', 37, $this->source);
        })()))) {
            // line 38
            yield '        <div class="readable">
            <blockquote class="note warning">
                <p>
                    ';
            // line 41
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('It looks like these settings are being overridden by {paths}.', 'app', ['paths' => Twig\Extension\CoreExtension::join(            // line 42
                (isset($context['customMailerFiles']) || array_key_exists('customMailerFiles', $context) ? $context['customMailerFiles'] : (function () {
                    throw new RuntimeError('Variable "customMailerFiles" does not exist.', 42, $this->source);
                })()), ', ')]), 'html', null, true);
            // line 43
            yield '
                </p>
            </blockquote>
        </div>
        <hr>
    ';
        }
        // line 49
        yield '
    ';
        // line 50
        yield craft\helpers\Html::actionInput('system-settings/save-email-settings');
        yield '
    ';
        // line 51
        yield craft\helpers\Html::redirectInput('settings');
        yield '

    ';
        // line 53
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('System Email Address', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The email address Craft CMS will use when sending email.', 'app'), 'id' => 'fromEmail', 'name' => 'fromEmail', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 60
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 60, $this->source);
            })()), 'fromEmail', [], 'any', false, false, false, 60), 'autofocus' => true, 'required' => true, 'errors' => ((        // line 63
                (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                    throw new RuntimeError('Variable "freshSettings" does not exist.', 63, $this->source);
                })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 63, $this->source);
                })()), 'getErrors', ['fromEmail'], 'method', false, false, false, 63)))]], 53, $context, $this->getSourceContext());
        // line 64
        yield '

    ';
        // line 66
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['first' => true, 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Reply-To Address', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The Reply-To email address Craft CMS should use when sending email.', 'app'), 'id' => 'replyToEmail', 'name' => 'replyToEmail', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 73
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 73, $this->source);
            })()), 'replyToEmail', [], 'any', false, false, false, 73), 'errors' => ((        // line 74
                (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                    throw new RuntimeError('Variable "freshSettings" does not exist.', 74, $this->source);
                })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 74, $this->source);
                })()), 'getErrors', ['replyToEmail'], 'method', false, false, false, 74)))]], 66, $context, $this->getSourceContext());
        // line 75
        yield '

    ';
        // line 77
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Sender Name', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The “From” name Craft CMS will use when sending email.', 'app'), 'id' => 'fromName', 'name' => 'fromName', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 83
            (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                throw new RuntimeError('Variable "settings" does not exist.', 83, $this->source);
            })()), 'fromName', [], 'any', false, false, false, 83), 'required' => true, 'errors' => ((        // line 85
                (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                    throw new RuntimeError('Variable "freshSettings" does not exist.', 85, $this->source);
                })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                    throw new RuntimeError('Variable "settings" does not exist.', 85, $this->source);
                })()), 'getErrors', ['fromName'], 'method', false, false, false, 85)))]], 77, $context, $this->getSourceContext());
        // line 86
        yield '

    ';
        // line 88
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 88, $this->source);
        })()) >= (isset($context['CraftPro']) || array_key_exists('CraftPro', $context) ? $context['CraftPro'] : (function () {
            throw new RuntimeError('Variable "CraftPro" does not exist.', 88, $this->source);
        })()))) {
            // line 89
            yield '        ';
            yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('HTML Email Template', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The template Craft CMS will use for HTML emails', 'app'), 'id' => 'template', 'name' => 'template', 'suggestions' => craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,             // line 94
                (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 94, $this->source);
                })()), 'cp', [], 'any', false, false, false, 94), 'getTemplateSuggestions', [], 'method', false, false, false, 94), 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,             // line 96
                    (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                        throw new RuntimeError('Variable "settings" does not exist.', 96, $this->source);
                    })()), 'template', [], 'any', false, false, false, 96), 'errors' => ((            // line 97
                        (isset($context['freshSettings']) || array_key_exists('freshSettings', $context) ? $context['freshSettings'] : (function () {
                            throw new RuntimeError('Variable "freshSettings" does not exist.', 97, $this->source);
                        })())) ? (null) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['settings']) || array_key_exists('settings', $context) ? $context['settings'] : (function () {
                            throw new RuntimeError('Variable "settings" does not exist.', 97, $this->source);
                        })()), 'getErrors', ['template'], 'method', false, false, false, 97)))]], 89, $context, $this->getSourceContext());
            // line 98
            yield '
    ';
        }
        // line 100
        yield '
    <hr>

    ';
        // line 103
        yield CoreExtension::callMacro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Transport Type', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('How should Craft CMS send the emails?', 'app'), 'id' => 'transportType', 'name' => 'transportType', 'options' =>         // line 108
(isset($context['transportTypeOptions']) || array_key_exists('transportTypeOptions', $context) ? $context['transportTypeOptions'] : (function () {
    throw new RuntimeError('Variable "transportTypeOptions" does not exist.', 108, $this->source);
})()), 'value' => (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
    throw new RuntimeError('Variable "adapter" does not exist.', 109, $this->source);
})())::class, 'errors' => (((craft\helpers\Template::attribute($this->env, $this->source,         // line 110
    ($context['adapter'] ?? null), 'getErrors', ['type'], 'method', true, true, false, 110) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['adapter'] ?? null), 'getErrors', ['type'], 'method', false, false, false, 110) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['adapter'] ?? null), 'getErrors', ['type'], 'method', false, false, false, 110)) : (null)), 'toggle' => true]], 103, $context, $this->getSourceContext());
        // line 112
        yield '


    ';
        // line 115
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['allTransportAdapters']) || array_key_exists('allTransportAdapters', $context) ? $context['allTransportAdapters'] : (function () {
            throw new RuntimeError('Variable "allTransportAdapters" does not exist.', 115, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['_adapter']) {
            // line 116
            yield '        ';
            $context['isCurrent'] = ($context['_adapter']::class == (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 116, $this->source);
            })())::class);
            // line 117
            yield '        <div id="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Html::id($context['_adapter']::class), 'html', null, true);
            yield '"';
            if (! (isset($context['isCurrent']) || array_key_exists('isCurrent', $context) ? $context['isCurrent'] : (function () {
                throw new RuntimeError('Variable "isCurrent" does not exist.', 117, $this->source);
            })())) {
                yield ' class="hidden"';
            }
            yield '>
            ';
            // line 118
            $_namespace = (('transportTypes['.craft\helpers\Html::id($context['_adapter']::class)).']');
            if ($_namespace !== '') {
                $_originalNamespace = Craft::$app->getView()->getNamespace();
                Craft::$app->getView()->setNamespace(Craft::$app->getView()->namespaceInputName($_namespace));
                ob_start();
                try {
                    // line 119
                    yield '                ';
                    yield craft\helpers\Template::attribute($this->env, $this->source, (((isset($context['isCurrent']) || array_key_exists('isCurrent', $context) ? $context['isCurrent'] : (function () {
                        throw new RuntimeError('Variable "isCurrent" does not exist.', 119, $this->source);
                    })())) ? ((isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                        throw new RuntimeError('Variable "adapter" does not exist.', 119, $this->source);
                    })())) : ($context['_adapter'])), 'getSettingsHtml', [], 'method', false, false, false, 119);
                    yield '
            ';
                } catch (Exception $e) {
                    ob_end_clean();

                    throw $e;
                }
                echo craft\helpers\Html::namespaceHtml(ob_get_clean(), $_namespace, false);
                Craft::$app->getView()->setNamespace($_originalNamespace);
            } else {
                yield '                ';
                yield craft\helpers\Template::attribute($this->env, $this->source, (((isset($context['isCurrent']) || array_key_exists('isCurrent', $context) ? $context['isCurrent'] : (function () {
                    throw new RuntimeError('Variable "isCurrent" does not exist.', 119, $this->source);
                })())) ? ((isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 119, $this->source);
                })())) : ($context['_adapter'])), 'getSettingsHtml', [], 'method', false, false, false, 119);
                yield '
            ';
            }
            unset($_originalNamespace, $_namespace);
            // line 121
            yield '        </div>
    ';
        }
        unset($context['_seq'], $context['_key'], $context['_adapter'], $context['_parent']);
        // line 123
        yield '
    <hr>

    <div class="buttons">
        <button type="button" id="test" class="btn formsubmit" data-action="system-settings/test-email-settings">';
        // line 127
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Test', 'app'), 'html', null, true);
        yield '</button>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'settings/email/_index.twig';
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
        return [223 => 127,  217 => 123,  210 => 121,  191 => 119,  184 => 118,  175 => 117,  172 => 116,  168 => 115,  163 => 112,  161 => 110,  160 => 109,  159 => 108,  158 => 103,  153 => 100,  149 => 98,  147 => 97,  146 => 96,  145 => 94,  143 => 89,  141 => 88,  137 => 86,  135 => 85,  134 => 83,  133 => 77,  129 => 75,  127 => 74,  126 => 73,  125 => 66,  121 => 64,  119 => 63,  118 => 60,  117 => 53,  112 => 51,  108 => 50,  105 => 49,  97 => 43,  95 => 42,  94 => 41,  89 => 38,  86 => 37,  78 => 36,  72 => 3,  69 => 32,  66 => 30,  64 => 29,  62 => 28,  60 => 23,  58 => 21,  56 => 12,  54 => 8,  52 => 6,  50 => 5,  48 => 1,  40 => 3];
    }

    public function getSourceContext(): Source
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

    {% if CraftEdition >= CraftPro %}
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
", 'settings/email/_index.twig', '/tmp/packages/craft5/src/templates/settings/email/_index.twig');
    }
}
