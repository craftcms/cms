<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _components/widgets/RecentEntries/settings.twig */
class __TwigTemplate_c80ffa3a4e17a51e1b21270f397a144c extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/RecentEntries/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/RecentEntries/settings.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'app', [], 'any', false, false, false, 3), 'getIsMultiSite', [], 'method', false, false, false, 3)) {
            // line 4
            yield '    ';
            $context['editableSites'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
            })()), 'app', [], 'any', false, false, false, 4), 'sites', [], 'any', false, false, false, 4), 'getEditableSites', [], 'method', false, false, false, 4);
            // line 5
            yield '
    ';
            // line 6
            if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                throw new RuntimeError('Variable "editableSites" does not exist.', 6, $this->source);
            })())) > 1)) {
                // line 7
                yield '        ';
                $context['siteInput'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
                    // line 8
                    yield '            <div class="select">
                <select id="site-id" name="siteId">
                    ';
                    // line 10
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable((isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                        throw new RuntimeError('Variable "editableSites" does not exist.', 10, $this->source);
                    })()));
                    foreach ($context['_seq'] as $context['_key'] => $context['site']) {
                        // line 11
                        yield '                        <option value="';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', [], 'any', false, false, false, 11), 'html', null, true);
                        yield '"';
                        if ((craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', [], 'any', false, false, false, 11) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                            throw new RuntimeError('Variable "widget" does not exist.', 11, $this->source);
                        })()), 'siteId', [], 'any', false, false, false, 11))) {
                            yield ' selected';
                        }
                        yield '>';
                        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', [], 'any', false, false, false, 11), 'site'), 'html', null, true);
                        yield '</option>
                    ';
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['site'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 13
                    yield '                </select>
            </div>
        ';
                    yield from [];
                })())) ? '' : new Markup($tmp, $this->env->getCharset());
                // line 16
                yield '
        ';
                // line 17
                yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['id' => 'site-id', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Site', 'app')],                 // line 20
                    (isset($context['siteInput']) || array_key_exists('siteInput', $context) ? $context['siteInput'] : (function () {
                        throw new RuntimeError('Variable "siteInput" does not exist.', 20, $this->source);
                    })())], 17, $context, $this->getSourceContext());
                yield '
    ';
            }
        }
        // line 23
        yield '
';
        // line 24
        $context['sectionInput'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
            // line 25
            yield '    <div class="select">
        <select id="section" name="section">
            <option value="*">';
            // line 27
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('All', 'app'), 'html', null, true);
            yield '</option>
            ';
            // line 28
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 28, $this->source);
            })()), 'app', [], 'any', false, false, false, 28), 'entries', [], 'any', false, false, false, 28), 'getAllSections', [], 'method', false, false, false, 28));
            foreach ($context['_seq'] as $context['_key'] => $context['section']) {
                // line 29
                yield '                ';
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'type', [], 'any', false, false, false, 29) != 'single')) {
                    // line 30
                    yield '                    <option value="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [], 'any', false, false, false, 30), 'html', null, true);
                    yield '"';
                    if ((craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [], 'any', false, false, false, 30) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                        throw new RuntimeError('Variable "widget" does not exist.', 30, $this->source);
                    })()), 'section', [], 'any', false, false, false, 30))) {
                        yield ' selected';
                    }
                    yield '>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'name', [], 'any', false, false, false, 30), 'site'), 'html', null, true);
                    yield '</option>
                ';
                }
                // line 32
                yield '            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['section'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 33
            yield '        </select>
    </div>
';
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 36
        yield '
';
        // line 37
        yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Section', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Which section do you want to pull recent entries from?', 'app'), 'id' => 'section'],         // line 41
            (isset($context['sectionInput']) || array_key_exists('sectionInput', $context) ? $context['sectionInput'] : (function () {
                throw new RuntimeError('Variable "sectionInput" does not exist.', 41, $this->source);
            })())], 37, $context, $this->getSourceContext());
        yield '

';
        // line 43
        yield CoreExtension::callMacro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Limit', 'app'), 'id' => 'limit', 'name' => 'limit', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 47
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 47, $this->source);
            })()), 'limit', [], 'any', false, false, false, 47), 'size' => 2, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 49
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 49, $this->source);
                })()), 'getErrors', ['limit'], 'method', false, false, false, 49)]], 43, $context, $this->getSourceContext());
        // line 50
        yield '
';
        craft\helpers\Template::endProfile('template', '_components/widgets/RecentEntries/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/RecentEntries/settings.twig';
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
        return [157 => 50,  155 => 49,  154 => 47,  153 => 43,  148 => 41,  147 => 37,  144 => 36,  138 => 33,  132 => 32,  120 => 30,  117 => 29,  113 => 28,  109 => 27,  105 => 25,  103 => 24,  100 => 23,  94 => 20,  93 => 17,  90 => 16,  84 => 13,  69 => 11,  65 => 10,  61 => 8,  58 => 7,  56 => 6,  53 => 5,  50 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{% if craft.app.getIsMultiSite() %}
    {% set editableSites = craft.app.sites.getEditableSites() %}

    {% if editableSites|length > 1 %}
        {% set siteInput %}
            <div class=\"select\">
                <select id=\"site-id\" name=\"siteId\">
                    {% for site in editableSites %}
                        <option value=\"{{ site.id }}\"{% if site.id == widget.siteId %} selected{% endif %}>{{ site.name|t('site') }}</option>
                    {% endfor %}
                </select>
            </div>
        {% endset %}

        {{ forms.field({
            id: 'site-id',
            label: \"Site\"|t('app')
        }, siteInput) }}
    {% endif %}
{% endif %}

{% set sectionInput %}
    <div class=\"select\">
        <select id=\"section\" name=\"section\">
            <option value=\"*\">{{ \"All\"|t('app') }}</option>
            {% for section in craft.app.entries.getAllSections() %}
                {% if section.type != 'single' %}
                    <option value=\"{{ section.id }}\"{% if section.id == widget.section %} selected{% endif %}>{{ section.name|t('site') }}</option>
                {% endif %}
            {% endfor %}
        </select>
    </div>
{% endset %}

{{ forms.field({
    label: \"Section\"|t('app'),
    instructions: \"Which section do you want to pull recent entries from?\"|t('app'),
    id: 'section',
}, sectionInput) }}

{{ forms.textField({
    label: \"Limit\"|t('app'),
    id: 'limit',
    name: 'limit',
    value: widget.limit,
    size: 2,
    errors: widget.getErrors('limit')
}) }}
", '_components/widgets/RecentEntries/settings.twig', '/tmp/packages/craft5/src/templates/_components/widgets/RecentEntries/settings.twig');
    }
}
