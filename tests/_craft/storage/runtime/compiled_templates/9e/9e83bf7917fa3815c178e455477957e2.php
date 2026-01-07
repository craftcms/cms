<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* _layouts/elementindex */
class __TwigTemplate_1f85a75f22446b1e49c889f1c29a022a extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'sidebar' => $this->block_sidebar(...),
            'toolbar' => $this->block_toolbar(...),
            'content' => $this->block_content(...),
            'footer' => $this->block_footer(...),
            'initJs' => $this->block_initJs(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return '_layouts/cp.twig';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/elementindex');
        // line 3
        $context['elementInstance'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'app', [], 'any', false, false, false, 3), 'elements', [], 'any', false, false, false, 3), 'createElement', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 3, $this->source);
        })())], 'method', false, false, false, 3);
        // line 4
        $context['title'] ??= craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 4, $this->source);
        })()), 'pluralDisplayName', [], 'method', false, false, false, 4);
        // line 5
        $context['context'] = 'index';
        // line 7
        if (! (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 7, $this->source);
        })())) {
            // line 8
            throw new yii\web\NotFoundHttpException;
        }
        // line 11
        $context['sources'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 11, $this->source);
        })()), 'app', [], 'any', false, false, false, 11), 'elementSources', [], 'any', false, false, false, 11), 'getSources', [(isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 11, $this->source);
        })()), 'index', true], 'method', false, false, false, 11);
        // line 13
        $context['showSiteMenu'] = ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 13, $this->source);
        })()), 'app', [], 'any', false, false, false, 13), 'getIsMultiSite', [], 'method', false, false, false, 13)) ? ((($context['showSiteMenu']) ?? ('auto'))) : (false));
        // line 14
        if (((isset($context['showSiteMenu']) || array_key_exists('showSiteMenu', $context) ? $context['showSiteMenu'] : (function () {
            throw new RuntimeError('Variable "showSiteMenu" does not exist.', 14, $this->source);
        })()) === 'auto')) {
            // line 15
            $context['showSiteMenu'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
                throw new RuntimeError('Variable "elementInstance" does not exist.', 15, $this->source);
            })()), 'isLocalized', [], 'method', false, false, false, 15);
        }
        // line 18
        if ((isset($context['showSiteMenu']) || array_key_exists('showSiteMenu', $context) ? $context['showSiteMenu'] : (function () {
            throw new RuntimeError('Variable "showSiteMenu" does not exist.', 18, $this->source);
        })())) {
            // line 19
            if (! array_key_exists('selectableSites', $context)) {
                // line 20
                if (array_key_exists('siteIds', $context)) {
                    // line 21
                    $context['selectableSites'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 21, $this->source);
                    })()), 'app', [], 'any', false, false, false, 21), 'sites', [], 'any', false, false, false, 21), 'getEditableSites', [], 'method', false, false, false, 21), function ($__s__) use ($context) {
                        $context['s'] = $__s__;

                        return CoreExtension::inFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['s']) || array_key_exists('s', $context) ? $context['s'] : (function () {
                            throw new RuntimeError('Variable "s" does not exist.', 21, $this->source);
                        })()), 'id', [], 'any', false, false, false, 21), (isset($context['siteIds']) || array_key_exists('siteIds', $context) ? $context['siteIds'] : (function () {
                            throw new RuntimeError('Variable "siteIds" does not exist.', 21, $this->source);
                        })()));
                    });
                } else {
                    // line 23
                    $context['selectableSites'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 23, $this->source);
                    })()), 'app', [], 'any', false, false, false, 23), 'sites', [], 'any', false, false, false, 23), 'getEditableSites', [], 'method', false, false, false, 23);
                }
            }
            // line 27
            if (! array_key_exists('selectedSite', $context)) {
                // line 28
                if (array_key_exists('selectedSiteId', $context)) {
                    // line 29
                    $context['selectedSite'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 29, $this->source);
                    })()), 'app', [], 'any', false, false, false, 29), 'sites', [], 'any', false, false, false, 29), 'getSiteById', [(isset($context['selectedSiteId']) || array_key_exists('selectedSiteId', $context) ? $context['selectedSiteId'] : (function () {
                        throw new RuntimeError('Variable "selectedSiteId" does not exist.', 29, $this->source);
                    })())], 'method', false, false, false, 29);
                } elseif ((                // line 30
                    (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                        throw new RuntimeError('Variable "requestedSite" does not exist.', 30, $this->source);
                    })()) && CoreExtension::inFilter((isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                        throw new RuntimeError('Variable "requestedSite" does not exist.', 30, $this->source);
                    })()), (isset($context['selectableSites']) || array_key_exists('selectableSites', $context) ? $context['selectableSites'] : (function () {
                        throw new RuntimeError('Variable "selectableSites" does not exist.', 30, $this->source);
                    })())))) {
                    // line 31
                    $context['selectedSite'] = (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                        throw new RuntimeError('Variable "requestedSite" does not exist.', 31, $this->source);
                    })());
                } else {
                    // line 33
                    $context['selectedSite'] = (($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['selectableSites']) || array_key_exists('selectableSites', $context) ? $context['selectableSites'] : (function () {
                        throw new RuntimeError('Variable "selectableSites" does not exist.', 33, $this->source);
                    })()))) ? (Twig\Extension\CoreExtension::first($this->env->getCharset(), (isset($context['selectableSites']) || array_key_exists('selectableSites', $context) ? $context['selectableSites'] : (function () {
                        throw new RuntimeError('Variable "selectableSites" does not exist.', 33, $this->source);
                    })()))) : (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
                    })()), 'app', [], 'any', false, false, false, 33), 'sites', [], 'any', false, false, false, 33), 'getPrimarySite', [], 'method', false, false, false, 33)));
                }
            }
            // line 37
            $context['crumbs'] = $this->extensions['craft\web\twig\Extension']->unshiftFilter((($context['crumbs']) ?? ([])), ['id' => 'site-crumb', 'icon' => 'world', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                (isset($context['selectedSite']) || array_key_exists('selectedSite', $context) ? $context['selectedSite'] : (function () {
                    throw new RuntimeError('Variable "selectedSite" does not exist.', 40, $this->source);
                })()), 'name', [], 'any', false, false, false, 40), 'site'), 'menu' => ['items' => craft\helpers\Cp::siteMenuItems(            // line 42
                    (isset($context['selectableSites']) || array_key_exists('selectableSites', $context) ? $context['selectableSites'] : (function () {
                        throw new RuntimeError('Variable "selectableSites" does not exist.', 42, $this->source);
                    })()), (isset($context['selectedSite']) || array_key_exists('selectedSite', $context) ? $context['selectedSite'] : (function () {
                        throw new RuntimeError('Variable "selectedSite" does not exist.', 42, $this->source);
                    })())), 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Select site', 'app')]]);
        }
        // line 48
        $context['canHaveDrafts'] ??= craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 48, $this->source);
        })()), 'hasDrafts', [], 'method', false, false, false, 48);
        // line 105
        craft\helpers\Template::js($this->unwrap()->renderBlock('initJs', $context, $blocks), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp.twig', '_layouts/elementindex', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/elementindex');
    }

    // line 50
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 51
        yield '    ';
        if (! Twig\Extension\CoreExtension::testEmpty((isset($context['sources']) || array_key_exists('sources', $context) ? $context['sources'] : (function () {
            throw new RuntimeError('Variable "sources" does not exist.', 51, $this->source);
        })()))) {
            // line 52
            yield '        ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => 'btn skip-link', 'href' => '#elements', 'html' => $this->extensions['craft\web\twig\Extension']->translateFilter('Skip to {title}', 'app', ['title' =>             // line 55
(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
    throw new RuntimeError('Variable "title" does not exist.', 55, $this->source);
})())])]);
            // line 56
            yield '

        <nav aria-labelledby="source-heading">
            <h2 id="source-heading" class="visually-hidden">';
            // line 59
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Sources', 'app'), 'html', null, true);
            yield '</h2>
            ';
            // line 60
            yield from $this->loadTemplate('_elements/sources', '_layouts/elementindex', 60)->unwrap()->yield($context);
            // line 61
            yield '        </nav>

        <div id="source-actions" class="buttons"></div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'sidebar');
        yield from [];
    }

    // line 68
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_toolbar(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'toolbar');
        // line 69
        yield '    ';
        yield from $this->loadTemplate('_elements/toolbar', '_layouts/elementindex', 69)->unwrap()->yield(CoreExtension::merge($context, ['showSiteMenu' => false]));
        craft\helpers\Template::endProfile('block', 'toolbar');
        yield from [];
    }

    // line 75
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 76
        yield '    <div class="main element-index">
        <span class="visually-hidden" role="status" data-status-message></span>
        <a class="skip-link btn" href="#footer">';
        // line 78
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Skip to {title}', 'app', ['title' => $this->extensions['craft\web\twig\Extension']->translateFilter('Footer')]), 'html', null, true);
        yield '</a>
        <div id="elements" class="elements busy">
            <div class="update-spinner spinner spinner-absolute"></div>
        </div>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
        yield from [];
    }

    // line 86
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_footer(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'footer');
        // line 87
        yield '    ';
        yield from $this->loadTemplate('_elements/footer', '_layouts/elementindex', 87)->unwrap()->yield($context);
        craft\helpers\Template::endProfile('block', 'footer');
        yield from [];
    }

    // line 91
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_initJs(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'initJs');
        // line 92
        yield "    Craft.elementIndex = Craft.createElementIndex('";
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 92, $this->source);
        })()), 'js'), 'html', null, true);
        yield "', \$('#page-container'), {
        elementTypeName: '";
        // line 93
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 93, $this->source);
        })()), 'displayName', [], 'method', false, false, false, 93), 'js'), 'html', null, true);
        yield "',
        elementTypePluralName: '";
        // line 94
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 94, $this->source);
        })()), 'pluralDisplayName', [], 'method', false, false, false, 94), 'js'), 'html', null, true);
        yield "',
        context: '";
        // line 95
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['context']) || array_key_exists('context', $context) ? $context['context'] : (function () {
            throw new RuntimeError('Variable "context" does not exist.', 95, $this->source);
        })()), 'html', null, true);
        yield "',
        storageKey: 'elementindex.";
        // line 96
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 96, $this->source);
        })()), 'js'), 'html', null, true);
        yield "',
        criteria: Craft.defaultIndexCriteria,
        toolbarSelector: '#toolbar',
        defaultSource: ";
        // line 99
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((($context['defaultSource']) ?? (null)));
        yield ',
        defaultSourcePath: ';
        // line 100
        yield $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((($context['defaultSourcePath']) ?? (null)));
        yield ',
        canHaveDrafts: ';
        // line 101
        yield ((isset($context['canHaveDrafts']) || array_key_exists('canHaveDrafts', $context) ? $context['canHaveDrafts'] : (function () {
            throw new RuntimeError('Variable "canHaveDrafts" does not exist.', 101, $this->source);
        })())) ? ('true') : ('false');
        yield ',
    });
