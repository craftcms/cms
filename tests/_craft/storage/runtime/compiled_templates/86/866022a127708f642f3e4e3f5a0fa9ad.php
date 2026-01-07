<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/mailertransportadapters/Gmail/settings.twig */
class __TwigTemplate_3294e52febf0f142ffe19bc7915174d6 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/mailertransportadapters/Gmail/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/mailertransportadapters/Gmail/settings.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Username', 'app'), 'id' => 'username', 'name' => 'username', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 8
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 8, $this->source);
            })()), 'username', [], 'any', false, false, false, 8), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 9
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 9, $this->source);
                })()), 'getErrors', ['username'], 'method', false, false, false, 9)]], 3, $context, $this->getSourceContext());
        // line 10
        yield '

';
        // line 12
        yield CoreExtension::callMacro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Password', 'app'), 'id' => 'password', 'name' => 'password', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 17
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 17, $this->source);
            })()), 'password', [], 'any', false, false, false, 17), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 18
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 18, $this->source);
                })()), 'getErrors', ['password'], 'method', false, false, false, 18)]], 12, $context, $this->getSourceContext());
        // line 19
        yield '
';
        craft\helpers\Template::endProfile('template', '_components/mailertransportadapters/Gmail/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/mailertransportadapters/Gmail/settings.twig';
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
        return [60 => 19,  58 => 18,  57 => 17,  56 => 12,  52 => 10,  50 => 9,  49 => 8,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

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
", '_components/mailertransportadapters/Gmail/settings.twig', '/tmp/packages/craft5/src/templates/_components/mailertransportadapters/Gmail/settings.twig');
    }
}
