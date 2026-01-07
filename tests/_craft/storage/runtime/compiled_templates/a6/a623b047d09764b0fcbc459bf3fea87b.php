<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _components/widgets/QuickPost/settings.twig */
class __TwigTemplate_1e3881b4fd81f36f8e398ca51b26b5d7 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/QuickPost/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/QuickPost/settings.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        if ((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
            throw new RuntimeError('Variable "sections" does not exist.', 3, $this->source);
        })())) {
            // line 4
            echo '
    ';
            // line 5
            if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 5, $this->source);
            })()), 'app', []), 'getIsMultiSite', [], 'method')) {
                // line 6
                echo '        ';
                $context['editableSites'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                    throw new RuntimeError('Variable "craft" does not exist.', 6, $this->source);
                })()), 'app', []), 'sites', []), 'getEditableSites', [], 'method');
                // line 7
                echo '
        ';
                // line 8
                if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                    throw new RuntimeError('Variable "editableSites" does not exist.', 8, $this->source);
                })())) > 1)) {
                    // line 9
                    echo '            ';
                    ob_start();
                    // line 10
                    echo '                <div class="select">
                    <select id="site-id" name="siteId">
                        ';
                    // line 12
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable((isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                        throw new RuntimeError('Variable "editableSites" does not exist.', 12, $this->source);
                    })()));
                    foreach ($context['_seq'] as $context['_key'] => $context['site']) {
                        // line 13
                        echo '                            <option value="';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', []), 'html', null, true);
                        echo '"';
                        if ((craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', []) == (isset($context['siteId']) || array_key_exists('siteId', $context) ? $context['siteId'] : (function () {
                            throw new RuntimeError('Variable "siteId" does not exist.', 13, $this->source);
                        })()))) {
                            echo ' selected';
                        }
                        echo '>';
                        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'name', []), 'site'), 'html', null, true);
                        echo '</option>
                        ';
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['site'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 15
                    echo '                    </select>
                </div>
            ';
                    $context['siteInput'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
                    // line 18
                    echo '
            ';
                    // line 19
                    echo twig_call_macro($macros['forms'], 'macro_field', [['id' => 'site-id', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Site', 'app')],                     // line 22
                        (isset($context['siteInput']) || array_key_exists('siteInput', $context) ? $context['siteInput'] : (function () {
                            throw new RuntimeError('Variable "siteInput" does not exist.', 22, $this->source);
                        })()), ], 19, $context, $this->getSourceContext());
                    echo '
        ';
                }
                // line 24
                echo '    ';
            }
            // line 25
            echo '
    ';
            // line 26
            $context['sectionOptions'] = [];
            // line 27
            echo '    ';
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
                throw new RuntimeError('Variable "sections" does not exist.', 27, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['section']) {
                // line 28
                echo '        ';
                $context['sectionOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['sectionOptions']) || array_key_exists('sectionOptions', $context) ? $context['sectionOptions'] : (function () {
                    throw new RuntimeError('Variable "sectionOptions" does not exist.', 28, $this->source);
                })()), [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'name', []), 'site'), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [])]]);
                // line 29
                echo '    ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['section'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 30
            echo '    ';
            echo twig_call_macro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Section', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Which section do you want to save entries to?', 'app'), 'id' => 'section', 'name' => 'section', 'options' =>             // line 35
                        (isset($context['sectionOptions']) || array_key_exists('sectionOptions', $context) ? $context['sectionOptions'] : (function () {
                            throw new RuntimeError('Variable "sectionOptions" does not exist.', 35, $this->source);
                        })()), 'value' =>             // line 36
                        (isset($context['sectionId']) || array_key_exists('sectionId', $context) ? $context['sectionId'] : (function () {
                            throw new RuntimeError('Variable "sectionId" does not exist.', 36, $this->source);
                        })()), 'toggle' => true, 'targetPrefix' => 'section', ]], 30, $context, $this->getSourceContext());
            // line 39
            echo '

    ';
            // line 41
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['sections']) || array_key_exists('sections', $context) ? $context['sections'] : (function () {
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
                echo '        ';
                $context['showSection'] = ((! (isset($context['sectionId']) || array_key_exists('sectionId', $context) ? $context['sectionId'] : (function () {
                    throw new RuntimeError('Variable "sectionId" does not exist.', 42, $this->source);
                })()) && craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'first', [])) || ((isset($context['sectionId']) || array_key_exists('sectionId', $context) ? $context['sectionId'] : (function () {
                    throw new RuntimeError('Variable "sectionId" does not exist.', 42, $this->source);
                })()) == craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [])));
                // line 43
                echo '        <div id="section';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', []), 'html', null, true);
                echo '"';
                if (! (isset($context['showSection']) || array_key_exists('showSection', $context) ? $context['showSection'] : (function () {
                    throw new RuntimeError('Variable "showSection" does not exist.', 43, $this->source);
                })())) {
                    echo ' class="hidden"';
                }
                echo '>

            ';
                // line 45
                $context['entryTypeOptions'] = [];
                // line 46
                echo '            ';
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'getEntryTypes', [], 'method'));
                foreach ($context['_seq'] as $context['_key'] => $context['entryType']) {
                    // line 47
                    echo '                ';
                    $context['entryTypeOptions'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((isset($context['entryTypeOptions']) || array_key_exists('entryTypeOptions', $context) ? $context['entryTypeOptions'] : (function () {
                        throw new RuntimeError('Variable "entryTypeOptions" does not exist.', 47, $this->source);
                    })()), [0 => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['entryType'], 'name', []), 'site'), 'value' => craft\helpers\Template::attribute($this->env, $this->source, $context['entryType'], 'id', [])]]);
                    // line 48
                    echo '            ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['entryType'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 49
                echo '
            ';
                // line 50
                if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['entryTypeOptions']) || array_key_exists('entryTypeOptions', $context) ? $context['entryTypeOptions'] : (function () {
                    throw new RuntimeError('Variable "entryTypeOptions" does not exist.', 50, $this->source);
                })())) == 1)) {
                    // line 51
                    echo '                ';
                    echo craft\helpers\Html::hiddenInput((('sections['.craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', [])).'][entryType]'), (isset($context['entryTypeId']) || array_key_exists('entryTypeId', $context) ? $context['entryTypeId'] : (function () {
                        throw new RuntimeError('Variable "entryTypeId" does not exist.', 51, $this->source);
                    })()));
                    echo '
            ';
                } else {
                    // line 53
                    echo '                ';
                    echo twig_call_macro($macros['forms'], 'macro_selectField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Entry Type', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Which type of entries do you want to create?', 'app'), 'id' => 'entryType', 'name' => (('sections['.craft\helpers\Template::attribute($this->env, $this->source,                     // line 57
                        $context['section'], 'id', [])).'][entryType]'), 'options' =>                     // line 58