';
        craft\helpers\Template::endProfile('block', 'initJs');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/elementindex';
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
        return [244 => 101,  240 => 100,  236 => 99,  230 => 96,  226 => 95,  222 => 94,  218 => 93,  213 => 92,  205 => 91,  198 => 87,  190 => 86,  178 => 78,  174 => 76,  166 => 75,  159 => 69,  151 => 68,  141 => 61,  139 => 60,  135 => 59,  130 => 56,  128 => 55,  126 => 52,  123 => 51,  115 => 50,  109 => 1,  107 => 105,  105 => 48,  102 => 42,  101 => 40,  100 => 37,  96 => 33,  93 => 31,  91 => 30,  89 => 29,  87 => 28,  85 => 27,  81 => 23,  78 => 21,  76 => 20,  74 => 19,  72 => 18,  69 => 15,  67 => 14,  65 => 13,  63 => 11,  60 => 8,  58 => 7,  56 => 5,  54 => 4,  52 => 3,  44 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends '_layouts/cp.twig' %}

{% set elementInstance = craft.app.elements.createElement(elementType) %}
{% set title = title ?? elementInstance.pluralDisplayName() %}
{% set context = 'index' %}

{% if not elementInstance %}
    {% exit 404 %}
{% endif %}

{% set sources = craft.app.elementSources.getSources(elementType, 'index', true) %}

