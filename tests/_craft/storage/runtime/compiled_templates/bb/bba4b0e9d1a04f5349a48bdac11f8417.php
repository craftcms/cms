<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/widgets/Feed/settings.twig */
class __TwigTemplate_e0211e660eb132287e69f310f220825e extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/Feed/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/Feed/settings.twig', 1)->unwrap();
        // line 2
        yield '

';
        // line 4
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('URL', 'app'), 'id' => 'url', 'name' => 'url', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 8
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 8, $this->source);
            })()), 'url', [], 'any', false, false, false, 8), 'required' => true, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 10
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 10, $this->source);
                })()), 'getErrors', ['url'], 'method', false, false, false, 10)]], 4, $context, $this->getSourceContext());
        // line 11
        yield '


';
        // line 14
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Title', 'app'), 'id' => 'title', 'name' => 'title', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 18
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 18, $this->source);
            })()), 'title', [], 'any', false, false, false, 18), 'required' => true, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 20
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 20, $this->source);
                })()), 'getErrors', ['title'], 'method', false, false, false, 20)]], 14, $context, $this->getSourceContext());
        // line 21
        yield '


';
        // line 24
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Limit', 'app'), 'id' => 'limit', 'name' => 'limit', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 28
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 28, $this->source);
            })()), 'limit', [], 'any', false, false, false, 28), 'size' => 2, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 30
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 30, $this->source);
                })()), 'getErrors', ['limit'], 'method', false, false, false, 30)]], 24, $context, $this->getSourceContext());
        // line 31
        yield '
';
        craft\helpers\Template::endProfile('template', '_components/widgets/Feed/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/Feed/settings.twig';
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
        return [71 => 31,  69 => 30,  68 => 28,  67 => 24,  62 => 21,  60 => 20,  59 => 18,  58 => 14,  53 => 11,  51 => 10,  50 => 8,  49 => 4,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}


{{ forms.textField({
    label: \"URL\"|t('app'),
    id: 'url',
    name: 'url',
    value: widget.url,
    required: true,
    errors: widget.getErrors('url')
}) }}


{{ forms.textField({
    label: \"Title\"|t('app'),
    id: 'title',
    name: 'title',
    value: widget.title,
    required: true,
    errors: widget.getErrors('title')
}) }}


{{ forms.textField({
    label: \"Limit\"|t('app'),
    id: 'limit',
    name: 'limit',
    value: widget.limit,
    size: 2,
    errors: widget.getErrors('limit')
}) }}
", '_components/widgets/Feed/settings.twig', '/tmp/packages/craft5/src/templates/_components/widgets/Feed/settings.twig');
    }
}
