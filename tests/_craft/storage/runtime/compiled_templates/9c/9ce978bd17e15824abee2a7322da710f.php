<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _components/widgets/QuickPost/settings.twig */
class __TwigTemplate_2fc5236c2d24081eaea256b2a61383cf extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/QuickPost/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/QuickPost/settings.twig', 1)->unwrap();
        // line 2
        yield '
';
        // line 3
        if ((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
            throw new RuntimeError('Variable "sections" does not exist.', 3, $this->source);
        })())) {
            // line 4
            yield '
    ';
            // line 5
            if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 5, $this->source);
            })()), 'app', [], 'any', false, false, false, 5), 'getIsMultiSite', [], 'method', false, false, false, 5)) {
                // line 6
                yield '        ';
                $context['editableSites'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
                })()), 'app', [], 'any', false, false, false, 6), 'sites', [], 'any', false, false, false, 6), 'getEditableSites', [], 'method', false, false, false, 6);
                // line 7
                yield '
        ';
                // line 8
                if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                    throw new RuntimeError('Variable "editableSites" does not exist.', 8, $this->source);
                })())) > 1)) {
                    // line 9
                    yield '            ';
                    $context['siteInput'] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context) {
                        // line 10
                        yield '                <div class="select">
                    <select id="site-id" name="siteId">
                        ';
                        // line 12
                        $context['_parent'] = $context;
                        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                            throw new RuntimeError('Variable "editableSites" does not exist.', 12, $this->source);
                        })()));
                        foreach ($context['_seq'] as $context['_key'] => $context['site']) {
                            // line 13
                            yield '                            <option value="';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', [], 'any', false, false, false, 13), 'html', null, true);
                            yield '"';
                            if ((craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', [], 'any', false, false, false, 13) == (isset($context['siteId']) || array_key_exists('siteId', $context) ? $context['siteId'] : (function () {
                                throw new RuntimeError('Variable "siteId" does not exist.', 13, $this->source);
                            })()))) {
                                yield ' selected';
                            }
                            yield '>';
                            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', [], 'any', false, false, false, 13), 'site'), 'html', null, true);
                            yield '</option>
                        ';
                        }
                        $_parent = $context['_parent'];
                        unset($context['_seq'], $context['_key'], $context['site'], $context['_parent']);
                        $context = array_intersect_key($context, $_parent) + $_parent;
                        // line 15
                        yield '                    </select>
                </div>
            ';
                        yield from [];
                    })())) ? '' : new Markup($tmp, $this->env->getCharset());
                    // line 18
                    yield '
            ';
                    // line 19
                    yield CoreExtension::callMacro($macros['forms'], 'macro_field', [['id' => 'site-id', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Site', 'app')],                     // line 22
                        (isset($context['siteInput']) || array_key_exists('siteInput', $context) ? $context['siteInput'] : (function () {
                            throw new RuntimeError('Variable "siteInput" does not exist.', 22, $this->source);
                        })())], 19, $context, $this->getSourceContext());
                    yield '
        ';
                }
                // line 24
                yield '    ';
            }
            // line 25
            yield '
    ';
            // line 26
            $context['sectionOptions'] = [];
            // line 27
            yield '    ';
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
                throw new RuntimeError('Variable "sections" does not exist.', 27, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['section']) {
                // line 28
                yield '        ';
                $context['sectionOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['sectionOptions']) || array_key_exists('sectionOptions', $context) ? $context['sectionOptions'] : (function () {
                    throw new RuntimeError('Variable "sectionOptions" does not exist.', 28, $this->source);
                })()), [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'name', [], 'any', false, false, false, 28), 'site'), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [], 'any', false, false, false, 28)]]);
                // line 29
                yield '    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['section'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 30
            yield '    ';
            yield CoreExtension::callMacro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Section', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Which section do you want to save entries to?', 'app'), 'id' => 'section', 'name' => 'section', 'options' =>             // line 35
                        (isset($context['sectionOptions']) || array_key_exists('sectionOptions', $context) ? $context['sectionOptions'] : (function () {
                            throw new RuntimeError('Variable "sectionOptions" does not exist.', 35, $this->source);
                        })()), 'value' =>             // line 36
                        (isset($context['sectionId']) || array_key_exists('sectionId', $context) ? $context['sectionId'] : (function () {
                            throw new RuntimeError('Variable "sectionId" does not exist.', 36, $this->source);
                        })()), 'toggle' => true, 'targetPrefix' => 'section']], 30, $context, $this->getSourceContext());
            // line 39
            yield '

    ';
            // line 41
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
                throw new RuntimeError('Variable "sections" does not exist.', 41, $this->source);
            })()));
            $context['loop'] = [
                'parent' => $context['_parent'],
                'index0' => 0,
                'index' => 1,
                'first' => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = $length === 1;
            }
            foreach ($context['_seq'] as $context['_key'] => $context['section']) {
                // line 42
                yield '        ';
                $context['showSection'] = ((! (isset($context['sectionId']) || array_key_exists('sectionId', $context) ? $context['sectionId'] : (function () {
                    throw new RuntimeError('Variable "sectionId" does not exist.', 42, $this->source);
                })()) && craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'first', [], 'any', false, false, false, 42)) || ((isset($context['sectionId']) || array_key_exists('sectionId', $context) ? $context['sectionId'] : (function () {
                    throw new RuntimeError('Variable "sectionId" does not exist.', 42, $this->source);
                })()) == craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [], 'any', false, false, false, 42)));
                // line 43
                yield '        <div id="section';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [], 'any', false, false, false, 43), 'html', null, true);
                yield '"';
                if (! (isset($context['showSection']) || array_key_exists('showSection', $context) ? $context['showSection'] : (function () {
                    throw new RuntimeError('Variable "showSection" does not exist.', 43, $this->source);
                })())) {
                    yield ' class="hidden"';
                }
                yield '>

            ';
                // line 45
                $context['entryTypeOptions'] = [];
                // line 46
                yield '            ';
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'getEntryTypes', [], 'method', false, false, false, 46));
                foreach ($context['_seq'] as $context['_key'] => $context['entryType']) {
                    // line 47
                    yield '                ';
                    $context['entryTypeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['entryTypeOptions']) || array_key_exists('entryTypeOptions', $context) ? $context['entryTypeOptions'] : (function () {
                        throw new RuntimeError('Variable "entryTypeOptions" does not exist.', 47, $this->source);
                    })()), [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['entryType'], 'name', [], 'any', false, false, false, 47), 'site'), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['entryType'], 'id', [], 'any', false, false, false, 47)]]);
                    // line 48
                    yield '            ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['entryType'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 49
                yield '
            ';
                // line 50
                if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['entryTypeOptions']) || array_key_exists('entryTypeOptions', $context) ? $context['entryTypeOptions'] : (function () {
                    throw new RuntimeError('Variable "entryTypeOptions" does not exist.', 50, $this->source);
                })())) == 1)) {
                    // line 51
                    yield '                ';
                    yield craft\helpers\Html::hiddenInput((('sections['.craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [], 'any', false, false, false, 51)).'][entryType]'), (isset($context['entryTypeId']) || array_key_exists('entryTypeId', $context) ? $context['entryTypeId'] : (function () {
                        throw new RuntimeError('Variable "entryTypeId" does not exist.', 51, $this->source);
                    })()));
                    yield '
            ';
                } else {
                    // line 53
                    yield '                ';
                    yield CoreExtension::callMacro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Entry Type', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Which type of entries do you want to create?', 'app'), 'id' => 'entryType', 'name' => (('sections['.craft\helpers\Template::attribute($this->env, $this->source,                     // line 57
                        $context['section'], 'id', [], 'any', false, false, false, 57)).'][entryType]'), 'options' =>                     // line 58
