<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template;

/* _layouts/components/global-sidebar */
class __TwigTemplate_406fcfeed59dff57a69942c6f7a22fc6 extends Template
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
        $macros['_self'] = $this->macros['_self'] = $this;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_layouts/components/global-sidebar');
        // line 53
        echo '
<craft-global-sidebar>
    <header id="global-sidebar" class="global-sidebar">
        <div class="global-sidebar__header">
            ';
        // line 57
        $this->loadTemplate('_layouts/components/system-info', '_layouts/components/global-sidebar', 57)->display($context);
        // line 58
        echo '        </div>

        <div class="global-sidebar__nav">

            <nav id="nav" class="global-nav" aria-label="';
        // line 62
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Primary', 'app'), 'html', null, true);
        echo '">
                <ul>
                    ';
        // line 64
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 64, $this->source);
        })()), 'cp', []), 'nav', [], 'method'));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 65
            echo '                        ';
            $context['itemAttributes'] = ['id' => craft\helpers\Template::attribute($this->env, $this->source,             // line 66
                $context['item'], 'id', []), 'class' => [0 => 'nav-item', 1 => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 69
                    $context['item'], 'sel', [])) ? ('sel') : ('')), 2 => ((craft\helpers\Template::attribute($this->env, $this->source,             // line 70
                        $context['item'], 'subnav', [])) ? ('has-subnav') : (''))]];
            // line 73
            echo '                        <li ';
            echo craft\helpers\Html::renderTagAttributes((isset($context['itemAttributes']) || array_key_exists('itemAttributes', $context) ? $context['itemAttributes'] : (function () {
                throw new RuntimeError('Variable "itemAttributes" does not exist.', 73, $this->source);
            })()));
            echo '>
                            ';
            // line 74
            echo twig_call_macro($macros['_self'], 'macro_action', [$context['item']], 74, $context, $this->getSourceContext());
            echo '

                            ';
            // line 76
            if (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'subnav', [])) {
                // line 77
                echo '                                <craft-disclosure class="nav-item__trigger">
                                  <button type="button" class="btn menubtn hairline" aria-controls="';
                // line 78
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'id', []), 'html', null, true);
                echo '-subnav" aria-expanded="';
                echo (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'sel', [])) ? ('true') : ('false');
                echo '">
                                      <span class="visually-hidden">';
                // line 79
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Open subnavigation', 'app'), 'html', null, true);
                echo '</span>
                                  </button>
                                </craft-disclosure>
                                <ul class="nav-item__subnav ';
                // line 82
                echo (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'sel', [])) ? ('is-open') : ('');
                echo '" id="';
                echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'id', []), 'html', null, true);
                echo '-subnav">
                                    ';
                // line 83
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'subnav', []));
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
                foreach ($context['_seq'] as $context['itemId'] => $context['item']) {
                    // line 84
                    echo '                                        ';
                    $context['itemIsSelected'] = ((                    // line 85
                        array_key_exists('selectedSubnavItem', $context) && ((isset($context['selectedSubnavItem']) || array_key_exists('selectedSubnavItem', $context) ? $context['selectedSubnavItem'] : (function () {
                            throw new RuntimeError('Variable "selectedSubnavItem" does not exist.', 85, $this->source);
                        })()) == $context['itemId'])) || (! // line 86
                        array_key_exists('selectedSubnavItem', $context) && craft\helpers\Template::attribute($this->env, $this->source, $context['loop'], 'first', [])));
                    // line 90
                    echo '<li class="nav-item nav-item--sub">
                                            ';
                    // line 91
                    echo twig_call_macro($macros['_self'], 'macro_action', [$this->extensions['craft\web\twig\Extension']->mergeFilter($context['item'], ['linkAttributes' => ['class' => [0 => 'sidebar-action--sub'], 'aria' => ['current' => ((                    // line 95
                        (isset($context['itemIsSelected']) || array_key_exists('itemIsSelected', $context) ? $context['itemIsSelected'] : (function () {
                            throw new RuntimeError('Variable "itemIsSelected" does not exist.', 95, $this->source);
                        })())) ? ('page') : (false))]]]),                     // line 98
                        (isset($context['itemIsSelected']) || array_key_exists('itemIsSelected', $context) ? $context['itemIsSelected'] : (function () {
                            throw new RuntimeError('Variable "itemIsSelected" does not exist.', 98, $this->source);
                        })()), false, ], 91, $context, $this->getSourceContext());
                    echo '
                                        </li>
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
                unset($context['_seq'], $context['_iterated'], $context['itemId'], $context['item'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 101
                echo '                                </ul>
                            ';
            }
            // line 103
            echo '                        </li>
                    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 105
        echo '                </ul>
            </nav>
        </div>

        <div class="global-sidebar__footer">
            <div class="sidebar-actions">
                <button type="button" class="sidebar-action" id="sidebar-trigger" aria-controls="global-sidebar" aria-expanded="';
        // line 111
        echo (((isset($context['sidebarState']) || array_key_exists('sidebarState', $context) ? $context['sidebarState'] : (function () {
            throw new RuntimeError('Variable "sidebarState" does not exist.', 111, $this->source);
        })()) == 'expanded')) ? ('true') : ('false');
        echo '">
                    <span class="sidebar-action__prefix">
                        <span class="nav-icon" aria-hidden="true" id="sidebar-toggle-icon">
                            ';
        // line 114
        echo craft\helpers\Cp::iconSvg('angle-right');
        echo '
                        </span>
                    </span>
                    <span class="sidebar-action__label">
                        <span class="label">';
        // line 118
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Toggle sidebar', 'app'), 'html', null, true);
        echo '</span>
                    </span>
                </button>
            </div>

            ';
        // line 123
        if ((craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 123, $this->source);
        })()), 'admin', []) && (isset($context['devMode']) || array_key_exists('devMode', $context) ? $context['devMode'] : (function () {
            throw new RuntimeError('Variable "devMode" does not exist.', 123, $this->source);
        })()))) {
            // line 124
            echo '                ';
            $context['devModeText'] = $this->extensions['craft\web\twig\Extension']->translateFilter('Craft CMS is running in Dev Mode.', 'app');
            // line 125
            echo '                <div id="devmode">
                    ';
            // line 126
            echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'text' =>             // line 128
                        (isset($context['devModeText']) || array_key_exists('devModeText', $context) ? $context['devModeText'] : (function () {
                            throw new RuntimeError('Variable "devModeText" does not exist.', 128, $this->source);
                        })()), ]);
            // line 129
            echo '
                </div>
            ';
        }
        // line 132
        echo '        </div>
    </header>
