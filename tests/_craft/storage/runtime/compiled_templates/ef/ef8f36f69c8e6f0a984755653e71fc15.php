<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/components/crumbs */
class __TwigTemplate_fc5be78a3fea154fb3002b58fcd98764 extends Template
{
    private $source;

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
        craft\helpers\Template::beginProfile('template', '_layouts/components/crumbs');
        // line 1
        echo '<div id="crumbs"';
        if (! (isset($context['crumbs']) || array_key_exists('crumbs', $context) ? $context['crumbs'] : (function () {
            throw new RuntimeError('Variable "crumbs" does not exist.', 1, $this->source);
        })())) {
            echo ' class="empty"';
        }
        echo '>
  <button id="primary-nav-toggle" class="nav-toggle" title="';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Show nav', 'app'), 'html', null, true);
        echo '" aria-label="';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Show nav', 'app'), 'html', null, true);
        echo '" aria-expanded="false" aria-controls="global-sidebar" aria-haspopup="true"></button>
  ';
        // line 3
        if ((isset($context['crumbs']) || array_key_exists('crumbs', $context) ? $context['crumbs'] : (function () {
            throw new RuntimeError('Variable "crumbs" does not exist.', 3, $this->source);
        })())) {
            // line 4
            echo '    <nav aria-label="';
            echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Breadcrumbs', 'app'), 'html', null, true);
            echo '">
      <ul id="crumb-list">
        ';
            // line 6
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context['crumbs']) || array_key_exists('crumbs', $context) ? $context['crumbs'] : (function () {
                throw new RuntimeError('Variable "crumbs" does not exist.', 6, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['crumb']) {
                // line 7
                echo '          ';
                $context['hasMenuItems'] = (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', true, true) && craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true), 'items', [], 'any', true, true));
                // line 8
                echo '          ';
                if ((isset($context['hasMenuItems']) || array_key_exists('hasMenuItems', $context) ? $context['hasMenuItems'] : (function () {
                    throw new RuntimeError('Variable "hasMenuItems" does not exist.', 8, $this->source);
                })())) {
                    // line 9
                    echo '            ';
                    $context['crumb'] = $this->extensions['craft\web\twig\Extension']->mergeFilter((craft\helpers\ArrayHelper::firstWhere(Illuminate\Support\Arr::flatten($this->extensions['craft\web\twig\Extension']->mapFilter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source,                     // line 10
                        $context['crumb'], 'menu', []), 'items', []), function ($__o__) use ($context) {
                            $context['o'] = $__o__;

                            return ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'group', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'group', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'group', [])) : (false))) ? ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'options', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'options', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'options', [])) : ([]))) : ([0 => (isset($context['o']) || array_key_exists('o', $context) ? $context['o'] : (function () {
                                throw new RuntimeError('Variable "o" does not exist.', 10, $this->source);
                            })())]);
                        }), 1), function ($__o__) use ($context) {
                            $context['o'] = $__o__;

                            return ((craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'selected', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'selected', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['o'] ?? null), 'selected', [])) : (false);
                        }) ?: []),                     // line 11
                        $context['crumb']);
                    // line 12
                    echo '          ';
                }
                // line 13
                echo '
          ';
                // line 14
                ob_start();
                // line 20
                echo '            ';
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'html', [], 'any', true, true)) {
                    // line 21
                    echo '              ';
                    echo craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'html', []);
                    echo '
            ';
                } else {
                    // line 23
                    echo '              ';
                    $context['labelId'] = ('crumb-label'.twig_random($this->env));
                    // line 24
                    echo '
              ';
                    // line 25
                    ob_start();
                    // line 30
                    echo '                ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'icon', [], 'any', true, true)) {
                        // line 31
                        echo '                  <span data-icon="';
                        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'icon', []), 'html', null, true);
                        echo '" aria-hidden="true"></span>
                ';
                    }
                    // line 33
                    echo '
                ';
                    // line 34
                    (((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'label', []) === null))) ? (print twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'label', []), 'html', null, true)) : (print craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'html', [])));
                    echo '
              ';
                    echo craft\helpers\Html::tag('a', ob_get_clean(), ['class' => 'crumb-link', 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 27
                        $context['crumb'], 'id', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', [])) : (null)), 'href' => ((craft\helpers\Template::attribute($this->env, $this->source,                     // line 28
                            $context['crumb'], 'url', [], 'any', true, true)) ? (craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'url', []))) : (null))]);
                    // line 36
                    echo '
              ';
                    // line 37
                    if ((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', true, true) && craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true), 'items', [], 'any', true, true))) {
                        // line 38
                        echo '                ';
                        $context['menuId'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', [], 'any', true, true)) ? ((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', []).'-menu')) : (('crumb-menu'.twig_random($this->env))));
                        // line 39
                        echo '                ';
                        $context['menuLabel'] = (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true), 'label', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true), 'label', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true), 'label', [])) : (null));
                        // line 40
                        echo '                ';
                        echo $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['class' => [0 => 'btn', 1 => 'menubtn'], 'type' => 'button', 'aria' => ['label' =>                         // line 44
(isset($context['menuLabel']) || array_key_exists('menuLabel', $context) ? $context['menuLabel'] : (function () {
    throw new RuntimeError('Variable "menuLabel" does not exist.', 44, $this->source);
})()), 'controls' =>                         // line 45
(isset($context['menuId']) || array_key_exists('menuId', $context) ? $context['menuId'] : (function () {
    throw new RuntimeError('Variable "menuId" does not exist.', 45, $this->source);
})()), 'describedby' => ((                        // line 46
    (isset($context['menuLabel']) || array_key_exists('menuLabel', $context) ? $context['menuLabel'] : (function () {
        throw new RuntimeError('Variable "menuLabel" does not exist.', 46, $this->source);
    })())) ? (null) : ((isset($context['labelId']) || array_key_exists('labelId', $context) ? $context['labelId'] : (function () {
        throw new RuntimeError('Variable "labelId" does not exist.', 46, $this->source);
    })()))), ], 'data' => ['disclosure-trigger' => true]]);
                        // line 51
                        echo '
              ';
                    }
                    // line 53
                    echo '
              ';
                    // line 54
                    if ((isset($context['hasMenuItems']) || array_key_exists('hasMenuItems', $context) ? $context['hasMenuItems'] : (function () {
                        throw new RuntimeError('Variable "hasMenuItems" does not exist.', 54, $this->source);
                    })())) {
                        // line 55
                        echo '                ';
                        echo craft\helpers\Cp::disclosureMenu(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', []), 'items', []), ['id' =>                         // line 56
(isset($context['menuId']) || array_key_exists('menuId', $context) ? $context['menuId'] : (function () {
    throw new RuntimeError('Variable "menuId" does not exist.', 56, $this->source);
})()), 'withButton' => false, ]);
                        // line 58
                        echo '
              ';
                    }
                    // line 60
                    echo '            ';
                }
                // line 61
                echo '          ';
                echo craft\helpers\Html::tag('li', ob_get_clean(), ['class' => twig_get_array_keys_filter($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['crumb' => true, 'current' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 17
                    $context['crumb'], 'current', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'current', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'current', [])) : (false))]))]);
                // line 62
                echo '        ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['crumb'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 63
            echo '      </ul>
    </nav>
  ';
        }
        // line 66
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/crumbs');
    }

    public function getTemplateName()
    {
        return '_layouts/components/crumbs';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [170 => 66,  165 => 63,  159 => 62,  157 => 17,  155 => 61,  152 => 60,  148 => 58,  146 => 56,  144 => 55,  142 => 54,  139 => 53,  135 => 51,  133 => 46,  132 => 45,  131 => 44,  129 => 40,  126 => 39,  123 => 38,  121 => 37,  118 => 36,  116 => 28,  115 => 27,  111 => 34,  108 => 33,  102 => 31,  99 => 30,  97 => 25,  94 => 24,  91 => 23,  85 => 21,  82 => 20,  80 => 14,  77 => 13,  74 => 12,  72 => 11,  71 => 10,  69 => 9,  66 => 8,  63 => 7,  59 => 6,  53 => 4,  51 => 3,  45 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div id=\"crumbs\"{% if not crumbs %} class=\"empty\"{% endif %}>
  <button id=\"primary-nav-toggle\" class=\"nav-toggle\" title=\"{{ 'Show nav'|t('app') }}\" aria-label=\"{{ 'Show nav'|t('app') }}\" aria-expanded=\"false\" aria-controls=\"global-sidebar\" aria-haspopup=\"true\"></button>
  {% if crumbs %}
    <nav aria-label=\"{{ 'Breadcrumbs'|t('app') }}\">
      <ul id=\"crumb-list\">
        {% for crumb in crumbs %}
          {% set hasMenuItems = crumb.menu is defined and crumb.menu.items is defined %}
          {% if hasMenuItems %}
            {% set crumb = (
              crumb.menu.items|map(o => (o.group ?? false) ? (o.options ?? []) : [o])|flatten(1)|firstWhere(o => o.selected ?? false) ?: {}
              )|merge(crumb) %}
          {% endif %}

          {% tag 'li' with {
            class: {
              crumb: true,
              current: crumb.current ?? false,
            }|filter|keys,
          } %}
            {% if crumb.html is defined %}
              {{ crumb.html|raw }}
            {% else %}
              {% set labelId = \"crumb-label#{random()}\" %}

              {% tag 'a' with {
                class: 'crumb-link',
                id: crumb.id ?? null,
                href: crumb.url is defined ? url(crumb.url) : null,
              } %}
                {% if crumb.icon is defined %}
                  <span data-icon=\"{{ crumb.icon }}\" aria-hidden=\"true\"></span>
                {% endif %}

                {{ crumb.label ?? crumb.html|raw }}
              {% endtag %}

              {% if crumb.menu is defined and crumb.menu.items is defined %}
                {% set menuId = crumb.id is defined ? \"#{crumb.id}-menu\" : \"crumb-menu#{random()}\" %}
                {% set menuLabel = crumb.menu.label ?? null %}
                {{ tag('button', {
                  class: ['btn', 'menubtn'],
                  type: 'button',
                  aria: {
                    label: menuLabel,
                    controls: menuId,
                    describedby: menuLabel ? null : labelId,
                  },
                  data: {
                    'disclosure-trigger': true,
                  },
                }) }}
              {% endif %}

              {% if hasMenuItems %}
                {{ disclosureMenu(crumb.menu.items, {
                  id: menuId,
                  withButton: false,
                }) }}
              {% endif %}
            {% endif %}
          {% endtag %}
        {% endfor %}
      </ul>
    </nav>
  {% endif %}
</div>
", '_layouts/components/crumbs', '/Users/brianhanson/Development/craft5/src/templates/_layouts/components/crumbs.twig');
    }
}
