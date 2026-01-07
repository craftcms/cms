<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/mailertransportadapters/Smtp/settings.twig */
class __TwigTemplate_b7a58d418613eb899fe06558a63814fa extends Template
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
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/mailertransportadapters/Smtp/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/mailertransportadapters/Smtp/settings.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Hostname', 'app'), 'id' => 'host', 'name' => 'host', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 8
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 8, $this->source);
            })()), 'host', []), 'required' => true, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 10
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 10, $this->source);
                })()), 'getErrors', [0 => 'host'], 'method')]], 3, $context, $this->getSourceContext());
        // line 11
        echo '

';
        // line 13
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Port', 'app'), 'id' => 'port', 'name' => 'port', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 18
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 18, $this->source);
            })()), 'port', []), 'size' => 20, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 20
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 20, $this->source);
                })()), 'getErrors', [0 => 'port'], 'method')]], 13, $context, $this->getSourceContext());
        // line 21
        echo '

';
        // line 23
        echo twig_call_macro($macros['forms'], 'macro_booleanMenuField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Use authentication', 'app'), 'id' => 'useAuthentication', 'name' => 'useAuthentication', 'includeEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 28
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 28, $this->source);
            })()), 'useAuthentication', []), 'toggle' => 'auth-credentials']], 23, $context, $this->getSourceContext());
        // line 30
        echo '

<div id="auth-credentials" class="nested-fields';
        // line 32
        if (! craft\helpers\Template::attribute($this->env, $this->source, (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
            throw new RuntimeError('Variable "adapter" does not exist.', 32, $this->source);
        })()), 'useAuthentication', [])) {
            echo ' hidden';
        }
        echo '">
    ';
        // line 33
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Username', 'app'), 'id' => 'username', 'name' => 'username', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 38
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 38, $this->source);
            })()), 'username', []), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 39
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 39, $this->source);
                })()), 'getErrors', [0 => 'username'], 'method')]], 33, $context, $this->getSourceContext());
        // line 40
        echo '

    ';
        // line 42
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Password', 'app'), 'id' => 'password', 'name' => 'password', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 47
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 47, $this->source);
            })()), 'password', []), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 48
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 48, $this->source);
                })()), 'getErrors', [0 => 'password'], 'method')]], 42, $context, $this->getSourceContext());
        // line 49
        echo '
</div>
';
        craft\helpers\Template::endProfile('template', '_components/mailertransportadapters/Smtp/settings.twig');
    }

    public function getTemplateName()
    {
        return '_components/mailertransportadapters/Smtp/settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [84 => 49,  82 => 48,  81 => 47,  80 => 42,  76 => 40,  74 => 39,  73 => 38,  72 => 33,  66 => 32,  62 => 30,  60 => 28,  59 => 23,  55 => 21,  53 => 20,  52 => 18,  51 => 13,  47 => 11,  45 => 10,  44 => 8,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{{ forms.autosuggestField({
    label: \"Hostname\"|t('app'),
    id: 'host',
    name: 'host',
    suggestEnvVars: true,
    value: adapter.host,
    required: true,
    errors: adapter.getErrors('host')
}) }}

{{ forms.autosuggestField({
    label: \"Port\"|t('app'),
    id: 'port',
    name: 'port',
    suggestEnvVars: true,
    value: adapter.port,
    size: 20,
    errors: adapter.getErrors('port')
}) }}

{{ forms.booleanMenuField({
    label: \"Use authentication\"|t('app'),
    id: 'useAuthentication',
    name: 'useAuthentication',
    includeEnvVars: true,
    value: adapter.useAuthentication,
    toggle: 'auth-credentials'
}) }}

<div id=\"auth-credentials\" class=\"nested-fields{% if not adapter.useAuthentication %} hidden{% endif %}\">
    {{ forms.autosuggestField({
        label: \"Username\"|t('app'),
        id: 'username',
        name: 'username',
        suggestEnvVars: true,
        value: adapter.username,
        errors: adapter.getErrors('username')
    }) }}

    {{ forms.autosuggestField({
        label: \"Password\"|t('app'),
        id: 'password',
        name: 'password',
        suggestEnvVars: true,
        value: adapter.password,
        errors: adapter.getErrors('password')
    }) }}
</div>
", '_components/mailertransportadapters/Smtp/settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/mailertransportadapters/Smtp/settings.twig');
    }
}
