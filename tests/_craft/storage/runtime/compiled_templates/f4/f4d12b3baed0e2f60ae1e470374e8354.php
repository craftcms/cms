<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _elements/toolbar */
class __TwigTemplate_29d035da0e3145ee2ff7a0242d884a2c extends Template
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
        craft\helpers\Template::beginProfile('template', '_elements/toolbar');
        // line 1
        $macros['__internal_parse_1'] = $this->macros['__internal_parse_1'] = $this->loadTemplate('_includes/forms', '_elements/toolbar', 1)->unwrap();
        // line 3
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 3, $this->source);
        })()), 'registerTranslations', ['app', ['Sort by {attribute}', 'Score', 'Structure', 'Display in a table', 'Display hierarchically', 'Display as thumbnails']], 'method', false, false, false, 3);
        // line 11
        yield '
';
        // line 12
        $context['elementInstance'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 12, $this->source);
        })()), 'app', [], 'any', false, false, false, 12), 'elements', [], 'any', false, false, false, 12), 'createElement', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 12, $this->source);
        })())], 'method', false, false, false, 12);
        // line 13
        $context['context'] = ((array_key_exists('context', $context)) ? ((isset($context['context']) || array_key_exists('context', $context) ? $context['context'] : (function () {
            throw new RuntimeError('Variable "context" does not exist.', 13, $this->source);
        })())) : ('index'));
        // line 14
        $context['isAdministrative'] = CoreExtension::inFilter((isset($context['context']) || array_key_exists('context', $context) ? $context['context'] : (function () {
            throw new RuntimeError('Variable "context" does not exist.', 14, $this->source);
        })()), ['index', 'embedded-index']);
        // line 15
        $context['showStatusMenu'] = (((array_key_exists('showStatusMenu', $context) && ((isset($context['showStatusMenu']) || array_key_exists('showStatusMenu', $context) ? $context['showStatusMenu'] : (function () {
            throw new RuntimeError('Variable "showStatusMenu" does not exist.', 15, $this->source);
        })()) != 'auto'))) ? ((isset($context['showStatusMenu']) || array_key_exists('showStatusMenu', $context) ? $context['showStatusMenu'] : (function () {
            throw new RuntimeError('Variable "showStatusMenu" does not exist.', 15, $this->source);
        })())) : (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 15, $this->source);
        })()), 'hasStatuses', [], 'method', false, false, false, 15)));
        // line 16
        $context['showSiteMenu'] = ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 16, $this->source);
        })()), 'app', [], 'any', false, false, false, 16), 'getIsMultiSite', [], 'method', false, false, false, 16)) ? ((($context['showSiteMenu']) ?? ('auto'))) : (false));
        // line 17
        if (((isset($context['showSiteMenu']) || array_key_exists('showSiteMenu', $context) ? $context['showSiteMenu'] : (function () {
            throw new RuntimeError('Variable "showSiteMenu" does not exist.', 17, $this->source);
        })()) === 'auto')) {
            // line 18
            yield '    ';
            $context['showSiteMenu'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
                throw new RuntimeError('Variable "elementInstance" does not exist.', 18, $this->source);
            })()), 'isLocalized', [], 'method', false, false, false, 18);
        }
        // line 20
        $context['idPrefix'] = (('elementtoolbar'.Twig\Extension\CoreExtension::random($this->env->getCharset())).'-');
        // line 21
        yield '