(isset($context['entryTypeOptions']) || array_key_exists('entryTypeOptions', $context) ? $context['entryTypeOptions'] : (function () {
    throw new RuntimeError('Variable "entryTypeOptions" does not exist.', 58, $this->source);
})()), 'value' =>                     // line 59
(isset($context['entryTypeId']) || array_key_exists('entryTypeId', $context) ? $context['entryTypeId'] : (function () {
    throw new RuntimeError('Variable "entryTypeId" does not exist.', 59, $this->source);
})()), 'toggle' => true, 'targetPrefix' => (('section'.craft\helpers\Template::attribute($this->env, $this->source,                     // line 61
    $context['section'], 'id', [], 'any', false, false, false, 61)).'-type')]], 53, $context, $this->getSourceContext());
                    // line 62
                    yield '
            ';
                }
                // line 64
                yield '        </div>
    ';
                $context['loop']['index0']++;
                $context['loop']['index']++;
                $context['loop']['first'] = false;
                if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                    $context['loop']['revindex0']--;
                    $context['loop']['revindex']--;
                    $context['loop']['last'] = $context['loop']['revindex0'] === 0;
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['section'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 66
            yield '
';
        } else {
            // line 68
            yield '
    <p>';
            // line 69
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('No sections are available.', 'app'), 'html', null, true);
            yield '</p>

