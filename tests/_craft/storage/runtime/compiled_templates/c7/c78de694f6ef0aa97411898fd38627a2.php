<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/mailertransportadapters/Sendmail/settings.twig */
class __TwigTemplate_a3824640da66fd6f353224780109c5cf extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/mailertransportadapters/Sendmail/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/mailertransportadapters/Sendmail/settings.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        echo twig_call_macro($macros['forms'], 'macro_selectizeField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Sendmail Command', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('The command to run Sendmail with.', 'app'), 'id' => 'command', 'name' => 'command', 'options' =>         // line 8
(isset($context['commandOptions']) || array_key_exists('commandOptions', $context) ? $context['commandOptions'] : (function () {
    throw new RuntimeError('Variable "commandOptions" does not exist.', 8, $this->source);
})()), 'includeEnvVars' => true, 'allowedEnvValues' => null, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 11
    (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
        throw new RuntimeError('Variable "adapter" does not exist.', 11, $this->source);
    })()), 'command', []), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 12
        (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
            throw new RuntimeError('Variable "adapter" does not exist.', 12, $this->source);
        })()), 'getErrors', [0 => 'command'], 'method'), ]], 3, $context, $this->getSourceContext());
        // line 13
        echo '
';
        craft\helpers\Template::endProfile('template', '_components/mailertransportadapters/Sendmail/settings.twig');
    }

    public function getTemplateName()
    {
        return '_components/mailertransportadapters/Sendmail/settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [48 => 13,  46 => 12,  45 => 11,  44 => 8,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
", '_components/mailertransportadapters/Sendmail/settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/mailertransportadapters/Sendmail/settings.twig');
    }
}