';
        // line 22
        if (((isset($context['showStatusMenu']) || array_key_exists('showStatusMenu', $context) ? $context['showStatusMenu'] : (function () {
            throw new RuntimeError('Variable "showStatusMenu" does not exist.', 22, $this->source);
        })()) || (isset($context['isAdministrative']) || array_key_exists('isAdministrative', $context) ? $context['isAdministrative'] : (function () {
            throw new RuntimeError('Variable "isAdministrative" does not exist.', 22, $this->source);
        })()))) {
            // line 23
            yield '    <div>
        <label id="';
            // line 24
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 24, $this->source);
            })()), 'html', null, true);
            yield 'status-label" class="visually-hidden">';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Status', 'app'), 'html', null, true);
            yield '</label>
        <button id="';
            // line 25
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 25, $this->source);
            })()), 'html', null, true);
            yield 'status-button" aria-labelledby="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['idPrefix']) || array_key_exists('idPrefix', $context) ? $context['idPrefix'] : (function () {
                throw new RuntimeError('Variable "idPrefix" does not exist.', 25, $this->source);
            })()), 'html', null, true);
            yield 'status-label" type="button" class="btn menubtn statusmenubtn"><span class="status all"></span>';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('All', 'app'), 'html', null, true);
            yield '</button>
        <div class="menu">
            <ul class="padded">
                <li><a data-status="" class="sel"><span class="status all"></span>';
            // line 28
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('All', 'app'), 'html', null, true);
            yield '</a></li>
                ';
            // line 29
            if ((isset($context['showStatusMenu']) || array_key_exists('showStatusMenu', $context) ? $context['showStatusMenu'] : (function () {
                throw new RuntimeError('Variable "showStatusMenu" does not exist.', 29, $this->source);
            })())) {
                // line 30
                yield '                    ';
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
                    throw new RuntimeError('Variable "elementInstance" does not exist.', 30, $this->source);
                })()), 'statuses', [], 'method', false, false, false, 30));
                foreach ($context['_seq'] as $context['status'] => $context['info']) {
                    // line 31
                    yield '                        ';
                    $context['label'] = (((craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'label', [], 'any', true, true, false, 31) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'label', [], 'any', false, false, false, 31) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'label', [], 'any', false, false, false, 31)) : ($context['info']));
                    // line 32
                    yield '                        ';
                    $context['color'] = (((craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'color', [], 'any', true, true, false, 32) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'color', [], 'any', false, false, false, 32) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['info'], 'color', [], 'any', false, false, false, 32)) : (''));
                    // line 33
                    yield '                        ';
                    $context['color'] = (($this->env->getTest('instance of')->getCallable()((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                        throw new RuntimeError('Variable "color" does not exist.', 33, $this->source);
                    })()), 'craft\\enums\\Color')) ? (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                        throw new RuntimeError('Variable "color" does not exist.', 33, $this->source);
                    })()), 'value', [], 'any', false, false, false, 33)) : ((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                        throw new RuntimeError('Variable "color" does not exist.', 33, $this->source);
                    })())));
                    // line 34
                    yield '                        <li><a data-status="';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['status'], 'html', null, true);
                    yield '"><span class="status ';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($context['status'], 'html', null, true);
                    yield ' ';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['color']) || array_key_exists('color', $context) ? $context['color'] : (function () {
                        throw new RuntimeError('Variable "color" does not exist.', 34, $this->source);
                    })()), 'html', null, true);
                    yield '"></span>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['label']) || array_key_exists('label', $context) ? $context['label'] : (function () {
                        throw new RuntimeError('Variable "label" does not exist.', 34, $this->source);
                    })()), 'html', null, true);
                    yield '</a></li>
                    ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['status'], $context['info'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 36
                yield '                ';
            }
            // line 37
            yield '            </ul>
            ';
            // line 38
            if ((isset($context['isAdministrative']) || array_key_exists('isAdministrative', $context) ? $context['isAdministrative'] : (function () {
                throw new RuntimeError('Variable "isAdministrative" does not exist.', 38, $this->source);
            })())) {
                // line 39
                yield '                ';
                if ((isset($context['showStatusMenu']) || array_key_exists('showStatusMenu', $context) ? $context['showStatusMenu'] : (function () {
                    throw new RuntimeError('Variable "showStatusMenu" does not exist.', 39, $this->source);
                })())) {
                    yield '<hr class="padded" role="presentation">';
                }
                // line 40
                yield '                <ul class="padded">
                    ';
                // line 41
                if ((($context['canHaveDrafts']) ?? (false))) {
                    // line 42
                    yield '                        <li><a data-drafts><span class="icon" data-icon="draft" aria-hidden="true"></span>';
                    yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Drafts', 'app'), 'html', null, true);
                    yield '</a></li>
                    ';
                }
                // line 44
                yield '                    <li><a data-trashed><span class="icon" data-icon="trash" aria-hidden="true"></span>';
                yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Trashed', 'app'), 'html', null, true);
                yield '</a></li>
                </ul>
            ';
            }
            // line 47
            yield '        </div>
    </div>
';
        }
        // line 50
        if ((isset($context['showSiteMenu']) || array_key_exists('showSiteMenu', $context) ? $context['showSiteMenu'] : (function () {
            throw new RuntimeError('Variable "showSiteMenu" does not exist.', 50, $this->source);
        })())) {
            // line 51
            yield '    ';
            yield from $this->loadTemplate('_elements/sitemenu', '_elements/toolbar', 51)->unwrap()->yield($context);
        }
        // line 53
        yield '<div class="search-container flex-grow texticon has-filter-btn">
    <span class="texticon-icon search icon" aria-hidden="true"></span>
    ';
        // line 55
        yield CoreExtension::callMacro($macros['__internal_parse_1'], 'macro_text', [['class' => 'clearable', 'placeholder' => $this->extensions['craft\web\twig\Extension']->translateFilter('Search', 'app'), 'value' => craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,         // line 58
            (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                throw new RuntimeError('Variable "craft" does not exist.', 58, $this->source);
            })()), 'app', [], 'any', false, false, false, 58), 'request', [], 'any', false, false, false, 58), 'getParam', ['search'], 'method', false, false, false, 58), 'inputAttributes' => ['aria' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Search', 'app')]]]], 55, $context, $this->getSourceContext());
        // line 64
        yield '
    ';
        // line 65
        yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['role' => 'button', 'class' => 'clear-btn hidden', 'title' => $this->extensions['craft\web\twig\Extension']->translateFilter('Clear search', 'app'), 'aria' => ['label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Clear search', 'app')]]);
        // line 72
        yield '
    <button type="button" class="filter-btn" title="';
        // line 73
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Filter results', 'app'), 'html', null, true);
        yield '" aria-label="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Filter results', 'app'), 'html', null, true);
        yield '" aria-expanded="false"></button>