{% set showSiteMenu = (craft.app.getIsMultiSite() ? (showSiteMenu ?? 'auto') : false) %}
{% if showSiteMenu is same as ('auto') %}
    {% set showSiteMenu = elementInstance.isLocalized() %}
{% endif %}

{% if showSiteMenu %}
    {% if selectableSites is not defined %}
        {% if siteIds is defined %}
            {% set selectableSites = craft.app.sites.getEditableSites()|filter(s => s.id in siteIds) %}
        {% else %}
            {% set selectableSites = craft.app.sites.getEditableSites() %}
        {% endif %}
    {% endif %}

    {% if selectedSite is not defined %}
        {% if selectedSiteId is defined %}
            {% set selectedSite = craft.app.sites.getSiteById(selectedSiteId) %}
        {% elseif requestedSite and requestedSite in selectableSites %}
            {% set selectedSite = requestedSite %}
        {% else %}
            {% set selectedSite = selectableSites|length ? selectableSites|first : craft.app.sites.getPrimarySite() %}
        {% endif %}
    {% endif %}

    {% set crumbs = (crumbs ?? [])|unshift({
        id: 'site-crumb',
        icon: 'world',
        label: selectedSite.name|t('site'),
        menu: {
            items: siteMenuItems(selectableSites, selectedSite),
            label: 'Select site'|t('app')
        }
    }) %}
{% endif %}

