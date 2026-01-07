<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/elementindex */
class __TwigTemplate_4dbdae94d7179f306d857dff6f734fa6 extends Template
{
    private $source;

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
    protected function doGetParent(array $context)
    {
        // line 1
        return '_layouts/cp.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_layouts/elementindex');
        // line 3
        $context['elementInstance'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'app', []), 'elements', []), 'createElement', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 3, $this->source);
        })())], 'method');
        // line 4
        $context['title'] ??= craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 4, $this->source);
        })()), 'pluralDisplayName', [], 'method');
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
        })()), 'app', []), 'elementSources', []), 'getSources', [0 => (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 11, $this->source);
        })()), 1 => 'index', 2 => true], 'method');
        // line 13
        $context['showSiteMenu'] = ((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 13, $this->source);
        })()), 'app', []), 'getIsMultiSite', [], 'method')) ? ((($context['showSiteMenu']) ?? ('auto'))) : (false));
        // line 14
        if (((isset($context['showSiteMenu']) || array_key_exists('showSiteMenu', $context) ? $context['showSiteMenu'] : (function () {
            throw new RuntimeError('Variable "showSiteMenu" does not exist.', 14, $this->source);
        })()) == 'auto')) {
            // line 15
            $context['showSiteMenu'] = craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
                throw new RuntimeError('Variable "elementInstance" does not exist.', 15, $this->source);
            })()), 'isLocalized', [], 'method');
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
                    })()), 'app', []), 'sites', []), 'getEditableSites', [], 'method'), function ($__s__) use ($context) {
                        $context['s'] = $__s__;

                        return twig_in_filter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['s']) || array_key_exists('s', $context) ? $context['s'] : (function () {
                            throw new RuntimeError('Variable "s" does not exist.', 21, $this->source);
                        })()), 'id', []), (isset($context['siteIds']) || array_key_exists('siteIds', $context) ? $context['siteIds'] : (function () {
                            throw new RuntimeError('Variable "siteIds" does not exist.', 21, $this->source);
                        })()));
                    });
                } else {
                    // line 23
                    $context['selectableSites'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 23, $this->source);
                    })()), 'app', []), 'sites', []), 'getEditableSites', [], 'method');
                }
            }
            // line 27
            if (! array_key_exists('selectedSite', $context)) {
                // line 28
                if (array_key_exists('selectedSiteId', $context)) {
                    // line 29
                    $context['selectedSite'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 29, $this->source);
                    })()), 'app', []), 'sites', []), 'getSiteById', [0 => (isset($context['selectedSiteId']) || array_key_exists('selectedSiteId', $context) ? $context['selectedSiteId'] : (function () {
                        throw new RuntimeError('Variable "selectedSiteId" does not exist.', 29, $this->source);
                    })())], 'method');
                } elseif ((                // line 30
                    (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                        throw new RuntimeError('Variable "requestedSite" does not exist.', 30, $this->source);
                    })()) && twig_in_filter((isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
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
                    })()))) ? (twig_first($this->env, (isset($context['selectableSites']) || array_key_exists('selectableSites', $context) ? $context['selectableSites'] : (function () {
                        throw new RuntimeError('Variable "selectableSites" does not exist.', 33, $this->source);
                    })()))) : (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
                        throw new RuntimeError('Variable "craft" does not exist.', 33, $this->source);
                    })()), 'app', []), 'sites', []), 'getPrimarySite', [], 'method')));
                }
            }
            // line 37
            $context['crumbs'] = $this->extensions['craft\web\twig\Extension']->unshiftFilter((($context['crumbs']) ?? ([])), ['id' => 'site-crumb', 'icon' => 'world', 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source,             // line 40
                (isset($context['selectedSite']) || array_key_exists('selectedSite', $context) ? $context['selectedSite'] : (function () {
                    throw new RuntimeError('Variable "selectedSite" does not exist.', 40, $this->source);
                })()), 'name', []), 'site'), 'menu' => ['items' => craft\helpers\Cp::siteMenuItems(            // line 42
                    (isset($context['selectableSites']) || array_key_exists('selectableSites', $context) ? $context['selectableSites'] : (function () {
                        throw new RuntimeError('Variable "selectableSites" does not exist.', 42, $this->source);
                    })()), (isset($context['selectedSite']) || array_key_exists('selectedSite', $context) ? $context['selectedSite'] : (function () {
                        throw new RuntimeError('Variable "selectedSite" does not exist.', 42, $this->source);
                    })())), 'label' => $this->extensions['craft\web\twig\Extension']->translateFilter('Select site', 'site')]]);
        }
        // line 48
        $context['canHaveDrafts'] ??= craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 48, $this->source);
        })()), 'hasDrafts', [], 'method');
        // line 105
        craft\helpers\Template::js($this->renderBlock('initJs', $context, $blocks), ['position' => 3]);
        // line 1
        $this->parent = $this->loadTemplate('_layouts/cp.twig', '_layouts/elementindex', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '_layouts/elementindex');
    }

    // line 50
    public function block_sidebar($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'sidebar');
        // line 51
        echo '    ';
        if (! twig_test_empty((isset($context['sources']) || array_key_exists('sources', $context) ? $context['sources'] : (function () {
            throw new RuntimeError('Variable "sources" does not exist.', 51, $this->source);
        })()))) {
            // line 52
            echo '        ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('a', ['class' => 'btn skip-link', 'href' => '#elements', 'html' => $this->extensions['craft\web\twig\Extension']->translateFilter('Skip to {title}', 'app', ['title' =>             // line 55
(isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
    throw new RuntimeError('Variable "title" does not exist.', 55, $this->source);
})()), ])]);
            // line 56
            echo '

        <nav aria-labelledby="source-heading">
            <h2 id="source-heading" class="visually-hidden">';
            // line 59
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Sources', 'app'), 'html', null, true);
            echo '</h2>
            ';
            // line 60
            $this->loadTemplate('_elements/sources', '_layouts/elementindex', 60)->display($context);
            // line 61
            echo '        </nav>

        <div id="source-actions" class="buttons"></div>
    ';
        }
        craft\helpers\Template::endProfile('block', 'sidebar');
    }

    // line 68
    public function block_toolbar($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'toolbar');
        // line 69
        echo '    ';
        $this->loadTemplate('_elements/toolbar', '_layouts/elementindex', 69)->display(twig_array_merge($context, ['showSiteMenu' => false]));
        craft\helpers\Template::endProfile('block', 'toolbar');
    }

    // line 75
    public function block_content($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'content');
        // line 76
        echo '    <div class="main element-index">
        <span class="visually-hidden" role="status" data-status-message></span>
        <a class="skip-link btn" href="#footer">';
        // line 78
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Skip to {title}', 'app', ['title' => $this->extensions['craft\web\twig\Extension']->translateFilter('Footer')]), 'html', null, true);
        echo '</a>
        <div id="elements" class="elements busy">
            <div class="update-spinner spinner spinner-absolute"></div>
        </div>
    </div>