(isset($context['entryTypeOptions']) || array_key_exists('entryTypeOptions', $context) ? $context['entryTypeOptions'] : (function () {
    throw new RuntimeError('Variable "entryTypeOptions" does not exist.', 58, $this->source);
})()), 'value' =>                     // line 59
(isset($context['entryTypeId']) || array_key_exists('entryTypeId', $context) ? $context['entryTypeId'] : (function () {
    throw new RuntimeError('Variable "entryTypeId" does not exist.', 59, $this->source);
})()), 'toggle' => true, 'targetPrefix' => (('section'.craft\helpers\Template::attribute($this->env, $this->source,                     // line 61
    $context['section'], 'id', [])).'-type'), ]], 53, $context, $this->getSourceContext());
                    // line 62
                    echo '
            ';
                }
                // line 64
                echo '        </div>
    ';
                $context['loop']['index0']++;
                $context['loop']['index']++;
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    $context['loop']['revindex0']--;
                    $context['loop']['revindex']--;
                    $context['loop']['last'] = $context['loop']['revindex0'] === 0;
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['section'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 66
            echo '
';
        } else {
            // line 68
            echo '
    <p>';
            // line 69
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No sections are available.', 'app'), 'html', null, true);
            echo '</p>

';
        }
        craft\helpers\Template::endProfile('template', '_components/widgets/QuickPost/settings.twig');
    }

    public function getTemplateName()
    {
        return '_components/widgets/QuickPost/settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [219 => 69,  216 => 68,  212 => 66,  197 => 64,  193 => 62,  191 => 61,  190 => 59,  189 => 58,  188 => 57,  186 => 53,  180 => 51,  178 => 50,  175 => 49,  169 => 48,  166 => 47,  161 => 46,  159 => 45,  149 => 43,  146 => 42,  129 => 41,  125 => 39,  123 => 36,  122 => 35,  120 => 30,  114 => 29,  111 => 28,  106 => 27,  104 => 26,  101 => 25,  98 => 24,  93 => 22,  92 => 19,  89 => 18,  84 => 15,  69 => 13,  65 => 12,  61 => 10,  58 => 9,  56 => 8,  53 => 7,  50 => 6,  48 => 5,  45 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
", '_components/widgets/QuickPost/settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/widgets/QuickPost/settings.twig');
    }
}