</craft-global-sidebar>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/global-sidebar');
    }

    // line 1
    public function macro_action($__item__ = null, $__isSelected__ = null, $__showIcon__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            'item' => $__item__,
            'isSelected' => $__isSelected__,
            'showIcon' => $__showIcon__,
            'varargs' => $__varargs__,
        ]);

        ob_start();
        try {
            craft\helpers\Template::beginProfile('macro', 'action');
            // line 2
            echo '    ';
            $context['showIcon'] ??= true;
            // line 3
            echo '    ';
            $context['selected'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'sel', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'sel', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'sel', [])) : ((($context['isSelected']) ?? (false))));
            // line 4
            echo '    ';
            $context['badgeCount'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'badgeCount', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'badgeCount', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'badgeCount', [])) : (false));
            // line 5
            echo '    ';
            $context['external'] = (((craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'external', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'external', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'external', [])) : (false));
            // line 6
            echo '
    ';
            // line 7
            $context['linkAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['href' => (((((craft\helpers\Template::attribute($this->env, $this->source,             // line 8
                ($context['item'] ?? null), 'url', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'url', [])) : (false))) ? (craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 8, $this->source);
                })()), 'url', []))) : (null)), 'class' => [0 => 'sidebar-action', 1 => ((            // line 11
                    (isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                        throw new RuntimeError('Variable "external" does not exist.', 11, $this->source);
                    })())) ? ('external') : ('')), 2 => ((            // line 12
                        (isset($context['selected']) || array_key_exists('selected', $context) ? $context['selected'] : (function () {
                            throw new RuntimeError('Variable "selected" does not exist.', 12, $this->source);
                        })())) ? ('sel') : (''))], 'target' => ((            // line 14
                            (isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                                throw new RuntimeError('Variable "external" does not exist.', 14, $this->source);
                            })())) ? ('_blank') : (''))], (((craft\helpers\Template::attribute($this->env, $this->source,             // line 15
                                ($context['item'] ?? null), 'linkAttributes', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'linkAttributes', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'linkAttributes', [])) : ([])), true);
            // line 16
            echo '
    ';
            // line 17
            ob_start();
            // line 18
            echo '        <span class="sidebar-action__prefix">
            ';
            // line 19
            if ((isset($context['showIcon']) || array_key_exists('showIcon', $context) ? $context['showIcon'] : (function () {
                throw new RuntimeError('Variable "showIcon" does not exist.', 19, $this->source);
            })())) {
                // line 20
                echo '            <span class="nav-icon" aria-hidden="true">';
                // line 21
                if (craft\helpers\Template::attribute($this->env, $this->source, ($context['item'] ?? null), 'icon', [], 'any', true, true)) {
                    // line 22
                    echo craft\helpers\Cp::iconSvg(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 22, $this->source);
                    })()), 'icon', []));
                } elseif (craft\helpers\Template::attribute($this->env, $this->source,                 // line 23
                    ($context['item'] ?? null), 'fontIcon', [], 'any', true, true)) {
                    // line 24
                    echo '<span data-icon="';
                    echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 24, $this->source);
                    })()), 'fontIcon', []), 'html', null, true);
                    echo '"></span>';
                } else {
                    // line 26
                    $this->loadTemplate('_includes/fallback-icon.svg.twig', '_layouts/components/global-sidebar', 26)->display(twig_array_merge($context, ['label' => craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 26, $this->source);
                    })()), 'label', [])]));
                }
                // line 28
                echo '</span>
            ';
            }
            // line 30
            echo '        </span>

        <span class="sidebar-action__label">
            <span class="label">';
            // line 33
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                throw new RuntimeError('Variable "item" does not exist.', 33, $this->source);
            })()), 'label', []), 'html', null, true);
            echo '</span>
            ';
            // line 34
            if ((isset($context['external']) || array_key_exists('external', $context) ? $context['external'] : (function () {
                throw new RuntimeError('Variable "external" does not exist.', 34, $this->source);
            })())) {
                echo '<span class="cp-icon puny">';
                echo craft\helpers\Cp::iconSvg('external');
                echo '</span>';
            }
            // line 35
            echo '        </span>';
            // line 37
            if ((isset($context['badgeCount']) || array_key_exists('badgeCount', $context) ? $context['badgeCount'] : (function () {
                throw new RuntimeError('Variable "badgeCount" does not exist.', 37, $this->source);
            })())) {
                // line 38
                echo '<span class="sidebar-action__badge">
                <span class="badge" aria-hidden="true">';
                // line 39
                echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->numberFilter(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                    throw new RuntimeError('Variable "item" does not exist.', 39, $this->source);
                })()), 'badgeCount', []), 0), 'html', null, true);
                echo '</span>
                ';
                // line 40
                echo $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'data' => ['notification' => true], 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('{num, number} {num, plural, =1{notification} other{notifications}}', 'app', ['num' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 46
                    (isset($context['item']) || array_key_exists('item', $context) ? $context['item'] : (function () {
                        throw new RuntimeError('Variable "item" does not exist.', 46, $this->source);
                    })()), 'badgeCount', [])])]);
                // line 48
                echo '
            </span>';
            }
            echo craft\helpers\Html::tag('a', ob_get_clean(),             // line 17
                (isset($context['linkAttributes']) || array_key_exists('linkAttributes', $context) ? $context['linkAttributes'] : (function () {
                    throw new RuntimeError('Variable "linkAttributes" does not exist.', 17, $this->source);
                })()));
            craft\helpers\Template::endProfile('macro', 'action');

            return ('' === $tmp = ob_get_contents()) ? '' : new Markup($tmp, $this->env->getCharset());
        } finally {
            ob_end_clean();
        }
    }

    public function getTemplateName()
    {
        return '_layouts/components/global-sidebar';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [310 => 17,  306 => 48,  304 => 46,  303 => 40,  299 => 39,  296 => 38,  294 => 37,  292 => 35,  286 => 34,  282 => 33,  277 => 30,  273 => 28,  270 => 26,  265 => 24,  263 => 23,  261 => 22,  259 => 21,  257 => 20,  255 => 19,  252 => 18,  250 => 17,  247 => 16,  245 => 15,  244 => 14,  243 => 12,  242 => 11,  241 => 8,  240 => 7,  237 => 6,  234 => 5,  231 => 4,  228 => 3,  225 => 2,  209 => 1,  201 => 132,  196 => 129,  194 => 128,  193 => 126,  190 => 125,  187 => 124,  185 => 123,  177 => 118,  170 => 114,  164 => 111,  156 => 105,  149 => 103,  145 => 101,  128 => 98,  127 => 95,  126 => 91,  123 => 90,  121 => 86,  120 => 85,  118 => 84,  101 => 83,  95 => 82,  89 => 79,  83 => 78,  80 => 77,  78 => 76,  73 => 74,  68 => 73,  66 => 70,  65 => 69,  64 => 66,  62 => 65,  58 => 64,  53 => 62,  47 => 58,  45 => 57,  39 => 53];
    }

    public function getSourceContext()
    {
        return new Source("{% macro action(item, isSelected, showIcon) %}
    {% set showIcon = showIcon ?? true %}
    {% set selected = item.sel ?? isSelected ?? false %}
    {% set badgeCount = item.badgeCount ?? false %}
    {% set external = item.external ?? false %}

    {% set linkAttributes = {
        href: (item.url ?? false) ? url(item.url) : null,
        class: [
            'sidebar-action',
            (external ? 'external'),
            (selected ? 'sel'),
        ],
        target: external ? '_blank',
    }|merge(item.linkAttributes ?? {}, recursive=true) %}

    {% tag 'a' with linkAttributes %}
        <span class=\"sidebar-action__prefix\">
            {% if showIcon %}
            <span class=\"nav-icon\" aria-hidden=\"true\">
                {%- if item.icon is defined -%}
                    {{ iconSvg(item.icon) }}
                {%- elseif item.fontIcon is defined -%}
                    <span data-icon=\"{{ item.fontIcon }}\"></span>
                {%- else -%}
                    {% include \"_includes/fallback-icon.svg.twig\" with { label: item.label } %}
                {%- endif -%}
                </span>
            {% endif %}
        </span>

        <span class=\"sidebar-action__label\">
            <span class=\"label\">{{ item.label }}</span>
            {% if external %}<span class=\"cp-icon puny\">{{ iconSvg('external') }}</span>{% endif %}
        </span>

        {%- if badgeCount -%}
            <span class=\"sidebar-action__badge\">
                <span class=\"badge\" aria-hidden=\"true\">{{ item.badgeCount|number(decimals=0) }}</span>
                {{ tag('span', {
                    class: 'visually-hidden',
                    data: {
                        notification: true,
                    },
                    text: '{num, number} {num, plural, =1{notification} other{notifications}}'|t('app', {
                        num: item.badgeCount,
                    }),
                }) }}
            </span>
        {%- endif -%}
    {% endtag %}
{% endmacro %}

<craft-global-sidebar>
    <header id=\"global-sidebar\" class=\"global-sidebar\">
        <div class=\"global-sidebar__header\">
            {% include '_layouts/components/system-info' %}
        </div>

        <div class=\"global-sidebar__nav\">

            <nav id=\"nav\" class=\"global-nav\" aria-label=\"{{ 'Primary'|t('app') }}\">
                <ul>
                    {% for item in craft.cp.nav() %}
                        {% set itemAttributes = {
                            id: item.id,
                            class: [
                                'nav-item',
                                item.sel ? 'sel',
                                item.subnav ? 'has-subnav'
                            ],
                        } %}
                        <li {{ attr(itemAttributes) }}>
                            {{ _self.action(item) }}

                            {% if item.subnav %}
                                <craft-disclosure class=\"nav-item__trigger\">
                                  <button type=\"button\" class=\"btn menubtn hairline\" aria-controls=\"{{ item.id }}-subnav\" aria-expanded=\"{{ item.sel ?  'true' : 'false' }}\">
                                      <span class=\"visually-hidden\">{{ 'Open subnavigation' |t('app') }}</span>
                                  </button>
                                </craft-disclosure>
                                <ul class=\"nav-item__subnav {{ item.sel ? 'is-open' : '' -}}\" id=\"{{ item.id }}-subnav\">
                                    {% for itemId, item in item.subnav %}
                                        {% set itemIsSelected = (
                                            (selectedSubnavItem is defined and selectedSubnavItem == itemId) or
                                            (selectedSubnavItem is not defined and loop.first)
                                        ) -%}


                                        <li class=\"nav-item nav-item--sub\">
                                            {{ _self.action(item|merge({
                                                linkAttributes:  {
                                                    class: ['sidebar-action--sub'],
                                                    aria: {
                                                        current: itemIsSelected ? 'page' : false,
                                                    },
                                                }
                                            }), itemIsSelected, false, ) }}
                                        </li>
                                    {% endfor %}
                                </ul>
                            {% endif %}
                        </li>
                    {% endfor %}
                </ul>
            </nav>
        </div>

        <div class=\"global-sidebar__footer\">
            <div class=\"sidebar-actions\">
                <button type=\"button\" class=\"sidebar-action\" id=\"sidebar-trigger\" aria-controls=\"global-sidebar\" aria-expanded=\"{{ sidebarState == 'expanded' ? 'true' : 'false' }}\">
                    <span class=\"sidebar-action__prefix\">
                        <span class=\"nav-icon\" aria-hidden=\"true\" id=\"sidebar-toggle-icon\">
                            {{ iconSvg('angle-right') }}
                        </span>
                    </span>
                    <span class=\"sidebar-action__label\">
                        <span class=\"label\">{{ 'Toggle sidebar'|t('app') }}</span>
                    </span>
                </button>
            </div>

            {% if currentUser.admin and devMode %}
                {% set devModeText = 'Craft CMS is running in Dev Mode.'|t('app') %}
                <div id=\"devmode\">
                    {{ tag('span', {
                        class: 'visually-hidden',
                        text: devModeText
                    }) }}
                </div>
            {% endif %}
        </div>
    </header>
</craft-global-sidebar>
", '_layouts/components/global-sidebar', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/global-sidebar.twig');
    }
}
