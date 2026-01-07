<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _layouts/components/crumbs */
class __TwigTemplate_6ad509b0404c22527c7829c282946e65 extends Template
{
    private readonly Source $source;

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
        craft\helpers\Template::beginProfile('template', '_layouts/components/crumbs');
        // line 1
        yield '<div id="crumbs"';
        if (! (isset($context['crumbs']) || array_key_exists('crumbs', $context) ? $context['crumbs'] : (function () {
            throw new RuntimeError('Variable "crumbs" does not exist.', 1, $this->source);
        })())) {
            yield ' class="empty"';
        }
        yield '>
  <button id="primary-nav-toggle" class="nav-toggle" title="';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Show nav', 'app'), 'html', null, true);
        yield '" aria-label="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Show nav', 'app'), 'html', null, true);
        yield '" aria-expanded="false" aria-controls="global-sidebar" aria-haspopup="true"></button>
  ';
        // line 3
        if ((isset($context['crumbs']) || array_key_exists('crumbs', $context) ? $context['crumbs'] : (function () {
            throw new RuntimeError('Variable "crumbs" does not exist.', 3, $this->source);
        })())) {
            // line 4
            yield '    <nav aria-label="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Breadcrumbs', 'app'), 'html', null, true);
            yield '">
      <ul id="crumb-list">
        ';
            // line 6
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['crumbs']) || array_key_exists('crumbs', $context) ? $context['crumbs'] : (function () {
                throw new RuntimeError('Variable "crumbs" does not exist.', 6, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['crumb']) {
                // line 7
                yield '          ';
                $context['hasMenuItems'] = $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true, false, 7), 'items', [], 'any', true, true, false, 7) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true, false, 7), 'items', [], 'any', false, false, false, 7) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true, false, 7), 'items', [], 'any', false, false, false, 7)) : ([])));
                // line 8
                yield '          ';
                if ((isset($context['hasMenuItems']) || array_key_exists('hasMenuItems', $context) ? $context['hasMenuItems'] : (function () {
                    throw new RuntimeError('Variable "hasMenuItems" does not exist.', 8, $this->source);
                })())) {
                    // line 9
                    yield '            ';
                    $context['crumb'] = $this->extensions['craft\web\twig\Extension']->mergeFilter($this->env->getFunction('findCrumb')->getCallable()(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, false, false, 9), 'items', [], 'any', false, false, false, 9)), $context['crumb']);
                    // line 10
                    yield '          ';
                }
                // line 11
                yield '
          ';
                // line 12
                ob_start();
                // line 18
                yield '            ';
                if (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'html', [], 'any', true, true, false, 18)) {
                    // line 19
                    yield '              ';
                    yield craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'html', [], 'any', false, false, false, 19);
                    yield '
            ';
                } else {
                    // line 21
                    yield '              ';
                    $context['labelId'] = ('crumb-label'.Twig\Extension\CoreExtension::random($this->env->getCharset()));
                    // line 22
                    yield '
              ';
                    // line 23
                    ob_start();
                    // line 28
                    yield '                ';
                    if (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'icon', [], 'any', true, true, false, 28)) {
                        // line 29
                        yield '                  <span class="cp-icon puny" aria-hidden="true">';
                        yield craft\helpers\Cp::iconSvg(craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'icon', [], 'any', false, false, false, 29));
                        yield '</span>
                ';
                    }
                    // line 31
                    yield '
                <span>';
                    // line 32
                    (((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'label', [], 'any', true, true, false, 32) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'label', [], 'any', false, false, false, 32) === null))) ? (yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'label', [], 'any', false, false, false, 32), 'html', null, true)) : (yield craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'html', [], 'any', false, false, false, 32)));
                    yield '</span>
              ';
                    echo craft\helpers\Html::tag('a', ob_get_clean(), ['class' => 'crumb-link', 'id' => (((craft\helpers\Template::attribute($this->env, $this->source,                     // line 25
                        $context['crumb'], 'id', [], 'any', true, true, false, 25) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', [], 'any', false, false, false, 25) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', [], 'any', false, false, false, 25)) : (null)), 'href' => ((craft\helpers\Template::attribute($this->env, $this->source,                     // line 26
                            $context['crumb'], 'url', [], 'any', true, true, false, 26)) ? (craft\helpers\UrlHelper::url(craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'url', [], 'any', false, false, false, 26))) : (null))]);
                    // line 34
                    yield '
              ';
                    // line 35
                    if ((isset($context['hasMenuItems']) || array_key_exists('hasMenuItems', $context) ? $context['hasMenuItems'] : (function () {
                        throw new RuntimeError('Variable "hasMenuItems" does not exist.', 35, $this->source);
                    })())) {
                        // line 36
                        yield '                ';
                        $context['menuId'] = ((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', [], 'any', true, true, false, 36)) ? ((craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'id', [], 'any', false, false, false, 36).'-menu')) : (('crumb-menu'.Twig\Extension\CoreExtension::random($this->env->getCharset()))));
                        // line 37
                        yield '                ';
                        $context['menuLabel'] = (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true, false, 37), 'label', [], 'any', true, true, false, 37) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true, false, 37), 'label', [], 'any', false, false, false, 37) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, true, false, 37), 'label', [], 'any', false, false, false, 37)) : (null));
                        // line 38
                        yield '                ';
                        yield $this->extensions['craft\web\twig\Extension']->tagFunction('button', ['class' => ['btn', 'menubtn'], 'type' => 'button', 'aria' => ['label' =>                         // line 42
(isset($context['menuLabel']) || array_key_exists('menuLabel', $context) ? $context['menuLabel'] : (function () {
    throw new RuntimeError('Variable "menuLabel" does not exist.', 42, $this->source);
})()), 'controls' =>                         // line 43
(isset($context['menuId']) || array_key_exists('menuId', $context) ? $context['menuId'] : (function () {
    throw new RuntimeError('Variable "menuId" does not exist.', 43, $this->source);
})()), 'describedby' => ((                        // line 44
    (isset($context['menuLabel']) || array_key_exists('menuLabel', $context) ? $context['menuLabel'] : (function () {
        throw new RuntimeError('Variable "menuLabel" does not exist.', 44, $this->source);
    })())) ? (null) : ((isset($context['labelId']) || array_key_exists('labelId', $context) ? $context['labelId'] : (function () {
        throw new RuntimeError('Variable "labelId" does not exist.', 44, $this->source);
    })())))], 'data' => ['disclosure-trigger' => true]]);
                        // line 49
                        yield '
                ';
                        // line 50
                        yield craft\helpers\Cp::disclosureMenu(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'menu', [], 'any', false, false, false, 50), 'items', [], 'any', false, false, false, 50), ['id' =>                         // line 51
(isset($context['menuId']) || array_key_exists('menuId', $context) ? $context['menuId'] : (function () {
    throw new RuntimeError('Variable "menuId" does not exist.', 51, $this->source);
})()), 'withButton' => false]);
                        // line 53
                        yield '
              ';
                    }
                    // line 55
                    yield '            ';
                }
                // line 56
                yield '          ';
                echo craft\helpers\Html::tag('li', ob_get_clean(), ['class' => Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->filterFilter($this->env, ['crumb' => true, 'current' => (((craft\helpers\Template::attribute($this->env, $this->source,                 // line 15
                    $context['crumb'], 'current', [], 'any', true, true, false, 15) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'current', [], 'any', false, false, false, 15) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['crumb'], 'current', [], 'any', false, false, false, 15)) : (false))]))]);
                // line 57
                yield '        ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['crumb'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 58
            yield '      </ul>
    </nav>
  ';
        }
        // line 61
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_layouts/components/crumbs');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/components/crumbs';
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
        return [166 => 61,  161 => 58,  155 => 57,  153 => 15,  151 => 56,  148 => 55,  144 => 53,  142 => 51,  141 => 50,  138 => 49,  136 => 44,  135 => 43,  134 => 42,  132 => 38,  129 => 37,  126 => 36,  124 => 35,  121 => 34,  119 => 26,  118 => 25,  114 => 32,  111 => 31,  105 => 29,  102 => 28,  100 => 23,  97 => 22,  94 => 21,  88 => 19,  85 => 18,  83 => 12,  80 => 11,  77 => 10,  74 => 9,  71 => 8,  68 => 7,  64 => 6,  58 => 4,  56 => 3,  50 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<div id=\"crumbs\"{% if not crumbs %} class=\"empty\"{% endif %}>
  <button id=\"primary-nav-toggle\" class=\"nav-toggle\" title=\"{{ 'Show nav'|t('app') }}\" aria-label=\"{{ 'Show nav'|t('app') }}\" aria-expanded=\"false\" aria-controls=\"global-sidebar\" aria-haspopup=\"true\"></button>
  {% if crumbs %}
    <nav aria-label=\"{{ 'Breadcrumbs'|t('app') }}\">
      <ul id=\"crumb-list\">
        {% for crumb in crumbs %}
          {% set hasMenuItems = (crumb.menu.items ?? [])|length %}
          {% if hasMenuItems %}
            {% set crumb = findCrumb(crumb.menu.items)|merge(crumb) %}
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
                  <span class=\"cp-icon puny\" aria-hidden=\"true\">{{ iconSvg(crumb.icon) }}</span>
                {% endif %}

                <span>{{ crumb.label ?? crumb.html|raw }}</span>
              {% endtag %}

              {% if hasMenuItems %}
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
", '_layouts/components/crumbs', '/tmp/packages/craft5/src/templates/_layouts/components/crumbs.twig');
    }
}
