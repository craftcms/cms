<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/mailertransportadapters/Gmail/settings.twig */
class __TwigTemplate_6e947b2474401b8e375e6396de54d776 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/mailertransportadapters/Gmail/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/mailertransportadapters/Gmail/settings.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Username', 'app'), 'id' => 'username', 'name' => 'username', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 8
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 8, $this->source);
            })()), 'username', []), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 9
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 9, $this->source);
                })()), 'getErrors', [0 => 'username'], 'method')]], 3, $context, $this->getSourceContext());
        // line 10
        echo '

';
        // line 12
        echo twig_call_macro($macros['forms'], 'macro_autosuggestField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Password', 'app'), 'id' => 'password', 'name' => 'password', 'suggestEnvVars' => true, 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 17
            (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                throw new RuntimeError('Variable "adapter" does not exist.', 17, $this->source);
            })()), 'password', []), 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 18
                (isset($context['adapter']) || array_key_exists('adapter', $context) ? $context['adapter'] : (function () {
                    throw new RuntimeError('Variable "adapter" does not exist.', 18, $this->source);
                })()), 'getErrors', [0 => 'password'], 'method')]], 12, $context, $this->getSourceContext());
        // line 19
        echo '
';
        craft\helpers\Template::endProfile('template', '_components/mailertransportadapters/Gmail/settings.twig');
    }

    public function getTemplateName()
    {
        return '_components/mailertransportadapters/Gmail/settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [55 => 19,  53 => 18,  52 => 17,  51 => 12,  47 => 10,  45 => 9,  44 => 8,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
", '_components/mailertransportadapters/Gmail/settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/mailertransportadapters/Gmail/settings.twig');
    }
}
