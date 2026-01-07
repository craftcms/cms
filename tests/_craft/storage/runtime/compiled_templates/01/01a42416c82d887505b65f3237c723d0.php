<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/mailertransportadapters/Smtp/settings.twig */
class __TwigTemplate_3335a1186f9bb141a3e3c9c95e726310 extends Template
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
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/mailertransportadapters/Smtp/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/mailertransportadapters/Smtp/settings.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Hostname', 'app'), 'id' => 'host', 'name' => 'host', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 8
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 8, $this->source);
            })()), 'host', [], 'any', false, false, false, 8), 'required' => true, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 10
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 10, $this->source);
                })()), 'getErrors', ['host'], 'method', false, false, false, 10)]], 3, $context, $this->getSourceContext());
        // line 11
        yield '

';
        // line 13
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Port', 'app'), 'id' => 'port', 'name' => 'port', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 18
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 18, $this->source);
            })()), 'port', [], 'any', false, false, false, 18), 'size' => 20, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 20
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 20, $this->source);
                })()), 'getErrors', ['port'], 'method', false, false, false, 20)]], 13, $context, $this->getSourceContext());
        // line 21
        yield '

';
        // line 23
        yield CoreExtension::callMacro($macros['forms'], 'macro_booleanMenuField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Use authentication', 'app'), 'id' => 'useAuthentication', 'name' => 'useAuthentication', 'includeEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 28
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 28, $this->source);
            })()), 'useAuthentication', [], 'any', false, false, false, 28), 'toggle' => 'auth-credentials']], 23, $context, $this->getSourceContext());
        // line 30
        yield '

<div id="auth-credentials" class="nested-fields';
        // line 32
        if (! craft\helpers\Template::attribute($this->env, $this->source, (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
            throw new RuntimeError('Variable "adapter" does not exist.', 32, $this->source);
        })()), 'useAuthentication', [], 'any', false, false, false, 32)) {
            yield ' hidden';
        }
        yield '">
    ';
        // line 33
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Username', 'app'), 'id' => 'username', 'name' => 'username', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 38
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 38, $this->source);
            })()), 'username', [], 'any', false, false, false, 38), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 39
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 39, $this->source);
                })()), 'getErrors', ['username'], 'method', false, false, false, 39)]], 33, $context, $this->getSourceContext());
        // line 40
        yield '

    ';
        // line 42
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Password', 'app'), 'id' => 'password', 'name' => 'password', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 47
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 47, $this->source);
            })()), 'password', [], 'any', false, false, false, 47), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 48
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 48, $this->source);
                })()), 'getErrors', ['password'], 'method', false, false, false, 48)]], 42, $context, $this->getSourceContext());
        // line 49
        yield '
</div>
';
        craft\helpers\Template::endProfile('template', '_components/mailertransportadapters/Smtp/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/mailertransportadapters/Smtp/settings.twig';
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
        return [89 => 49,  87 => 48,  86 => 47,  85 => 42,  81 => 40,  79 => 39,  78 => 38,  77 => 33,  71 => 32,  67 => 30,  65 => 28,  64 => 23,  60 => 21,  58 => 20,  57 => 18,  56 => 13,  52 => 11,  50 => 10,  49 => 8,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
", '_components/mailertransportadapters/Smtp/settings.twig', '/tmp/packages/craft5/src/templates/_components/mailertransportadapters/Smtp/settings.twig');
    }
}
