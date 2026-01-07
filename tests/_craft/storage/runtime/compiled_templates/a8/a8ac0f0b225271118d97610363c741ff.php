<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/mailertransportadapters/Sendmail/settings.twig */
class __TwigTemplate_e459cd6d678e646c526cdc972d0c4ed6 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/mailertransportadapters/Sendmail/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/mailertransportadapters/Sendmail/settings.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        yield CoreExtension::callMacro($macros['forms'], 'macro_selectizeField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Sendmail Command', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The command to run Sendmail with.', 'app'), 'id' => 'command', 'name' => 'command', 'options' =>         // line 8
(isset($context['commandOptions']) || array_key_exists('commandOptions', $context) ? $context['commandOptions'] : (function () {
    throw new RuntimeError('Variable "commandOptions" does not exist.', 8, $this->source);
})()), 'includeEnvVars' => true, 'allowedEnvValues' => null, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 11
    (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
        throw new RuntimeError('Variable "adapter" does not exist.', 11, $this->source);
    })()), 'command', [], 'any', false, false, false, 11), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 12
        (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
            throw new RuntimeError('Variable "adapter" does not exist.', 12, $this->source);
        })()), 'getErrors', ['command'], 'method', false, false, false, 12)]], 3, $context, $this->getSourceContext());
        // line 13
        yield '
';
        craft\helpers\Template::endProfile('template', '_components/mailertransportadapters/Sendmail/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/mailertransportadapters/Sendmail/settings.twig';
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
        return [53 => 13,  51 => 12,  50 => 11,  49 => 8,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{{ forms.selectizeField({
    label: 'Sendmail Command'|t('app'),
    instructions: 'The command to run Sendmail with.'|t('app'),
    id: 'command',
    name: 'command',
    options: commandOptions,
    includeEnvVars: true,
    allowedEnvValues: null,
    value: adapter.command,
    errors: adapter.getErrors('command')
}) }}
", '_components/mailertransportadapters/Sendmail/settings.twig', '/tmp/packages/craft5/src/templates/_components/mailertransportadapters/Sendmail/settings.twig');
    }
}