</div>
';
        craft\helpers\Template::endProfile('template', '_elements/toolbar');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_elements/toolbar';
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
        return [184 => 73,  181 => 72,  179 => 65,  176 => 64,  174 => 58,  173 => 55,  169 => 53,  165 => 51,  163 => 50,  158 => 47,  151 => 44,  145 => 42,  143 => 41,  140 => 40,  135 => 39,  133 => 38,  130 => 37,  127 => 36,  112 => 34,  109 => 33,  106 => 32,  103 => 31,  98 => 30,  96 => 29,  92 => 28,  82 => 25,  76 => 24,  73 => 23,  71 => 22,  68 => 21,  66 => 20,  62 => 18,  60 => 17,  58 => 16,  56 => 15,  54 => 14,  52 => 13,  50 => 12,  47 => 11,  45 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% from \"_includes/forms\" import text -%}

{% do view.registerTranslations('app', [
    \"Sort by {attribute}\",
    \"Score\",
    \"Structure\",
    \"Display in a table\",
    \"Display hierarchically\",
    \"Display as thumbnails\",
]) %}

{% set elementInstance = craft.app.elements.createElement(elementType) %}
{% set context = context is defined ? context : 'index' %}
{% set isAdministrative = context in ['index', 'embedded-index'] %}
{% set showStatusMenu = (showStatusMenu is defined and showStatusMenu != 'auto' ? showStatusMenu : elementInstance.hasStatuses()) %}
{% set showSiteMenu = (craft.app.getIsMultiSite() ? (showSiteMenu ?? 'auto') : false) %}
{% if showSiteMenu is same as ('auto') %}
    {% set showSiteMenu = elementInstance.isLocalized() %}
{% endif %}
{% set idPrefix = \"elementtoolbar#{random()}-\" %}

{% if showStatusMenu or isAdministrative %}
    <div>
        <label id=\"{{ idPrefix }}status-label\" class=\"visually-hidden\">{{ \"Status\"|t('app') }}</label>
        <button id=\"{{ idPrefix }}status-button\" aria-labelledby=\"{{ idPrefix }}status-label\" type=\"button\" class=\"btn menubtn statusmenubtn\"><span class=\"status all\"></span>{{ \"All\"|t('app') }}</button>
        <div class=\"menu\">
            <ul class=\"padded\">
                <li><a data-status=\"\" class=\"sel\"><span class=\"status all\"></span>{{ \"All\"|t('app') }}</a></li>
                {% if showStatusMenu %}
                    {% for status, info in elementInstance.statuses() %}
                        {% set label = info.label ?? info %}
                        {% set color = info.color ?? '' %}
                        {% set color = color is instance of ('craft\\\\enums\\\\Color') ? color.value : color %}
                        <li><a data-status=\"{{ status }}\"><span class=\"status {{ status }} {{ color }}\"></span>{{ label }}</a></li>
                    {% endfor %}
                {% endif %}
            </ul>
            {% if isAdministrative %}
                {% if showStatusMenu %}<hr class=\"padded\" role=\"presentation\">{% endif %}
                <ul class=\"padded\">
                    {% if canHaveDrafts ?? false %}
                        <li><a data-drafts><span class=\"icon\" data-icon=\"draft\" aria-hidden=\"true\"></span>{{ 'Drafts'|t('app') }}</a></li>
                    {% endif %}
                    <li><a data-trashed><span class=\"icon\" data-icon=\"trash\" aria-hidden=\"true\"></span>{{ \"Trashed\"|t('app') }}</a></li>
                </ul>
            {% endif %}
        </div>
    </div>
{% endif %}
{% if showSiteMenu %}
    {% include \"_elements/sitemenu\" %}
{% endif %}
<div class=\"search-container flex-grow texticon has-filter-btn\">
    <span class=\"texticon-icon search icon\" aria-hidden=\"true\"></span>
    {{ text({
        class: 'clearable',
        placeholder: \"Search\"|t('app'),
        value: craft.app.request.getParam('search'),
        inputAttributes: {
            aria: {
                label: 'Search'|t('app'),
            },
        },
    }) }}
    {{ tag('button', {
        role: 'button',
        class: 'clear-btn hidden',
        title: 'Clear search'|t('app'),
        aria: {
            label: 'Clear search'|t('app'),
        },
    }) }}
    <button type=\"button\" class=\"filter-btn\" title=\"{{ 'Filter results'|t('app') }}\" aria-label=\"{{ 'Filter results'|t('app') }}\" aria-expanded=\"false\"></button>
</div>
", '_elements/toolbar', '/tmp/packages/craft5/src/templates/_elements/toolbar.twig');
    }
}