{% set canHaveDrafts = canHaveDrafts ?? elementInstance.hasDrafts() %}

{% block sidebar %}
    {% if sources is not empty %}
        {{ tag('a', {
            class: 'btn skip-link',
            href: '#elements',
            html: 'Skip to {title}'|t('app', {title: title}),
        }) }}

        <nav aria-labelledby=\"source-heading\">
            <h2 id=\"source-heading\" class=\"visually-hidden\">{{ 'Sources'|t('app') }}</h2>
            {% include \"_elements/sources\" %}
        </nav>

        <div id=\"source-actions\" class=\"buttons\"></div>
    {% endif %}
{% endblock %}


{% block toolbar %}
    {% include '_elements/toolbar' with {
        showSiteMenu: false,
    } %}
{% endblock %}


{% block content %}
    <div class=\"main element-index\">
        <span class=\"visually-hidden\" role=\"status\" data-status-message></span>
        <a class=\"skip-link btn\" href=\"#footer\">{{ 'Skip to {title}'|t('app', { title: 'Footer'|t }) }}</a>
        <div id=\"elements\" class=\"elements busy\">
            <div class=\"update-spinner spinner spinner-absolute\"></div>
        </div>
    </div>
{% endblock %}


{% block footer %}
    {% include '_elements/footer' %}
{% endblock %}


{% block initJs %}
    Craft.elementIndex = Craft.createElementIndex('{{ elementType|e(\"js\") }}', \$('#page-container'), {
        elementTypeName: '{{ elementInstance.displayName()|e(\"js\") }}',
        elementTypePluralName: '{{ elementInstance.pluralDisplayName()|e(\"js\") }}',
        context: '{{ context }}',
        storageKey: 'elementindex.{{ elementType|e(\"js\") }}',
        criteria: Craft.defaultIndexCriteria,
        toolbarSelector: '#toolbar',
        defaultSource: {{ (defaultSource ?? null)|json_encode|raw }},
        defaultSourcePath: {{ (defaultSourcePath ?? null)|json_encode|raw }},
        canHaveDrafts: {{ canHaveDrafts ? 'true' : 'false' }},
    });
{% endblock %}

{% js block('initJs') %}
", '_layouts/elementindex', '/tmp/packages/craft5/src/templates/_layouts/elementindex.twig');
    }
}