';
        craft\helpers\Template::endProfile('block', 'content');
    }

    // line 86
    public function block_footer($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'footer');
        // line 87
        echo '    ';
        $this->loadTemplate('_elements/footer', '_layouts/elementindex', 87)->display($context);
        craft\helpers\Template::endProfile('block', 'footer');
    }

    // line 91
    public function block_initJs($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'initJs');
        // line 92
        echo "    Craft.elementIndex = Craft.createElementIndex('";
        echo twig_escape_filter($this->env, twig_escape_filter($this->env, (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 92, $this->source);
        })()), 'js'), 'html', null, true);
        echo "', \$('#page-container'), {
        elementTypeName: '";
        // line 93
        echo twig_escape_filter($this->env, twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 93, $this->source);
        })()), 'displayName', [], 'method'), 'js'), 'html', null, true);
        echo "',
        elementTypePluralName: '";
        // line 94
        echo twig_escape_filter($this->env, twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['elementInstance']) || array_key_exists('elementInstance', $context) ? $context['elementInstance'] : (function () {
            throw new RuntimeError('Variable "elementInstance" does not exist.', 94, $this->source);
        })()), 'pluralDisplayName', [], 'method'), 'js'), 'html', null, true);
        echo "',
        context: '";
        // line 95
        echo twig_escape_filter($this->env, (isset($context['context']) || array_key_exists('context', $context) ? $context['context'] : (function () {
            throw new RuntimeError('Variable "context" does not exist.', 95, $this->source);
        })()), 'html', null, true);
        echo "',
        storageKey: 'elementindex.";
        // line 96
        echo twig_escape_filter($this->env, twig_escape_filter($this->env, (isset($context['elementType']) || array_key_exists('elementType', $context) ? $context['elementType'] : (function () {
            throw new RuntimeError('Variable "elementType" does not exist.', 96, $this->source);
        })()), 'js'), 'html', null, true);
        echo "',
        criteria: Craft.defaultIndexCriteria,
        toolbarSelector: '#toolbar',
        defaultSource: ";
        // line 99
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((($context['defaultSource']) ?? (null)));
        echo ',
        defaultSourcePath: ';
        // line 100
        echo $this->extensions['craft\web\twig\Extension']->jsonEncodeFilter((($context['defaultSourcePath']) ?? (null)));
        echo ',
        canHaveDrafts: ';
        // line 101
        echo ((isset($context['canHaveDrafts']) || array_key_exists('canHaveDrafts', $context) ? $context['canHaveDrafts'] : (function () {
            throw new RuntimeError('Variable "canHaveDrafts" does not exist.', 101, $this->source);
        })())) ? ('true') : ('false');
        echo ',
    });
';
        craft\helpers\Template::endProfile('block', 'initJs');
    }

    public function getTemplateName()
    {
        return '_layouts/elementindex';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [220 => 101,  216 => 100,  212 => 99,  206 => 96,  202 => 95,  198 => 94,  194 => 93,  189 => 92,  184 => 91,  178 => 87,  173 => 86,  162 => 78,  158 => 76,  153 => 75,  147 => 69,  142 => 68,  133 => 61,  131 => 60,  127 => 59,  122 => 56,  120 => 55,  118 => 52,  115 => 51,  110 => 50,  104 => 1,  102 => 105,  100 => 48,  97 => 42,  96 => 40,  95 => 37,  91 => 33,  88 => 31,  86 => 30,  84 => 29,  82 => 28,  80 => 27,  76 => 23,  73 => 21,  71 => 20,  69 => 19,  67 => 18,  64 => 15,  62 => 14,  60 => 13,  58 => 11,  55 => 8,  53 => 7,  51 => 5,  49 => 4,  47 => 3,  39 => 1];
    }

    public function getSourceContext()
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
{% if showSiteMenu == 'auto' %}
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
            label: 'Select site'|t('site')
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
", '_layouts/elementindex', '/Users/brianhanson/Development/craft5/src/templates/_layouts/elementindex.twig');
    }
}