';
        }
        craft\helpers\Template::endProfile('template', '_components/widgets/QuickPost/settings.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/QuickPost/settings.twig';
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
        return [225 => 69,  222 => 68,  218 => 66,  203 => 64,  199 => 62,  197 => 61,  196 => 59,  195 => 58,  194 => 57,  192 => 53,  186 => 51,  184 => 50,  181 => 49,  175 => 48,  172 => 47,  167 => 46,  165 => 45,  155 => 43,  152 => 42,  135 => 41,  131 => 39,  129 => 36,  128 => 35,  126 => 30,  120 => 29,  117 => 28,  112 => 27,  110 => 26,  107 => 25,  104 => 24,  99 => 22,  98 => 19,  95 => 18,  89 => 15,  74 => 13,  70 => 12,  66 => 10,  63 => 9,  61 => 8,  58 => 7,  55 => 6,  53 => 5,  50 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/forms\" as forms %}

{% if sections %}

    {% if craft.app.getIsMultiSite() %}
        {% set editableSites = craft.app.sites.getEditableSites() %}

        {% if editableSites|length > 1 %}
            {% set siteInput %}
                <div class=\"select\">
                    <select id=\"site-id\" name=\"siteId\">
                        {% for site in editableSites %}
                            <option value=\"{{ site.id }}\"{% if site.id == siteId %} selected{% endif %}>{{ site.name|t('site') }}</option>
                        {% endfor %}
                    </select>
                </div>
            {% endset %}

            {{ forms.field({
                id: 'site-id',
                label: \"Site\"|t('app'),
            }, siteInput) }}
        {% endif %}
    {% endif %}

    {% set sectionOptions = [] %}
    {% for section in sections %}
        {% set sectionOptions = sectionOptions|merge([{ label: section.name|t('site'), value: section.id }]) %}
    {% endfor %}
    {{ forms.selectField({
        label: \"Section\"|t('app'),
        instructions: 'Which section do you want to save entries to?'|t('app'),
        id: 'section',
        name: 'section',
        options: sectionOptions,
        value: sectionId,
        toggle: true,
        targetPrefix: 'section'
    }) }}

    {% for section in sections %}
        {% set showSection = ((not sectionId and loop.first) or sectionId == section.id) %}
        <div id=\"section{{ section.id }}\"{% if not showSection %} class=\"hidden\"{% endif %}>

            {% set entryTypeOptions = [] %}
            {% for entryType in section.getEntryTypes() %}
                {% set entryTypeOptions = entryTypeOptions|merge([{ label: entryType.name|t('site'), value: entryType.id }]) %}
            {% endfor %}

            {% if entryTypeOptions|length == 1 %}
                {{ hiddenInput(\"sections[#{section.id}][entryType]\", entryTypeId) }}
            {% else %}
                {{ forms.selectField({
                    label: \"Entry Type\"|t('app'),
                    instructions: \"Which type of entries do you want to create?\"|t('app'),
                    id: 'entryType',
                    name: 'sections['~section.id~'][entryType]',
                    options: entryTypeOptions,
                    value: entryTypeId,
                    toggle: true,
                    targetPrefix: 'section'~section.id~'-type'
                }) }}
            {% endif %}
        </div>
    {% endfor %}

{% else %}

    <p>{{ \"No sections are available.\"|t('app') }}</p>

{% endif %}
", '_components/widgets/QuickPost/settings.twig', '/tmp/packages/craft5/src/templates/_components/widgets/QuickPost/settings.twig');
    }
}
