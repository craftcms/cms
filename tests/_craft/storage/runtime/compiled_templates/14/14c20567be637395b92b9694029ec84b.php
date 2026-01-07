<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _components/widgets/RecentEntries/settings.twig */
class __TwigTemplate_51f884ef0b53a42d6efcda849a88c7f7 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/widgets/RecentEntries/settings.twig');
        // line 1
        $macros['forms'] = $this->macros['forms'] = $this->loadTemplate('_includes/forms', '_components/widgets/RecentEntries/settings.twig', 1)->unwrap();
        // line 2
        echo '
';
        // line 3
        if (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'app', []), 'getIsMultiSite', [], 'method')) {
            // line 4
            echo '    ';
            $context['editableSites'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
            })()), 'app', []), 'sites', []), 'getEditableSites', [], 'method');
            // line 5
            echo '
    ';
            // line 6
            if (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                throw new RuntimeError('Variable "editableSites" does not exist.', 6, $this->source);
            })())) > 1)) {
                // line 7
                echo '        ';
                ob_start();
                // line 8
                echo '            <div class="select">
                <select id="site-id" name="siteId">
                    ';
                // line 10
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable((isset($context['editableSites']) || array_key_exists('editableSites', $context) ? $context['editableSites'] : (function () {
                    throw new RuntimeError('Variable "editableSites" does not exist.', 10, $this->source);
                })()));
                foreach ($context['_seq'] as $context['_key'] => $context['site']) {
                    // line 11
                    echo '                        <option value="';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', []), 'html', null, true);
                    echo '"';
                    if ((craft\helpers\Template::attribute($this->env, $this->source, $context['site'], 'id', []) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                        throw new RuntimeError('Variable "widget" does not exist.', 11, $this->source);
                    })()), 'siteId', []))) {
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
                // line 13
                echo '                </select>
            </div>
        ';
                $context['siteInput'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
                // line 16
                echo '
        ';
                // line 17
                echo twig_call_macro($macros['forms'], 'macro_field', [['id' => 'site-id', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Site', 'app')],                 // line 20
                    (isset($context['siteInput']) || array_key_exists('siteInput', $context) ? $context['siteInput'] : (function () {
                        throw new RuntimeError('Variable "siteInput" does not exist.', 20, $this->source);
                    })()), ], 17, $context, $this->getSourceContext());
                echo '
    ';
            }
        }
        // line 23
        echo '
';
        // line 24
        ob_start();
        // line 25
        echo '    <div class="select">
        <select id="section" name="section">
            <option value="*">';
        // line 27
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('All', 'app'), 'html', null, true);
        echo '</option>
            ';
        // line 28
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 28, $this->source);
        })()), 'app', []), 'entries', []), 'getAllSections', [], 'method'));
        foreach ($context['_seq'] as $context['_key'] => $context['section']) {
            // line 29
            echo '                ';
            if ((craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'type', []) != 'single')) {
                // line 30
                echo '                    <option value="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', []), 'html', null, true);
                echo '"';
                if ((craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'id', []) == craft\helpers\Template::attribute($this->env, $this->source, (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 30, $this->source);
                })()), 'section', []))) {
                    echo ' selected';
                }
                echo '>';
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, $context['section'], 'name', []), 'site'), 'html', null, true);
                echo '</option>
                ';
            }
            // line 32
            echo '            ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['section'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 33
        echo '        </select>
    </div>
';
        $context['sectionInput'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 36
        echo '
';
        // line 37
        echo twig_call_macro($macros['forms'], 'macro_field', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Section', 'app'), 'instructions' => $this->extensions['craft\web\twig\Extension']->translateFilter('Which section do you want to pull recent entries from?', 'app'), 'id' => 'section'],         // line 41
            (isset($context['sectionInput']) || array_key_exists('sectionInput', $context) ? $context['sectionInput'] : (function () {
                throw new RuntimeError('Variable "sectionInput" does not exist.', 41, $this->source);
            })()), ], 37, $context, $this->getSourceContext());
        echo '

';
        // line 43
        echo twig_call_macro($macros['forms'], 'macro_textField', [['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Limit', 'app'), 'id' => 'limit', 'name' => 'limit', 'value' => craft\helpers\Template::attribute($this->env, $this->source,         // line 47
            (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                throw new RuntimeError('Variable "widget" does not exist.', 47, $this->source);
            })()), 'limit', []), 'size' => 2, 'errors' => craft\helpers\Template::attribute($this->env, $this->source,         // line 49
                (isset($context['widget']) || array_key_exists('widget', $context) ? $context['widget'] : (function () {
                    throw new RuntimeError('Variable "widget" does not exist.', 49, $this->source);
                })()), 'getErrors', [0 => 'limit'], 'method')]], 43, $context, $this->getSourceContext());
        // line 50
        echo '
';
        craft\helpers\Template::endProfile('template', '_components/widgets/RecentEntries/settings.twig');
    }

    public function getTemplateName()
    {
        return '_components/widgets/RecentEntries/settings.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [150 => 50,  148 => 49,  147 => 47,  146 => 43,  141 => 41,  140 => 37,  137 => 36,  132 => 33,  126 => 32,  114 => 30,  111 => 29,  107 => 28,  103 => 27,  99 => 25,  97 => 24,  94 => 23,  88 => 20,  87 => 17,  84 => 16,  79 => 13,  64 => 11,  60 => 10,  56 => 8,  53 => 7,  51 => 6,  48 => 5,  45 => 4,  43 => 3,  40 => 2,  38 => 1];
    }

    public function getSourceContext()
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
", '_components/widgets/RecentEntries/settings.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/widgets/RecentEntries/settings.twig');
    }
}
